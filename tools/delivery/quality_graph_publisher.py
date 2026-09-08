"""Offline adapter for pinned Quality Graph result-artifact validation."""

from __future__ import annotations

import re
from collections.abc import Mapping
from typing import Any

from qg_github.artifacts import ArtifactError, ArtifactExpectation, download_results

_NAME = re.compile(r"^quality-result-(?P<node>[a-z][a-z0-9-]{0,62})-(?P<attempt>[1-9][0-9]*)$")


class _InspectingPort:
    def __init__(self, delegate: Any) -> None:
        self._delegate = delegate
        self.descriptors: list[tuple[str, int]] = []

    def request(self, method: str, path: str, **kwargs: Any) -> Any:
        response = self._delegate.request(method, path, **kwargs)
        if isinstance(response, Mapping):
            artifacts = response.get("artifacts")
            if isinstance(artifacts, list):
                for artifact in artifacts:
                    if not isinstance(artifact, Mapping):
                        continue
                    match = _NAME.fullmatch(str(artifact.get("name", "")))
                    if match is not None:
                        self.descriptors.append((match.group("node"), int(match.group("attempt"))))
        return response

    def download(self, path: str) -> bytes:
        return self._delegate.download(path)


def validate_result_artifacts(
    port: Any,
    *,
    repository: str,
    pull_request: int,
    head_sha: str,
    workflow_run_id: int,
    run_attempt: int,
    graph_digest: str,
    expected_node_ids: frozenset[str],
) -> dict[str, Any]:
    """Validate one exact trusted run/attempt and its complete node set."""
    inspected = _InspectingPort(port)
    results = download_results(
        inspected,
        ArtifactExpectation(
            repository=repository,
            pull_request=pull_request,
            head_sha=head_sha,
            workflow_run_id=workflow_run_id,
            graph_digest=graph_digest,
            node_ids=expected_node_ids,
        ),
    )
    if set(results) != set(expected_node_ids):
        raise ArtifactError("result artifacts do not cover the exact expected node set")
    if sorted(inspected.descriptors) != sorted((node, run_attempt) for node in expected_node_ids):
        raise ArtifactError("result artifacts do not belong to the exact trusted run attempt")
    if any(result.provenance.run_attempt != run_attempt for result in results.values()):
        raise ArtifactError("result provenance does not belong to the exact trusted run attempt")
    return results
