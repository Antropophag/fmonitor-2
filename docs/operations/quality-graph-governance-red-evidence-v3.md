```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"081eb9968e491d423e572c4216b0274980efbfc8147715869702681517442ea8"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"checker returns placeholder invalid_schema instead of unsafe_path for escaping artifact path","recordedAt":"2026-09-02T23:35:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v3

Adds the next tracer bullet after the approved isolated missing-inventory behavior. Earlier RED evidence remains retained.

```text
$ php tests/Verification/quality_graph_governance_001_test.php
exit=255
PHP Fatal error: Uncaught TestFailure: RED_ASSERTION: escaping artifact path must be rejected before artifact access
evidence={"status":1,"stdout":"","stderr":"DELIVERY_EVIDENCE_FAILURE category=invalid_schema receipt=delivery/evidence detail=receipt validation is not implemented by this tracer slice\n"}
Expected: 1
Actual: 0
```

The preceding isolated missing-inventory assertion is GREEN. The new failure proves absent strict receipt parsing/path rejection rather than setup failure.
