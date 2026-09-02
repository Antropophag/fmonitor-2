```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"ab4292dbc393185a7fdb7c56e69607a48b1e12cc40eb1b960ef159a887d53390","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"a4b0a557c839776f6077d3201a51a6c5e22fb0c4eccbd470b6cb42825a182400"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"04b942eafdfb83112c9c66b6d13000e46687adc021110c7eec34a037b8d3ab84"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_publisher_001_test.php","observedFailure":"generated publisher baseline for allowlisted privilege-removal comparison is absent","recordedAt":"2026-09-03T01:25:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v10

The pinned compiler-generated publisher exists as test input, but no retained comparison baseline/custom override exists. The test is syntax-clean and stops at the first missing publisher-override behavior.
