```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"committed spec with wrong digest returns invalid_schema instead of hash_mismatch","recordedAt":"2026-09-03T00:25:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v7

The governed spec fixture is committed before invocation, so the independently wrong receipt digest tests stage-commit content rather than an untracked working-tree file. Expected `hash_mismatch` remains RED; prior cases remain GREEN.
