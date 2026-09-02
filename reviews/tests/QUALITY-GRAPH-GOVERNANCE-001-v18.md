```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"bbc502074061bb0c0e595051880f82643525f29a595e47a50e08bb3603f763c4"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"b603dfb3f384524ccecd40a8fb0f2d790c162f04","recordedAt":"2026-09-03T03:53:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — duplicate-slice RED v18

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `b603dfb3f384524ccecd40a8fb0f2d790c162f04`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v18.md`
- Verdict: `APPROVED`

## Findings

No blocking findings for this tracer slice.

- **Traceability:** the test directly covers the v0.6 `duplicate_slice` acceptance outcome: two distinct inventory paths and receipt IDs claim one authoritative `sliceId`.
- **Prior behavior:** the exact single-receipt lineage first passes with zero failures and terminal `receipts=1` success, proving the fixture is valid before duplication.
- **Sensitivity:** the duplicate invocation independently requires nonzero status, exactly one `duplicate_slice` failure naming the second bytewise-later receipt path, and no success marker. The current incorrect `receipts=2` success cannot pass.
- **Expected-value independence:** the second path/ID are fixed by the fixture while the repeated slice identity comes from the already valid receipt; category and terminal rules are specification literals.
- **Isolation/determinism:** both receipts live only in the short-lived lineage repository, discovery paths have deterministic bytewise order, execution is offline, and cleanup runs in `finally`.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax passed and the valid-lineage scenario was GREEN. After adding the second receipt, the checker incorrectly exited zero with:

```text
DELIVERY_EVIDENCE_OK receipts=2 head=<fixture-head>
```

The test exited `255` at the expected-nonzero assertion. No fixture directory remained.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
bbc502074061bb0c0e595051880f82643525f29a595e47a50e08bb3603f763c4  tests/Verification/quality_graph_governance_001_test.php
b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

Evidence v18 matches the approved spec, complete sorted base-to-RED test set, exact hashes and reproduced failure.

## Authorized minimal GREEN

This approval authorizes only global inventory uniqueness enforcement for authoritative `sliceId`, producing the tested `duplicate_slice` failure while preserving the valid single receipt. Receipt supersession, duplicate IDs within a chain, directory-name equality, immutable commit history, multiple leaves and cycles still require their own RED cycles.

Gate 3 is approved only at this narrow scope. Any expectation change restarts Gate 2.
