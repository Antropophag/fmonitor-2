# Code re-review: ORDER-PREPARE-008

- Reviewer: `Codex agent /root/order_prepare_008_code_rereview` (independent; did not author the specification, approved test, fixture changes, production implementation, or prior code review)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-008.md`](../../specs/ORDER-PREPARE-008.md), version `0.1`, `APPROVED 2026-08-28`
- Approved test re-review: [`reviews/tests/ORDER-PREPARE-008.md`](../tests/ORDER-PREPARE-008.md), verdict `APPROVED`
- Production implementation: [`app/InstallationProcess/PersistenceFailureWithConfirmedRollback.php`](../../app/InstallationProcess/PersistenceFailureWithConfirmedRollback.php) and [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_008_test.php`](../../tests/InstallationProcess/order_prepare_008_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Prior Gate 5 verdict: `CHANGES_REQUESTED` by `Codex agent /root/order_prepare_008_code_review`; the implementation caught every `\Throwable` as a confirmed rollback and the test lacked an unknown-commit regression guard.
- Repeat-review verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2` after the corrective changes:

```text
php -d display_errors=1 -d error_reporting=E_ALL tests/InstallationProcess/order_prepare_008_test.php
for test_file in tests/InstallationProcess/*_test.php; do php -d display_errors=1 -d error_reporting=E_ALL "$test_file" || exit 1; done
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
git diff --check
```

Observed result: the focused test printed `PASS ORDER-PREPARE-008 persistence failure`; the complete `tests/InstallationProcess` suite printed twenty PASS lines, including ORDER-PREPARE-002 success, ORDER-PREPARE-006 concurrency, and ORDER-PREPARE-008. All relevant PHP syntax checks exited `0`. `git diff --check` exited `0` with no output.

## Standards

No documented-standard violation or blocking maintainability issue was found. The corrective change is small and confined to the persistence boundary. `PersistenceFailureWithConfirmedRollback` is a dedicated final signal whose type identity and name carry the guarantee required by the caller; `InstallationProcess` catches exactly that type. Generic `RuntimeException`, `LogicException`, `Error`, and other `Throwable` values therefore remain outside the retryable business-rejection branch.

The empty exception subclass is not speculative generality or a middle man: distinguishing confirmed rollback from unknown commit outcome is required by the approved specification and by the prior review. No actionable Fowler-baseline smell was found. A future `@throws PersistenceFailureWithConfirmedRollback` annotation on a formal persistence adapter contract could improve discoverability, but the current object-based in-memory seam has no such declared interface and this is not a correctness or Gate 5 blocker.

## Specification

- **Confirmed rollback is explicit:** `replaceInstallationObjectProcessAtRevision(...)` emits `PersistenceFailureWithConfirmedRollback` from the confirmed-failure fixture before changing the stored process or revision. The production catch handles only that dedicated signal, satisfying specification sections 2 and 5 rather than inferring rollback from an arbitrary exception.
- **Unknown commit remains distinct:** `failProcessReplacementWithUnknownOutcome()` emits a generic `RuntimeException('commit outcome unknown')`. It is not caught by the exact production handler and escapes the public command. The approved guard proves that it is not converted into `ASSIGNMENT_ORDER_PERSISTENCE_FAILED`, as required by specification section 7. Unrelated programming and contract failures likewise cannot enter this branch.
- **Exact response and privacy-safe audit:** the handled branch returns only the exact `accepted = false` violation, approved Russian message, and `field = null`. It separately appends exactly one `assignment_order_prepare_rejected` event with only the fixed reason code, normalized installer count, and engineer-presence boolean; it does not expose exception text, SQL, paths, configuration, people, object data, or artifact contents/hashes.
- **No partial process result:** the exact observed projection remains `needs_assignment_order`, keeps the original task open, and contains no order version, assignments, artifact metadata, success event, work opening, checklist availability, or legacy-facing change. The fixture throws before mutation, so the dedicated signal genuinely models the specification's guaranteed rollback precondition.
- **No automatic retry:** the focused test proves exactly one renderer invocation and exactly one atomic replacement attempt. The failure handler does not loop either operation.
- **Concurrency and success regressions:** a normal `replaced = false` result remains outside the exception handler and continues to the existing `CONCURRENT_MODIFICATION` path. ORDER-PREPARE-006 and ORDER-PREPARE-002 remain green, as does the full suite.
- **Scope:** no recovery response for unknown commit, audit-append failure policy, persistence retry mechanism, renderer cleanup, production adapter, UI, or HTTP behavior was added.

## Test sensitivity

The renewed approved test fails if the confirmed-rollback signal is left unhandled, if the catch is broadened to generic `RuntimeException`/`Throwable`, if response or audit literals change, if successful or sensitive facts leak into the projection, or if renderer/persistence is retried. Together with the green success and concurrency regressions, it covers the plausible regressions identified by the prior Gate 5 review.

## Required changes

None for `ORDER-PREPARE-008` version `0.1`.

Gate 5 is approved on re-review. This approval supersedes the prior `CHANGES_REQUESTED` verdict because the persistence seam now represents confirmed rollback explicitly, the production handler is exactly typed, and the approved test guards the excluded unknown-commit boundary.
