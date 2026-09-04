```delivery-metadata
{"schemaVersion":1,"kind":"green","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"}],"testReviewRecordPath":"reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-v35.md","implementationFiles":[{"path":"tools/delivery/check-quality-graph.php","status":"M","sha256":"18b64b0d8f9ed87f1c5d3e129af0a122fb56454951af7089b4584a747c43ac65"}],"commands":["php -l tools/delivery/check-quality-graph.php","php tests/Verification/quality_graph_runner_security_001_test.php","php tests/Verification/quality_graph_publisher_001_test.php","php tests/Verification/quality_graph_toolchain_001_test.php","make quality-graph-validate","git diff --check","make architecture-check"],"recordedAt":"2026-09-04T06:17:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 runner security GREEN

- Implementation: `f87c5a2edb9cccbc2b609cd78eb2e1095ac02fc1`
- Gate 3: `reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-v35.md` — `APPROVED`

Both deployed untrusted runners now fail early as `runner_security` for any
floating third-party action, persisted checkout credential, top-level write or
job-level write permission. The validator does not prescribe a setup action,
its SHA or a package-install command. Generated parity remains independently
classified as `generated_drift`.

Focused tests, graph validation and diff check passed. Architecture remains RED
only on the separately governed PilotHttp SQL/hotspot predecessor; no
repository-wide GREEN, receipt, parity or Gate 5 is claimed.
