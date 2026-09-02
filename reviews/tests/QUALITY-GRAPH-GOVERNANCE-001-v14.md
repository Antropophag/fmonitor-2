```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"c20db4cdf1da7d7946aac178d4af73454c692b678dd347f7a15b1c4f1802ee2e"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"a55dd84506319b3c0054f020f8915245a347c47f","recordedAt":"2026-09-03T02:43:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — validation RED v14

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `a55dd84506319b3c0054f020f8915245a347c47f`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v14.md`
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

1. **The new drift probe cannot run independently after the happy path turns GREEN.** It invokes `qggRun(...)`, but that function is defined only in `tests/Verification/quality_graph_governance_001_test.php`. The publisher test requires only `tests/bootstrap.php`, which does not define or load `qggRun`. PHP test files are executed independently. Once `make quality-graph-validate` exists and the happy path passes, execution will reach the mutation block and fail with undefined-function setup error instead of observing validator behavior.

   Return to Gate 2 by adding a locally owned runner helper (with a publisher-specific name) or a deliberately shared support fixture required by both tests. Then demonstrate the intended RED at the mutation assertion or at the still-missing validator seam without relying on execution order between test files.

## Checks that passed

- The success digest is now bound to the manifest's validated 64-character `graphDigest`, so a constant zero digest cannot satisfy the happy path unless the manifest itself says so.
- The production invocation checks zero exit, one exact digest-bearing success and unchanged publisher bytes.
- The isolated fixture copies all declared graph/baseline/workflow inputs, appends arbitrary publisher drift, and is designed to require nonzero `publisher_override_drift` with no success.
- Cleanup is in `finally`, and the current missing-target RED leaves no publisher fixture.
- Evidence metadata contains the complete sorted test set and exact hashes.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_publisher_001_test.php
php tests/Verification/quality_graph_publisher_001_test.php
```

Syntax passed. Prior assertions were GREEN. The test exited `255` because `make quality-graph-validate` exited `2` with no such target. This intended RED occurs before the latent undefined-helper setup failure, so it cannot validate the new drift probe as written.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400  tests/Verification/quality_graph_governance_001_test.php
c20db4cdf1da7d7946aac178d4af73454c692b678dd347f7a15b1c4f1802ee2e  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

After the setup correction, approval may cover only a read-only repository validator that returns the manifest-bound digest for the exact override and rejects arbitrary publisher drift through the same executable. Broader graph drift and CI behavior remain outside this tracer.

Gate 3 is not approved; no validator implementation is authorized from v14.
