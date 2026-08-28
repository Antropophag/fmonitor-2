# Test review: ORDER-PREPARE-003

- Reviewer: `Codex agent /root/order_prepare_003_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-003.md`](../../specs/ORDER-PREPARE-003.md), version `0.2`, `APPROVED 2026-08-27`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)` and `::getOrderProcess(orderId)`
- Test: [`tests/InstallationProcess/order_prepare_003_test.php`](../../tests/InstallationProcess/order_prepare_003_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_003_test.php`
- Intended failure: the current command uses the non-nullable installer lookup and aborts on missing tab ID `9999` instead of collecting all approved кадровые violations
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Missing installer must be handled through nullable catalog lookup. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:120
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(136): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->getInstallerSnapshot()
#1 [internal function]: FMonitor2\InstallationProcess\InstallationProcess->{closure:FMonitor2\InstallationProcess\InstallationProcess::prepareOrder():136}()
#2 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(135): array_map()
#3 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_003_test.php(100): FMonitor2\InstallationProcess\InstallationProcess->prepareOrder()
#4 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 120
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-003`, example A, and reproduces version `0.2`'s authorized actor, fixed server instant, complete order snapshot, seven ordered tab IDs, six invalid кадровые cases, and one valid inclusive-boundary case. Exact expectations match the approved executable example.
- **Public seam:** behavior is invoked only through `InstallationProcess::prepareOrder(...)` and observed through its result plus `InstallationProcess::getOrderProcess(...)`. Environment methods arrange deterministic external facts and prohibit downstream interactions; no private method or adapter storage is inspected.
- **Genuine RED and nullable fixture:** `markInstallerMissing(9999)` intentionally makes `findInstallerSnapshot(...)` return `null`. The old non-nullable `getInstallerSnapshot(...)` throws the captured guard after authorization, composition, and required order data have passed. This proves the missing aggregate eligibility behavior rather than broken setup.
- **Complete кадровая rule sensitivity:** exact result equality requires the missing-catalog violation first, then violations for dismissed status, known end before planned finish, missing `employedFrom`, future `employedFrom`, and `status = employed` with an end before the order date. Tab ID `4001` has `employedFrom = orderDate` and `employedTo = plannedFinishDate`; its deliberate absence from the exact violation list proves both boundaries are inclusive. One violation per invalid installer and input-index fields are also enforced.
- **Aggregation and ordering:** a single invocation must return all six invalid installers in first-input order. The audit must deduplicate the six violations to exactly two reason codes in first-occurrence order, while reporting `installerCount = 7` and `invalidInstallerCount = 6`; the valid boundary record is therefore counted but not rejected.
- **State and downstream boundaries:** full public projection equality retains `needs_order`, no versions or assignments, the open `prepare_order` task, and closed work/checklist gates. Engineer lookup and rendering are explicit forbidden-call guards, so neither may occur after any installer violation.
- **Audit privacy:** the only allowed mutation is one rejection event with server time, actor, reason codes, aggregate counts, and engineer-presence boolean. Exact equality excludes installer tab IDs, names, employment dates, engineer ID, order values, and snapshots.
- **Expected-value independence:** codes, Russian messages, fields, ordering, counts, timestamp, and unchanged projection are literal values from approved example A. They are not obtained from production constants, production output, or a duplicate eligibility calculation in the test.
- **Sensitivity:** the test fails if production stops at the first invalid record, skips any date/status rule, treats either equality boundary as exclusive, rejects the allowed boundary record, reorders or duplicates reasons, emits PII, mutates process facts, or calls engineer/renderer dependencies.
- **Determinism, isolation, and scope:** all time, order, catalog, command, and process facts are fixed in memory. There is no production database, legacy system, network, filesystem, real renderer, ambient timezone, or wall-clock dependency. Freshness, integration failure, load conflicts, engineer eligibility, opening-time recheck, successful brigade preparation, UI, and HTTP remain correctly outside this slice.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; additional uncovered cases require a separately approved slice.
