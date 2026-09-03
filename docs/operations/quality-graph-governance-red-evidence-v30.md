```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"bb6ad69744fe72c8e733326719e5a0dec8d351038a2f6dc7962542fc189375d7"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"committed authoritative RED sliceId mismatch reaches Git traversal and reports gate_order instead of metadata_mismatch","recordedAt":"2026-09-03T19:42:19+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v30

The isolated fixture starts from the previously valid immutable lineage. It
clones that repository, verifies every setup operation, changes only the
authoritative RED metadata `sliceId`, recomputes the receipt's RED artifact
SHA-256, and commits both changes. The receipt continues to claim
`LINEAGE-001`, so the approved contract requires a single terminal
`metadata_mismatch` before Git chronology traversal.

Command:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Observed result:

```text
No syntax errors detected in tests/Verification/quality_graph_governance_001_test.php
PHP Fatal error: Uncaught TestFailure: RED_ASSERTION: receipt and authoritative RED slice identity must match
evidence={"status":1,"stdout":"","stderr":"DELIVERY_EVIDENCE_FAILURE category=gate_order receipt=delivery/evidence/LINEAGE-001/lineage-v1.json detail=docs/red.md has no unique first matching blob commit\n"}
Expected: 1
Actual: 0
exit=255
```

The failure is the intended missing behavior: setup, the preceding valid
lineage, and the earlier unknown-field mutation all complete. The checker
rejects the mutated fixture, but only after entering Git traversal and with the
wrong category. No production implementation was changed for this RED.
