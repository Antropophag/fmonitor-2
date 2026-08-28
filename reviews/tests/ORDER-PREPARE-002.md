# Test review: ORDER-PREPARE-002

- Reviewer: `Codex agent /root/order_prepare_002_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-002.md`](../../specs/ORDER-PREPARE-002.md), version `0.1`, `APPROVED 2026-08-27`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)` and `::getOrderProcess(orderId)`
- Test: [`tests/InstallationProcess/order_prepare_002_test.php`](../../tests/InstallationProcess/order_prepare_002_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_002_test.php`
- Intended failure: the existing public command explicitly has no accepted preparation path yet and throws `LogicException` after the already implemented authorization and mandatory-composition checks
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: The accepted preparation path is not specified by this slice. in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php:89
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_002_test.php(68): FMonitor2\InstallationProcess\InstallationProcess->prepareOrder()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php on line 89
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-002`, example A, and reproduces its approved command, server moment, order snapshot, installer snapshot, engineer snapshot, renderer bytes, command result, prepared-version facts, preliminary assignments, task closure, closed work/checklist gates, and append-only preparation event.
- **Public seam:** behavior is invoked through the approved `InstallationProcess::prepareOrder(...)` command and observed through its result plus the approved `InstallationProcess::getOrderProcess(...)` query. Environment methods are confined to deterministic fixture setup; assertions do not inspect adapter storage or private implementation details.
- **Sensitivity:** the assertions would fail if the successful command were not accepted, the Moscow business date were derived incorrectly, the version/status/organization type or snapshots differed, either artifact's metadata or digest differed, work or checklist became available, the preparation task remained open, a separate registration task appeared, preliminary assignments were omitted, or the audit event did not carry the approved facts.
- **Expected-value independence:** scalar and structured expected values are literals from the approved worked example, not values read back from the environment or computed with production code. The reviewed SHA-256 literals independently match the two normative byte strings (`71656f...337c4` and `6b662f...82fe7`), and the asserted sizes independently match their byte lengths (`42` and `36`).
- **Rejected cases:** this successful-path slice introduces no new rejection code. The test supplies the already approved authorization and mandatory composition, so it reaches the new behavior after the `ORDER-PREPARE-001` checks. Existing rejection behavior remains outside this test and is covered by its own reviewed slices.
- **Determinism and isolation:** time, authorization, process state, all three source snapshots, and renderer output are fixed in memory. The test has no database, filesystem, network, production renderer, legacy system, timezone-default, or live-clock dependency.
- **Single vertical slice:** one command prepares one order with one installer and one engineer, and its complete caller-visible result is read through the process module. Although the observable state assertion is broad, it describes one atomic successful behavior rather than multiple horizontal implementation units.
- **Red result:** an independent rerun reaches the existing public seam and fails at its deliberate accepted-path placeholder. Authorization and required inputs have already passed, so the failure is caused by the missing `ORDER-PREPARE-002` behavior, not bootstrap, namespace resolution, fixture lookup, or another setup defect.
- **Coverage limit:** this approval applies to example A's first successful preparation. The test does not prove repeat-read stability after mutating source catalogs, rollback on renderer/persistence failure, or the separately deferred rejection cases. Those acceptance statements require later red tests before their implementation is claimed.

## Required changes

None for this one-command successful-path slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass; uncovered acceptance statements require their own red tests and independent review.
