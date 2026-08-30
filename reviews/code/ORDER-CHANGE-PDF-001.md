# Code review: ORDER-CHANGE-PDF-001

- Reviewer: separately tasked Codex agent `/root/code_review` (independent; did not author the specification, tests, or implementation)
- Implementation author: Codex agent `/root`, working session `2026-08-30`
- Reviewed commit: final ObjectCard policy follow-up at HEAD `513363b02570ee25a5d5c699630803a544f7cced`; production hashes `ba8a2d73ce3c96c0da7eb947727c823592118d74c7215505ea34b3bba64fc7a4` (`InstallationProcess.php`), `566dde97b9a0f2badc2790176483ce37e897b1652014df18ffe234bc1dd8f2d5` (`ProductionPdfAssignmentOrderRenderer.php`), and `b3dfa8380539b167e2bf805a560042e71d5b831e591967994dd97ad92b99fce5` (`PilotHttp.php`)
- Specification: [`specs/ORDER-CHANGE-PDF-001.md`](../../specs/ORDER-CHANGE-PDF-001.md), amended SHA-256 `0ad2d27597ae45384a8132b4dbb42cd032ec4f6fe83e40ad4de0e546aa763554`
- Approved test review: [`reviews/tests/ORDER-CHANGE-PDF-001.md`](../tests/ORDER-CHANGE-PDF-001.md), reader-policy and amendment verdicts `APPROVED`, SHA-256 `ca6939a3bd6e171231857e5058689f755708c8e40137050f232bb52bd7b098bc`
- Verification commands: `php tests/InstallationProcess/object_card_working_change_policy_test.php` — PASS; `php tests/InstallationProcess/order_change_pdf_001_test.php` — PASS; `php tests/InstallationProcess/production_pdf_assignment_order_renderer_test.php` — PASS; prior prepare, registration, and opening regression set — PASS; relevant PHP syntax checks — PASS; `git diff --check` — PASS; live MariaDB reads of all five opened cards return `В работе`, including prepared-change objects 966/1609 and registered-change object 1103; rebuilt stand healthy
- Verdict: `APPROVED`

## Findings

### Urgent ObjectCard consistency follow-up (2026-08-30)

- **Blocking invariant gap — the reader accepts an initial prepared order as the basis of an opened `working` case.** The amended specification permits `working + prepared` only while an *изменяющее распоряжение* is being registered. The new match arm checks only that opening facts are complete and the highest order status is `prepared` or `registered`; it does not require a prepared order to be a later version with a previous registered version. Consequently, corrupt state such as an opened `working` case whose sole order is version 1 `prepared` is now rendered as healthy `В работе` instead of failing closed. Restrict the prepared exception to a genuine change order (at minimum version greater than 1 with its preceding registered basis present and unambiguous).
- **Blocking test-sensitivity gap — no approved test reaches the changed reader branch.** `order_change_pdf_001_test.php` exercises the in-memory `InstallationProcess` projection and passes whether or not the `MariaDbObjectCardReader` match arm is changed. The existing ObjectCard fixture covers `needs_assignment_change + prepared`, not the amended `working + prepared` state. Add a DB-backed reader/card case for `working`, complete opening facts, registered version 1, and prepared version 2; require successful `В работе` projection. Add the fail-closed counterpart for a sole version 1 prepared order (or equivalent missing registered predecessor), and obtain independent test approval before re-review.

The source change does resolve the reported happy-path 503 in principle: a valid opened case with a latest prepared change order can pass the `working` match arm, while `working + registered` behavior remains accepted. The approval is withheld because the condition is broader than that valid state and the changed integration seam has no regression-sensitive test.

### Re-review after condition narrowing (2026-08-30)

- The original `prepared v1` invariant gap is partially resolved: `workingOrderValid` now requires a prepared highest version to be greater than 1 and requires at least one lower registered order. A sole prepared version 1 therefore fails closed.
- Live MariaDB reads of the five current working objects provide useful operational evidence: prepared-change objects 966 and 1609, registered-change object 1103, and the other working objects all project `В работе` after rebuild.
- **The test-sensitivity blocker remains.** No verifier or approved test was added or changed. The live sample contains valid operational rows only and does not exercise the required negative `working + prepared v1` case or a missing-predecessor/corrupt history. The approved in-memory domain test still cannot fail when this reader condition is removed or broadened.
- **The chain check remains weaker than a genuine predecessor check.** The implementation accepts any lower registered version. For example, highest prepared version 3 plus registered version 1 and a non-registered/missing version 2 satisfies the predicate. Use the stored `previous_assignment_order_id` relationship or require the exact immediately preceding version to be registered so malformed version history remains fail-closed.

