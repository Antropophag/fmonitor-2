#!/usr/bin/env python3
"""QUALITY-GRAPH-GOVERNANCE-001 publisher provenance acceptance fixture."""

from __future__ import annotations

import hashlib
import importlib.util
import io
import sys
import zipfile
from dataclasses import replace
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
from qg_github.artifacts import ArtifactError
from qg_github.github import MemoryGitHubPort
from quality_graph_core.result import Provenance, Result, ResultStatus

SEAM = ROOT / "tools" / "delivery" / "quality_graph_publisher.py"
if not SEAM.is_file():
    raise AssertionError("RED_ASSERTION: repository-owned offline publisher validation seam is absent")
spec = importlib.util.spec_from_file_location("fmonitor_quality_graph_publisher", SEAM)
if spec is None or spec.loader is None:
    raise AssertionError("RED_ASSERTION: repository-owned offline publisher validation seam is absent")
publisher = importlib.util.module_from_spec(spec)
sys.modules[spec.name] = publisher
spec.loader.exec_module(publisher)

REPOSITORY = "Antropophag/fmonitor-2"
PULL_REQUEST = 41
HEAD_SHA = "1234567890abcdef1234567890abcdef12345678"
WORKFLOW_RUN_ID = 33780511678
RUN_ATTEMPT = 3
GRAPH_DIGEST = "8b6c4ef2f5d3f09c76c80ce35b9732305f0f5b98a8d39ae724494e553ba4b890"
NODE_IDS = frozenset({"repository-verification", "quality-graph-validation"})


def archive_for(result: Result) -> bytes:
    output = io.BytesIO()
    with zipfile.ZipFile(output, "w", zipfile.ZIP_DEFLATED) as bundle:
        info = zipfile.ZipInfo(f"{result.node_id}.json", (2026, 9, 4, 3, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        bundle.writestr(info, result.to_json().encode("utf-8"))
    return output.getvalue()


def result_for(node_id: str, **provenance_changes: object) -> Result:
    provenance = Provenance(
        repository=REPOSITORY,
        pull_request=PULL_REQUEST,
        head_sha=HEAD_SHA,
        workflow_run_id=WORKFLOW_RUN_ID,
        run_attempt=RUN_ATTEMPT,
        graph_digest=GRAPH_DIGEST,
    )
    return Result(
        node_id=node_id,
        title=f"Result for {node_id}",
        status=ResultStatus.PASSED,
        provenance=replace(provenance, **provenance_changes),
    )


def port_for(
    results: tuple[Result, ...],
    *,
    expired: bool = False,
    digest_drift: bool = False,
    duplicate_first: bool = False,
    artifact_attempts: dict[str, int] | None = None,
) -> MemoryGitHubPort:
    port = MemoryGitHubPort(repository=REPOSITORY)
    descriptors: list[dict[str, object]] = []
    artifact_id = 700
    emitted = results + ((results[0],) if duplicate_first else ())
    for result in emitted:
        artifact_attempt = (artifact_attempts or {}).get(result.node_id, RUN_ATTEMPT)
        archive = archive_for(result)
        digest = hashlib.sha256(archive).hexdigest()
        descriptor = {
            "id": artifact_id,
            "name": f"quality-result-{result.node_id}-{artifact_attempt}",
            "size_in_bytes": len(archive),
            "digest": f"sha256:{'f' * 64 if digest_drift else digest}",
            "expired": expired,
        }
        descriptors.append(descriptor)
        port.downloads[f"/actions/artifacts/{artifact_id}/zip"] = archive
        artifact_id += 1
    path = f"/actions/runs/{WORKFLOW_RUN_ID}/artifacts?per_page=100&page=1"
    port.enqueue("GET", path, {"artifacts": descriptors})
    return port


def validate(port: MemoryGitHubPort) -> dict[str, Result]:
    return publisher.validate_result_artifacts(
        port,
        repository=REPOSITORY,
        pull_request=PULL_REQUEST,
        head_sha=HEAD_SHA,
        workflow_run_id=WORKFLOW_RUN_ID,
        run_attempt=RUN_ATTEMPT,
        graph_digest=GRAPH_DIGEST,
        expected_node_ids=NODE_IDS,
    )


def rejects(name: str, port: MemoryGitHubPort) -> None:
    try:
        validate(port)
    except ArtifactError:
        return
    raise AssertionError(f"RED_ASSERTION: publisher accepted {name}")


valid_results = tuple(result_for(node_id) for node_id in sorted(NODE_IDS))
accepted = validate(port_for(valid_results))
assert set(accepted) == NODE_IDS, "RED_ASSERTION: every expected node must be returned"

provenance_mutations = {
    "wrong repository": {"repository": "Antropophag/another-repository"},
    "wrong pull request": {"pull_request": 42},
    "wrong head SHA": {"head_sha": "abcdef1234567890abcdef1234567890abcdef12"},
    "wrong workflow run id": {"workflow_run_id": 33780511679},
    "wrong run attempt": {"run_attempt": 4},
    "wrong graph digest": {"graph_digest": "0" * 64},
}
for case_name, changes in provenance_mutations.items():
    mutated = (result_for("quality-graph-validation", **changes), valid_results[1])
    rejects(case_name, port_for(mutated))

coherent_attempt_replay = (
    result_for("quality-graph-validation", run_attempt=4),
    valid_results[1],
)
rejects(
    "coherent replay from another run attempt",
    port_for(coherent_attempt_replay, artifact_attempts={"quality-graph-validation": 4}),
)

rejects("omitted expected node artifact", port_for(valid_results[:1]))
rejects("unexpected node artifact", port_for(valid_results + (result_for("unexpected-node"),)))
rejects("duplicate node attempt artifact", port_for(valid_results, duplicate_first=True))
rejects("expired result artifact", port_for(valid_results, expired=True))
rejects("artifact digest drift", port_for(valid_results, digest_drift=True))

print("QUALITY-GRAPH-PUBLISHER-PROVENANCE-001 TESTS PASSED")
