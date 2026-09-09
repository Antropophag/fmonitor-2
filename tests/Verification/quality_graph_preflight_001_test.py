#!/usr/bin/env python3
"""QUALITY-GRAPH-PREFLIGHT-001 public CLI and localhost HTTP contract."""

from __future__ import annotations

import json
import os
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
import subprocess
import tempfile
import threading

ROOT = Path(__file__).resolve().parents[2]
CLI = ROOT / "tools/delivery/quality-graph-preflight.py"
REPOSITORY = "Antropophag/fmonitor-2"
RUN_ID = 424242
ATTEMPT = 3
HEAD = "1234567890abcdef1234567890abcdef12345678"
NODES = ("plan", "fast", "unit", "integration", "e2e", "governance", "verify")


class Fixture:
    def __init__(self, run: dict[str, object], pages: dict[int, object], errors: dict[str, int] | None = None,
                 job_pages: dict[int, object] | None = None):
        self.run = run
        self.pages = pages
        self.errors = errors or {}
        self.job_pages = job_pages or {
            1: {"total_count": 1, "jobs": [reporting_job()]},
        }
        self.trace: list[dict[str, object]] = []


def run_value(**changes: object) -> dict[str, object]:
    value: dict[str, object] = {
        "id": RUN_ID, "run_attempt": ATTEMPT, "event": "pull_request",
        "status": "completed", "conclusion": "success", "head_sha": HEAD,
        "repository": {"full_name": REPOSITORY},
    }
    value.update(changes)
    return value


def artifact(identity: int, node: str, attempt: int = ATTEMPT, *, expired: bool = False) -> dict[str, object]:
    return {
        "id": identity, "name": f"quality-result-{node}-{attempt}",
        "expired": expired, "size_in_bytes": 100, "digest": "sha256:" + "a" * 64,
    }


def current() -> list[dict[str, object]]:
    return [artifact(100 + index, node) for index, node in enumerate(NODES)]


def reporting_job(identity: int = 700, *, name: str = "quality-results",
                  attempt: int = ATTEMPT, status: str = "completed",
                  conclusion: str | None = "success") -> dict[str, object]:
    return {
        "id": identity, "name": name, "run_attempt": attempt,
        "status": status, "conclusion": conclusion,
    }


def event(path: Path, *, action: str = "completed", **run_changes: object) -> None:
    workflow = run_value(**run_changes)
    path.write_text(json.dumps({"action": action, "workflow_run": workflow}) + "\n")


def serve(fixture: Fixture) -> tuple[ThreadingHTTPServer, threading.Thread]:
    class Handler(BaseHTTPRequestHandler):
        def do_GET(self) -> None:  # noqa: N802 - stdlib callback name
            fixture.trace.append({"method": "GET", "path": self.path,
                                  "authorization": self.headers.get("Authorization")})
            if self.path in fixture.errors:
                self.send_response(fixture.errors[self.path]); self.end_headers(); return
            if self.path == f"/repos/{REPOSITORY}/actions/runs/{RUN_ID}":
                payload: object = fixture.run
            elif self.path.startswith(f"/repos/{REPOSITORY}/actions/runs/{RUN_ID}/artifacts?"):
                query = self.path.partition("?")[2]
                page = int(next((part.split("=", 1)[1] for part in query.split("&")
                                 if part.startswith("page=")), "1"))
                payload = fixture.pages.get(page, {"total_count": 0, "artifacts": []})
            elif self.path.startswith(f"/repos/{REPOSITORY}/actions/runs/{RUN_ID}/jobs?"):
                query = self.path.partition("?")[2]
                page = int(next((part.split("=", 1)[1] for part in query.split("&")
                                 if part.startswith("page=")), "1"))
                payload = fixture.job_pages.get(page, {"total_count": 0, "jobs": []})
            else:
                self.send_response(404); self.end_headers(); return
            body = payload if isinstance(payload, bytes) else json.dumps(payload).encode()
            self.send_response(200); self.send_header("Content-Type", "application/json")
            self.send_header("Content-Length", str(len(body))); self.end_headers(); self.wfile.write(body)

        def do_POST(self) -> None:  # noqa: N802
            fixture.trace.append({"method": "POST", "path": self.path})
            self.send_response(405); self.end_headers()

        def log_message(self, _format: str, *args: object) -> None:
            del args

    server = ThreadingHTTPServer(("127.0.0.1", 0), Handler)
    thread = threading.Thread(target=server.serve_forever, daemon=True)
    thread.start()
    return server, thread


