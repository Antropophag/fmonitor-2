```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"b2a130c7d38e4a73fe912655c7d5d661d78648c42b3028b9550f6115d71ae809"}],"command":"php tests/Verification/quality_graph_toolchain_001_test.php","observedFailure":"approved exact package install command is absent from runner declaration","recordedAt":"2026-09-03T05:20:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v23

No new Action contract is introduced. The test requires one standard Python install command for the already approved exact 0.1.7 package set and proves the complete declaration+TOML occurrence set contains no additional ranged/mixed references. Current declaration lacks the install command.
