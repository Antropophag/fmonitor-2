```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root/qg_metadata_binding_red","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"e59af02ab3d73cccb5245cfeb4d023b35ea02453","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"M","sha256":"226daa74d3deae719ceb3368aa61f0b47a7b6920ff75e64e620aa8e3729ee0d1"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"isolated valid-lineage spec sliceId mutation yields one stale_spec instead of the independently required metadata_mismatch","recordedAt":"2026-09-04T05:53:23+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v33

This correction returns the metadata behavior implemented by `7b554a8` beyond
the narrow v31 Gate 3 authorization to Gate 2. The test adds independently
specified isolated-valid-lineage mutations for:

- every authoritative artifact's `schemaVersion` and `sliceId`;
- RED `specPath` and `baseCommit`;
- GREEN `testReviewRecordPath`;
- test-review and code-review `specSha256` metadata;
- the receipt-carried test-review and code-review `specSha256` values.

Each case clones the previously accepted lineage independently, asserts clone,
both Git identity commands, metadata discovery, every write, staging and commit,
then calls the public `check-evidence.php --repo` seam. Artifact mutations also
recompute only that artifact's receipt digest, preventing `hash_mismatch` from
masking the field under test. Every oracle names an exact category, requires
exactly one total failure and forbids `DELIVERY_EVIDENCE_OK`.

## Honest historical RED

The exact new test blob was applied in a detached home-directory worktree at
`e59af02ab3d73cccb5245cfeb4d023b35ea02453`. This is the immediate predecessor
of over-authorized implementation `7b554a8e68aa8b2e6d982fd0d48644a90c61674e`:
the valid lineage and earlier narrow RED binding fixture were already
constructible, while the broader artifact/reference bindings were not yet
implemented.

Commands and result at `2026-09-04T05:53:23+03:00`:

```text
sha256sum tests/Verification/quality_graph_governance_001_test.php
226daa74d3deae719ceb3368aa61f0b47a7b6920ff75e64e620aa8e3729ee0d1  tests/Verification/quality_graph_governance_001_test.php

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

The preceding `spec.schemaVersion` mutation produced its exact single
`invalid_schema` outcome, proving execution reached the intended missing
general identity binding. The failure is not setup-related and production was
untouched.

## Current diagnostic

On current pre-RED-record head `da21b4a`, the same test blob passed:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
No syntax errors detected in tests/Verification/quality_graph_governance_001_test.php

php tests/Verification/quality_graph_governance_001_test.php
QUALITY-GRAPH-GOVERNANCE-001 TESTS PASSED
exit=0
```

This diagnostic GREEN shows the test is sensitive to the broader implementation
already present; it is not Gate 3 approval and does not authorize production.
