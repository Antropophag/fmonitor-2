# Test review: ORDER-PREPARE-002-C

- Reviewer: `Codex agent /root/order_prepare_002_c_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-002-C.md`](../../specs/ORDER-PREPARE-002-C.md), version `0.1`, `APPROVED 2026-08-27`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)` and `::getOrderProcess(orderId)`
- Test: [`tests/InstallationProcess/order_prepare_002_c_test.php`](../../tests/InstallationProcess/order_prepare_002_c_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_002_c_test.php`
- Intended failure: after accepting the populated address and loading the blank-entrance order snapshot, the current command continues into the installer catalog instead of returning the required early rejection
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Installer catalog must not be read for this fixture. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:94
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(123): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->getInstallerSnapshot()
#1 [internal function]: FMonitor2\InstallationProcess\InstallationProcess->{closure:FMonitor2\InstallationProcess\InstallationProcess::prepareOrder():123}()
#2 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(122): array_map()
#3 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_002_c_test.php(40): FMonitor2\InstallationProcess\InstallationProcess->prepareOrder()
#4 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 94
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-002-C`, example A, and reproduces its authorized actor, selected mandatory composition, fixed server moment, initial process, populated address and other required order data, and whitespace-only `entrance`. It asserts the exact approved violation, unchanged process facts, and sole append-only rejection event with the specified minimal payload.
- **Public seam:** the command is invoked only through the approved `InstallationProcess::prepareOrder(...)` seam and its persisted effect is observed through `InstallationProcess::getOrderProcess(...)`. Environment calls arrange deterministic inputs and guard dependency boundaries; the assertions do not call private methods or inspect adapter storage.
- **Expected-value independence:** the expected response, process state, timestamp, audit type, and payload are literal values from the approved worked example. None is derived from production constants, production output, catalog data, renderer output, or a shared expected-value helper.
- **Sensitivity:** the test fails if whitespace is accepted as an entrance; if the violation code, Russian message, field, or response shape changes; if either people catalog or the renderer is called; if any version, assignment, artifact, state transition, task closure, work opening, or checklist availability appears; or if the audit is missing, duplicated, mistimed, malformed, or contains fields beyond the approved payload.
- **Red cause and fixture validity:** the catalog guard demonstrates the intended missing behavior rather than broken setup. Authorization and mandatory-composition checks pass, the order snapshot exists and has a populated address, and the stack shows execution reaches `getInstallerSnapshot()` on the current successful path. The approved behavior must reject the whitespace-only entrance after the address check and before this call. An explicit guard also avoids an accidental undefined-index failure masquerading as the desired red state; engineer and renderer guards cover later forbidden interactions once the first is removed.
- **Ordering after address:** the fixture's nonblank address forces the already approved address validation to pass before the entrance case is reached. This proves the new entrance rejection belongs after address on its own example. The earlier `ORDER-PREPARE-002-B` test protects address rejection. The simultaneous-missing precedence case is explicitly outside this slice and is therefore not independently claimed by this single test.
- **Audit and unchanged state:** the complete public process assertion preserves `needs_order`, empty versions and assignments, the open `prepare_order` task, closed work/checklist flags, and adds exactly one event. Exact array equality rejects leakage of the address, entrance value, names, tab ID, or engineer ID.
- **Determinism and isolation:** authorization, clock, starting process, order snapshot, and forbidden interactions are fixed in memory. The test has no live database, legacy application, filesystem, network, renderer, timezone default, or wall-clock dependency.
- **Scope:** this is the smallest red test for example A's whitespace-only entrance. It does not claim direct coverage of `null` and empty-string equivalence, non-string malformed values, simultaneous missing address and entrance, or later required fields; those need separately approved tests before broader delivery is claimed.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; uncovered acceptance statements require their own red tests and independent review.
