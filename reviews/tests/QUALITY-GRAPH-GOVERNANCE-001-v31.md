```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_binding_gate3","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"bb6ad69744fe72c8e733326719e5a0dec8d351038a2f6dc7962542fc189375d7"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"906538b47980815b527d1571498245026e5e289b","recordedAt":"2026-09-03T19:43:51+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — metadata identity binding RED v31

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_binding_gate3`
- Independence: reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `906538b47980815b527d1571498245026e5e289b`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v30.md`
- Verdict: `APPROVED`

## Findings

No blocking findings for this tracer slice.

The new scenario clones the already proven valid v1 lineage before the main
fixture is modified. Clone, both Git identity commands, metadata discovery,
both file writes, staging and commit are asserted independently, so an
uncommitted mutation cannot masquerade as the intended RED. It changes only the
authoritative RED metadata `sliceId`, recomputes the receipt's RED artifact hash
from those changed bytes, and leaves the receipt identity as `LINEAGE-001`.
Consequently neither setup failure nor `hash_mismatch` can satisfy the oracle.

The assertions require nonzero exit, exactly one
`metadata_mismatch` for the current `lineage-v1.json`, exactly one total failure
marker, and no success marker. A validator that skips identity equality and
continues into Git traversal therefore remains observably RED. Expected
identity, category and receipt path are literals from the approved canonical
metadata/receipt contract, not values derived from checker output.

The test uses the specification-permitted test-only executable seam
`tools/delivery/check-evidence.php --repo <isolated-git-fixture>`. It is offline,
uses real committed Git blobs and history, does not touch the production
checkout, and removes the temporary clone in `finally`.

## Reproduced RED

At exact commit `906538b47980815b527d1571498245026e5e289b`:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Syntax passed. The valid lineage and preceding strict-schema mutation passed.
The binding fixture setup completed, then the checker exited `1` with exactly
one `gate_order` for `docs/red.md` and no success marker; the test exited `255`
at the intended `metadata_mismatch` assertion. Cleanup left no binding fixture.

## Reviewed hashes and inventory

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
bb6ad69744fe72c8e733326719e5a0dec8d351038a2f6dc7962542fc189375d7  tests/Verification/quality_graph_governance_001_test.php
391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
```

The three entries are the complete bytewise-sorted `baseCommit..RED` inventory
under `tests/`, all with status `A`. Evidence v30 matches the current spec hash,
exact test hashes, base commit, RED commit chronology and reproduced failure.

## Authorized minimal GREEN

This approval authorizes only enough canonical metadata validation to require
the authoritative RED `sliceId` to equal the receipt `sliceId`, emitting one
`metadata_mismatch` before Git chronology traversal while preserving every
earlier scenario. Identity binding for the other artifact kinds or any broader
schema/history behavior requires its own RED and independent review.

Gate 3 is approved only for the exact specification and test blobs recorded
above. Any expectation, fixture, test-inventory or specification change restarts
Gate 2.
