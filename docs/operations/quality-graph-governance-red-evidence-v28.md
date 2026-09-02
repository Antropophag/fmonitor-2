```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"86e731bde8ae0f998229e50f936c51f65605e200061507a3b9d11385900f5bee"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"unknown RED metadata field reaches Git traversal and returns gate_order","recordedAt":"2026-09-03T07:00:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v28

An isolated clone adds an unknown field to authoritative RED metadata and updates the receipt digest, preventing a trivial hash mismatch. Checker incorrectly reaches blob chronology and returns `gate_order`; strict schema requires `invalid_schema` before traversal, nonzero and no success.
