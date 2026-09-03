```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_allowlist_gate3_v2","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"16fc1c6d652561543247c990cc244c2d05fe2a84a0b2e15ba2a2f9a0077b4dde"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"736a78e34ae7a0765c378062476afaec3feee62d","recordedAt":"2026-09-04T01:00:13+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — corrected exact post-review evidence allowlist RED v33

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_allowlist_gate3_v2`
- Independence: reviewer did not author the specification, test, RED evidence, or implementation
- Reviewed RED commit: `736a78e34ae7a0765c378062476afaec3feee62d`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v32.md`
- Previous review: `reviews/tests/QUALITY-GRAPH-GOVERNANCE-001-v32.md` (`CHANGES_REQUESTED`)
- Gate 5 source finding: `reviews/code/QUALITY-GRAPH-GOVERNANCE-001-v2.md`, blocking finding 1
- Verdict: `APPROVED`

## Findings

No blocking findings for this corrected tracer slice.

The scenario now commits both approved post-review operations artifacts before
the negative case: the representative phase-A parity record
`docs/operations/quality-graph-representative-pr-phase-a-2026-09-03.md` and the
named final-verification record
`docs/operations/quality-graph-governance-final-verification-2026-09-04.md`.
It requires the public checker to remain GREEN at that exact head, with one
terminal success for the fixture HEAD and no failure marker. This prevents an
over-restrictive implementation that merely removes the existing
`docs/operations/**` exception.

The subsequent independently committed `docs/operations/unrelated-note.md`
must produce nonzero status, exactly one `commit_mismatch`, exactly one total
failure marker and no success marker. Together, the positive and negative
fixtures distinguish the two contract-authorized records from the current
directory-wide exception. Expected paths, outcome and category are fixed from
the approved evidence-envelope contract and the Gate 5 finding rather than
derived from checker output.

The test resets to the positive-envelope head before the existing governed
implementation-mutation regression, so the new negative record cannot affect
later scenarios. Fixture commits use the specification-permitted test-only
executable seam `tools/delivery/check-evidence.php --repo <isolated-repository>`;
they are offline, deterministic real Git history and are removed in `finally`.

## Reproduced RED

At exact commit `736a78e34ae7a0765c378062476afaec3feee62d`:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
No syntax errors detected in tests/Verification/quality_graph_governance_001_test.php
exit=0

php tests/Verification/quality_graph_governance_001_test.php
PHP Fatal error: Uncaught TestFailure: Unallowlisted post-review operations evidence must fail;
evidence={"status":0,"stdout":"DELIVERY_EVIDENCE_OK receipts=1 head=<fixture-sha>\n","stderr":""}
Expected: true
Actual: false
exit=255
```

The positive envelope completed before the intended assertion, proving both
allowed records remain accepted by the current checker. The failure then
reproduced because the unrelated operations record was also accepted. The
varying fixture SHA is deterministic fixture history content, not an oracle.

## Reviewed hashes and inventory

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
16fc1c6d652561543247c990cc244c2d05fe2a84a0b2e15ba2a2f9a0077b4dde  tests/Verification/quality_graph_governance_001_test.php
391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600  tests/Verification/quality_graph_publisher_001_test.php
ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863  tests/Verification/quality_graph_toolchain_001_test.php
08d60998f76789db19203a201fe20dffd720b40c9755786e29302be91a1a3f04  docs/operations/quality-graph-governance-red-evidence-v32.md
```

The three test entries are the complete bytewise-sorted
`9c87164393b2048428fc0987c357e65e0e9fc146..RED` inventory under `tests/`, all
with status `A`. RED evidence v32 matches the executable specification hash,
test hashes, base commit, intended failure, and exact RED commit chronology.

## Authorized minimal GREEN

This approval authorizes only replacing the directory-wide post-review
`docs/operations/**` exception with acceptance of the two exact positive paths
exercised above while preserving the code-review record, receipt-chain and
OpenSpec-task exceptions and every earlier test scenario. It does not authorize
a broader filename prefix, directory, regex class, metadata, lineage, publisher
or graph behavior change.

Gate 3 is approved only for the exact specification and test blobs recorded
above. Any expectation, fixture, test inventory or specification change
restarts Gate 2.
