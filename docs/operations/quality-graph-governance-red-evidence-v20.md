```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"2e4b01fecc02705c81228084cff5b8823f76821b0073f514ed20d3544b78d32b"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"valid lineage-v2 superseding lineage-v1 is rejected as duplicate_slice","recordedAt":"2026-09-03T04:25:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v20

After the duplicate-path rejection stays GREEN, the foreign claimant is removed and an immutable `lineage-v2` is committed beside `lineage-v1` with `supersedes: lineage-v1`. Current checker rejects the valid history as duplicate; expected one current leaf, exact current head, zero failures.
