```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"da5d51fd815015887334d27cc5504d76df8bf4fbb1b2dd70e2011a59cf4712de"}],"command":"php tests/Verification/quality_graph_toolchain_001_test.php","observedFailure":"canonical declaration and exact-version project are absent","recordedAt":"2026-09-03T00:40:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v8

The isolated toolchain test fails at the first missing behavior: `quality-graph.yml` does not exist. PHP and the test bootstrap work; expected pins are literals independently taken from the audited upstream release.
