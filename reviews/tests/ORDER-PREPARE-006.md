# Test review: ORDER-PREPARE-006

- Reviewer: `Codex agent /root/order_prepare_006_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-006.md`](../../specs/ORDER-PREPARE-006.md), version `0.1`, `APPROVED 2026-08-28`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(installationObjectId)`
- Red command and intended failure: `php tests/InstallationProcess/order_prepare_006_test.php` exits `255`; current production reads process and revision separately, and the concurrency fixture raises `LogicException: Concurrent fixture requires atomic process and revision read.`
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Concurrent fixture requires atomic process and revision read. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:275
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(90): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->getInstallationObjectProcessRevision()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_006_test.php(138): FMonitor2\InstallationProcess\InstallationProcess->prepareAssignmentOrder()
#2 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 275
```

Exit code: `255`.

## Findings

- **Traceability and rejected result:** the test cites `ORDER-PREPARE-006`, example A, arranges the approved stale-revision race (`7` observed, `8` current), and fixes the exact `CONCURRENT_MODIFICATION` result and message.
- **Public seam:** production behavior is invoked and observed only through `prepareAssignmentOrder(...)` and `getInstallationObjectProcess(...)`. The environment's concurrency hook is fixture arrangement for the persistence boundary, not a production assertion side channel.
- **Genuine RED:** authorization and composition are valid, and the seeded process and revision form a valid initial observation. The failure occurs at the current second, non-atomic read while a save-time race is scheduled. It is therefore sensitive to the split-read window identified by Gate 5, not to invalid business setup.
- **Atomic read sensitivity without private production assertions:** `loadInstallationObjectProcessAtRevision(...)` supplies the persistence capability required by specification sections 1-3, while the old separate revision read is guarded only in the concurrent fixture. The test still invokes and observes the public process seam; it does not call a private production method or inspect adapter storage. An equivalent implementation may perform any internal strategy that obtains one consistent process/revision observation.
- **Save and audit atomicity:** after the atomic-read defect is fixed, the existing save-time replacement still forces revision `7 → 8`. The added concurrent audit append then forces `8 → 9`, and the generic `appendEvent(...)` is explicitly guarded. Exact final equality requires preservation of both the winner projection and the intervening event, followed by exactly one rejection event. This detects replacing current state, dropping the intervening event, duplicating the rejection, or retrying the business save successfully.
- **Audit semantics and privacy:** the rejection retains `observedProcessRevision = 7` and `currentProcessRevision = 8`, the revision that caused the business conflict, while a later unrelated event advances persistence to `9`. Its payload remains limited to the approved reason, normalized installer count, engineer-presence flag, and revisions; losing identities, snapshots, bytes, hashes, assignments, and artifacts remain absent.
- **Expected-value independence:** the rejection and audit expectations are literals from the approved specification. The winner projection is authored as a literal rather than obtained from the production command under review.
- **Determinism and isolation:** both races are explicitly scheduled once in one in-memory environment with a fixed clock, revisions, catalogs, and renderer output. There is no thread scheduler, production database, filesystem, network, legacy system, timezone, or wall-clock dependency.
- **Corrected winner fixture:** the independently authored `$winnerProcess` is now a complete valid `ORDER-PREPARE-002` successful projection for actor `17`: it includes the full object, installer, and engineer snapshots; both artifact records with metadata and hashes; both preliminary assignments; and the complete success-event payload. Exact equality therefore exercises preservation of every winner fact expressly listed in sections 5-6 while keeping winner people and artifacts distinct from the losing command.
- **Renderer retry sensitivity:** `forbidRepeatedRendering()` allows the one render required before the save conflict and raises on every later render call. Together with the exact final projection, this makes the section 5 rule executable: an audit conflict may retry only the rejection-event append, never the renderer or a successful business save. The guard observes call count only and does not couple expectations to renderer input or production internals.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; additional uncovered cases require a separately approved slice.
