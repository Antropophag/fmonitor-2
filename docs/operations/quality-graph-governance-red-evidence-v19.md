```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b4e4af246b18044639ed91ab187bc215957fa41751fb1057f691e3a248e9e60d"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"later bytewise duplicate claimant still accepted","recordedAt":"2026-09-03T04:05:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v19

Corrects v18 discovery-order ambiguity: canonical `LINEAGE-001` is processed before `ZZZ-DUPLICATE-001`, so the expected reported receipt is independently determined. Checker still succeeds with two duplicate claims.
