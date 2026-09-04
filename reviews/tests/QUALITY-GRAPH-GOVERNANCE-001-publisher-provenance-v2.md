```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_publisher_provenance_gate3_v2","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"M","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"}],"redCommit":"0ddc10c13ae0f0090dabf2aba83c70d46129c0e8","recordedAt":"2026-09-04T06:06:59+03:00"}
```

# Test rereview: QUALITY-GRAPH-GOVERNANCE-001 — publisher provenance RED v2

- Gate: 3 — fresh independent test rereview
- Reviewer: separately tasked agent `/root/qg_publisher_provenance_gate3_v2`
- Independence: reviewer did not author the specification, test, RED evidence,
  runner correction, or publisher implementation
- Reviewed RED commit: `0ddc10c13ae0f0090dabf2aba83c70d46129c0e8`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v35.md`
- Superseded review: `reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-publisher-provenance-v1.md`
  (`CHANGES_REQUESTED`)
- Specification: `specs/QUALITY-GRAPH-GOVERNANCE-001.md` v0.6
- Verdict: **APPROVED**

## Findings

No blocking findings remain in this publisher-provenance slice.

The trusted workflow attempt is now an independent input to
`validate_result_artifacts(...)`. The fixture retains the payload-only
`run_attempt=4` mutation and adds a coherent replay in which both the artifact
descriptor name and Result provenance say attempt `4`, while the trusted input
remains `3`. Consequently, an implementation that compares only two
attacker-controlled values cannot satisfy the reviewed test. The descriptor
factory defaults every other case to the trusted attempt, so this correction
does not weaken the valid path or earlier isolated mutations.

All v1 cases remain present: valid exact provenance; wrong repository, pull
request, head SHA, workflow run, payload attempt and graph digest; omitted and
unexpected node; duplicate same-node/same-attempt artifact; expired artifact;
and archive digest drift. Each rejection uses a fresh `MemoryGitHubPort`, fixed
Result provenance and deterministic ZIP entry identity/time. The accepted path
requires exactly the two expected node IDs.

Repository execution is now owned by `tools/verification/run.sh`: the
`characterization` suite invokes the test through `uv run python` before the PHP
fixtures. The test no longer modifies `sys.path` or refers to ignored `.venv`
contents. `pyproject.toml` and `uv.lock` both bind the invoked environment to
Python `>=3.12` and exact `quality-graph-github==0.1.7`; a frozen sync completed
before both reproductions. Import therefore succeeds before the intended RED,
and missing dependency/setup cannot masquerade as missing publisher behavior.

## Independent RED reproduction

At exact reviewed head, at `2026-09-04T06:06:59+03:00`:

```text
$ uv sync --frozen
Checked 13 packages in 0.18ms
exit=0

$ uv run python tests/Verification/quality_graph_publisher_provenance_001_test.py
AssertionError: RED_ASSERTION: repository-owned offline publisher validation seam is absent
exit=1

$ tools/verification/run.sh characterization
VERIFY tests/Verification/quality_graph_publisher_provenance_001_test.py
AssertionError: RED_ASSERTION: repository-owned offline publisher validation seam is absent
REGRESSION_FAILURE: tests/Verification/quality_graph_publisher_provenance_001_test.py
REGRESSION_FAILURE: 1 verifier(s) failed
exit=1
```

Both public paths reach the same intended missing-seam assertion after pinned
dependency import. The repository runner stops on that independently reproduced
RED, as it should; this review does not claim GREEN or repository integration.

## Traceability and exact inventory

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d  tests/Verification/quality_graph_publisher_provenance_001_test.py
558414844f58a68fe7a1b475ee7664ea3718758cdf3064a634a7afa5fa4e5479  docs/operations/quality-graph-governance-red-evidence-v35.md
cda57af0edf979095c57de5a88121d4ac321a817e883ce84a613017cbd117746  tools/verification/run.sh
```

`d320590470b258dc5fd7705e6857d8827a9f6450` is an ancestor of the reviewed
commit. Its complete delta is exactly the modified publisher-provenance test,
modified characterization runner, and added evidence v35. This matches the
append-only evidence metadata and the Gate 3 correction scope.

## Authorized minimal GREEN

Gate 4 may add only the repository-owned offline validation seam required to
make this exact reviewed test pass: exact trusted repository/PR/head/workflow
run/attempt/graph binding, exact expected-node completeness, rejection of
unexpected or duplicate artifacts, expiry and digest validation, while
preserving the pinned upstream behavior. No workflow deployment, network
publisher, receipt, evidence-lineage, graph-generation, or broader Quality
Graph behavior is authorized by this approval.

Any change to the specification, reviewed test, or runner invocation invalidates
this approval and requires a fresh RED and independent Gate 3 review.
