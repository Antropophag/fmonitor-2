#!/usr/bin/env python3
"""Historical executable probe for the pinned stock Quality Graph 0.1.7 publisher."""

from __future__ import annotations

import argparse
import base64
import hashlib
import io
import json
import sys
import zipfile
from dataclasses import replace
from pathlib import Path

try:
    from qg_github.compiler import compile_graph
    from qg_github.github import MemoryGitHubPort
    from qg_github.publication import publish_workflow_run
    from quality_graph_core.graph import Graph
    from quality_graph_core.result import FailureKind, Provenance, Result, ResultStatus
except ImportError as error:
    raise SystemExit(f"SETUP_FAILURE: install pinned Quality Graph 0.1.7 toolchain: {error}")

REPOSITORY = "Antropophag/fmonitor-2"
PR = 41
RUN = 33780511678
HEAD = "1234567890abcdef1234567890abcdef12345678"
BASE = "abcdef1234567890abcdef1234567890abcdef12"
NODE = "verify"
TITLE = "Repository verification"
ACTION = "alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef"
GRAPH = f"""version: 0
provider:
  name: github
  configuration:
    default-branch: main
    runtime:
      action: {ACTION}
profiles:
  default:
    runner: ubuntu-latest
    permissions:
      contents: read
nodes:
  {NODE}:
    title: {TITLE}
    run: make test
    policy:
      blocking: true
      approvals:
        findings: false
        files: false
        node: false
"""
DIGEST = compile_graph(Graph.from_yaml(GRAPH)).graph_digest


def result(status: ResultStatus, *, attempt: int = 3, run: int = RUN,
           head: str = HEAD, digest: str = DIGEST) -> Result:
    failure = None
    if status is ResultStatus.FAILED:
        failure = FailureKind.COMMAND
    elif status is ResultStatus.CANCELLED:
        failure = FailureKind.CANCELLATION
    return Result(
        NODE, TITLE, status,
        Provenance(REPOSITORY, head, run, attempt, digest, PR),
        failure,
        f"stock probe {status.value}",
    )


def archive(value: Result, *, malformed: bool = False) -> bytes:
    output = io.BytesIO()
    with zipfile.ZipFile(output, "w", zipfile.ZIP_DEFLATED) as bundle:
        bundle.writestr(f"{NODE}.json", b"{" if malformed else value.to_json().encode())
    return output.getvalue()


def descriptor(identity: int, value: Result, *, name_attempt: int | None = None,
               malformed: bool = False) -> tuple[dict[str, object], bytes]:
    payload = archive(value, malformed=malformed)
    attempt = value.provenance.run_attempt if name_attempt is None else name_attempt
    return ({
        "id": identity,
        "name": f"quality-result-{NODE}-{attempt}",
        "size_in_bytes": len(payload),
        "digest": f"sha256:{hashlib.sha256(payload).hexdigest()}",
        "expired": False,
    }, payload)


def execute(items: list[tuple[dict[str, object], bytes]], *, event_attempt: int = 3,
            event_head: str = HEAD) -> dict[str, object]:
    port = MemoryGitHubPort(repository=REPOSITORY)
    port.enqueue("GET", f"/pulls/{PR}", {"head": {"sha": HEAD}, "base": {"sha": BASE}})
    latest = f"/actions/workflows/quality-graph.yml/runs?event=pull_request&per_page=100&page=1"
    port.enqueue("GET", latest, {"workflow_runs": []})
    content = base64.b64encode(GRAPH.encode()).decode()
    port.enqueue("GET", f"/contents/quality-graph.yml?ref={BASE}", {"content": content})
    artifacts_path = f"/actions/runs/{RUN}/artifacts?per_page=100&page=1"
    port.enqueue("GET", artifacts_path, {"artifacts": [item[0] for item in items]})
    jobs_path = f"/actions/runs/{RUN}/jobs?filter=latest&per_page=100&page=1"
    port.enqueue("GET", jobs_path, {"jobs": [{
        "name": TITLE, "status": "completed", "conclusion": "success",
    }]})
    for meta, payload in items:
        port.downloads[f"/actions/artifacts/{meta['id']}/zip"] = payload
    comments = f"/issues/{PR}/comments?per_page=100&page=1"
    port.enqueue("GET", comments, [])
    port.enqueue("POST", f"/issues/{PR}/comments", {"id": 901, "body": "published"})
    port.enqueue("POST", "/check-runs", {})
    event = {
        "action": "completed",
        "workflow_run": {
            "event": "pull_request", "id": RUN, "run_attempt": event_attempt,
            "head_sha": event_head, "html_url": f"https://example.invalid/runs/{RUN}",
            "pull_requests": [{"number": PR, "head": {"sha": event_head}}],
        },
    }
    try:
        outcome = publish_workflow_run(port, event)
        return {"raised": None, "published": outcome.published,
                "status": outcome.status.value if outcome.status else None}
    except Exception as error:  # probe records stock behavior, including fail-closed exceptions
        return {"raised": type(error).__name__, "detail": str(error)[:240],
                "published": False, "status": None}


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", type=Path)
    args = parser.parse_args()
    passed = descriptor(701, result(ResultStatus.PASSED))
    failed = descriptor(702, result(ResultStatus.FAILED))
    cases = {
        "current_pass": (execute([passed]), "passed"),
        "current_failure": (execute([failed]), "failed"),
        "missing": (execute([]), "failed"),
        "malformed": (execute([descriptor(703, result(ResultStatus.PASSED), malformed=True)]), "failed"),
        "duplicate_same_attempt": (execute([passed, descriptor(704, result(ResultStatus.PASSED))]), "failed"),
        "stale_attempt": (execute([descriptor(705, result(ResultStatus.PASSED, attempt=2))]), "failed"),
        "future_attempt": (execute([descriptor(709, result(ResultStatus.PASSED, attempt=4))]), "failed"),
        "duplicate_cross_attempt": (execute([
            descriptor(710, result(ResultStatus.FAILED, attempt=2)),
            descriptor(711, result(ResultStatus.PASSED, attempt=4)),
        ]), "failed"),
        "wrong_run": (execute([descriptor(706, result(ResultStatus.PASSED, run=RUN - 1))]), "failed"),
        "wrong_head": (execute([descriptor(707, result(ResultStatus.PASSED, head="0" * 40))]), "failed"),
        "wrong_digest": (execute([descriptor(708, result(ResultStatus.PASSED, digest="0" * 64))]), "failed"),
    }
    report: dict[str, object] = {"toolchain": "quality-graph 0.1.7", "digest": DIGEST, "cases": {}}
    failures: list[str] = []
    for name, (observed, expected) in cases.items():
        secure = observed.get("status") == expected
        report["cases"][name] = {"expected": expected, "observed": observed, "secure": secure}
        if not secure:
            failures.append(name)
    report["securityFailures"] = failures
    rendered = json.dumps(report, indent=2, sort_keys=True) + "\n"
    if args.output:
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(rendered)
    sys.stdout.write(rendered)
    if failures:
        sys.stderr.write("RED_ASSERTION: stock publisher violated artifact admission requirements: "
                         + ",".join(failures) + "\n")
        return 1
    print("QUALITY_GRAPH_STOCK_PUBLISHER_PROBE_OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
