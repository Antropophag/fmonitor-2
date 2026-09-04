```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root/qg_publisher_provenance_red","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"d320590470b258dc5fd7705e6857d8827a9f6450","tests":[{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"M","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"}],"command":"tools/verification/run.sh characterization","observedFailure":"repository verification reaches the publisher provenance test under the declared uv environment and fails because the repository-owned offline validation seam is absent","recordedAt":"2026-09-04T06:04:56+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 publisher provenance RED v2

This append-only correction answers Gate 3 `CHANGES_REQUESTED` in
`reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-publisher-provenance-v1.md`.

The proposed seam now receives trusted `run_attempt=3` independently from all
artifact data. In addition to the payload-only attempt mismatch, the fixture
constructs a coherent replay whose artifact name and Result v0 provenance both
carry attempt `4`; it must still be rejected against trusted attempt `3`. All
previous repository, PR, head, workflow-run, graph-digest, completeness,
unexpected, duplicate, expiry and archive-digest cases remain.

The test imports pinned dependencies exclusively from the `uv run` environment;
it contains no ignored `.venv` or interpreter-minor path. The repository
characterization runner invokes it through `uv run python` before PHP fixtures,
so `make characterization-test` and `make verify` cannot omit the contract.
Changing `tools/verification/run.sh` here is test-harness wiring, not publisher
or product implementation.

## Honest RED

At exact base `d320590470b258dc5fd7705e6857d8827a9f6450`, commands at
`2026-09-04T06:04:56+03:00` produced:

```text
uv run python tests/Verification/quality_graph_publisher_provenance_001_test.py
AssertionError: RED_ASSERTION: repository-owned offline publisher validation seam is absent
direct_exit=1

tools/verification/run.sh characterization
VERIFY tests/Verification/quality_graph_publisher_provenance_001_test.py
AssertionError: RED_ASSERTION: repository-owned offline publisher validation seam is absent
REGRESSION_FAILURE: tests/Verification/quality_graph_publisher_provenance_001_test.py
REGRESSION_FAILURE: 1 verifier(s) failed
runner_exit=1
```

Dependency imports succeeded before the intended assertion, proving the RED is
not an ambient Python/setup failure. No publisher/tooling implementation was
added and this record does not claim Gate 3 approval.
