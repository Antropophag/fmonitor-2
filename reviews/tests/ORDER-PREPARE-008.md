# Test re-review: ORDER-PREPARE-008

- Reviewer: `Codex agent /root/order_prepare_008_test_rereview` (independent; did not author the specification, test, persistence contract type, or fixture changes)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree after Gate 5 returned the slice to Gate 2` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-008.md`](../../specs/ORDER-PREPARE-008.md), version `0.1`, `APPROVED 2026-08-28`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(installationObjectId)`
- Persistence seam contract reviewed for fixture setup: `FMonitor2\InstallationProcess\PersistenceFailureWithConfirmedRollback`
- Prior test review: this record supersedes and amends the earlier Gate 3 approval after [`reviews/code/ORDER-PREPARE-008.md`](../code/ORDER-PREPARE-008.md) returned the test to Gate 2 for a missing unknown-commit regression guard.
- Red command and intended failure: `php tests/InstallationProcess/order_prepare_008_test.php` exits `255`; the dedicated confirmed-rollback exception `PersistenceFailureWithConfirmedRollback: database unavailable` escapes from the first revision-checked replacement instead of becoming the specified safe rejection and separate audit.
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught FMonitor2\InstallationProcess\PersistenceFailureWithConfirmedRollback: database unavailable in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:358
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(341): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->replaceInstallationObjectProcessAtRevision()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_008_test.php(68): FMonitor2\InstallationProcess\InstallationProcess->prepareAssignmentOrder()
#2 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 358
```

Exit code: `255`.

## Findings

- **Traceability and public seam:** the test cites executable example A and drives the approved command seam, then observes its result and process projection through the approved module interface. Failure modes and invocation counts belong to the in-memory persistence/renderer adapters; the test neither invokes private production methods nor inspects a production database.
- **Genuine RED and valid setup:** authorization, revision `7`, initial `needs_assignment_order` state, object data, employed installer, eligible engineer, fixed time, absence of an existing order, and complete renderer artifacts establish the specified path up to persistence. The reproduced failure occurs at the deliberately configured first atomic replacement, so RED is caused by the missing handling behavior, not broken setup.
- **Confirmed rollback is explicit:** `failProcessReplacement()` now throws the dedicated seam signal `PersistenceFailureWithConfirmedRollback` before changing either stored process or revision. This aligns the fixture with the normative precondition that rollback is guaranteed and no longer asks a generic `RuntimeException` to imply that guarantee.
- **Exact original expectations remain independently sourced:** fixed literals still assert the sole safe violation, exact Russian message, `field = null`, unchanged process facts, exactly one separately appended safe rejection event, actor/time and approved payload, one renderer call, and one replacement attempt. These values come from specification sections 2–5 and executable example A, not production constants, renderer output, or planned handling code.
- **State, audit, disclosure, and retry sensitivity:** exact projection equality rejects a partial version, assignments, artifact metadata/hash, success event, task closure, state transition, work opening, checklist access, legacy-facing change, duplicated audit, or extra sensitive audit fields. Exact boundary-call counts reject automatic renderer or persistence retries. The response literal rejects leakage of the database message or other infrastructure detail.
- **Unknown commit guard closes the Gate 5 gap:** after the original scenario, the fixture configures a distinct generic `RuntimeException('commit outcome unknown')`. The test requires that exact exception to escape rather than be converted into `ASSIGNMENT_ORDER_PERSISTENCE_FAILED`; therefore catching all `RuntimeException`/`Throwable` as confirmed rollback will fail the test once the first scenario is implemented.
- **No invented user outcome for excluded behavior:** the guard does not prescribe a response, audit event, recovery workflow, or retry advice for unknown commit. It observes only the specification section 7 boundary—this excluded outcome must not be converted into the retryable rejection—and uses the fixture exception message solely to prove that the same unknown-outcome signal propagated.
- **Expected-value independence and sensitivity:** the safe response and public projection are literal specification values. The unknown-outcome assertion is a test-adapter sentinel, not a new product-facing expectation. Removing confirmed-rollback handling produces the captured RED; broadening the future handler to a generic exception category makes the new sentinel assertion fail.
- **Determinism and isolation:** all state, snapshots, revision, authorization, time, renderer bytes, and both persistence outcomes are fixed in memory. The test uses no production database, filesystem, network, legacy system, wall clock, locale, or ambient timezone.
- **Slice scope:** the added guard protects an explicit negative boundary of ORDER-PREPARE-008 without specifying the excluded unknown-commit behavior. Audit-append failure, infrastructure retry policy, renderer temporary-file cleanup, production adapters, UI/HTTP, and persistence errors in other commands remain outside this test.

## Required changes

None.

Gate 3 is freshly approved for the amended Gate 2 test. The prior approval is superseded. Gate 4 may handle only `PersistenceFailureWithConfirmedRollback` as the specified retryable persistence rejection; the unknown-outcome guard and all original approved expectations must remain unchanged.
