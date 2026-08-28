# Test review: PROCESS-USER-DIRECTORY-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, migration, or directory)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PROCESS-USER-DIRECTORY-001.md`](../../specs/PROCESS-USER-DIRECTORY-001.md), version `0.1`, `APPROVED 2026-08-28`
- Inherited contracts: `MIGRATION-PROCESS-001`, `WORKFORCE-CATALOG-001`, `LEGACY-OBJECT-SNAPSHOT-001`, `ORDER-PREPARE-001-H`, `ORDER-PREPARE-004`, and `PERSISTENCE-PREPARE-001`
- Technical seam: `ProcessUserCapabilitiesSchemaMigration::apply(connection, tablePrefix)`
- Public behavior seam: `InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(...)` through production MariaDB delegates/persistence
- Red command: `php tests/InstallationProcess/process_user_directory_001_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Intermediate re-review verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`
- CHECK-canonicalization Gate 5 restart verdict: `APPROVED`
- Corrupt-position Gate 5 restart verdict: `APPROVED`
- Spaced-position/blank-corruption Gate 5 restart verdict: `APPROVED`

## Spaced valid position and whitespace-only corruption Gate 5 restart review

The paired cases correctly distinguish validation from mapping:

- the valid engineer capability now stores the independently chosen literal `"  Инженер строительного контроля  "`. It is nonblank under the schema's `trim(...) <> ''` eligibility predicate, while section 5 requires `position` to equal the **exact** configured `position_snapshot`; therefore the full persisted projection must retain both leading and trailing spaces rather than silently normalize configuration;
- the whitespace-only corrupt scenario seeds exactly `"   "` under `SET SESSION check_constraint_checks=OFF`, immediately restores `check_constraint_checks=ON`, and then calls the public command with otherwise active user, active role, and exact engineer capability. It must fail closed as `CONTROL_ENGINEER_NOT_ELIGIBLE` because `trim(position_snapshot)` is empty.

The valid mapping expectation follows the normative exact-field mapping, not production output or a PHP trim computation. The blank rejection uses the existing exact inherited result and audit. The two cases together catch both an over-normalizing reader and a reader that checks only `IS NOT NULL`.

Fixture safety/no-mutation remains adequate. Constraint disabling is scoped to the synchronous corrupt UPDATE and re-enabled before state capture, directory construction, and the public seam. The rejection compares complete database schema/data/auto-increment state before/after, requires exact audit, no security audit, no renderer, and no order. Its restore reinstates the spaced valid literal for the later success tracer. The enclosing random database preserves test-process isolation.

The reviewer reran `php tests/InstallationProcess/process_user_directory_001_test.php`. Both NULL and whitespace-only corrupt scenarios now reject correctly; the single RED occurs after successful preparation/reconnect because the partial production directory trims the valid configured position:

