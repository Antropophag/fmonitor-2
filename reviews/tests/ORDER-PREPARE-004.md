# Test review: ORDER-PREPARE-004

- Reviewer: `Codex agent /root/order_prepare_004_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-004.md`](../../specs/ORDER-PREPARE-004.md), version `0.1`, `APPROVED 2026-08-27`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)` and `::getOrderProcess(orderId)`
- Test: [`tests/InstallationProcess/order_prepare_004_test.php`](../../tests/InstallationProcess/order_prepare_004_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_004_test.php`
- Intended failure: the current command accepts an ineligible engineer and reaches the explicitly forbidden renderer instead of returning the approved rejection
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Renderer must not be called for this fixture. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:188
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(187): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->renderAssignmentOrder()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_004_test.php(57): FMonitor2\InstallationProcess\InstallationProcess->prepareOrder()
#2 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_004_test.php(109): assertEngineerRejected()
#3 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 188
```

Exit code: `255`.

Positive regression command `php tests/InstallationProcess/order_prepare_002_test.php` remains green:

```text
PASS ORDER-PREPARE-002 example A
```

## Findings

- **Traceability and slice shape:** the test cites `ORDER-PREPARE-004`, examples A-C, and exercises the three approved ways the same conjunctive eligibility invariant can fail: inactive with the exact role, active with the wrong role, and absence from the catalog. One test file and a shared scenario helper are appropriate here: they express one boolean business rule through three independently seeded command executions, rather than splitting its two predicates and nullable lookup into artificial product slices.
- **Public seam:** each scenario invokes behavior only through `InstallationProcess::prepareOrder(...)` and observes the exact command result plus `InstallationProcess::getOrderProcess(...)`. Environment methods are fixture arrangement and dependency guards; the test does not call private production methods or inspect adapter storage as an assertion side channel.
- **Genuine RED:** authorization, required composition, required order data, and installer eligibility all pass. The first approved ineligible-engineer scenario then reaches the renderer guard. The failure is therefore caused by the missing engineer eligibility decision at the correct point in the command, not by incomplete setup. Because the scenarios execute sequentially, later examples become observable as implementation advances through the same rule; exact assertions prevent accepting only one of the three cases.
- **Rule sensitivity:** the first two snapshots isolate the two required predicates: changing only `active` to false must reject an otherwise correct-role user, while changing only `role` to `fkr` must reject an otherwise active user. `markEngineerMissing(73)` requires nullable lookup semantics and prevents a missing aggregate from becoming an infrastructure exception. The existing successful `ORDER-PREPARE-002` example uses `active = true` with exact role `construction_control_engineer`; its green result guards the positive side against an implementation that rejects every engineer.
- **Exact rejection contract:** exact result equality fixes `accepted = false`, the single code, Russian message, and `controlEngineerUserId` field for all three technical causes, thereby enforcing the specification's non-disclosing unified response.
- **State, audit, and privacy:** exact public projection equality proves no order version, assignment, artifact, process transition, task closure, or gate opening occurs. It permits only one append-only rejection event per fresh scenario and fixes its time, actor, reason code, installer count, provided flag, and `controlEngineerEligible = false`. Exact equality excludes engineer ID, name, position, role, activity, installer identity or employment facts, and order snapshots from the audit.
- **Downstream guard:** `forbidRendering()` makes every rejection scenario fail if document generation is attempted. The unchanged projection additionally catches partial persistence before or after such an attempt.
- **Expected-value independence:** result, event, and unchanged-state expectations are literal values from the approved specification examples and prior public process contract. They are not imported from production constants, copied from production output, or recomputed through a duplicate eligibility function. Scenario setup varies only the external facts whose consequences are specified.
- **Determinism and isolation:** every scenario creates a fresh in-memory environment with fixed actor, order, participant snapshots, process projection, and server instant. There is no production database, directory, legacy service, renderer, filesystem, network, ambient timezone, or wall-clock dependency. The shared helper removes repetition without sharing mutable state between scenarios.
- **Scope:** multiple-role canonicalization, role validity periods, directory failures, changing the engineer, concurrency, renderer/persistence failures, UI, and HTTP remain correctly outside this slice.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; additional uncovered cases require a separately approved slice.
