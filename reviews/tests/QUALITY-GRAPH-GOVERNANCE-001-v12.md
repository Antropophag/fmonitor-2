```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"0a5b2a946c950dfd083e7d4a6cc8a6e4bfcd03091ae50ee7af1da10f6bdefe9f"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"e3c01efeedb8eb2c9f257645df7d65111eaa8e6e","recordedAt":"2026-09-03T02:03:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — corrected publisher RED v12

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, baseline, or publisher
- Reviewed RED commit: `e3c01efeedb8eb2c9f257645df7d65111eaa8e6e`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v12.md`
- Verdict: `APPROVED`

## Findings

No blocking findings for this tracer slice.

The v11 contract mismatch is corrected. Against the pinned compiler-generated workflow, the byte-exact expected publisher preserves the generated header, `workflow_run` content, concurrency bytes, publish job condition, runner, runtime SHA, step/input shape and watch/publish operation. Its only changes are the Gate 1-approved removal of `issue_comment`, removal of the command job, and removal of `issues: write` plus `pull-requests: write` from the retained job permissions.

- **Traceability:** the exact expected bytes implement requirement 9 and the independently approved v0.6 amendment without extending its allowlist.
- **Baseline provenance:** the generated comparison file must exist and match the independently fixed SHA-256 `a5de72...a8ee`; arbitrary replacement cannot become an oracle.
- **Sensitivity:** byte equality rejects any extra trigger, permission, job, step, checkout, runtime, operation or mutation of retained YAML. Explicit mutations demonstrate trigger, write-permission and checkout-step rejection.
- **Setup versus RED:** the compiler-generated deployable input exists, PHP/bootstrap pass, and the test stops at the absent retained baseline. The missing comparison artifact is the intended behavior gap.
- **Determinism:** validation uses fixed repository bytes and hashes only; it is offline and has no GitHub or package-install dependency.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_publisher_001_test.php
php tests/Verification/quality_graph_publisher_001_test.php
```

Syntax passed. The behavior test exited `255` at:

```text
RED_ASSERTION: pinned generated publisher baseline must be retained for allowlisted comparison
Expected: true
Actual: false
```

## Reviewed hashes and test set

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400  tests/Verification/quality_graph_governance_001_test.php
0a5b2a946c950dfd083e7d4a6cc8a6e4bfcd03091ae50ee7af1da10f6bdefe9f  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

Evidence v12 metadata matches the current v0.6 spec and complete sorted base-to-RED test set. Gate 1 amendment approval and prior review history precede this corrected RED.

## Authorized minimal GREEN

This approval authorizes only retaining the exact pinned generated baseline and deploying the byte-exact publisher described above through the three approved privilege-removal transformations. It does not authorize changes to runner/manifest output, publisher runtime semantics, artifact provenance, CI topology, or representative GitHub execution.

Gate 3 is approved only at this narrow scope. Any expectation change restarts Gate 2.
