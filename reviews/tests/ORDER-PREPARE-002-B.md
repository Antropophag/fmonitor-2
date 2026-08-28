# Test review: ORDER-PREPARE-002-B

- Reviewer: `Codex agent /root/order_prepare_002_b_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-002-B.md`](../../specs/ORDER-PREPARE-002-B.md), version `0.1`, `APPROVED 2026-08-27`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)` and `::getOrderProcess(orderId)`
- Test: [`tests/InstallationProcess/order_prepare_002_b_test.php`](../../tests/InstallationProcess/order_prepare_002_b_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_002_b_test.php`
- Intended failure: after loading the blank-address order snapshot, the current command continues into the installer catalog instead of returning the required early rejection
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Installer catalog must not be read for this fixture. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:94
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(96): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->getInstallerSnapshot()
#1 [internal function]: FMonitor2\InstallationProcess\InstallationProcess->{closure:FMonitor2\InstallationProcess\InstallationProcess::prepareOrder():96}()
#2 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(95): array_map()
#3 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_002_b_test.php(42): FMonitor2\InstallationProcess\InstallationProcess->prepareOrder()
#4 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 94
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-002-B`, example A, and reproduces its authorized actor, selected composition, fixed server moment, initial process, complete order snapshot, and whitespace-only address. It asserts the exact approved violation, unchanged process facts, and sole append-only rejection event with the specified nonsensitive payload.
- **Public seam:** the behavior is invoked through the approved `InstallationProcess::prepareOrder(...)` command and observed through its return value and `InstallationProcess::getOrderProcess(...)`. Environment calls are fixture arrangement and call-boundary guards; assertions do not inspect private methods or adapter storage.
- **Sensitivity:** the test fails if whitespace is treated as a valid address; if the code/message/field or result shape differs; if the rejection occurs after reading either people catalog or calling the renderer; if a version, assignment, document, state transition, task closure, work opening, or checklist access is introduced; or if the rejection audit is missing, duplicated, mistimed, or contains the wrong approved fields.
- **Expected-value independence:** all expected values are literal facts from the approved worked example. They are not derived from production output, implementation constants, catalog results, or a shared expected-value helper.
- **Red cause and fixture validity:** the explicit catalog-read prohibition is a valid proof of the missing early rejection, not broken setup. Authorization and mandatory composition pass, the seeded order snapshot is successfully loaded, and the stack then reaches `getInstallerSnapshot()` at the current successful-path line. Under the approved ordering, a blank-address violation must be returned immediately after the order read, so that call is precisely the observable forbidden interaction. Explicitly forbidding the call also distinguishes this contract from an accidental missing catalog record. The corresponding engineer and renderer guards remain ready to catch either later forbidden interaction once the first is removed.
- **Audit and unchanged state:** the full public process assertion verifies that every initial domain/process fact remains unchanged and that exactly one rejection event is appended. Its payload contains only reason code, missing field, normalized installer count, and engineer-presence boolean; it would reject leakage of address contents, names, tab IDs, or the engineer ID.
- **Determinism and isolation:** authorization, time, initial process, order snapshot, and forbidden dependency interactions are fixed in memory. The test has no production database, filesystem, network, renderer, legacy application, live clock, or default-timezone dependency.
- **Scope:** this is one vertical rejected-command slice for example A's whitespace-only address. It intentionally does not claim the separately listed `null` and empty-string equivalence, other missing order fields, combined missing fields, authorization/composition precedence, adapter failures, or successful preparation. Those require later approved red tests if implementation or delivery is claimed.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; uncovered acceptance statements require their own red tests and independent review.
