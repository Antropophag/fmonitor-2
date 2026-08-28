# Test review: ORDER-PREPARE-001-E

- Reviewer: `Codex agent /root/order_prepare_001_e_test_review` (independent; did not author the specification or test)
- Test author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after approved examples A–D`
- Specification: [`specs/ORDER-PREPARE-001-E.md`](../../specs/ORDER-PREPARE-001-E.md)
- Public seams: `InstallationProcess::prepareOrder(...)` and `InstallationProcess::getOrderProcess(...)`
- Test: [`tests/InstallationProcess/order_prepare_001_e_test.php`](../../tests/InstallationProcess/order_prepare_001_e_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_001_e_test.php`
- Intended failure: public process projection and rejection audit behavior are not implemented
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error: Uncaught Error:
Call to undefined method FMonitor2\InstallationProcess\InstallationProcess::getOrderProcess()
in tests/InstallationProcess/order_prepare_001_e_test.php:58
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-001-E` and reproduces its exact Example A command, initial process projection, fixed server time, and complete expected post-rejection projection. The asserted event type, time, actor, reason list, normalized installer count, and engineer-presence flag all come directly from sections 2–5 of the approved specification.
- **Both public seams:** setup is performed through the in-memory environment, but the behavior under review crosses the approved process boundary: the test submits `InstallationProcess::prepareOrder(...)` and observes the result only through `InstallationProcess::getOrderProcess(...)`. It does not query the adapter, database state, private methods, or implementation collaborators for assertions.
- **Sensitivity to audit behavior:** strict comparison requires exactly one appended `assignment_order_prepare_rejected` event with the complete public shape and minimal payload. The test fails if the event is absent, duplicated, timestamped from the real clock, attributed to another actor, contains the wrong reason/count/presence values, or exposes extra fields such as participant identifiers or the command body.
- **Sensitivity to absence of partial state:** the full expected projection fixes `processState`, empty `assignmentOrders`, empty `assignments`, and the single pre-existing open task. Creating a partial order or assignment, changing state, closing/replacing/duplicating the task, or adding another visible process fact fails strict comparison. Together with the exact one-event list, this proves that the only publicly observable change for the specified fixture is the rejection event.
- **Expected-value independence:** the expected projection is a literal transcription of the approved executable specification, including its independently fixed initial facts and time. It is not calculated from production output, adapter state after execution, a database representation, or assertions from the earlier A–D tests.
- **Determinism and time isolation:** authorization, initial projection, and clock are explicitly seeded in memory. The expected ISO-8601 timestamp retains the specified `+03:00` offset. The test has no database, network, filesystem-state, legacy-application, production-data, or wall-clock dependency.
- **Adapter boundary:** `seedProcess(...)` and `setNow(...)` are fixture arrangement capabilities only. No assertion names an internal table, event row identifier, transaction API, repository call, or adapter invocation. The asserted projection is the public contract approved specifically for this slice, not an internal adapter layout.
- **Command-result scope:** this test intentionally does not repeat the exact `INSTALLER_REQUIRED` command-result assertion already independently approved and implemented by Example A. It invokes that same command case and focuses the new red-green cycle on E's previously unproved audit and unchanged-state observation. The relevant regression suite must continue to include Example A when implementing E.
- **Red cause:** independently running `php tests/InstallationProcess/order_prepare_001_e_test.php` reaches the existing production command and then exits `255` because the newly approved public observation seam `getOrderProcess(...)` is absent. Syntax validation passes. This is the intended absence of the E behavior, not malformed fixture setup or an external dependency failure.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to append the specified rejection event and expose the approved process projection without changing the reviewed expectation; the focused test and A–D regression suite must be green before Gate 5.
