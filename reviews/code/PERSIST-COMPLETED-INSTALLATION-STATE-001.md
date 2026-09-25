# Independent Gate 5 review — PERSIST-COMPLETED-INSTALLATION-STATE-001

## Final rereview — exact completed and unaccepted-draft canaries

- Verdict: **APPROVED**
- Reviewer: `gpt-5.6-sol/low /root/final_review_completed`; independent from specification, test and implementation authorship.
- Base: `5e5c6ec3b36f3c21c7fc984edfe64d8680c1e789`.
- Reviewed HEAD: `1324e246e915e1e9986db2284cb4ca9c486609f1`.
- Exact candidate source: `fdbbd5bd4b835b4ba67698c8a6c79e7b70cdaa17f4bdf541339d4b1c2fcdda49`.
- Executable source: `cf62496a3af73cf37eac606c4c6dfaed4650b005f5e1fa46e4712baeabace4f6`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T140203Z-8f9f447974/package.json`.
- Verification plan SHA-256: `0efaa6a3be6a4341583a4358165e2398070bfaa567ea4e0bbc668847a8194e97`.

### Prior findings disposition

1. **Accepted financial baseline — RESOLVED.** `MariaDbSnapshotBuilderPersistence::previousProgress()` selects only `status='accepted'` snapshots while permitting an accepted same-day predecessor. The focused OTIZ scenario accepts the real 85% snapshot, creates a newer same-day published-but-unaccepted draft, corrupts its progress to the distinguishable value `1234`, then proves the completed 100% snapshot still uses `8500`. The oracle therefore fails if draft publication is again confused with acceptance.
2. **Construction-control completed projection — RESOLVED.** The public HTTP fixture now sets case 6103 to exact `process_state='completed'`, proves object 4514 is absent from the default queue, and proves it is present with `completed=1` and the completed marker. This directly exercises the new server predicate rather than relying only on documentary facts in a `working` case.
3. **Completion-owner cleanup — RESOLVED.** The locked case helper returns id and state together; the unused wrapper and redundant read are gone.

### Final assessment

The first valid declaration retains the existing capability and validation seam and atomically persists the declaration root, conditional `working → completed` transition and one append-only `installation_completed` event. Failure and concurrent-loss paths leave no partial transition. PTO/declaration corrections remain append-only in `completed`, while item completion, retraction and installer-attribution changes fail closed. Object/card/queue compatibility and the completed-only construction-control filter are consistent. Native OTIZ selection includes eligible `working|completed` cases without widening migration eligibility, and the accepted-baseline canary proves the final 85→100 delta without admitting an unaccepted draft.

All 13 focused records in the package are `GREEN` and bound to exact candidate source `fdbbd5bd4b835b4ba67698c8a6c79e7b70cdaa17f4bdf541339d4b1c2fcdda49`. They cover documentary HTTP/concurrency, transition/rollback, both corrections, all three prohibited checklist mutations, exact completed construction-control projection, OTIZ inputs and snapshot publication. The rebase diff against `origin/main@5e5c6ec3` contains only this scoped delivery, and `git diff --check` is clean.

No blocking specification, authorization, history, atomicity, financial, test-sensitivity or maintainability finding remains. Exact-source GitHub CI is still a separate publication prerequisite; this review does not infer CI, merge or deployment.

---

## Rereview — accepted baseline and construction-control correction

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `gpt-5.6-sol/low /root/final_review_completed`; independent from specification, test and implementation authorship.
- Base: `51f2169b966fe4841477ab7303a5600814796382`.
- Exact candidate source: `6e8a6d9392576a97106f25a936e3350a9d231a6f5e756cda8e9cd3bf64d92cb9`.
- Executable source: `a0c886a257a61a61756aa6369b887aafee46bfcb05ff8a60710d38d69529fc39`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T135604Z-4a18ac05b5/package.json`.
- Verification plan SHA-256: `c32469e9bb53ce527360c04ff756f54f4b6d9ef9f0034874f9d5f8d7a7191e3a`.

### Corrected implementation

- The financial query now requires `status='accepted'` while allowing an accepted same-day predecessor. The 85% snapshot is explicitly accepted before the 100% calculation. An unaccepted or rejected draft therefore no longer becomes the payout-progress baseline.
- The construction-control owner now admits an exact `completed` case only when `includeCompleted=true` and both documentary facts exist. Default queue behavior remains exclusionary, and checklist mutation admission remains exact-`working`.
- The completion owner now returns the locked case id and state together; the unused wrapper and redundant state query were removed.

No new production correctness, authorization, atomicity, append-only history or maintainability defect was found in these corrections.

### Remaining test-sensitivity findings

1. **HIGH — the construction-control GREEN does not exercise `process_state='completed'`.** `tests/Yii2/yii2_construction_control_active_queue_001_test.php:62-68` calls object 4514 a completed case, but `InspectionFixture::queueFixtures()` creates case 6103 by copying the original `working` row and only inserts PTO/declaration facts. The test therefore passes against the old predicate and cannot detect omission of the new `c.process_state='completed'` branch. Set this fixture case to exact `completed`, assert it is absent with the default filter and present with `completed=1`, then rerun the public HTTP test.