```text
PHP Fatal error:  Uncaught TestFailure: Complete persisted projection must retain original engineer after current user/capability mutation.
Expected controlEngineer.position:
  '  Инженер строительного контроля  '
Actual controlEngineer.position:
  'Инженер строительного контроля'
in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php(192): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. All prior schema, rejection, audit and no-mutation assertions complete before the intended exact-mapping failure.

No blocking test findings remain. The spaced-position/blank-corruption Gate 3 restart is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Corrupt NULL engineer-position Gate 5 restart review

The new adversarial case is a precise, command-level test of the section 5 read invariant independently of normal schema enforcement:

- fixture mutation executes `SET SESSION check_constraint_checks=OFF`, changes only the exact engineer capability row's `position_snapshot` to `NULL`, and executes `SET SESSION check_constraint_checks=ON` before the directory/module is constructed and before the public command seam;
- each statement is executed synchronously on the same connection; MariaDB query failure aborts setup, while the final `ON` precedes state capture and command execution. Constraint enforcement is therefore disabled only to seed the corrupt source fact, not during behavior under test;
- actor capability, engineer capability string, active user and active linked role all remain otherwise valid, so only the corrupt position distinguishes this scenario;
- the expected exact `CONTROL_ENGINEER_NOT_ELIGIBLE` response and process-audit payload are inherited literals, while zero security audit and zero renderer calls preserve established ordering.

No-mutation sensitivity remains complete. The full database `SHOW CREATE TABLE` and row state is captured after corruption and after constraint re-enablement, then compared after rejection. The command must preserve the deliberately corrupt row and every process/catalog/schema/auto-increment fact. The focused zero-order assertion remains in addition to this complete comparison. The restore writes the approved position after the scenario so the existing success/reload path remains isolated from the corrupt fixture.

The scenario shares the already isolated random database and fixed IDs/data; it introduces no global/session leakage beyond its connection, and explicitly restores both constraint checking and the row. Expected values are specification literals rather than production output.

The reviewer reran `php tests/InstallationProcess/process_user_directory_001_test.php` against the partial production directory. All prior scenarios pass; the new corrupt-position case is incorrectly accepted:

```text
PHP Fatal error:  Uncaught TestFailure: corrupt engineer capability with NULL position despite active user and role must reject engineer eligibility.
Expected: array (
  'accepted' => false,
  'violations' => array (
    0 => array (
      'code' => 'CONTROL_ENGINEER_NOT_ELIGIBLE',
      'message' => 'Выбранный пользователь не является активным инженером строительного контроля.',
      'field' => 'controlEngineerUserId',
    ),
  ),
)
Actual: array (
  'accepted' => true,
  'assignmentOrderVersion' => 1,
  'status' => 'prepared',
  'assignmentOrderDate' => '2026-08-27',
  'organizationType' => 'individual',
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php(164): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. The RED is the intended missing entry-point validation, not a schema CHECK insertion failure or disabled-constraint leak.

No blocking test findings remain. The corrupt-position Gate 3 restart is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## CHECK-canonicalization Gate 5 restart review

The narrow Gate 2 change correctly accommodates MariaDB's equivalent rendering of the engineer-position `CHECK` without weakening the schema contract:

- normalization still lowercases and removes only identifier quoting/whitespace as before;
- it then recognizes exactly two known MariaDB renderings of the same right-hand disjunction—one or two redundant parenthesis layers around `position_snapshot IS NOT NULL AND trim(position_snapshot) <> ''`—and maps only that exact substring to one canonical literal;
- it does not reorder operands, remove arbitrary parentheses, simplify operators, accept alternative capability strings, or normalize unrelated expressions;
- after mapping, the array is sorted deterministically and compared by strict equality to exactly two independent expected clauses. Missing checks, extra checks, altered capability membership, weakened position semantics, duplicates, or any third constraint still fail by count/content equality.

The expected sorted order is correct: the canonical `capability<>...` clause sorts before `capabilityin(...)`. Both literals remain direct representations of section 2, not values copied from production inspection output.

The reviewer reran `php tests/InstallationProcess/process_user_directory_001_test.php` against the partial production implementation. Initial v3 creation and the strict schema assertions now pass; the test remains RED because production compatibility fingerprinting does not yet canonicalize the same MariaDB-equivalent CHECK on safe repeat:

```text
PHP Fatal error:  Uncaught TestFailure: Compatible v3 repeat must be a no-op.
Expected: array (
  'applied' => false,
  'schemaVersion' => 3,
  'tablesCreated' => array (),
)
Actual: array (
  'applied' => false,
  'schemaVersion' => 3,
  'reason' => 'SCHEMA_MIGRATION_CONFLICT',
  'conflictingTables' => array (
    0 => 'process_fm2_process_user_capabilities',
  ),
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php(106): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. This is the intended new RED: emitted DDL is accepted by the independently normalized exact catalog assertion, but the production repeat path falsely reports its own table as conflicting.

No blocking test findings remain. The CHECK-canonicalization Gate 3 restart is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Final re-review

The current test resolves both remaining blockers:

- the actor exact-capability scenario now removes `assignment_order.prepare` and inserts a valid `construction_control_engineer` capability with a nonblank position while keeping the actor user/role active and the misleading `ФКР` role name. Exact `FORBIDDEN` proves that “any capability” and engineer-only capability cannot authorize preparation;
- immediately after each of all six fixture mutations, the test captures `userDirectoryDatabaseState()` for every table. After the public rejection and exact intercepted audit assertions, it requires the complete `SHOW CREATE TABLE` plus row state to be identical before restoring the fixture. Changes to installation-case state/revision, tasks, events, orders, artifacts, installers, catalogs, users, roles, capabilities, schema, or auto-increment are observable.

Audit interception is explicit at the environment boundary: expected security/process audit calls are captured in dedicated arrays and are intentionally excluded from MariaDB state, while the base production environment handles all other public-command behavior. Renderer remains at zero and the redundant zero-order assertion gives a focused failure in addition to the complete state comparison.

The six scenarios now distinguish every intended conjunction dimension in this success integration slice: actor/engineer user status, linked role status, exact actor/engineer capability, both capability directions, and legacy role-name non-authority. All results and audits are strict inherited literals. The successful full projection/read-only/current-fact-mutation/reload scenario remains intact.

The reviewer reran `php tests/InstallationProcess/process_user_directory_001_test.php` from the current shared file. It remains RED for the intended absent v3 migration:

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php:78
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php on line 78
```

Exit code: `255`. The isolated v1/v2 and legacy/current-source setup completes before reaching the missing class.

No blocking findings remain. Gate 3 is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Re-review after six adversarial scenarios

The revision resolves most of the original production-directory sensitivity gap:

- three actor scenarios independently deactivate the actor user, deactivate the linked actor role, and remove the prepare capability while retaining the semantically matching `ФКР` role name;
- three engineer scenarios deactivate the engineer user, deactivate the linked engineer role, and replace engineer capability with `assignment_order.prepare` while changing the legacy role name to the exact-suggesting `construction_control_engineer`;
- all scenarios call only the public command and assert exact inherited `FORBIDDEN` or `CONTROL_ENGINEER_NOT_ELIGIBLE` responses;
- actor failures require the exact closed security audit and no process audit; engineer failures require the exact process audit and no security audit;
- every scenario requires zero renderer calls and zero persisted assignment-order rows before restoring its fixture;
- the original successful full-projection/read-only/post-mutation tracer remains unchanged.

The expected rejection messages, audit payloads and counts are independent literals inherited from the approved specifications. Mutations and restores are fixed SQL against known primary keys, and the random enclosing database preserves parallel isolation.

The reviewer reran `php tests/InstallationProcess/process_user_directory_001_test.php`. The test remains RED at the intended absent v3 migration seam:

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php:78
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php on line 78
```

Exit code: `255`.

### Remaining blocking findings

1. **Actor capability separation is still not sensitive.** The “missing prepare capability” scenario deletes the actor's only capability and leaves that user with zero capability rows. An incorrect directory that authorizes an active user/role whenever *any* capability exists behaves identically to the required exact lookup in this fixture: both return false when no row exists. Section 5 explicitly states the converse separation too—`construction_control_engineer` alone must not permit preparation. The engineer scenario proves prepare-only does not make an engineer, but there is no symmetric actor scenario.
2. **No-partial-process-state is under-observed.** Each rejection asserts only `COUNT(*) = 0` in `fm2_assignment_orders`. An implementation can leave that table empty while changing the installation-case state/revision or inserting installer/artifact/task/process-event rows and still pass. The exact audit wrapper proves which public audit method was called, but it does not prove the rest of MariaDB process persistence stayed unchanged. The requested “no rows”/rejected atomicity should compare the complete relevant process state before and after every adversarial command (excluding the deliberately intercepted audit side effect).

### Required changes

1. In the actor exact-capability adversarial case, replace `assignment_order.prepare` with a valid `construction_control_engineer` capability and a valid nonblank position (or add a separate seventh case). Keep active user/role and a misleading FKR role name. Require `FORBIDDEN`, proving engineer capability alone cannot authorize preparation.
2. Capture the complete process tables/state before each rejected command and assert it is unchanged afterward, not merely that assignment-orders remains empty. If audit fixture interception intentionally removes persisted audit from this comparison, state that boundary explicitly while retaining the exact captured audit assertions.
3. Rerun the focused test, retain RED for absent v3/directory behavior, and request another independent Gate 3 review. Gate 4 remains paused.

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php:78
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_user_directory_001_test.php on line 78
```

Exit code: `255`.

The isolated database, v1/v2 migrations, minimal legacy object/users/roles, Workforce row, and process precondition are created before the absent v3 migration fails. This is an intended missing-production RED rather than broken MariaDB setup.

## Findings

- **Traceability and seams:** the test cites the approved spec, applies production v1/v2 before v3, wires all production data delegates except deterministic clock/renderer, calls only the public command, and observes the public projection through a new module/connection.
- **Exact additive v3 schema:** strict literals cover all three ordered columns, types, nullability, charset, InnoDB/utf8mb4, exact named primary and lookup indexes including direction/prefix/visibility, both normalized checks with no extras, and absence of legacy FKs. The fresh table is asserted empty and all database tables are enumerated, so extra v3 tables cannot hide.
- **Preservation, repeat, conflict, and prefix:** the pre-v3 complete table state is preserved table-by-table. Compatible repeat compares every table's `SHOW CREATE TABLE` and rows; conflict does the same before/after its exact result. Invalid syntax is expected as `InvalidArgumentException`. These checks are sensitive to ordinary forbidden DDL/DML and prefix/result mistakes.
- **No `users_rights2roles`:** that table is deliberately absent. Any directory implementation that queries it fails rather than receiving a hidden fixture green. The public projection and audit expectations contain no legacy right or role name.
- **Configured engineer snapshot:** `position` can only come from the capability row in this minimal schema, while exact expected `role = construction_control_engineer` differs from legacy display name `Строительный контроль`. Returning legacy role name or omitting the configured position fails the full projection.
- **Blocking core-semantics sensitivity gap:** the only command scenario makes every condition true simultaneously: actor user active, actor role active, prepare capability present; engineer user active, engineer role active, engineer capability present; legacy role names also align semantically with their users. An implementation that always authorizes an existing actor, ignores either status, ignores both capability rows, or derives eligibility from legacy role names can return the same success and full projection. The test therefore does not prove the section 4/5 conjunctions or capability separation—the central behavior introduced by this slice.
- **Why absence of rejected examples is blocking:** section 8 says the rejected paths are normative while declining separate production fixtures, but Gate 2 must remain sensitive to missing approved behavior. `ORDER-PREPARE-001-H` and `ORDER-PREPARE-004` prove the module reacts correctly to false/null internal results; they do not prove this new production directory computes those results from active user + active role + exact capability. A trivial permissive production directory can pass the current integration test.
- **Read-only current facts:** complete legacy object, users, roles and capability rows are compared before/after the command. This detects normal inserts, updates and deletes by the directory/command. Workforce read-only behavior is inherited from its independently approved slice.
- **Post-mutation durability:** fixture visibly changes engineer name/status, deactivates the engineer role and removes engineer capability. After connection/delegate destruction, a fresh module with a delegate that throws on all external calls must return the complete independently literal projection, including the original configured engineer position/canonical role and all inherited artifacts/assignments/event/gates.
- **Expected-value independence:** migration results, schema, command result, user/engineer facts and complete projection are fixed literals from the specification/inherited examples. They are not calculated from SQL, directory output or command output.
- **Isolation and determinism:** all IDs, rows, timestamps and renderer bytes are fixed. A random hexadecimal database contains all otherwise production-named fixtures, while static internal prefixes cannot collide across parallel databases. `finally` drops only the exact test database. The empty `users_rights2roles` dependency cannot be inherited from another schema.
- **Scope:** admin UI, capability-change audit, multi-role users, production clock/renderer and typed infrastructure errors remain correctly absent. Those omissions do not justify leaving the newly introduced production authorization/eligibility lookup permissive and untested.

## Required changes

1. Add command-level adversarial scenarios (prefer independent clean databases or reset process cases) that prove `FORBIDDEN` when each actor condition is removed/deactivated: actor status, linked role status/existence, and exact `assignment_order.prepare` capability. At minimum use misleading legacy role names so name-based authorization cannot satisfy a capability case.
2. Add command-level adversarial scenarios that prove `CONTROL_ENGINEER_NOT_ELIGIBLE` when engineer user/role is inactive or the exact engineer capability is absent, and prove `assignment_order.prepare` alone does not make that user an engineer. Retain the inherited exact public result/audit expectations rather than calling directory methods directly.
3. Keep the successful full-projection tracer and all migration/read-only/reload assertions. Split the rejected cases into follow-on spec IDs if one vertical behavior per cycle requires it; in that case narrow this specification's completion claim accordingly rather than treating the production conjunctions as already proved.
4. Rerun the focused test(s), retain RED caused by missing v3/directory behavior, and request fresh independent Gate 3 review. Gate 4 must not begin for the current broad specification.
