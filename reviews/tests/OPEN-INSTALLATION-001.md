# Test review: OPEN-INSTALLATION-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, support fixture, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/OPEN-INSTALLATION-001.md`](../../specs/OPEN-INSTALLATION-001.md), version `0.2`, `APPROVED 2026-08-28`
- Inherited behavior: [`specs/ORDER-PREPARE-002.md`](../../specs/ORDER-PREPARE-002.md) and [`specs/REGISTRATION-CONFIRM-001.md`](../../specs/REGISTRATION-CONFIRM-001.md)
- Public seam: `InstallationProcess::openInstallation(...)`, `::getInstallationObjectProcess(...)`, and the inherited public security-audit reader
- Success regression command: `php tests/InstallationProcess/open_installation_001_test.php`
- Integrity RED command: `php tests/InstallationProcess/open_installation_001_integrity_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## v0.2 Gate 5 restart review

The v0.2 integrity test is approved. It reproduces the exact fail-open defect identified by independent Gate 5 while the previously approved success tracer remains green.

Commands and results:

```text
php tests/InstallationProcess/open_installation_001_test.php
PASS: OPEN-INSTALLATION-001 successful opening
```

Exit code: `0`.

```text
php tests/InstallationProcess/open_installation_001_integrity_test.php
PHP Fatal error:  Uncaught TestFailure: Registered order without installers must fail closed before date and Workforce checks.
Expected: array (
  'accepted' => false,
  'violations' =>
  array (
    0 =>
    array (
      'code' => 'REGISTERED_ORDER_COMPOSITION_INVALID',
      'message' => 'Зарегистрированное распоряжение не содержит ни одного монтажника. Открытие работ невозможно.',
      'field' => NULL,
    ),
  ),
)
Actual: array (
  'accepted' => true,
  'processState' => 'working',
  'actualStartDate' => '2026-08-28',
  'openedAt' => '2026-08-28T12:45:00+03:00',
  'openedByUserId' => 18,
  'installationOpened' => true,
  'checklistAvailable' => true,
  'assignmentOrderVersion' => 1,
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/open_installation_001_integrity_test.php(30): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`.

- **Corruption scope and seam:** setup first prepares and registers version `1` through public commands. The support hook then changes only the current order's internal `installers` list to `[]`, accurately simulating the persistence/import corruption named by v0.2. It does not perform the command, manufacture the rejection, modify gates/events, or derive expectations. Action and all behavioral observations remain public.
- **Exact rejection:** strict equality requires the single `REGISTERED_ORDER_COMPOSITION_INVALID` violation, exact Russian message, and null field. The current implementation instead returns its full success result, producing a direct, sensitive RED for the reported vacuous-`foreach` defect.
- **Exact audit and security boundary:** the full projection requires exactly one appended `installation_open_rejected` event at the fixed clock, with actor `18` and only the ordered reason code, version `1`, and installer count `0`. Registration number, people, actual date, workforce facts, and internal IDs are absent by strict payload equality. Public security audit must remain exactly empty.
- **No partial opening and full immutability:** strict projection equality retains `assignment_order_prepared`, null actual/opened/by fields, false opening/checklist gates, no tasks, the registered order metadata, object/engineer snapshots, two artifacts, assignments, and both prior events. Apart from the deliberate empty installer corruption and one rejection event, extra or changed process facts fail.
- **Dependency ordering sensitivity:** current Workforce lookup is forbidden and its read log must remain exactly empty; renderer, legacy-object, preparation-installer, and engineer-directory reads also throw. Thus the corrupt composition must fail before external eligibility work, and historical facts cannot be rebuilt.
- **Independent literals and isolation:** reason/message/audit values come directly from approved v0.2, while inherited projection values remain literal. Clock and fixtures are fixed in a fresh in-memory environment with no shared external state.
- **Scope:** the test covers only the newly approved empty registered-composition integrity rejection. It does not broaden into unrelated date, status, authorization, Workforce eligibility, repeat/concurrency, or persistence failures.

**Fresh Gate 3 verdict for v0.2: `APPROVED`. Gate 4 may implement only the reviewed fail-closed guard; Gate 5 must then restart independently.**

## Re-review after Gate 2 correction

The revised test grants security-audit read permission to independent actor `99` and, after the exact opening projection, asserts that public `getSecurityAudit(4512, 99)` equals the exact empty list. This closes the sole prior blocker without inspecting fixture internals: any additional success security event is now observable and rejected.

Fresh exact RED:

```text
PHP Fatal error:  Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\InstallationProcess::openInstallation() in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/open_installation_001_test.php:32
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/open_installation_001_test.php on line 32
```

Exit code: `255`.

The inherited public preparation and registration still complete before the intended failure. Exact opening literals, the current Workforce `[1042]` observation, competing current-person fields, immutable full registered history, dependency guards, gates/tasks, process-event payload, and deterministic isolation remain unchanged.

**Gate 3 verdict: `APPROVED`. Gate 4 may proceed.**

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Call to undefined method FMonitor2\InstallationProcess\InstallationProcess::openInstallation() in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/open_installation_001_test.php:31
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/open_installation_001_test.php on line 31
```

Exit code: `255`.

The fixture successfully prepares and registers the exact inherited version through the two public commands before reaching the intended absent opening command. The RED is not caused by fixture setup, authorization setup, or an inherited regression.

## Findings

- **Traceability and public seam:** the test cites approved v0.1, establishes the exact registered precondition only through public commands, invokes the approved three-argument opening seam once, and observes its exact result plus the complete public process projection. Fixture-only call recording is appropriately limited to proving the separately approved current-catalog collaboration.
- **Exact successful boundary values:** the fixed actual date is exactly the Moscow business date and one day after the immutable order date, exercising the approved equality-at-today success boundary. The fixed clock, actor `18`, current version `1`, and result fields are independently literal. Rejected date/status/authorization outcomes are explicitly deferred and need not be invented by this success tracer.
- **Current Workforce collaboration:** the test provides a distinct current snapshot and strictly requires the current-catalog read list to equal `[1042]`. It therefore detects using the preparation lookup instead of the approved `findCurrentInstallerSnapshot` seam, missing the lookup, duplicate lookup, or checking a person outside the registered composition. The current record is employed for the actual date. Exact rejection semantics for an ignored/ineligible current response remain intentionally reserved for the subsequent rejection slice.
- **Historical immutability sensitivity:** the current Workforce name, position, and freshness timestamp deliberately conflict with the saved order snapshot. The exact post-command projection retains the original Ivanov snapshot, order date/form, complete object and engineer snapshots, both artifacts and hashes, assignments, registration metadata, and prior events. Copying current catalog fields into history or rebuilding the order cannot pass.
- **Forbidden dependencies:** after the inherited setup, renderer, legacy object snapshot, preparation installer lookup, and engineer directory reads all throw. Opening can use saved order facts plus the one approved current Workforce seam only; re-rendering or rehydrating historical facts fails.
- **Opening fact, gates, tasks, and event:** strict result/projection equality covers `working`, actual/opened/by audit fields, both true gates, exact version, no tasks, and exactly one appended `installation_opened` event after the unchanged preparation/registration events. Its time, actor, and minimal payload are exact, so extra process events or leaked names/tab IDs/documents fail.
- **Expected-value independence:** all command, date, clock, snapshot, artifact, assignment, gate, and event expectations are fixed literals from v0.1 and its cited inherited examples. No expected value is copied from command output, current environment state after execution, or production constants.
- **Support fixture:** the opening authorization set, current-snapshot map, and read log expose only the approved internal decisions. They do not perform the opening transition, derive expected results, or mutate production state, and are reusable for later rejection cases.
- **Resolved security-audit gap:** the revised test observes the inherited public security-audit seam as independent reader `99` and requires exact emptiness after opening. An extra success security event can no longer pass.
- **Determinism and isolation:** all facts and clocks are fixed in a fresh in-memory environment; there is no database, filesystem, network, or wall-clock dependency. The single environment instance is local to the test process.
- **Scope:** exact rejection results, retry/concurrency, persistence, production capability mapping, task creation/SLA, checklist content, and UI remain correctly excluded.

## Previously required changes (resolved)

1. Completed: actor `99` reads the exact empty audit through `getSecurityAudit(4512, 99)` without private-state inspection.
2. Completed: the exact result/full projection, competing current Workforce literal, `[1042]` read assertion, guards, and missing-method RED are retained.
3. Completed: fresh independent Gate 3 approval is recorded above.
