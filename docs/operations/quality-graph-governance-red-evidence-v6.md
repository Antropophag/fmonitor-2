```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e266b326ff9a1d423ebc35490f5f0c7aac94aaa576633ec5b58631661872a087"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"present spec with wrong digest returns invalid_schema instead of hash_mismatch","recordedAt":"2026-09-03T00:15:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v6

Prior inventory, path and missing-artifact cases are GREEN. A present specification whose content does not match the receipt digest remains RED at the required `hash_mismatch` classification, with nonzero exit and no success marker.
