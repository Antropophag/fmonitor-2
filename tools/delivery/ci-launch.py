#!/usr/bin/env python3
"""CLI adapter for bounded Quality Graph run selection."""

import argparse
import importlib.util
import json
from pathlib import Path
import subprocess
import sys
import time
from urllib.parse import quote

from ci_launch import select_or_dispatch


class GithubTransport:
    def __init__(self, args, *, runner=subprocess.run, mode_resolver=None):
        self.args = args
        self.runner = runner
        self.mode_resolver = mode_resolver or self._policy_mode

    def _gh(self, *argv, allow_empty=False):
        result = self.runner(["gh", *argv], text=True, capture_output=True)
        if result.returncode:
            raise RuntimeError(result.stderr.strip() or "gh failed")
        if not result.stdout.strip():
            if allow_empty:
                return None
            raise RuntimeError("gh returned no JSON")
        return json.loads(result.stdout)

    def _policy_mode(self, event, base):
        if event == "workflow_dispatch":
            return "full"
        if event != "pull_request" or not base:
            return None
        result = self.runner(
            [sys.executable, "tools/verification/ci.py", "plan", "--base", base,
             "--event", event], text=True, capture_output=True)
        if result.returncode:
            return None
        try:
            return json.loads(result.stdout).get("mode")
        except (json.JSONDecodeError, AttributeError):
            return None

    def _workflow_runs(self, event):
        workflow = quote(self.args.workflow, safe="")
        data = self._gh("api", "--method", "GET",
                        f"repos/{self.args.repository}/actions/workflows/{workflow}/runs",
                        "-f", f"event={event}", "-f", f"head_sha={self.args.head}")
        runs = data.get("workflow_runs") if isinstance(data, dict) else None
        if not isinstance(runs, list):
            raise RuntimeError("workflow run list is incomplete")
        return runs

    def _run(self, value):
        pull_requests = value.get("pull_requests") or []
        event = value.get("event")
        base = (pull_requests[0].get("base", {}).get("sha") if pull_requests
                else self.args.base if event == "workflow_dispatch" else None)
        return {"id": value.get("id"), "url": value.get("html_url"),
                "repository": self.args.repository, "workflow": value.get("path"),
                "head": value.get("head_sha"), "base": base,
                "mode": self.mode_resolver(event, base), "event": event,
                "status": value.get("status"), "conclusion": value.get("conclusion")}

    def list_runs(self):
        return [self._run(item) for item in self._workflow_runs("pull_request")]

    def dispatch(self):
        if self.args.mode != "full":
            raise RuntimeError("manual Quality Graph dispatch authoritatively selects full mode")
        before = {item.get("id") for item in self._workflow_runs("workflow_dispatch")
                  if isinstance(item, dict) and isinstance(item.get("id"), int)}
        workflow = quote(self.args.workflow, safe="")
        self._gh("api", "--method", "POST",
                 f"repos/{self.args.repository}/actions/workflows/{workflow}/dispatches",
                 "-f", f"ref={self.args.head}", allow_empty=True)
        for attempt in range(self.args.polls):
            runs = self._workflow_runs("workflow_dispatch")
            created = [item for item in runs if isinstance(item, dict)
                       and isinstance(item.get("id"), int) and item["id"] not in before]
            if len(created) == 1:
                return {"id": created[0]["id"], "url": created[0].get("html_url")}
            if len(created) > 1:
                raise RuntimeError("multiple new workflow runs after dispatch")
            if attempt + 1 < self.args.polls and self.args.poll_interval:
                time.sleep(self.args.poll_interval)
        raise RuntimeError("dispatched workflow run identity unavailable")

    def observe(self, run_id):
        run = self._run(self._gh("api", f"repos/{self.args.repository}/actions/runs/{run_id}"))
        jobs = self._gh("api", f"repos/{self.args.repository}/actions/runs/{run_id}/jobs").get("jobs", [])
        run["jobs"] = [{**job, "run_id": run_id} for job in jobs]
        return run


class Admission:
    def __init__(self, root=None):
        self.root = Path(root or Path(__file__).resolve().parents[2])

    def evaluate(self, run):
        jobs = run.get("jobs") or []
        spec = importlib.util.spec_from_file_location(
            "fmonitor_delivery_admission", self.root / "tools/delivery/admission.py")
        module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(module)
        try:
            expected = module.expected_jobs(self.root, run.get("mode"))
        except (OSError, TypeError, ValueError, AttributeError):
            return {"status": "UNKNOWN", "required_results_complete": False}
        by_name = {job.get("name"): job for job in jobs if isinstance(job, dict)}
        complete = (len(by_name) == len(jobs) and set(expected).issubset(by_name)
                    and all(str(by_name[name].get("status", "")).upper() == "COMPLETED"
                            and str(by_name[name].get("conclusion", "")).upper() == conclusion
                            for name, conclusion in expected.items()))
        success = bool(jobs) and complete
        return {"status": "SUCCESS" if success else "UNKNOWN",
                "required_results_complete": success}


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--repository", required=True)
    parser.add_argument("--workflow", required=True)
    parser.add_argument("--head", required=True)
    parser.add_argument("--base", required=True)
    parser.add_argument("--mode", required=True)
    parser.add_argument("--polls", type=int, default=3)
    parser.add_argument("--poll-interval", type=float, default=5)
    args = parser.parse_args(argv)
    binding = {key: getattr(args, key) for key in ("repository", "workflow", "head", "base", "mode")}
    result = select_or_dispatch(GithubTransport(args), Admission(), binding,
                                polls=args.polls, poll_interval=args.poll_interval)
    print(json.dumps(result, ensure_ascii=False, sort_keys=True))
    admitted = (result["decision"] in {"REUSE", "DISPATCHED"}
                and result.get("admission", {}).get("status") == "SUCCESS")
    return 0 if admitted else 1


if __name__ == "__main__":
    raise SystemExit(main())
