```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"cd51769d720b89908f75aeff5c6390030b517550878bb19ed362ff4c59047b89"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"post-review drift accepted; expected one current-leaf commit_mismatch","recordedAt":"2026-09-03T06:45:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v27

The correction binds the rejection to unique current leaf `lineage-v2` and requires exactly one failure line. Historical predecessor v1 cannot be reported as another current failure. Checker still incorrectly succeeds.
