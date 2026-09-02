```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b4e4af246b18044639ed91ab187bc215957fa41751fb1057f691e3a248e9e60d"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"6301791ea1368d84904d1b4afa43edce610af9dc","recordedAt":"2026-09-03T04:08:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — corrected duplicate-slice RED v19

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `6301791ea1368d84904d1b4afa43edce610af9dc`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v19.md`
- Verdict: `APPROVED`

## Findings

No blocking findings for this corrected tracer.

The v18 ordering ambiguity is removed. Bytewise inventory order places `delivery/evidence/LINEAGE-001/lineage-v1.json` before `delivery/evidence/ZZZ-DUPLICATE-001/duplicate-v1.json`, so the expected failure path is independently and deterministically the later claimant rather than depending on traversal implementation.

- The canonical single receipt first passes the complete valid-lineage assertions.
- The later receipt has a distinct path and `receiptId` but repeats authoritative `sliceId: LINEAGE-001`.
- The duplicate invocation requires nonzero exit, exactly one `duplicate_slice` failure naming the later claimant, and no success marker.
- Fixture execution is offline, isolated and cleaned in `finally`; random repository identity does not affect expected ordering.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax and the valid lineage passed. With the later duplicate present, the checker incorrectly exited zero and emitted `DELIVERY_EVIDENCE_OK receipts=2 head=<fixture-head>`. The test exited `255` at the expected-nonzero assertion.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
b4e4af246b18044639ed91ab187bc215957fa41751fb1057f691e3a248e9e60d  tests/Verification/quality_graph_governance_001_test.php
b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

Evidence v19 matches the approved spec, complete sorted base-to-RED test set, exact hashes and reproduced failure. This record supersedes the v18 approval for the duplicate-slice expectation while retaining it as history.

## Authorized minimal GREEN

This approval authorizes only deterministic global `sliceId` uniqueness enforcement that reports the bytewise-later claimant as `duplicate_slice` and preserves the valid single receipt. Supersession and other receipt-history cases remain outside this tracer.

Gate 3 is approved only at this narrow scope. Any expectation change restarts Gate 2.
