# Test review: REGISTRATION-CONFIRM-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, support fixture changes, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/REGISTRATION-CONFIRM-001.md`](../../specs/REGISTRATION-CONFIRM-001.md), version `0.1`, `APPROVED 2026-08-28`
- Inherited prepared state: [`specs/ORDER-PREPARE-002.md`](../../specs/ORDER-PREPARE-002.md), example A
- Public seam: `InstallationProcess::confirmOrderRegistration(...)` and `::getInstallationObjectProcess(...)`
- Red command: `php tests/InstallationProcess/registration_confirm_001_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## Re-review after Gate 2 correction

The current test now grants security-audit read access to an independent reader (`99`) before confirmation and, after the exact process-projection assertion, requires `getSecurityAudit(4512, 99)` to equal the exact empty list. This closes the sole prior blocker through an already approved public seam: an implementation that appends any success security event can no longer pass. The assertion neither inspects fixture internals nor couples the test to the registration implementation.

Fresh focused RED reproduced with the current file:

```text
PHP Fatal error:  Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\InstallationProcess::confirmOrderRegistration() in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/registration_confirm_001_test.php:37
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/registration_confirm_001_test.php on line 37
```

Exit code: `255`.

The inherited public preparation fixture completes before this intended failure. The revised audit observation occurs after confirmation and does not mask or replace the exact missing-seam RED. All previously reviewed command-result, immutable full projection, stage/task/gate, append-only process history, forbidden external-read/render, fixed-clock, isolation, and scope properties remain intact.

**Gate 3 verdict: `APPROVED`. Gate 4 may proceed.**

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\InstallationProcess::confirmOrderRegistration() in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/registration_confirm_001_test.php:36
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/registration_confirm_001_test.php on line 36
```

Exit code: `255`.

The test first establishes the complete approved prepared version through the existing public preparation command, then fails at the absent registration-confirmation public seam. Fixture setup, renderer and inherited behavior are green before the intended missing-method RED.

## Findings

- **Public seam and traceability:** the test cites `REGISTRATION-CONFIRM-001 v0.1`, establishes the normative precondition through public behavior, calls the exact approved five-argument command once, and observes only its result and the public process projection.
- **Independent exact registration literals:** input includes surrounding spaces while expected `registrationNumber = 12-Р`, proving trim without changing internal characters/case. Strict command-result equality covers exact version, `registered`, fixed clock, `user` actor type, integer actor `18`, `manual` source, null external ID, and intentionally unchanged `assignment_order_prepared` state.
- **Exact version and full immutability:** the resulting projection contains exactly one version `1`, so creating version `2` or registering a different version fails. Strict literals preserve assignment-order date/form, complete object/installer/engineer snapshots, both artifact filenames/sizes/hashes, and both preliminary assignment links. An implementation that rebuilds or edits the prepared document cannot pass.
- **Stage, tasks and gates:** strict `processState = assignment_order_prepared`, empty tasks, `installationOpened = false`, and `checklistAvailable = false` detect a new registration stage/task, premature opening action, or checklist access.
- **Append-only process history:** the expected event list has exactly the unchanged preparation event followed by one exact registration event. Strict array equality catches deletion/rewrite/reordering of preparation history, duplicate registration events, extra payload facts, raw untrimmed number, wrong time/actor/source, or extra process events.
- **Forbidden external reads/rendering:** after preparation, fixture flags reject object, installer and engineer snapshot reads and all renderer calls. Confirmation must use the saved prepared version; rehydrating external facts or rebuilding either artifact fails immediately. Clock remains the deterministic approved registration moment.
- **Authorization fixture:** `allowRegistrationConfirmationBy(18)` and `actorCanConfirmOrderRegistration()` expose the approved environment decision without equating it to preparation permission. This is an adapter seam, not a production implementation detail. Unauthorized behavior is explicitly reserved for another slice.
- **Support changes:** the only registration-specific support state is an allowed-actor set plus its query method. Existing public process load/revision/replace and read/render guard mechanisms remain generic. The test does not introduce a helper that performs registration, derives expected values, or mutates private production state.
- **Resolved success-security-audit gap:** specification section 6 says a successful confirmation creates no separate security event. The revised test now reads the audit through the approved public `getSecurityAudit(...)` seam and requires exact emptiness, so an extra success security event is observable and rejected.
- **Determinism/isolation:** all facts, clock values, artifact bytes and expectations are fixed in memory; there is no DB, filesystem, network or wall-clock dependency. Each test process owns its environment instance.
- **Scope:** wrong/empty number, unsupported/integration source, unauthorized/wrong-version/status, retries, concurrency and persistence failures are correctly absent from this single success tracer. MariaDB persistence and production capability are not implied.

## Previously required changes (resolved)

1. Completed: the revised test allows actor `99` to read the fixture audit and asserts the exact empty list through `getSecurityAudit(4512, 99)` without private-state inspection.
2. Completed: the command result, full projection and all read/render guards remain, and the focused RED is preserved at the absent `confirmOrderRegistration` method.
3. Completed: fresh independent Gate 3 review is recorded above.
