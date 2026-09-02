```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"ead4333e80da301b46f5e733fd67e2436e2b2d29a1f23accb3c7007e5fa3f135"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_publisher_001_test.php","observedFailure":"reviewed exact baseline/custom publisher contract is absent","recordedAt":"2026-09-03T01:45:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v11

The corrected test requires the exact reviewed generated baseline digest and byte-exact minimal publisher. Mutation checks demonstrate that an extra trigger, expanded permission or checkout step cannot match. RED remains the absent retained baseline.