def execute(name: str, fixture: Fixture, *, should_pass: bool, event_changes: dict[str, object] | None = None) -> str:
    with tempfile.TemporaryDirectory(prefix="fmonitor-qg-preflight-") as directory:
        event_path = Path(directory) / "event.json"
        changes = dict(event_changes or {})
        action = str(changes.pop("_action", "completed"))
        event(event_path, action=action, **changes)
        server, thread = serve(fixture)
        try:
            environment = dict(os.environ)
            environment.update({
                "GITHUB_EVENT_PATH": str(event_path), "GITHUB_REPOSITORY": REPOSITORY,
                "GITHUB_API_URL": f"http://127.0.0.1:{server.server_port}",
                "GITHUB_TOKEN": "fixture-token",
            })
            completed = subprocess.run(
                ["python3", str(CLI)], cwd=ROOT, env=environment,
                text=True, capture_output=True, timeout=5,
            )
        finally:
            server.shutdown(); server.server_close(); thread.join(timeout=2)
    output = completed.stdout + completed.stderr
    success = completed.returncode == 0 and "QUALITY_GRAPH_PREFLIGHT_OK nodes=7" in output
    failure = completed.returncode != 0 and "QUALITY_GRAPH_PREFLIGHT_FAILURE" in output
    if should_pass and not success:
        raise AssertionError(f"RED_ASSERTION: {name} should pass current admission; got {completed.returncode}: {output[:400]}")
    if not should_pass and not failure:
        raise AssertionError(f"RED_ASSERTION: {name} should fail closed with stable prefix; got {completed.returncode}: {output[:400]}")
    if any(item["method"] != "GET" for item in fixture.trace):
        raise AssertionError(f"RED_ASSERTION: {name} used a non-GET API request: {fixture.trace}")
    if fixture.trace and any(item.get("authorization") != "Bearer fixture-token" for item in fixture.trace):
        raise AssertionError(f"RED_ASSERTION: {name} omitted exact bearer authentication")
    if should_pass:
        jobs_requests = [str(item["path"]) for item in fixture.trace if "/jobs?" in str(item["path"])]
        if not jobs_requests or not all("filter=latest" in path for path in jobs_requests):
            raise AssertionError(
                f"RED_ASSERTION: {name} must read current jobs with filter=latest: {fixture.trace}"
            )
    return name