2. **MEDIUM — the accepted-only financial predicate has no negative draft canary.** `tests/Otiz/excel_inputs_001_test.php:49-54` now accepts the sole 85% predecessor, so the test would still pass if the production query stopped requiring `status='accepted'`. Add a newer same-day published-but-unaccepted draft with a distinguishable progress value (or an equivalent focused fixture) and prove the 100% snapshot still selects the accepted 85% baseline. This keeps the correction sensitive to the exact financial boundary that caused the first Gate 5 return.

### Evidence assessment

All 13 package records are `GREEN` and bound to exact candidate source `6e8a6d9392576a97106f25a936e3350a9d231a6f5e756cda8e9cd3bf64d92cb9`; `git diff --check` is clean. The added records include the construction-control queue and snapshot-publication regressions, but neither supplies the missing exact-state/negative-draft oracle above. Exact-source CI, merge and deployment remain `UNKNOWN`.

Required before approval: strengthen the two existing focused tests without broadening production scope, prepare a fresh exact-source package, and repeat independent Gate 5 review.

---

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `gpt-5.6-sol/low /root/final_review_completed`; independent from specification, test and implementation authorship.
- Base: `51f2169b966fe4841477ab7303a5600814796382`.
- Exact candidate source: `58604736e4abd61700532d925e31eb3980aed4494449fa93ef5b9ebb0ac3df43`.
- Executable source: `fc5b62b55039bfbaa2d4b4ed0bd535b9419e118b8a0c660dba8b2730ecf0db92`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T134712Z-65e2b7f5cd/package.json`.
- Verification plan SHA-256: `f053973333c3ef5d8d9fbd5468939c376cdbefdeb931a3630b22048f6cd038e7`.

## Spec findings

1. **HIGH — the 85→100 witness changes the financial baseline from an accepted prior snapshot to an unaccepted same-day draft.** `app/Otiz/MariaDbSnapshotBuilderPersistence.php:11` replaces the established `status='accepted' AND report_date<?` predicate with the existence of any publication receipt and `report_date<=?`. `tests/Otiz/excel_inputs_001_test.php:49,54` then deliberately leaves the 85% snapshot in `draft` and uses that same-date draft as `previous_progress_bp=8500`. A published draft can still contain blockers and be refused by `SnapshotPublication::accept`; it is not an approved payout baseline. This exceeds A5's requirement to include `completed` while leaving the existing settlement contract and formula unchanged, and can let an unaccepted or rejected calculation suppress a later delta. Retain the accepted/earlier-date rule and construct a legitimate accepted prior 85% snapshot on an earlier report date, or explicitly change the governing OTIZ contract and add the corresponding settlement/rejection coverage.

2. **HIGH — a newly persisted `completed` case disappears from the construction-control queue.** The OpenSpec `Согласованное чтение` scenario explicitly requires the object card, object queue and construction-control queue to represent a completed case consistently. `app/InspectionEvidence/MariaDbYiiChecklistRead.php:73-76` computes completion from PTO+declaration but its mandatory `$active` predicate accepts only `process_state='working'` (plus pre-opening states). Consequently the case is excluded before `includeCompleted=true` can have any effect. The existing focused test covers the object card and general object queue, but not this public construction-control queue regression. Admit `completed` in the completed branch without reopening checklist mutations, and add a focused assertion for both the default and `includeCompleted` behavior.

## Standards and maintainability

1. **LOW — dead wrapper and redundant state read in the completion owner.** `app/InstallationProcess/MariaDbInstallationCompletion.php:71-78` leaves `workingCase()` unused and performs a second state query after `caseInStates()` already locked and read the row. Returning the locked state with the case id would remove dead code and make the conditional transition easier to reason about. This is not independently blocking.

The declaration root, conditional `working → completed` update and `installation_completed` event otherwise share one transaction. Existing exact capabilities remain authoritative, document corrections remain append-only for `working|completed`, and all three checklist mutation classes fail closed after completion. No second event or reopen path was found.

## Evidence assessment

The package contains 11 focused records, all `GREEN` and bound to candidate source `58604736e4abd61700532d925e31eb3980aed4494449fa93ef5b9ebb0ac3df43`. They cover the transition, rollback, concurrent declaration, both document corrections, all three checklist mutations, documentary HTTP and the OTIZ input test. `git diff --check` is clean. The evidence does not cover the missing construction-control queue behavior, and the OTIZ GREEN depends on the financial-semantic regression described above. Exact-source GitHub CI, merge and deployment remain `UNKNOWN`; this review does not promote them.

## Required changes

- Restore the accepted, earlier-date previous-progress boundary and make the 85→100 test use a genuinely accepted prior snapshot, unless the financial contract is deliberately revised through a separate scoped decision.
- Preserve completed cases in the construction-control completed view and add sensitive focused coverage proving they remain visible only where intended while checklist writes stay forbidden.
- Prepare a fresh exact-source package and obtain a new independent Gate 5 review before CI/merge admission.

Summary: Standards axis has one non-blocking maintainability finding; Spec axis has two HIGH findings, with the financial-baseline change and missing construction-control projection both blocking approval.
