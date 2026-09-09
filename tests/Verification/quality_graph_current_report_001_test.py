#!/usr/bin/env python3
"""QUALITY-GRAPH-CURRENT-CI-001: native reports from current GitHub outcomes."""

from __future__ import annotations

import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]
COMMAND = ROOT / "tools/delivery/quality-graph-report.py"
NODES = ("plan", "fast", "unit", "integration", "e2e", "governance", "verify")
TITLES = {
    "plan": "plan",
    "fast": "fast",
    "unit": "unit",
    "integration": "Integration",
    "e2e": "e2e",
    "governance": "governance",
    "verify": "verify",
}
REPOSITORY = "Antropophag/fmonitor-2"
HEAD = "1234567890abcdef1234567890abcdef12345678"
MERGE_SHA = "abcdefabcdefabcdefabcdefabcdefabcdefabcd"
GRAPH_DIGEST = "9" * 64
RUN_ID = 33780511678
RUN_ATTEMPT = 3
PULL_REQUEST = 61


class QualityGraphCurrentReport(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.temp = tempfile.TemporaryDirectory(prefix="fmonitor-qg-current-")
        cls.workspace = Path(cls.temp.name) / "workspace"
        cls.workspace.mkdir()
        cls.event_path = Path(cls.temp.name) / "pull-request.json"
        cls.event = {
            "repository": {"full_name": REPOSITORY},
            "pull_request": {
                "number": PULL_REQUEST,
                "head": {"sha": HEAD},
                "base": {"sha": "0" * 40},
            },
        }
        cls.event_path.write_text(json.dumps(cls.event), encoding="utf-8")
        cls.env = dict(
            os.environ,
            GITHUB_REPOSITORY=REPOSITORY,
            GITHUB_EVENT_NAME="pull_request",
            GITHUB_EVENT_PATH=str(cls.event_path),
            GITHUB_RUN_ID=str(RUN_ID),
            GITHUB_RUN_ATTEMPT=str(RUN_ATTEMPT),
            GITHUB_WORKSPACE=str(cls.workspace),
            GITHUB_SHA=MERGE_SHA,
        )

        # Qualify the synthetic GitHub fixture before the intended missing-seam RED.
        loaded = json.loads(cls.event_path.read_text(encoding="utf-8"))
        if loaded != cls.event or cls.env["GITHUB_WORKSPACE"] != str(cls.workspace):
            raise AssertionError("SETUP_FAILURE: synthetic GitHub fixture is not readable")
        if not cls.workspace.is_dir() or HEAD == MERGE_SHA:
            raise AssertionError("SETUP_FAILURE: workspace/head fixture is invalid")
        if not COMMAND.is_file():
            raise AssertionError(
                "INTENDED_RED: missing public command tools/delivery/quality-graph-report.py"
            )

    @classmethod
    def tearDownClass(cls):
        cls.temp.cleanup()

    def setUp(self):
        self.output_name = self.id().rsplit(".", 1)[-1]

    @staticmethod
    def outcomes(**changes):
        value = dict.fromkeys(NODES, "success")
        value.update(changes)
        return value

    def run_cli(self, outcomes, *, full="true", output=None, env=None, raw=False):
        output = self.output_name if output is None else output
        results = outcomes if raw else json.dumps(outcomes, separators=(",", ":"))
        return subprocess.run(
            [
                "python3", str(COMMAND), "--full", full,
                "--results-json", results,
                "--graph-digest", GRAPH_DIGEST,
                "--output-dir", str(output),
            ],
            cwd=self.workspace,
            env=env or self.env,
            capture_output=True,
            text=True,
            timeout=20,
        )

    def output_dir(self, name=None):
        return self.workspace / (name or self.output_name)

    def read_reports(self, name=None):
        directory = self.output_dir(name)
        self.assertEqual(
            {f"{node}.json" for node in NODES},
            {path.name for path in directory.iterdir()},
        )
        return {
            node: json.loads((directory / f"{node}.json").read_text(encoding="utf-8"))
            for node in NODES
        }

    def assert_native_result(self, value, node, status, failure_kind=None):
        required = {
            "schemaVersion", "nodeId", "title", "status", "summary", "metrics",
            "findings", "annotations", "diagnostics", "controls", "notes", "provenance",
        }
        expected_keys = required | ({"failureKind"} if failure_kind else set())
        self.assertEqual(expected_keys, set(value), node)
        self.assertIs(type(value["schemaVersion"]), int)
        self.assertEqual(0, value["schemaVersion"])
        self.assertIs(type(value["nodeId"]), str)
        self.assertEqual(node, value["nodeId"])
        self.assertIs(type(value["title"]), str)
        self.assertEqual(TITLES[node], value["title"])
        self.assertIs(type(value["status"]), str)
        self.assertEqual(status, value["status"])
        self.assertIs(type(value["summary"]), str)
        self.assertTrue(value["summary"].strip())
        for key in ("metrics", "findings", "annotations", "diagnostics", "controls", "notes"):
            self.assertIs(type(value[key]), list)
            self.assertEqual([], value[key])
        if failure_kind:
            self.assertIs(type(value["failureKind"]), str)
            self.assertEqual(failure_kind, value["failureKind"])
        expected_provenance = {
            "repository": REPOSITORY,
            "pullRequest": PULL_REQUEST,
            "headSha": HEAD,
            "workflowRunId": RUN_ID,
            "runAttempt": RUN_ATTEMPT,
            "graphDigest": GRAPH_DIGEST,
        }
        self.assertEqual(expected_provenance, value["provenance"])
        for key in ("repository", "headSha", "graphDigest"):
            self.assertIs(type(value["provenance"][key]), str)
        for key in ("pullRequest", "workflowRunId", "runAttempt"):
            self.assertIs(type(value["provenance"][key]), int)

    def assert_failure(self, result, category, output=None):
        self.assertEqual(1, result.returncode, (result.stdout, result.stderr))
        self.assertIn(f"QUALITY_GRAPH_REPORT_FAILURE category={category}", result.stderr)
        self.assertNotIn("QUALITY_GRAPH_REPORT_OK", result.stdout + result.stderr)
        if output is not None:
            path = self.output_dir(output)
            self.assertFalse(path.exists() and any(path.iterdir()))

    def test_full_success_has_seven_exact_passed_native_results(self):
        result = self.run_cli(self.outcomes())
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("QUALITY_GRAPH_REPORT_OK nodes=7\n", result.stdout)
        self.assertEqual("", result.stderr)
        reports = self.read_reports()
        for node in NODES:
            self.assert_native_result(reports[node], node, "passed")

    def test_docs_only_marks_only_test_categories_skipped_with_honest_reason(self):
        outcomes = self.outcomes(unit="skipped", integration="skipped", e2e="skipped",
                                 governance="skipped")
        result = self.run_cli(outcomes, full="false")
        self.assertEqual(0, result.returncode, result.stderr)
        reports = self.read_reports()
        for node in ("plan", "fast", "verify"):
            self.assert_native_result(reports[node], node, "passed")
        for node in ("unit", "integration", "e2e", "governance"):
            self.assert_native_result(reports[node], node, "skipped")
            summary = reports[node]["summary"].lower()
            self.assertIn("docs-only", summary)
            self.assertNotIn("passed", summary)

    def test_failure_and_cancellation_keep_native_failure_kinds(self):
        cases = (
            ("failure", "failed", "command"),
            ("cancelled", "cancelled", "cancellation"),
        )
        for outcome, status, kind in cases:
            with self.subTest(outcome=outcome):
                name = f"{self.output_name}-{outcome}"
                result = self.run_cli(
                    self.outcomes(integration=outcome, verify="failure"), output=name
                )
                self.assertEqual(0, result.returncode, result.stderr)
                reports = self.read_reports(name)
                self.assert_native_result(reports["integration"], "integration", status, kind)
                self.assert_native_result(reports["verify"], "verify", "failed", "command")
                self.assertNotEqual("passed", reports["integration"]["status"])

    def test_failed_plan_allows_unknown_mode_and_dependency_skips_only(self):
        outcomes = self.outcomes(plan="failure", fast="skipped", unit="skipped",
                                 integration="skipped", e2e="skipped", governance="skipped",
                                 verify="failure")
        result = self.run_cli(outcomes, full="unknown")
        self.assertEqual(0, result.returncode, result.stderr)
        reports = self.read_reports()
        self.assert_native_result(reports["plan"], "plan", "failed", "command")
        self.assert_native_result(reports["verify"], "verify", "failed", "command")
        for node in ("fast", "unit", "integration", "e2e", "governance"):
            self.assert_native_result(reports[node], node, "skipped")
            summary = reports[node]["summary"].lower()
            self.assertIn("dependency", summary)
            self.assertNotIn("passed", summary)

    def test_full_mode_preserves_category_skipped_when_verify_failed(self):
        outcomes = self.outcomes(e2e="skipped", verify="failure")
        result = self.run_cli(outcomes, full="true")
        self.assertEqual(0, result.returncode, result.stderr)
        reports = self.read_reports()
        self.assert_native_result(reports["e2e"], "e2e", "skipped")
        self.assertIn("dependency", reports["e2e"]["summary"].lower())
        self.assertNotIn("docs-only", reports["e2e"]["summary"].lower())
        self.assert_native_result(reports["verify"], "verify", "failed", "command")

    def test_results_json_is_exact_unambiguous_and_semantically_consistent(self):
        valid = self.outcomes()
        cases = {
            "malformed": ("{", True, "true"),
            "array": ([], False, "true"),
            "missing": ({key: value for key, value in valid.items() if key != "e2e"}, False, "true"),
            "unknown": (dict(valid, alien="success"), False, "true"),
            "wrong_type": (dict(valid, unit=True), False, "true"),
            "wrong_value": (dict(valid, unit="passed"), False, "true"),
            "duplicate": ('{"plan":"success","plan":"failure","fast":"success",'
                          '"unit":"success","integration":"success","e2e":"success",'
                          '"governance":"success","verify":"failure"}', True, "true"),
            "docs_non_skipped_success": (
                dict(valid, integration="skipped", e2e="skipped", governance="skipped"),
                False,
                "false",
            ),
            "docs_mixed_skipped_failure": (
                dict(valid, unit="skipped", integration="failure", e2e="skipped",
                     governance="skipped", verify="failure"),
                False,
                "false",
            ),
            "green_verify_after_failure": (dict(valid, unit="failure"), False, "true"),
            "unknown_after_green_plan": (dict(valid, verify="failure"), False, "unknown"),
        }
        for name, (payload, raw, full) in cases.items():
            with self.subTest(name=name):
                output = f"{self.output_name}-{name}"
                result = self.run_cli(payload, raw=raw, full=full, output=output)
                self.assert_failure(result, "input", output)

    def test_malformed_or_contradictory_github_provenance_writes_nothing(self):
        cases = {}
        for key in ("GITHUB_REPOSITORY", "GITHUB_EVENT_NAME", "GITHUB_EVENT_PATH",
                    "GITHUB_RUN_ID", "GITHUB_RUN_ATTEMPT", "GITHUB_WORKSPACE"):
            changed = dict(self.env)
            changed.pop(key)
            cases[f"missing-{key}"] = changed
        cases["wrong-event"] = dict(self.env, GITHUB_EVENT_NAME="push")
        cases["invalid-run"] = dict(self.env, GITHUB_RUN_ID="not-an-integer")
        cases["invalid-attempt"] = dict(self.env, GITHUB_RUN_ATTEMPT="0")

        event_cases = {
            "repository-mismatch": dict(self.event, repository={"full_name": "other/repo"}),
            "missing-pr": {"repository": {"full_name": REPOSITORY}},
            "invalid-pr": dict(self.event, pull_request={
                "number": "61", "head": {"sha": HEAD}, "base": {"sha": "0" * 40}
            }),
            "invalid-head": dict(self.event, pull_request={
                "number": PULL_REQUEST, "head": {"sha": "f" * 39},
                "base": {"sha": "0" * 40},
            }),
        }
        malformed_event = Path(self.temp.name) / "malformed-event.json"
        malformed_event.write_text("{", encoding="utf-8")
        cases["malformed-event"] = dict(self.env, GITHUB_EVENT_PATH=str(malformed_event))
        for name, event in event_cases.items():
            path = Path(self.temp.name) / f"{name}.json"
            path.write_text(json.dumps(event), encoding="utf-8")
            cases[name] = dict(self.env, GITHUB_EVENT_PATH=str(path))

        for name, env in cases.items():
            with self.subTest(name=name):
                output = f"{self.output_name}-{name}"
                result = self.run_cli(self.outcomes(), env=env, output=output)
                self.assert_failure(result, "provenance", output)

    def test_output_rejects_escape_absolute_paths_and_symlinks(self):
        outside = Path(self.temp.name) / "outside"
        outside.mkdir()
        link = self.workspace / f"{self.output_name}-link"
        link.symlink_to(outside, target_is_directory=True)
        cases = {
            "../escaped": Path(self.temp.name) / "escaped",
            str(outside): outside,
            link.name: outside,
        }
        for supplied, forbidden in cases.items():
            with self.subTest(output=supplied):
                before = sorted(path.name for path in forbidden.iterdir()) if forbidden.exists() else []
                result = self.run_cli(self.outcomes(), output=supplied)
                self.assert_failure(result, "output")
                after = sorted(path.name for path in forbidden.iterdir()) if forbidden.exists() else []
                self.assertEqual(before, after)

    def test_replay_is_byte_identical_and_conflicts_never_overwrite(self):
        output = self.output_name
        first = self.run_cli(self.outcomes(), output=output)
        self.assertEqual(0, first.returncode, first.stderr)
        originals = {
            path.name: path.read_bytes() for path in self.output_dir(output).iterdir()
        }
        repeat = self.run_cli(self.outcomes(), output=output)
        self.assertEqual(0, repeat.returncode, repeat.stderr)
        self.assertEqual(originals, {
            path.name: path.read_bytes() for path in self.output_dir(output).iterdir()
        })

        conflict = self.run_cli(
            self.outcomes(unit="failure", verify="failure"), output=output
        )
        self.assert_failure(conflict, "output")
        self.assertEqual(originals, {
            path.name: path.read_bytes() for path in self.output_dir(output).iterdir()
        })

        partial_name = f"{output}-partial"
        partial = self.output_dir(partial_name)
        partial.mkdir()
        (partial / "plan.json").write_bytes(originals["plan.json"])
        rejected = self.run_cli(self.outcomes(), output=partial_name)
        self.assert_failure(rejected, "output")
        self.assertEqual(["plan.json"], [path.name for path in partial.iterdir()])
        self.assertEqual(originals["plan.json"], (partial / "plan.json").read_bytes())


if __name__ == "__main__":
    unittest.main()
