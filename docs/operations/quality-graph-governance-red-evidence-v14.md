```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"c20db4cdf1da7d7946aac178d4af73454c692b678dd347f7a15b1c4f1802ee2e"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_publisher_001_test.php","observedFailure":"repository validation target absent before valid and isolated drift checks","recordedAt":"2026-09-03T02:40:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v14

The corrected test binds success to the independently parsed manifest digest, verifies non-mutation, and invokes the same validator executable on an isolated arbitrary publisher drift, requiring nonzero `publisher_override_drift` and no success. RED remains the missing Make target.
