# Test review: ORDER-PREPARE-002-E

- Reviewer: `Codex agent /root/order_prepare_002_e_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-002-E.md`](../../specs/ORDER-PREPARE-002-E.md), version `0.2`, `APPROVED 2026-08-27`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)` and `::getOrderProcess(orderId)`
- Test: [`tests/InstallationProcess/order_prepare_002_e_test.php`](../../tests/InstallationProcess/order_prepare_002_e_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_002_e_test.php`
- Intended failure: after loading the snapshot with both required plan dates absent, the current command continues into the installer catalog instead of returning both ordered violations and one rejection event
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Installer catalog must not be read for this fixture. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:94
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(179): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->getInstallerSnapshot()
#1 [internal function]: FMonitor2\InstallationProcess\InstallationProcess->{closure:FMonitor2\InstallationProcess\InstallationProcess::prepareOrder():179}()
#2 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(178): array_map()
#3 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_002_e_test.php(40): FMonitor2\InstallationProcess\InstallationProcess->prepareOrder()
#4 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 94
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-002-E v0.2`, example A, and reproduces its authorized actor, selected composition, fixed time, initial `needs_order` process, populated first three required fields, whitespace-only start date, `null` finish date, and absent PТО date. It asserts the exact two approved violations and the specified single audit event.
- **Public seam:** the command crosses only the confirmed `InstallationProcess::prepareOrder(...)` seam; persisted behavior is observed only through `InstallationProcess::getOrderProcess(...)`. Environment methods arrange deterministic inputs and guard integration boundaries, without inspecting private production state or adapter storage as an assertion side channel.
- **Expected-value independence:** both Russian messages, field names, normative order, response shape, unchanged process projection, timestamp, event type, deduplicated `reasonCodes`, and ordered `missingFields` are literal expectations from the approved executable example. They are not derived from production constants, production output, or shared implementation logic.
- **Complete multi-error behavior:** one invocation proves that the command does not stop at the first missing date: exact equality requires the `plannedStartDate` violation followed by `plannedFinishDate`. The audit assertion independently requires `reasonCodes = [ORDER_REQUIRED_DATA_MISSING]` once and `missingFields = [plannedStartDate, plannedFinishDate]` in the same normative order. Existing approved B–D tests retain the single-field contracts for address, entrance, and object registration number.
- **Sensitivity:** the test fails if either date is accepted, only one violation is returned, the two violations or missing fields are reordered, the common reason code is duplicated, a code/message/field changes, additional output appears, more than one event is appended, or any process/task/work/checklist/version/assignment state changes. Explicit installer, engineer, and renderer guards catch forbidden continuation beyond required-data validation.
- **Red cause and guard validity:** bootstrap and syntax are valid; authorization and mandatory-composition checks pass; the process and order snapshots exist; the three earlier required fields are nonblank. The stack reaches the first explicitly forbidden downstream catalog call. Because the approved behavior must collect both missing dates and return before all people catalogs and rendering, this guard failure demonstrates the absent validation rather than broken fixture setup. The engineer and renderer guards protect later forbidden interactions after the first guard ceases to fire.
- **Unchanged state and audit privacy:** the full public process equality retains `needs_order`, empty versions and assignments, the open `prepare_order` task, and closed work/checklist gates. The only addition is one event containing the specified actor, server time, deduplicated reason code, ordered missing fields, installer count, and engineer-presence boolean; no order values or personal identifiers are persisted in its payload.
- **Determinism and isolation:** authorization, clock, process, order snapshot, command values, and dependency prohibitions are all fixed in memory. The test has no live database, legacy application, filesystem, network, real renderer, ambient timezone, or wall-clock dependency.
- **Scope:** this is the smallest combined example proving collection of two simultaneous missing date fields. Together with B–D it covers the required-field progression relevant to version `0.2`; it does not claim separate coverage for every `null`/empty/whitespace permutation, malformed non-string values, all five fields missing simultaneously, date format/range/order, or legacy date-selection rules excluded by the specification.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; additional uncovered cases require a separately approved slice.
