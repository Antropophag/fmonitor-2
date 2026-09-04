```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_metadata_binding_gate3","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"M","sha256":"226daa74d3deae719ceb3368aa61f0b47a7b6920ff75e64e620aa8e3729ee0d1"}],"redCommit":"3e81812f0ce964c58f8669085c0a3b47a6588946","recordedAt":"2026-09-04T05:55:48+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — complete metadata binding RED v34

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_metadata_binding_gate3`
- Independence: reviewer did not author the specification, test, RED evidence, or implementation
- Reviewed RED commit: `3e81812f0ce964c58f8669085c0a3b47a6588946`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v33.md`
- Gate 5 source finding: `reviews/code/QUALITY-GRAPH-GOVERNANCE-001-v2.md`, blocking finding 4
- Verdict: `APPROVED`

## Findings

No blocking findings for this grouped metadata-binding slice.

The 17 independent mutations cover the remaining behavior that Gate 4 commit
`7b554a8e68aa8b2e6d982fd0d48644a90c61674e` implemented beyond Gate 3 v31:

- five authoritative artifact `schemaVersion` mutations require exactly one
  `invalid_schema` each;
- five authoritative artifact `sliceId` mutations, RED `specPath`, RED
  `baseCommit`, and GREEN `testReviewRecordPath` require exactly one
  `metadata_mismatch` each;
- test-review and code-review artifact `specSha256` mutations require exactly
  one `stale_spec` each;
- receipt-carried test-review and code-review `specSha256` mutations require
  exactly one `metadata_mismatch` each.

Each case starts from an independent clone of the already valid lineage. The
test asserts clone and both Git identity commands, presence and decoding of the
metadata block, every mutation write, receipt write, staging, and commit before
calling the public test-only `check-evidence.php --repo` seam. Artifact cases
recompute only the corresponding receipt digest from the independently mutated
bytes; receipt-only cases leave artifacts untouched. Every oracle requires a
nonzero exit, one literal specification-derived category for the current
receipt, one total failure marker, and no success marker. Thus setup failure,
`hash_mismatch`, another category, multiple diagnostics, or traversal after the
intended rejection cannot satisfy a case.

The grouped table is complete and has no duplicate field target. It covers all
five authoritative metadata kinds and both receipt-carried review spec hashes.
The expected distinction is contract-relevant: malformed schema versions are
`invalid_schema`, cross-artifact identity/reference disagreement is
`metadata_mismatch`, and an authoritative review bound to another spec digest
is `stale_spec`.

## Reproduced historical RED

The exact test blob from reviewed commit `3e81812f` was applied without the RED
evidence record to a detached home-directory worktree at historical commit
`e59af02ab3d73cccb5245cfeb4d023b35ea02453`, the immediate predecessor of the
over-authorized implementation:

```text
sha256sum tests/Verification/quality_graph_governance_001_test.php
226daa74d3deae719ceb3368aa61f0b47a7b6920ff75e64e620aa8e3729ee0d1

php -l tests/Verification/quality_graph_governance_001_test.php
No syntax errors detected in tests/Verification/quality_graph_governance_001_test.php
exit=0

php tests/Verification/quality_graph_governance_001_test.php
PHP Fatal error: Uncaught TestFailure: RED_ASSERTION: spec sliceId must produce exactly one metadata_mismatch;
evidence={"status":1,"stdout":"","stderr":"DELIVERY_EVIDENCE_FAILURE category=stale_spec receipt=delivery/evidence/LINEAGE-001/lineage-v1.json detail=red spec digest is stale\n"}
Expected: 1
Actual: 0
exit=255
```

The preceding `spec schemaVersion` case completed with its required exact
`invalid_schema`, so execution reached the first missing general identity
binding and failed for the intended reason. The fixture was outside `/tmp` and
was removed after inspection.

On current reviewed head `3e81812f`, the exact focused test is diagnostic GREEN:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
No syntax errors detected in tests/Verification/quality_graph_governance_001_test.php

php tests/Verification/quality_graph_governance_001_test.php
QUALITY-GRAPH-GOVERNANCE-001 TESTS PASSED
exit=0
```

This confirms sensitivity to the existing broader implementation but is not a
Gate 4 or integration verdict.

## Traceability, hashes, and inventory

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
226daa74d3deae719ceb3368aa61f0b47a7b6920ff75e64e620aa8e3729ee0d1  tests/Verification/quality_graph_governance_001_test.php
6c1039817f23349efedbd2926cc5ae6305b6e829d334a1ac9f992713bb3fa8bf  docs/operations/quality-graph-governance-red-evidence-v33.md
```

`git diff --no-renames --name-status
e59af02ab3d73cccb5245cfeb4d023b35ea02453..3e81812f0ce964c58f8669085c0a3b47a6588946
-- tests/` contains exactly the single `M` entry recorded in RED evidence and
this review. The specification hash, test hash, base commit, observed failure,
and Git ancestry all agree with evidence v33.

## Authorized minimal GREEN

This approval authorizes only the 17 exact metadata/reference bindings listed
above, with the reviewed categories and failure cardinality, while preserving
all earlier scenarios. It does not authorize changes to receipt shape,
artifact path safety, lineage chronology, evidence-envelope allowlisting,
publisher behavior, graph behavior, or any additional metadata field.

Gate 3 is approved only for the exact specification and test blobs recorded
above. Any expectation, fixture, test inventory, or specification change
restarts Gate 2.
