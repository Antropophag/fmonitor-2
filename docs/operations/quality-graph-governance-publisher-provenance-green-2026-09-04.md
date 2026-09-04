```delivery-metadata
{"schemaVersion":1,"kind":"green","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"M","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"}],"testReviewRecordPath":"reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-publisher-provenance-v2.md","implementationFiles":[{"path":"tools/delivery/quality_graph_publisher.py","status":"A","sha256":"989944eff52f7babbf15be395c61a3d8f4fc0efd81b33a58046cc74a4ec80549"}],"commands":["uv run python tests/Verification/quality_graph_publisher_provenance_001_test.py","make test-db-reset","make migrate","tools/verification/run.sh characterization","make quality-graph-validate","make architecture-check","git diff --check"],"recordedAt":"2026-09-04T06:09:29+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 publisher provenance GREEN

- Implementation commit: `94afae43fe699633048805b7df2e21e4a041eebd`
- Gate 3: `reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-publisher-provenance-v2.md` — `APPROVED`

The repository seam delegates ZIP/Result v0 validation to pinned upstream
`qg_github.artifacts`, then enforces the trusted exact run attempt and complete
one-artifact-per-node set. Valid fixtures pass; wrong repository, PR, head,
run, attempt (including coherent replay), graph digest, omitted/unexpected/
duplicate/expired artifacts and digest drift fail.

Direct and canonical characterization execution passed after an owned test DB
reset/migration. Quality Graph validation passed with digest
`a6d37d59715b355c8e717ad6f06a71f50f09806dbd6a57dcfcdea7a0f0a8dbdf`.
Architecture remains RED only on the separately governed PilotHttp SQL/hotspot
predecessor. Repository-wide GREEN, receipt, parity and Gate 5 are not claimed.
