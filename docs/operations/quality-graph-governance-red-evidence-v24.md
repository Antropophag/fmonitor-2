```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"c24a9f2dd4c009608b0fd75fbeb43ed38cb3cc83f92a8274a86231d768fb9843"}],"command":"php tests/Verification/quality_graph_publisher_001_test.php","observedFailure":"production validator accepts appended floating CLI package reference","recordedAt":"2026-09-03T06:00:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v24

Exact publisher validation remains GREEN. In an isolated otherwise-valid graph fixture, appending `quality-graph-cli>=0.1` to project configuration incorrectly returns success. Expected nonzero `toolchain_pin_drift` and no success marker.
