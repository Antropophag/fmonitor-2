# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 review, initial RED v1

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_original_gate3`
- Owner-approved Gate 1 commit: `565be908a101ec26aff52c219df642083e610f6a`
- Reviewed RED commit: `249e83901b039c1c85a6cff7318c21080b925236`
- Scope: OpenSpec task 2.1 only; task 2.2 remains open
- Public seam: `AssignmentOrderOriginalVerificationFactory::create(...)->submitAssignmentOrderOriginal(...)`
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author the executable specification, OpenSpec artifacts,
owner approval, test, support fixture, production code, or RED evidence. This
append-only record is the only review edit.

## Blocking finding G3-1 — the no-downstream-mutation assertion is inert

The required task-2.1 outcome includes unchanged composition and opening state,
and the executable verifier contract requires snapshots of order/composition,
case/opening, tasks, events, audit, and unrelated decoy facts. The reviewed test
does not observe any such state through an independent evidence reader.

Instead, it assigns the same local string to two local variables before the
application is called:

```php
$beforeProcess = '{...}';
$processEvidence = $beforeProcess;
```

and later compares those two variables. Neither variable is passed to the
application or backed by a dependency which the application can mutate. The
assertion therefore passes even if a production implementation changes order
composition, case/opening state, tasks, checklist availability, or unrelated
facts. The repository fake exposes only accepted original evidence and attempt
commits; it provides no independently seeded/read canonical process snapshot.

This is a sensitivity failure at the exact acceptance statement selected for
task 2.1, not work that can be deferred to the broader task-2.2 matrix. Gate 2
must add a genuinely application-reachable, independently seeded evidence
surface (or another public-seam-observable equivalent), capture it before and
after the command, and prove the complete required downstream snapshot remains
byte-identical. The correction must also demonstrate sensitivity: a bounded
mutating test double or equivalent perturbation must make that assertion fail.

## Checks that passed

- **Authority and traceability:** the approved executable/OpenSpec hashes in
  the RED record reproduce exactly. Commit `565be908...` is the owner approval,
  and commit `249e839...` adds only the task-2.1 test/support/evidence files.
- **Current RED cause:** both the direct command and the RED harness stop at the
  explicit missing `AssignmentOrderOriginalVerificationFactory`; no fixture,
  Docker, database, parser, filesystem, or configuration error precedes it.
- **Anti-self-attestation:** the support fixture cannot manufacture the command
  `Result`; only the absent production application can return it. Exact result
  fields and typed accepted-commit evidence are asserted rather than copied
  from a production projection. This does not cure G3-1 because the downstream
  snapshot is wholly disconnected from both production and the fixture.
- **Example A positive oracle:** request/case/order/actor, exact upload
  capability call, literal PDF bytes, independent size/digest, immutable
  composition identity/hash, document date, distinct upload time,
  application-owned root/revision identities, revision number, protected
  evidence JSON, one accepted commit, exact event type, and absence of rejected
  attempts are asserted with specification literals.
- **Owned-resource assertions:** the accepted path requires exactly one stream
  close, stage close, content-lease release, and delivery, with no stage abort.
  The fixture uses no DB, network, shared/production storage, temporary path,
  child process, production document, or secret, so there is no external test
  resource left to clean up in this task-2.1 case.
- **Scope:** the absent authorization-negative, real parser, full storage-event,
  replay/correction/concurrency/fault, maintenance, and five-FD cases are
  accurately retained for task 2.2 and are not grounds for this verdict.

## Reproduced evidence

```text
$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
exit 255

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_upload_001_test.php
exit 0
```

No production implementation is authorized from this review. Correct G3-1 in
Gate 2 and obtain a fresh independent Gate 3 review before Gate 4.

## Reviewed SHA-256 inputs

```text
97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
b81f11b5aabd69645404b624d5301cd65a209b870d06ef587dcb34eebbcfc9b2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
08a3f37cc6d03e1057f5ceb0347ff53c337a5369bef455b0e23961229c78cbf7  docs/operations/assignment-order-original-upload-gate2-constructibility-gap-2026-09-02.md
5b459540a6bae9737ce52b4c78f501d33137cfa1836d3d5e6fc0b8e0c20d444d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
cc903c354b2388d0d4ce3d8fa37b1a0f4366943844b99fa5d2ca34835c5e3c20  tests/InstallationProcess/assignment_order_original_upload_001_test.php
9abd94baada20fb6983ec988ba8eb88585b892b27ec70d4fe64985051aa04e1e  tests/Support/AssignmentOrderOriginalInitialFixture.php
cd774b520c57b243838837779154327b6f1697f52875bc7a6a3d8f9f60803ec1  docs/operations/assignment-order-original-upload-initial-red-2026-09-04.md
```

The review record path is metadata because a self-hash is circular. Any change
to reviewed test/support bytes requires a fresh independent review.
