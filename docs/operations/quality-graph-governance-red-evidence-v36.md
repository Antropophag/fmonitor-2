```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root/qg_publisher_provenance_red","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"533ebca7225e5691bb049ca3ff39037f64a93a76","tests":[{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"}],"command":"php tests/Verification/quality_graph_runner_security_001_test.php","observedFailure":"floating third-party action mutation is rejected only as generated drift instead of the required explicit untrusted-runner security failure","recordedAt":"2026-09-04T06:12:05+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 untrusted runner security RED

The repository-owned `check-quality-graph.php --repo` fixture now specifies a
generic security boundary for both deployed untrusted runners:
`.github/workflows/quality-graph.yml` and
`.github/workflows/quality-graph-push.yml`.

For every runner it mutates one independently pinned third-party `uses:` ref to
a floating branch, enables checkout credential persistence, grants a top-level
write permission, and grants a job-level write permission. Each mutation must
fail early with stable `runner_security`; no particular setup action, action
SHA, or literal package bootstrap command is required. A separate mutation
keeps the existing generated-runner parity oracle and its `generated_drift`
classification.

The test is wired into the characterization verification list. The
`tools/verification/run.sh` edit is test discovery only; deployed workflows,
publisher code and product code remain byte-identical.

## Honest historical pre-pin RED

Exact base `533ebca7225e5691bb049ca3ff39037f64a93a76` predates the proposed
explicit runner-security validator. At `2026-09-04T06:12:05+03:00`:

```text
php -l tests/Verification/quality_graph_runner_security_001_test.php
No syntax errors detected in tests/Verification/quality_graph_runner_security_001_test.php

php tests/Verification/quality_graph_runner_security_001_test.php
QUALITY_GRAPH_VALIDATION_FAILURE category=generated_drift detail=stale generated file: .github/workflows/quality-graph.yml
TestFailure: RED_ASSERTION: .github/workflows/quality-graph.yml floating third-party action must be classified before generated parity
exit=255
```

The first constructible mutation is blocking already, but only incidentally via
generated parity. The intended RED is the absent direct security classification
which must remain effective even if generated representation changes. This
record adds no implementation and does not claim Gate 3.

The exact test blob was additionally cherry-picked without modification onto
historical pre-Node-24-pin commit
`c2b653efdcb3cbd78491a9dc8d4dd1be33f7d627`. After `uv sync --frozen` installed
the repository's exact `0.1.7` toolchain, the same command reached the same
intended `generated_drift` versus `runner_security` assertion and exited `255`.
Thus the historical witness is not masked by a missing validation dependency.
