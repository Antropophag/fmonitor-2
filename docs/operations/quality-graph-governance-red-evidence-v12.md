```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"0a5b2a946c950dfd083e7d4a6cc8a6e4bfcd03091ae50ee7af1da10f6bdefe9f"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_publisher_001_test.php","observedFailure":"allowlisted exact publisher baseline/custom files are absent","recordedAt":"2026-09-03T02:00:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v12

The expected publisher now preserves the generated header, concurrency expression, publish condition, runtime and watch/publish bytes. Only `issue_comment`, command job and excess permissions differ. RED remains the absent retained baseline.
