```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"2e4b01fecc02705c81228084cff5b8823f76821b0073f514ed20d3544b78d32b"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"504c7404f1c394af600465b2acfc10602029586a","recordedAt":"2026-09-03T04:28:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — valid-supersession RED v20

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `504c7404f1c394af600465b2acfc10602029586a`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v20.md`
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

1. **The new valid success path does not preserve the v17 terminal-stdout invariant.** It requires zero exit, one matching OK in combined stdout/stderr, and zero failures, but does not require that OK be emitted on stdout as the terminal nonempty line. A supersession-specific implementation that writes OK to stderr, or writes trailing nonempty stdout after OK, passes this scenario despite violating the normative terminal protocol already enforced for the preceding single-receipt valid path.

   Return to Gate 2 by applying the same terminal nonempty stdout assertion to the supersession result. Keep the exact `receipts=1` and independently derived supersession HEAD. Recapture RED; no fixture-history change is required.

## Checks that passed

- **Temporary duplicate cleanup:** the foreign `ZZZ-DUPLICATE-001` claimant is untracked, is explicitly unlinked with its directory removed, and therefore is neither present nor staged in the subsequent commit.
- **Immutable history:** committed `lineage-v1.json` is never rewritten or removed.
- **Commit order:** `lineage-v2.json` is added in a distinct later commit after v1 and names `supersedes: lineage-v1` in the same slice directory.
- **Current-leaf semantics:** v1 has one successor, v2 has none, so there is exactly one current leaf and expected `receipts=1` is independently determined rather than counting both historical files.
- **Prior behavior:** the valid v1 chain and foreign duplicate rejection are GREEN before the supersession scenario.
- **Evidence:** metadata contains the complete sorted base-to-RED test set and exact hashes.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax and all prior scenarios passed. The checker rejected v2 as `duplicate_slice` naming `lineage-v2.json`; the test exited `255` at the expected-zero assertion. This is the intended missing-supersession behavior.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
2e4b01fecc02705c81228084cff5b8823f76821b0073f514ed20d3544b78d32b  tests/Verification/quality_graph_governance_001_test.php
b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

After correction, approval may authorize only one valid immutable v1→v2 supersession chain and current-leaf counting while preserving duplicate rejection. Missing targets, multiple leaves, cycles, reused IDs and historical mutation remain for later RED cycles.

Gate 3 is not approved; no supersession implementation is authorized from v20.
