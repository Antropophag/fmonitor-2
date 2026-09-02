```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b9fb3e7d7ecb68d3fcf654f47deeeb7abec35b613d1c293389efb0f94d2f0ecc"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"committed governed implementation mutation after review/receipt is accepted","recordedAt":"2026-09-03T06:30:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v26

Valid lineage and supersession remain GREEN. A later committed change to the governed implementation file is incorrectly accepted with `DELIVERY_EVIDENCE_OK`; expected nonzero `commit_mismatch` and no success.