At that intermediate re-review the verdict remained `CHANGES_REQUESTED`; the later final policy revision below resolves those findings.

### Final ObjectCard policy re-review (2026-08-30)

- Both previous blockers are resolved. `ObjectCardProcessStatePolicy` accepts a prepared order for `working` only when it is a later `kind=change` version and its positive `previous_assignment_order_id` resolves to exactly one registered lower-version row in the order history loaded for the same installation case.
- A prepared initial version is rejected. Merely having some unrelated earlier registered order is also rejected because its ID does not match the change order's recorded predecessor.
- The policy is used directly by `MariaDbObjectCardReader`; the query now loads `kind` and `previous_assignment_order_id`, so the tested decision and the production reader decision are the same code path rather than duplicated conditions.
- The independently approved focused test is regression-sensitive to the legitimate prepared-change state and both fail-closed near misses. Its fixed fixture values do not derive expected outcomes from production output.
- Existing registered latest-order behavior remains accepted without weakening its established card path. Live reader verification confirms all five opened production-like cards project `В работе`, and the rebuilt stand is healthy.

Final follow-up verdict: `APPROVED`.

### Standards

- The process compares against the only admissible current order: the command already rejects a latest order unless it is `registered`, so the newly derived moved rows come from the previous registered version required by the specification.
- The active snapshot and the presentation-only document rows remain separate. Only selected current workforce snapshots are persisted in the new version and used to derive `organizationType`; removed historical snapshots are appended only to `documentInstallers`. This preserves append-only document history without reactivating a removed installer or contaminating subsequent process calculations.
- Rendering remains behind the existing `DocumentRenderer` environment boundary. Input validation restricts the new status vocabulary, while the fallback to `installers` preserves existing renderer callers and initial-order behavior.
- The implementation adds no authorization bypass, direct historical edit, repeated state switch, speculative abstraction, or integration-boundary violation. The comparison is local to the command that owns version creation.
- The state branch is explicit at the existing version boundary: a first order still enters `assignment_order_prepared`, while a later order based on an already opened registered version retains `working`. Registration continues to preserve the prepared process state rather than inventing a second transition.
- The unrelated pre-existing `rapid-pilot/` working-tree changes were inspected for interaction but are not required by this specification and are not covered by this Gate 5 approval. The approved slice does not modify rapid-pilot behavior directly; rapid-pilot consumes the shared process/renderer path.

### Spec

- Every selected installer is sent to the PDF renderer with `Работа`; each installer present in the previous registered version but absent from the complete selected ID set is additionally sent with `Перемещён`.
- Removed installers are absent from the persisted new-order `installers` collection and from the new `organizationType` calculation. Existing persistence and commit paths continue to store only the selected active snapshots.
- When the composition is unchanged, no moved row is derived. The real renderer emits the selected rows and no `Перемещён` marker.
- The renderer uses `documentInstallers` for the appendix rows and correlates each row's person and status. The approved tests observe both the public process seam and decoded real PDF output, including row order/correlation and the negative no-removal branch.
- Preparing the later version only appends its own artifact metadata. The earlier registered version and its artifact identity remain unchanged.
- Preparation of version 2 now preserves `processState = working`, `installationOpened = true`, and `checklistAvailable = true`. Registration of that exact prepared version changes only its document registration facts and preserves the same state/availability tuple.
- Initial-order behavior is unchanged: the full `order_prepare_*` regression set confirms that version 1 still enters `assignment_order_prepared`; the registration and opening regressions confirm its established `assignment_order_prepared → working` lifecycle remains intact.

## Required changes

None.

Gate 5 is approved for the final domain, PDF, state-preservation, and ObjectCard policy slice identified above. Approval does not extend to unrelated working-tree changes.
