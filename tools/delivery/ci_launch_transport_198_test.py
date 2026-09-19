#!/usr/bin/env python3
"""Focused executor regression tests for the issue #198 GitHub adapter."""

import argparse
import importlib.util
import json
from pathlib import Path
import unittest
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "tools/delivery"))


def load(path, name):
    spec = importlib.util.spec_from_file_location(name, ROOT / path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


CLI = load("tools/delivery/ci-launch.py", "ci_launch_cli_198")
CORE = load("tools/delivery/ci_launch.py", "ci_launch_core_198")
POLICY = load("tools/delivery/admission.py", "ci_launch_admission_198")


def args(**changes):
    values = {"repository": "Antropophag/fmonitor-2",
              "workflow": ".github/workflows/quality-graph.yml",
              "head": "a" * 40, "base": "b" * 40, "mode": "full",
              "polls": 1, "poll_interval": 0}
    values.update(changes)
    return argparse.Namespace(**values)


def api_run(run_id=77, *, event="pull_request", status="completed",
            conclusion="success"):
    pulls = [{"base": {"sha": "b" * 40}}] if event == "pull_request" else []
    return {"id": run_id, "html_url": f"https://example.invalid/{run_id}",
            "path": ".github/workflows/quality-graph.yml", "head_sha": "a" * 40,
            "pull_requests": pulls, "event": event, "status": status,
            "conclusion": conclusion}


def jobs(run_id=77, missing=None):
    expected = POLICY.expected_jobs(ROOT, "full")
    return [{"id": index, "name": name, "status": "completed",
             "conclusion": conclusion.lower()} for index, (name, conclusion)
            in enumerate(expected.items(), 1) if name != missing]


class Result:
    def __init__(self, value=None, returncode=0):
        self.returncode = returncode
        self.stdout = "" if value is None else json.dumps(value)
        self.stderr = ""


class CompletedRunner:
    def __init__(self, *, missing=None):
        self.calls = []
        self.missing = missing

    def __call__(self, argv, **kwargs):
        self.calls.append(argv)
        endpoint = next((part for part in argv if part.startswith("repos/")), "")
        if endpoint.endswith("/runs"):
            return Result({"workflow_runs": [api_run()]})
        if endpoint.endswith("/runs/77"):
            return Result(api_run())
        if endpoint.endswith("/runs/77/jobs"):
            return Result({"jobs": jobs(missing=self.missing)})
        raise AssertionError(argv)


class DispatchRunner:
    def __init__(self):
        self.calls = []
        self.manual_lists = 0

    def __call__(self, argv, **kwargs):
        self.calls.append(argv)
        endpoint = next((part for part in argv if part.startswith("repos/")), "")
        fields = [argv[index + 1] for index, value in enumerate(argv[:-1]) if value == "-f"]
        if endpoint.endswith("/dispatches"):
            return Result(None)
        if endpoint.endswith("/runs") and "event=pull_request" in fields:
            return Result({"workflow_runs": []})
        if endpoint.endswith("/runs") and "event=workflow_dispatch" in fields:
            self.manual_lists += 1
            old = api_run(55, event="workflow_dispatch")
            new = api_run(900, event="workflow_dispatch", status="queued", conclusion=None)
            return Result({"workflow_runs": [old] if self.manual_lists == 1 else [new, old]})
        if endpoint.endswith("/runs/900"):
            return Result(api_run(900, event="workflow_dispatch", status="queued", conclusion=None))
        if endpoint.endswith("/runs/900/jobs"):
            return Result({"jobs": []})
        raise AssertionError(argv)


class TransportContract(unittest.TestCase):
    def test_completed_list_run_is_hydrated_and_admitted_without_dispatch(self):
        runner = CompletedRunner()
        transport = CLI.GithubTransport(args(), runner=runner,
                                        mode_resolver=lambda event, base: "full")
        result = CORE.select_or_dispatch(transport, CLI.Admission(ROOT), vars(args()),
                                         polls=1, poll_interval=0)
        self.assertEqual(("REUSE", 77, "SUCCESS"),
                         (result["decision"], result["run"]["id"],
                          result["admission"]["status"]))
        self.assertFalse(any("/dispatches" in part for call in runner.calls for part in call))

    def test_missing_required_job_and_authoritative_mode_mismatch_fail_closed(self):
        runner = CompletedRunner(missing="verify")
        transport = CLI.GithubTransport(args(), runner=runner,
                                        mode_resolver=lambda event, base: "full")
        result = CORE.select_or_dispatch(transport, CLI.Admission(ROOT), vars(args()),
                                         polls=1, poll_interval=0)
        self.assertEqual("UNKNOWN", result["decision"])
        mismatch = CLI.GithubTransport(args(), runner=CompletedRunner(),
                                       mode_resolver=lambda event, base: "harness")
        result = CORE.select_or_dispatch(mismatch, CLI.Admission(ROOT), vars(args()),
                                         polls=1, poll_interval=0)
        self.assertEqual("BLOCKED_MISMATCH", result["decision"])

    def test_empty_204_dispatch_diffs_old_ids_and_queued_is_pending(self):
        runner = DispatchRunner()
        transport = CLI.GithubTransport(args(), runner=runner,
                                        mode_resolver=lambda event, base: "full")
        result = CORE.select_or_dispatch(transport, CLI.Admission(ROOT), vars(args()),
                                         polls=1, poll_interval=0)
        self.assertEqual(("PENDING", "PENDING", 900),
                         (result["decision"], result["status"], result["run"]["id"]))
        posts = [call for call in runner.calls if "POST" in call]
        self.assertEqual(1, len(posts))

    def test_every_non_success_terminal_conclusion_is_failure(self):
        for conclusion in ("failure", "cancelled", "timed_out", "action_required",
                           "stale", "skipped", None):
            with self.subTest(conclusion=conclusion):
                run = {"id": 77, "status": "completed", "conclusion": conclusion,
                       "jobs": [{"run_id": 77}]}
                self.assertEqual("OBSERVE_FAILURE",
                                 CORE._decide_existing(run, CLI.Admission(ROOT))["decision"])

    def test_dispatched_completed_missing_conclusion_uses_terminal_decision(self):
        class Github:
            def __init__(self):
                self.observations = [
                    {"id": 900, "status": "queued"},
                    {"id": 900, "status": "completed", "conclusion": None,
                     "jobs": [{"run_id": 900}]},
                ]

            def list_runs(self):
                return []

            def dispatch(self):
                return {"id": 900}

            def observe(self, run_id):
                return self.observations.pop(0)

        class PermissiveAdmission:
            def evaluate(self, run):
                return {"status": "SUCCESS", "required_results_complete": True}

        result = CORE.select_or_dispatch(Github(), PermissiveAdmission(), vars(args()),
                                         polls=1, poll_interval=0)
        self.assertEqual("OBSERVE_FAILURE", result["decision"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
