# Test review: ORDER-PREPARE-007

- Reviewer: `Codex agent /root/order_prepare_007_test_review` (independent; did not author the specification or test)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree before implementation` (HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`)
- Specification: [`specs/ORDER-PREPARE-007.md`](../../specs/ORDER-PREPARE-007.md), version `0.1`, `APPROVED 2026-08-28`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(installationObjectId)`
- Red command and intended failure: `php tests/InstallationProcess/order_prepare_007_test.php` exits `255`; the renderer's configured `RuntimeException: template service unavailable` currently escapes from `InstallationProcess::prepareAssignmentOrder(...)` instead of becoming the specified safe rejection.
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught RuntimeException: template service unavailable in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:254
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(249): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->renderAssignmentOrder()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_007_test.php(55): FMonitor2\InstallationProcess\InstallationProcess->prepareAssignmentOrder()
#2 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 254
```

Exit code: `255`.

## Findings

- **Traceability and public seam:** the test cites executable example A and invokes only the approved public command and observation methods. Renderer failure and persistence prohibition are fixture arrangement at external ports, not assertions against private production methods.
- **Genuine RED and valid preconditions:** authorization, initial process state and revision, object data, installer employment, engineer role, fixed time, and lack of an existing order all satisfy the successful path through validation. The failure occurs at the renderer call for the intended missing behavior, rather than from broken setup.
- **Exact safe response:** the literal result fixes the approved code, Russian message, null field, and response shape. It therefore rejects leakage of the renderer exception message, template/path details, or a stack trace through the command response.
- **Unchanged process facts and safe audit:** exact equality of the public projection requires the initial state, open task, absent orders and assignments, closed installation, and unavailable checklist to remain unchanged while exactly one safe rejection event is appended. The literal event payload checks the fixed time and actor plus only the approved reason code, normalized installer count, and engineer-presence flag; identities, snapshots, document data, hashes, and exception details cannot appear in the public projection.
- **Persistence guard:** `forbidProcessReplacement()` makes an attempted successful aggregate save after renderer failure fail the test before observation. In this in-memory port, orders, assignments, artifacts, tasks, success event, process state, and the legacy-facing process projection are persisted by that aggregate replacement; the rejection audit append remains intentionally permitted.
- **Expected-value independence:** the expected rejection and projection are fixed literals from specification sections 3-5. They are not computed using production behavior or renderer output.
- **Determinism and isolation:** the clock, process revision, source snapshots, and renderer failure are fixed in memory. The test has no production database, filesystem, network, legacy-system, timezone, wall-clock, or scheduler dependency.
- **Renderer retry sensitivity:** specification sections 2 and 6 require one renderer invocation and prohibit an automatic retry in the failed command. The amended test reads the renderer port fixture's call count after the public command and requires exactly `1`. A retry therefore fails even if production catches or converts every renderer exception into the expected business rejection. This observes only a specified interaction with the external renderer boundary and does not couple the test to production internals or renderer input structure.

## Required changes

None.

Gate 3 is approved. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectations; additional uncovered cases require a separately approved slice.