def main() -> None:
    run = run_value()
    execute("current full seven", Fixture(run, {1: {"total_count": 7, "artifacts": current()}}), should_pass=True)
    execute("completed failed run", Fixture(run_value(conclusion="failure"),
                                            {1: {"total_count": 7, "artifacts": current()}}), should_pass=True)
    execute("completed cancelled run", Fixture(run_value(conclusion="cancelled"),
                                               {1: {"total_count": 7, "artifacts": current()}}), should_pass=True)
    retained = [artifact(10 + index, node, 2) for index, node in enumerate(NODES)] + current()
    execute("retained old plus current", Fixture(run, {1: {"total_count": 14, "artifacts": retained}}), should_pass=True)

    negative: list[tuple[str, Fixture, dict[str, object] | None]] = []
    negative.append(("missing current", Fixture(run, {1: {"total_count": 6, "artifacts": current()[:-1]}}), None))
    negative.append(("stale only", Fixture(run, {1: {"total_count": 7, "artifacts": [artifact(200+i, n, 2) for i, n in enumerate(NODES)]}}), None))
    negative.append(("future attempt", Fixture(run, {1: {"total_count": 8, "artifacts": current()+[artifact(220, "plan", 4)]}}), None))
    negative.append(("duplicate current", Fixture(run, {1: {"total_count": 8, "artifacts": current()+[artifact(221, "plan")]}}), None))
    old_duplicate = current()+[artifact(222, "plan", 2), artifact(223, "plan", 2)]
    negative.append(("duplicate old", Fixture(run, {1: {"total_count": 9, "artifacts": old_duplicate}}), None))
    negative.append(("unknown node", Fixture(run, {1: {"total_count": 8, "artifacts": current()+[artifact(224, "foreign")]}}), None))
    invalid_name = current()+[{"id": 226, "name": "quality-result-BAD-3", "expired": False}]
    negative.append(("invalid Quality Graph artifact name", Fixture(run, {1: {"total_count": 8, "artifacts": invalid_name}}), None))
    expired = current(); expired[3] = artifact(225, "integration", expired=True)
    negative.append(("expired current", Fixture(run, {1: {"total_count": 7, "artifacts": expired}}), None))

    filler = [{"id": 1000+i, "name": f"unrelated-{i}", "expired": False} for i in range(100)]
    execute("pagination", Fixture(run, {1: {"total_count": 107, "artifacts": filler},
                                         2: {"total_count": 107, "artifacts": current()}}), should_pass=True)
    job_filler = [reporting_job(2000+i, name=f"unrelated-{i}") for i in range(100)]
    execute("reporting job pagination", Fixture(
        run,
        {1: {"total_count": 7, "artifacts": current()}},
        job_pages={
            1: {"total_count": 101, "jobs": job_filler},
            2: {"total_count": 101, "jobs": [reporting_job()]},
        },
    ), should_pass=True)

    artifact_page = {1: {"total_count": 7, "artifacts": current()}}
    reporting_negative = {
        "reporting failed": [reporting_job(conclusion="failure")],
        "reporting cancelled": [reporting_job(conclusion="cancelled")],
        "reporting in progress": [reporting_job(status="in_progress", conclusion=None)],
        "reporting missing": [reporting_job(name="unrelated")],
        "reporting old attempt": [reporting_job(attempt=ATTEMPT - 1)],
        "reporting duplicate": [reporting_job(), reporting_job(701)],
    }
    for name, jobs in reporting_negative.items():
        negative.append((name, Fixture(
            run,
            artifact_page,
            job_pages={1: {"total_count": len(jobs), "jobs": jobs}},
        ), None))
    first_page_jobs = [reporting_job()] + [
        reporting_job(3000+i, name=f"unrelated-duplicate-probe-{i}") for i in range(99)
    ]
    negative.append(("reporting duplicate on second page", Fixture(
        run,
        artifact_page,
        job_pages={
            1: {"total_count": 101, "jobs": first_page_jobs},
            2: {"total_count": 101, "jobs": [reporting_job(3999)]},
        },
    ), None))
    api_path = f"/repos/{REPOSITORY}/actions/runs/{RUN_ID}"
    negative.append(("API error", Fixture(run, {}, {api_path: 503}), None))
    negative.append(("malformed API JSON", Fixture(b"{" , {}), None))  # type: ignore[arg-type]
    cycle_page = {"total_count": 1000, "artifacts": current() + filler[:93]}
    negative.append(("pagination cycle", Fixture(run, {page: cycle_page for page in range(1, 40)}), None))
    negative.append(("stale event attempt", Fixture(run, {1: {"total_count": 7, "artifacts": current()}}), {"run_attempt": 2}))
    negative.append(("non-completed event", Fixture(run, {1: {"total_count": 7, "artifacts": current()}}), {"_action": "in_progress"}))
    negative.append(("run mismatch", Fixture(run_value(id=RUN_ID+1), {1: {"total_count": 7, "artifacts": current()}}), None))
    negative.append(("head mismatch", Fixture(run_value(head_sha="0"*40), {1: {"total_count": 7, "artifacts": current()}}), None))
    negative.append(("repository mismatch", Fixture(run_value(repository={"full_name": "Other/repository"}), {1: {"total_count": 7, "artifacts": current()}}), None))
    for name, fixture, changes in negative:
        execute(name, fixture, should_pass=False, event_changes=changes)
    print("QUALITY_GRAPH_PREFLIGHT_001_TESTS_PASSED")


if __name__ == "__main__":
    main()
