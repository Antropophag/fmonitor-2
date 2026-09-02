```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"cf82b728a3e33667cafa1f2e418b679c8417ca39cb4df81528ad5b0013f21c29"}],"command":"php tests/Verification/quality_graph_toolchain_001_test.php","observedFailure":"immutable setup-uv action required but absent from declaration","recordedAt":"2026-09-03T05:00:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v22

Exact runtime/packages remain present. The test now requires the dereferenced official `setup-uv` v7 commit and mutation-proves rejection of an additional floating `@v7`; declaration currently has no setup action.
