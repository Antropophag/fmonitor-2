# Test review: ORDER-PREPARE-010

- Initial reviewer: `Codex agent /root/order_prepare_010_test_review` (independent; did not author the specification, test, fixture delta, or production implementation)
- Rereviewer: `Codex agent /root/order_prepare_010_test_rereview` (independent; did not author the specification, test, fixture delta, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-010.md`](../../specs/ORDER-PREPARE-010.md), version `0.1`, `APPROVED`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(installationObjectId)`
- Fixture seam reviewed: fixed operation-ID generation, typed unknown commit outcome without a stored result, and reconciliation lookup in `InMemoryInstallationProcessEnvironment`
- Red command: `php tests/InstallationProcess/order_prepare_010_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## Rereview after requested changes

The rereview confirms that both requested Gate 2 changes are present and sensitive:

- atomic persistence records the `preparationOperationId` received at its adapter boundary, and the test asserts the exact independently specified literal `prep-op-a71d`;
- reconciliation separately records its lookup key, and the test asserts that it is also exactly `prep-op-a71d`;
- because both assertions use the fixed specification literal, passing a different constant to either call, swapping the generated ID before either boundary, or using unequal IDs would fail the test;
- the original exact response, unchanged full public projection, and once-only assertions remain intact.

The rereviewer reran `php tests/InstallationProcess/order_prepare_010_test.php`. The test remains RED for the intended missing behavior:

```text
PHP Fatal error:  Uncaught FMonitor2\InstallationProcess\PersistenceCommitOutcomeUnknown: commit outcome unknown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:459
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(343): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->replaceInstallationObjectProcessAtRevision()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_010_test.php(44): FMonitor2\InstallationProcess\InstallationProcess->prepareAssignmentOrder()
#2 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 459
```

Exit code: `255`. The typed unknown outcome reaches production, reconciliation returns `not found`, and production rethrows instead of returning `ASSIGNMENT_ORDER_RESULT_UNKNOWN`. Execution does not reach the new ID assertions because the missing production behavior aborts first; inspection confirms their fixture accessors independently expose the arguments actually received by persistence and reconciliation. The strengthened test will exercise those assertions once Gate 4 supplies the missing return path.

No further findings. Gate 3 is `APPROVED`; Gate 4 may proceed against the reviewed test without changing its expectations.

## Initial review history (`CHANGES_REQUESTED`)

## Captured red result

```text
PHP Fatal error:  Uncaught FMonitor2\InstallationProcess\PersistenceCommitOutcomeUnknown: commit outcome unknown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:443
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(343): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->replaceInstallationObjectProcessAtRevision()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_010_test.php(44): FMonitor2\InstallationProcess\InstallationProcess->prepareAssignmentOrder()
#2 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 443
```

Exit code: `255`.

The independent review reproduced the intended RED. Production creates an operation ID, renders once, calls atomic persistence once, catches the typed unknown outcome, reconciles once, receives `null`, and then rethrows the infrastructure exception instead of returning the specified stable indeterminate result. The fixture does not mutate the public process before throwing, so the failure is not caused by pre-applied behavior or broken setup.

## Findings

- **Traceability and public seam:** the test cites executable example A, invokes the approved public command, and observes the result and process through the two approved public seams. Fixture controls and counters are appropriate adapter-boundary instrumentation for a deliberately induced persistence outcome.
- **Exact user-visible contract:** the expected response is a complete literal copied from the approved specification, including `accepted = false`, the exact new code and Russian message, and `field = null`. Strict equality excludes extra diagnostics and therefore catches disclosure of the operation ID, exception text, or reconciliation details in the response.
- **Unchanged process and no rejection audit:** strict equality between the complete public projection and the independently declared initial literal excludes an added order, assignment, task transition, process event, or rejection audit. The fixture's unknown-without-result branch performs no state mutation before throwing.
- **Once-only behavior:** explicit counters require exactly one operation-ID generation, renderer call, atomic replacement call, and reconciliation call. A retry of any of these operations would fail the reviewed assertions.
- **Determinism and isolation:** authorization, initial process and revision, all snapshots, clock, rendered bytes, operation ID, and persistence outcome are fixed in memory. The test has no production database, network, filesystem, legacy application, or wall-clock dependency.
- **Expected-value independence:** the response and initial/final projection expectations are literals derived from the approved specification example, not computed from production output. The call-count expectations are also fixed by the normative once-only sequence.
- **Blocking sensitivity gap — same operation ID:** the specification requires atomic persistence and reconciliation to use the same generated `prep-op-a71d`. In this fixture, `loseAcknowledgementWithoutResult()` makes persistence accept any non-null or null operation ID and `findPreparationResult()` returns `null` for every lookup key. The test checks only generation and call counts; it never observes the ID passed to persistence or reconciliation. Consequently, an implementation that generates `prep-op-a71d` once but passes a different constant to persistence, reconciliation, or both can satisfy every assertion after implementing the new return value. This leaves a normative part of example A unproved.

## Required changes

1. Add adapter-boundary instrumentation that records (or validates) the operation ID received by atomic persistence and the operation ID received by reconciliation, then assert both are exactly the independently fixed literal `prep-op-a71d` (and therefore equal). Keep the test at the existing public command seam; fixture observation is sufficient and no production internals should be exposed.
2. Rerun `php tests/InstallationProcess/order_prepare_010_test.php` and retain a RED caused by the missing `ASSIGNMENT_ORDER_RESULT_UNKNOWN` behavior, not by the new fixture assertion or setup.

At the initial review, Gate 4 could not proceed until the strengthened test received a fresh independent Gate 3 approval. The rereview above records that approval.
