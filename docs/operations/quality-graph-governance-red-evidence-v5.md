```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"3f3b7e1c5b72a2c1749047d7472e936bd2a5471b4b828a9ea3f2e63faeb35059"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"safe missing artifact returns tracer invalid_schema instead of missing_artifact","recordedAt":"2026-09-03T00:05:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v5

The missing-inventory and unsafe-path scenarios remain GREEN. A safe normalized but absent specification path exits nonzero without success, yet is classified `invalid_schema`; the contract requires `missing_artifact`.
