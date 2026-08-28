# Code review: ORDER-PREPARE-009

- Reviewer: `Codex agent /root/order_prepare_009_code_review` (independent; did not author the specification, approved test, fixture, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-009.md`](../../specs/ORDER-PREPARE-009.md), version `0.1`, `APPROVED — inherited end-to-end invariant`
- Approved test review: [`reviews/tests/ORDER-PREPARE-009.md`](../tests/ORDER-PREPARE-009.md), verdict `APPROVED`
- Production implementation: [`app/InstallationProcess/PersistenceCommitOutcomeUnknown.php`](../../app/InstallationProcess/PersistenceCommitOutcomeUnknown.php) and [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_009_test.php`](../../tests/InstallationProcess/order_prepare_009_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php -d display_errors=1 -d error_reporting=E_ALL tests/InstallationProcess/order_prepare_009_test.php
for test_file in tests/InstallationProcess/*_test.php; do php -d display_errors=1 -d error_reporting=E_ALL "$test_file" || exit 1; done
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
git diff --check
```

Observed result: the focused test printed `PASS ORDER-PREPARE-009 unknown commit recovered`; the complete 21-test `tests/InstallationProcess` suite printed 21 PASS lines, including ORDER-PREPARE-002 success, ORDER-PREPARE-006 concurrency, ORDER-PREPARE-008 confirmed rollback, ORDER-PREPARE-009 recovery, and TERMINOLOGY-OBJECT-001. All relevant PHP syntax checks exited `0`. `git diff --check` exited `0` with no output.

## Standards

No documented-standard violation or blocking maintainability issue was found. The implementation remains inside the deep `InstallationProcess` module and adds recovery at its internal persistence seam; callers do not receive or construct the technical operation ID. `PersistenceCommitOutcomeUnknown` is a focused final signal rather than primitive exception-message matching. It is not speculative generality or a middle man: its type identity distinguishes the exact persistence condition for which reconciliation is permitted.

The production change is small and linear. Generation, atomic save, and lookup use one local `$preparationOperationId`, making the correlation invariant visible without duplicating recovery logic. The empty exception type and object-based environment are consistent with the already approved confirmed-rollback seam. No actionable Fowler-baseline smell was found. Formalizing the environment as a typed adapter interface may later improve discoverability, but is outside this slice and is not a Gate 5 blocker.

## Specification

- **One internal ID and no disclosure:** after all validations and document construction, `newPreparationOperationId()` is invoked exactly once before atomic persistence. The same local value is passed to `replaceInstallationObjectProcessAtRevision(...)` and `findPreparationResult(...)`. It never enters the public command arguments, returned result, process projection, or audit payload. The exact recursive projection assertion and generation count prove the approved example and disclosure boundary.
- **Exactly typed recovery:** only `PersistenceCommitOutcomeUnknown` enters reconciliation. Generic `RuntimeException`, `Throwable`, confirmed rollback, concurrency responses, and unrelated programming failures do not. The ORDER-PREPARE-008 unknown-outcome guard remains green and continues to prove that an untyped unknown exception is not converted into either recovery or a retryable business rejection.
- **One reconciliation and stored success:** the typed catch performs one lookup. A non-null stored result is returned unchanged, so the response is the persistence-established result of the committed operation rather than a newly synthesized second success. The focused test requires the exact approved response and exactly one reconciliation call.
- **Missing reconciliation result rethrows:** if lookup returns `null`, production rethrows the original typed exception. It does not report a false failure, create a rejection event, retry persistence, or proceed into concurrency handling. Specification section 7 excludes defining a new public outcome for this case, and the implementation preserves that boundary.
- **No duplicate work:** neither the catch nor its success branch calls the renderer or atomic replacement. The focused test proves one ID generation, one render, one save, and one reconciliation. The complete literal projection proves exactly one version, two artifacts, two preliminary assignments, one closed preparation task, and one `assignment_order_prepared` event, with no rejection event or technical operation ID.
- **Persistence fixture fidelity:** the adapter commits the complete candidate process and advances revision `7` to `8` before recording the correlated successful result and throwing the typed lost-acknowledgement signal. Lookup succeeds only for the same ID. Thus the test exercises commit-success/acknowledgement-loss rather than a pre-seeded shortcut.
- **Regression boundaries:** ordinary success still returns the existing literal success; confirmed rollback still produces `ASSIGNMENT_ORDER_PERSISTENCE_FAILED`; revision conflict still produces one concurrency rejection; renderer failure still avoids persistence. ORDER-PREPARE-002, 006, 007, and 008 all remain green.
- **Scope:** the change adds no HTTP retry key, UI behavior, production database adapter, automatic renderer/save retry, temporary-file cleanup, reconciliation-failure response, partial-result policy, second order version, or legacy projection behavior.

## Test sensitivity

The approved focused test fails if operation-ID creation is missing or repeated, if save and reconciliation use different IDs, if the typed exception is not handled, if rendering/save/reconciliation is retried, if the stored success is not returned exactly, if any duplicate version/assignment/artifact/event is created, or if the technical ID leaks anywhere in the public projection. The green ORDER-PREPARE-008 guard separately catches an over-broad exception handler, while the success and concurrency regressions protect the neighboring branches.

## Required changes

None for `ORDER-PREPARE-009` version `0.1`.

Gate 5 is approved.
