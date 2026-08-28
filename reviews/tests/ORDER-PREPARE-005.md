# Test review: ORDER-PREPARE-005

- Reviewer: `Codex agent /root/order_prepare_005_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-005.md`](../../specs/ORDER-PREPARE-005.md), version `0.1`, `APPROVED 2026-08-28`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(installationObjectId)`
- Red command and intended failure: `php tests/InstallationProcess/order_prepare_005_test.php` exits `255`; the current command reads the explicitly forbidden installation-object snapshot before checking the existing current version, instead of returning the approved repeated-preparation rejection
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Installation object snapshot must not be read for this fixture. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:90
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(89): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->getInstallationObjectSnapshot()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_005_test.php(79): FMonitor2\InstallationProcess\InstallationProcess->prepareAssignmentOrder()
#2 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_005_test.php(115): assertRepeatedFirstPreparationRejected()
#3 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 90
```

Exit code: `255`.

## Findings

- **Traceability and rejected cases:** the test cites `ORDER-PREPARE-005`, examples A-B, and executes the same approved command against separate current version `1` fixtures in both normative statuses, `prepared` and `registered`. The differing process states, registration number, assignment status, task state, and prior event make both rejected states independently observable.
- **Public seam:** each scenario invokes only `InstallationProcess::prepareAssignmentOrder(...)` and observes the exact command result plus `InstallationProcess::getInstallationObjectProcess(...)`. Environment methods arrange fixed external facts and guard forbidden dependencies; no private production method or adapter storage is used as an assertion side channel.
- **Genuine RED and ordering sensitivity:** actor `18` is authorized and `[2088]` plus engineer `74` satisfy mandatory composition. The captured failure occurs when current production proceeds to the installation-object catalog. The fixture deliberately forbids that call, so the RED is caused by the missing current-version decision at the specified point, after authorization/composition and before object/person catalogs and renderer.
- **Unified non-disclosing rejection:** exact result equality fixes `accepted = false`, the sole `ASSIGNMENT_ORDER_ALREADY_PREPARED` violation, its Russian message, and `field = null` for both statuses. It prevents leaking whether the existing version is merely prepared or already registered.
- **Immutability and append-only history:** the expected projection begins as the complete literal seeded projection and permits only one appended rejection event. Exact equality preserves the current version, registration number, immutable snapshots, assignments, artifacts, prior events, process state, tasks, work-opening flag, and checklist gate. Distinct saved and incoming participant identifiers make replacement or merging of composition detectable.
- **Audit contract and privacy:** the appended event fixes server time, actor, reason-code list, normalized unique installer count, engineer-presence flag, and current version. Exact projection equality excludes incoming tab IDs, engineer ID, names, new command snapshots, current status, and registration number from the new audit payload.
- **Forbidden downstream work:** explicit guards cover installation-object snapshot lookup, both people catalogs, and renderer invocation. The modeled public projection has no separate legacy-projection operation; exact unchanged state nevertheless prevents any modeled process projection mutation in this slice.
- **Expected-value independence:** rejection values and audit values are literal expectations from approved specification sections 4-6. Existing process fixtures are independently authored worked examples of the two specified statuses; expected state is obtained by retaining that independently fixed fixture and adding the one explicitly specified event, not by invoking production logic or copying production output.
- **Determinism and isolation:** each status creates a fresh in-memory environment with a fixed process, actor, command, and clock. There is no production database, legacy system, network, filesystem, real renderer, ambient timezone, or wall-clock dependency, and the scenarios share no mutable environment.
- **Slice scope:** changing orders, effective dates, unknown-outcome retries/idempotency, concurrency, registration-number correction, cancellation, UI, and HTTP remain outside this test as specified.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; additional uncovered cases require a separately approved slice.
