# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v1

- Reviewer: separately tasked agent `/root/original_upload_gate3`
- Test authors: separately tasked RED agents `/root/original_upload_red` and `/root/original_upload_red_maintenance`
- Reviewed commit: `ac689d99a1e5e8a0bed3abec90cca4739f1301f1`
- Branch/upstream at review: `codex/original-assignment-upload` / `origin/codex/original-assignment-upload`, both at the reviewed commit
- Specification: v4 owner-approved executable specification hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec proposal/design/delta hashes `a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482`, `b81f11b5aabd69645404b624d5301cd65a209b870d06ef587dcb34eebbcfc9b2`, `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Owner approval: `docs/operations/pilot-assignment-order-original-v4-owner-approval-2026-09-04.md`, decision `APPROVED FOR GATE 2`
- Public seams: `AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(...)`, `AssignmentOrderOriginalMaintenanceApplication::reconcileAssignmentOrderOriginalPrivateOrphans(...)`, owned `FMonitorPassivePdfInspector`, production/verification factories, and `AssignmentOrderOriginalVerificationWorkerBootstrap::run(...)`
- Red command: the ten commands listed under Independent reproduction; each exits `255` at its explicit missing canonical production seam guard
- Intended failure: application, maintenance, owned-parser, production MariaDB factory and worker implementation are absent; no verifier reaches DB/storage/network setup before that missing behavior
- Verdict: `APPROVED`

## Independence and delivery metadata

The reviewer did not author or edit the reviewed executable specification,
OpenSpec artifacts, tests, support fixtures, schema implementation or production
code. This append-only review record is the reviewer's only change. The schema
migration is a separately delivered predecessor: its final test approval is
`reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v7.md`, and its GREEN
evidence names implementation commit `17f03a5c0e997c0f15188c736b50a97ab95e4014`.
The application Gate 2 commits after the original RED add tests, support and
append-only evidence only; they do not implement the reviewed public seams.

## Exact reviewed test inventory

```text
da7b6dd2587929ffe5d8a6a64d9667fd34383c40ab919de105ee265da17dc6e3  tests/InstallationProcess/assignment_order_original_upload_001_test.php
4a371dd1a01b8a69ee423311bb427923229a2297d6f62c6f500c57feac5b3718  tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php
254cbdcb98affbedba43c73fb2337c976f51f6654703b5690d16404559f6341e  tests/InstallationProcess/assignment_order_original_upload_001_owned_pdf_test.php
b0fda054123048241912990267da7dbb2ef9dfcc59c85995bbd4e35d30a71b53  tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
538f64b4ec1e5fd917d737ca4c70d86c89961c98226dacfdffc1bcc3c1c45cfb  tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
f57f2ebe5a716f526c1381ebbc21354d283450e5fd12127ea2d615c998593099  tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
c73c33e0d9ac1761475f71d95b79aeb5876eacaf7511ab7d807fc54cedd32d31  tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
8ac6b9ab184e7f21f753745729cb3d797e1a58e1c549f9b2eeeadff433355d4e  tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
2981ac7e4e0a40119bffcb765d8475bef3143428fc408e55c8a18b9af519453d  tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
30c5dd86d13790552461f0f252a8bb80721f3d0bdeca8468fa8d2461c47a9f22  tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
08772767e46dd77d6e5c8a551d95ad57ab22fcbd009bde7fca7e036e74f364b5  tests/Support/AssignmentOrderOriginalPdfOracle.php
31bfd7b714b2e8cdde4a790fa1a48a48c9a4f82aac751dd0a3d16162623cda11  tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
95edf94fa25e8669a8413c08739eea205b19a881dc9c1306d915703a69f1c5c0  tests/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php
2a9cfc055ed2262e607a6d365bb7ee21b1a0f19595a02fb6f224d08ad97cd109  tests/Support/assignment_order_original_worker_runner.php
```

The schema verifier hash remains
`5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f`;
it is not re-approved here because its independent v7 Gate 3 and subsequent
GREEN evidence are append-only and unchanged.

## Findings

### Traceability and seam choice

The inventory covers the complete v4 Gate 2 contract through public
application/factory seams. Initial and post-template parity, exact capability
authorization, literal result/evidence DTOs, immutable composition/opening
snapshots, parser policy, received-byte streaming, repository replay,
append-only correction/CAS, orphan maintenance, commit ambiguity, audit
precedence and real two-worker MariaDB construction each map to named normative
requirements. Test support supplies ports and observations but does not call a
private method of the future application owner or manufacture application
Result objects as an oracle.

### Sensitivity and independent expected values

Expected status/reason tuples, event ordering, safe DTO/log allowlists, the
327-byte PDF digest, the 20,971,520-byte boundary digest/chunk count, revision
counts and accepted/conflict status multisets are test-owned literals derived
from the specification. The suite would fail plausible implementations that
authorize by role/adjacent capability, trust MIME or magic only, accept active,
encrypted or structurally invalid PDFs, count transformed bytes, mutate prior
revisions or downstream process facts, check stale before replay, duplicate a
CAS correction, release the content lease before reconciliation, skip conflict
audit after release failure, delete referenced/locked content, or misreport
commit/response loss.

### Rejections, failure precedence and cleanup

The tests distinguish non-retryable rejection/conflict from retryable stream,
storage and persistence failures and cover atomic attempt-audit failure. Safe
audit and log assertions reject sensitive filename/path/bytes/composition,
reason, SQL, exception and private-content identity fields. Maintenance uses
bounded pages, canonical cursors, digest locks, reference rereads and
at-most-once deletion. The MariaDB verifier owns random databases and validated
repository-local private roots, mode-0600 credential/config files, closes all
pipes, terminates and reaps children in `finally`, and drops/removes only its
recorded owned resources.

### Five-FD determinism

Each worker receives separate command, barrier-read, barrier-write and result
descriptors while stdout/stderr remain isolated. Parent release occurs only
after both exact READY lines. Malformed release, EOF and the bounded five-second
timeout require exit 70, no result/stdout and no second revision. Results come
only from the result descriptor; objects and connections are never serialized.

No blocking finding remains.

## Independent reproduction

At `2026-09-04T02:37:00+03:00` through `2026-09-04T02:37:01+03:00` on exact
reviewed commit `ac689d99a1e5e8a0bed3abec90cca4739f1301f1`:

```text
$ php -l <each of the 10 tests and 4 support files listed above>
No syntax errors detected (all 14 files)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> php tests/InstallationProcess/assignment_order_original_upload_001_test.php
INTENDED_RED: ... AssignmentOrderOriginalApplication ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php
INTENDED_RED: ... AssignmentOrderOriginalApplication ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_owned_pdf_test.php
INTENDED_RED: ... FMonitorPassivePdfInspector ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
INTENDED_RED: ... AssignmentOrderOriginalApplication ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
INTENDED_RED: ... AssignmentOrderOriginalApplication ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
INTENDED_RED: ... AssignmentOrderOriginalApplication ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
INTENDED_RED: ... AssignmentOrderOriginalMaintenanceApplication ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
INTENDED_RED: ... AssignmentOrderOriginalApplication ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
INTENDED_RED: ... AssignmentOrderOriginalApplication ... missing (exit 255)
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
INTENDED_RED: ... ProductionAssignmentOrderOriginalFactory ... missing (exit 255)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output before this review record)
```

The explicit guards run before fixture loading or MariaDB access, so these are
missing-behavior REDs rather than credential/setup failures. The supplied local
test credential is ready for the full MariaDB path once Gate 4 introduces the
guarded production classes.

## Required changes

None.

## Gate decision

Fresh independent Gate 3 for the complete v4 application/maintenance RED at
`ac689d99a1e5e8a0bed3abec90cca4739f1301f1` is `APPROVED`. Gate 4 may implement
only the reviewed contract without changing these tests or expectations. Any
test/support/oracle change restarts Gate 2 and requires a new independent Gate
3 record.
