# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v2

- Reviewer: separately tasked agent `/root/original_upload_gate3_v2`
- RED authors: separately tasked agents `/root/original_upload_red` and `/root/original_upload_red_maintenance`
- Reviewed correction commit: `66fe7f0e329434e63d02296f465f9679567637b2`
- Corrected preimplementation base: `921cbafdcf394d567be3e4aa6680baeec99e0427`
- Branch/upstream at review: `codex/original-assignment-upload` / `origin/codex/original-assignment-upload`, both at the reviewed correction commit
- Prior review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md` (`APPROVED` at `ac689d99a1e5e8a0bed3abec90cca4739f1301f1`, superseded only for the corrected audit-precedence test)
- Specification: v4 owner-approved executable specification hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec proposal/design/delta hashes `a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482`, `b81f11b5aabd69645404b624d5301cd65a209b870d06ef587dcb34eebbcfc9b2`, `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Owner approval: `docs/operations/pilot-assignment-order-original-v4-owner-approval-2026-09-04.md`, decision `APPROVED FOR GATE 2`
- Correction evidence: `docs/operations/assignment-order-original-upload-gate2-audit-precedence-red-correction-v2-2026-09-04.md`
- Verdict: `APPROVED`

## Independence and scope

The reviewer did not author or edit the specification, OpenSpec artifacts,
tests, support fixtures, evidence or production code. This append-only review
record is the reviewer's only change. The rereview covers the one-line corrected
audit-precedence oracle and its evidence against the complete v1 test inventory;
current production GREEN is diagnostic only and is not the approval basis.

## Correction review and sensitivity

The test executes an accepted initial upload and then a correction CAS conflict
using the same in-memory environment. `leaseReleaseCalls` is intentionally an
environment-wide cumulative counter: the successful initial operation releases
its lease once, and the conflict operation attempts its own release once before
the required terminal/audit commit. The corrected literal `[2, 1]` therefore
proves both per-operation release attempts plus exactly one safe log for the
injected conflict release failure. The adjacent trace assertion independently
requires that conflict release precede the atomic attempt commit, while the
result assertion preserves persistence-failure precedence when that commit also
fails. Reverting the counter expectation to `[1, 1]` rejects the valid cumulative
history and does not express the per-operation contract.

The correction diff changes only that expected tuple and its explanatory
message. It does not weaken the selected-result, ordering, audit-failure or
safe-log assertions and introduces no implementation coupling.

## Inventory reconciliation with v1

Thirteen of the fourteen v1 reviewed test/support files retain their exact
SHA-256 hashes. The only changed file is:

```text
2b03d9364e1aef6016dfb400614dc8712920d10831906cb76a652d2dd1909b28  tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
```

The unchanged hashes are:

```text
da7b6dd2587929ffe5d8a6a64d9667fd34383c40ab919de105ee265da17dc6e3  tests/InstallationProcess/assignment_order_original_upload_001_test.php
4a371dd1a01b8a69ee423311bb427923229a2297d6f62c6f500c57feac5b3718  tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php
254cbdcb98affbedba43c73fb2337c976f51f6654703b5690d16404559f6341e  tests/InstallationProcess/assignment_order_original_upload_001_owned_pdf_test.php
b0fda054123048241912990267da7dbb2ef9dfcc59c85995bbd4e35d30a71b53  tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
538f64b4ec1e5fd917d737ca4c70d86c89961c98226dacfdffc1bcc3c1c45cfb  tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
f57f2ebe5a716f526c1381ebbc21354d283450e5fd12127ea2d615c998593099  tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
c73c33e0d9ac1761475f71d95b79aeb5876eacaf7511ab7d807fc54cedd32d31  tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
8ac6b9ab184e7f21f753745729cb3d797e1a58e1c549f9b2eeeadff433355d4e  tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
30c5dd86d13790552461f0f252a8bb80721f3d0bdeca8468fa8d2461c47a9f22  tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
08772767e46dd77d6e5c8a551d95ad57ab22fcbd009bde7fca7e036e74f364b5  tests/Support/AssignmentOrderOriginalPdfOracle.php
31bfd7b714b2e8cdde4a790fa1a48a48c9a4f82aac751dd0a3d16162623cda11  tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
95edf94fa25e8669a8413c08739eea205b19a881dc9c1306d915703a69f1c5c0  tests/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php
2a9cfc055ed2262e607a6d365bb7ee21b1a0f19595a02fb6f224d08ad97cd109  tests/Support/assignment_order_original_worker_runner.php
```

`git diff ac689d99a1e5e8a0bed3abec90cca4739f1301f1..66fe7f0e -- tests`
reports exactly that one modified test. The v4 specification and three OpenSpec
hashes also remain exactly those approved and recorded by v1.

## Independent RED reproduction

At `2026-09-04T02:45:14+03:00`, a detached worktree under the user's home
directory used exact preimplementation revision
`921cbafdcf394d567be3e4aa6680baeec99e0427`. Applying only the reviewed one-line
test correction produced:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication
exit 255

$ git diff --check
PASS (exit 0, no output)
```

The explicit production-seam guard fails before fixture setup or external
services, so this is the intended missing-behavior RED rather than a setup
failure.

For diagnosis only, the corrected test was also run at current exact revision
`66fe7f0e329434e63d02296f465f9679567637b2` at
`2026-09-04T02:45:25+03:00` and printed
`ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUDIT_PRECEDENCE_OK` with exit 0. This current
GREEN did not substitute for the preimplementation RED review.

`openspec validate replace-pilot-registration-with-original-upload --strict`
also passed, and `git diff --check` produced no output.

## Findings and Gate decision

No blocking finding remains. The corrected cumulative oracle is independently
sensitive to the required initial and conflict per-operation lease releases,
the unchanged inventory retains its v1 approval, and the reproduced failure is
the intended public-seam RED.

Fresh independent Gate 3 for `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` at corrected
test revision `66fe7f0e329434e63d02296f465f9679567637b2` is `APPROVED`. Gate 4 may proceed
against this exact test inventory. Any subsequent test/support/oracle change
requires a fresh Gate 2/3 cycle.
