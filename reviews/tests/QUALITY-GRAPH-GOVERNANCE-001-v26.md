```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b9fb3e7d7ecb68d3fcf654f47deeeb7abec35b613d1c293389efb0f94d2f0ecc"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"6071e533818f89e26e5a070ceb53d927c3d01ed2","recordedAt":"2026-09-03T06:33:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — post-review drift RED v26

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `6071e533818f89e26e5a070ceb53d927c3d01ed2`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v26.md`
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

1. **The expected failure names the historical predecessor rather than the current receipt leaf.** Immediately before mutation, the fixture proves valid supersession `lineage-v2 supersedes lineage-v1` and `receipts=1`. Under the contract, v2 is the unique current governance claim; v1 remains immutable history. The later implementation mutation must invalidate the current leaf and therefore report `delivery/evidence/LINEAGE-001/lineage-v2.json`. Expecting `lineage-v1.json` couples diagnostics to bytewise historical traversal and contradicts the current-leaf semantics established by v21.

   Return to Gate 2 by changing the expected `commit_mismatch` receipt path to `lineage-v2.json`. Also require deterministic cardinality for the relevant failure set so an implementation does not pass by reporting both historical and current receipts as independent current failures. Recapture RED; mutation chronology requires no change.

## Checks that passed

- Valid base lineage, duplicate rejection and v1→v2 supersession are GREEN before mutation.
- The governed implementation file is changed after code review, v1 receipt, and v2 receipt, then explicitly added and committed.
- The changed path is exactly the implementation path recorded in GREEN, code review and both receipts, so `commit_mismatch` is the specified category.
- Negative terminal assertions require nonzero status and no success marker.
- Fixture history is real, offline and removed in `finally`; evidence metadata and exact hashes match.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax and all prior scenarios passed. After the committed mutation the checker incorrectly exited zero with `DELIVERY_EVIDENCE_OK receipts=1 head=<post-mutation-head>`. The test exited `255` at expected nonzero. This is an intended behavioral RED, but its expected receipt identity is wrong.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
b9fb3e7d7ecb68d3fcf654f47deeeb7abec35b613d1c293389efb0f94d2f0ecc  tests/Verification/quality_graph_governance_001_test.php
391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

After correction, approval may authorize only detection of a committed post-review mutation to a governed implementation path as `commit_mismatch` on the current v2 receipt. Other post-review spec/test/graph and envelope cases remain for later RED cycles.

Gate 3 is not approved; no post-review drift implementation is authorized from v26.
