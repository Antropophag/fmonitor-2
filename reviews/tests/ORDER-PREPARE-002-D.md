# Test review: ORDER-PREPARE-002-D

- Reviewer: `Codex agent /root/order_prepare_002_d_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-002-D.md`](../../specs/ORDER-PREPARE-002-D.md), version `0.1`, `APPROVED 2026-08-27`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)` and `::getOrderProcess(orderId)`
- Test: [`tests/InstallationProcess/order_prepare_002_d_test.php`](../../tests/InstallationProcess/order_prepare_002_d_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_002_d_test.php`
- Intended failure: after accepting the populated address and entrance and loading the whitespace-only object registration number, the current command continues into the installer catalog instead of returning the required early rejection
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Installer catalog must not be read for this fixture. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:94
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(150): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->getInstallerSnapshot()
#1 [internal function]: FMonitor2\InstallationProcess\InstallationProcess->{closure:FMonitor2\InstallationProcess\InstallationProcess::prepareOrder():150}()
#2 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(149): array_map()
#3 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_002_d_test.php(40): FMonitor2\InstallationProcess\InstallationProcess->prepareOrder()
#4 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 94
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-002-D`, example A, and reproduces its authorized actor, mandatory selected composition, fixed server moment, complete initial process, populated address and entrance, whitespace-only `objectRegistrationNumber`, populated plan dates, and absent PТО date. It asserts the exact approved violation and the specified rejection audit outcome.
- **Public seam:** behavior is invoked only through `InstallationProcess::prepareOrder(...)` and observed through its result and `InstallationProcess::getOrderProcess(...)`. Fixture methods arrange deterministic boundary inputs and forbid later dependency interactions; no private production method or adapter storage is inspected.
- **Expected-value independence:** response, Russian message, field name, process projection, timestamp, event type, and payload are independently written literals from the approved specification. Expected values are not copied from production constants, computed from production output, renderer data, or a shared expectation helper.
- **Sensitivity:** exact equality detects acceptance of a whitespace-only registration number, a wrong code/message/field, altered response shape, missing or extra events, audit leakage, task closure, process transition, assignment/version/artifact creation, work opening, or checklist availability. Explicit installer, engineer, and renderer guards detect any forbidden continuation beyond required-order-data checks.
- **Red cause:** authorization, mandatory composition, process and order snapshots are present. The stack proves that the command passes the already implemented address and entrance checks and reaches the first forbidden downstream catalog call. The missing behavior is therefore the specified `objectRegistrationNumber` rejection; this is an intended RED rather than broken setup.
- **Ordering after address and entrance:** nonblank literal values for both earlier fields force their approved guards to pass before this case. The focused `ORDER-PREPARE-002-B` and `ORDER-PREPARE-002-C` tests separately protect the preceding failures. Simultaneous missing-field precedence remains outside this slice, as specified.
- **Exact result, audit, and unchanged state:** the result contains the sole required violation. The full process assertion keeps `needs_order`, empty order versions and assignments, the open `prepare_order` task, and closed work/checklist gates, while appending exactly one rejection event with the fixed actor, time, reason, missing field, installer count, and engineer-presence flag. Order identity remains observable at the containing process root (`orderId = 4512`), consistent with the established public projection; the nested event does not duplicate personal or order-snapshot values.
- **Determinism and isolation:** clock, authorization, aggregate, order snapshot, command, and forbidden interactions are all fixed in memory. There is no production database, legacy service, filesystem, network, renderer, ambient timezone, or wall-clock dependency.
- **Scope:** the test is one vertical example for a whitespace-only registration number. It does not overclaim separate proof for `null`, empty string, malformed non-string input, simultaneous missing fields, plan-date validation, 1С ДО registration, or other state/people/integration failures.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; uncovered acceptance statements require separately approved slices.
