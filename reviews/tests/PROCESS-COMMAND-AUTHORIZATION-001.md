# Test review: PROCESS-COMMAND-AUTHORIZATION-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, migrations, directory adapter, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PROCESS-COMMAND-AUTHORIZATION-001.md`](../../specs/PROCESS-COMMAND-AUTHORIZATION-001.md), version `0.2`, `APPROVED 2026-08-28`
- Public/deployment seams: v4 `ProcessCommandCapabilitiesSchemaMigration::apply(...)`, `MariaDbProcessUserDirectory` authorization methods, public prepare/confirm/open chain, and fresh-connection `getInstallationObjectProcess(...)`
- Red command: `php tests/InstallationProcess/process_command_authorization_001_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## Re-review after Gate 2 correction

Both prior sensitivity gaps are resolved.

User `97` has an active role, all three command capabilities, and a configured engineer capability/position, while `users.status = 0`. The exact matrix requires `[false, false, false, null]`. This independently makes active user status necessary for every authorization method and engineer lookup; neither inactive-role user `95` nor no-capability user `96` can mask that condition.

The external before/after snapshot now covers the production-used `fm_maintable` and prefixed `fm2_workforce_catalog` together with `users`, `users_roles`, and `fm2_process_user_capabilities`. Accidental writes to any external source/configuration table fail, while process state/history remains observed only through the full public fresh-connection projection.

Fresh RED:

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_command_authorization_001_test.php:72
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_command_authorization_001_test.php on line 72
```

Exit code: `255`.

The v1-v3 migrations and fixture still complete before the intended absent v4 deployment seam. Exact named CHECK replacement, schema/row preservation, repeat/conflict/missing/prefix behavior, capability converse and engineer matrix, absent `users_rights2roles`, real public chain, independent full reload, and isolated cleanup remain intact.

**Gate 3 verdict: `APPROVED`. Gate 4 may proceed without changing the reviewed expectations.**

## Gate 2 restart: alternate utf8mb4 collation

The added `utf8mb4_bin` v3 fixture is approved. It is not a relaxed or corrupt schema: it has the exact v3 columns/types/nullability, primary/index shape, named v3 capability CHECK, unchanged named engineer-position CHECK, no foreign keys, and two independently fixed valid rows. The alternate table/column collation remains within the inherited supported `utf8mb4_*` family.

`commandAuthNonCapabilityState()` captures engine, table collation, AUTO_INCREMENT, every column including `COLLATION_NAME`, complete index details including prefix/direction/ignored state, the position CHECK, foreign keys, and all rows. Exact before/after equality therefore permits only the deliberately excluded capability CHECK to change. A separate exact two-CHECK assertion proves that the replacement has the normative v4 name/clause and the position constraint remains present and unchanged.

The subsequent exact-v4 repeat must return the no-op result and preserve complete `SHOW CREATE TABLE`, rows, and AUTO_INCREMENT state. This detects a repeat ALTER, collation normalization, table rebuild side effect visible in schema, row rewriting, or constraint drift.

Fresh RED against the partial production migration:

```text
PHP Fatal error:  Uncaught TestFailure: Valid v3 with an alternate utf8mb4 collation must migrate successfully.
Expected: array (
  'applied' => true,
  'schemaVersion' => 4,
  'constraintsChanged' =>
  array (
    0 => 'ck_fm2_process_user_capability',
  ),
)
Actual: array (
  'applied' => false,
  'schemaVersion' => 4,
  'reason' => 'SCHEMA_MIGRATION_CONFLICT',
  'conflictingTables' =>
  array (
    0 => 'binary_fm2_process_user_capabilities',
  ),
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_command_authorization_001_test.php(110): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`.

The original general-collation v4 apply/schema/repeat checks pass before this new assertion, so the RED is specifically the production migration's over-restrictive collation preflight, not missing v4 behavior or a malformed baseline. All approved authorization matrix/public-chain/external-equality/reload/isolation coverage remains after the new case.

**Fresh Gate 3 verdict: `APPROVED`. Gate 4 may correct only alternate supported utf8mb4 handling, then Gate 5 must restart independently.**

## Gate 2 harness repair: CHECK catalog join isolation

The corrected test remains approved. `commandAuthChecks()` now joins `information_schema.CHECK_CONSTRAINTS` to `TABLE_CONSTRAINTS` on all three identity dimensions available in this MariaDB catalog: constraint schema, table name, and constraint name. The previous schema/name-only join was ambiguous once the primary, binary-collation, and conflict fixtures intentionally reused the same normative CHECK names; it could associate clauses from another table and make an otherwise correct implementation fail for test-harness reasons.

The added `cc.TABLE_NAME = tc.TABLE_NAME` predicate is a test observation repair, not a weakened expectation. The query still filters the exact target schema/table and `CHECK` type and orders exact constraint names. All exact semantic-clause assertions remain unchanged, including the named v3/v4 capability constraint and preserved engineer-position constraint in both collations.

Fresh corrected focused result:

```text
php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001
```

Exit code: `0`.

This green run does not replace or retroactively manufacture RED evidence. The independently captured pre-implementation alternate-collation RED remains recorded above: valid `utf8mb4_bin` v3 returned `SCHEMA_MIGRATION_CONFLICT` at line 110 with exit `255`. The repaired harness is now eligible as Gate 4 verification against the implemented change.

Schema preservation/repeat/conflict/missing/prefix sensitivity, alternate collation preservation, inactive-user and role conjunctions, exact capability converse/engineer matrix, absence of `users_rights2roles`, full external equality, real public chain, fresh forbidden-external reload, independent literals, and isolated cleanup all remain present and green.

**Fresh Gate 3 verdict after harness repair: `APPROVED`. The corrected green test may be used as Gate 4 evidence; independent Gate 5 remains mandatory.**

## v0.2 Gate 5 restart: historical CHECK names and ambiguity

The v0.2 historical-schema additions are approved.

Fresh RED:

```text
PHP Fatal error:  Uncaught TestFailure: Exact historical v3 semantic checks with safe generated names must migrate.
Expected: array (
  'applied' => true,
  'schemaVersion' => 4,
  'constraintsChanged' =>
  array (
    0 => 'ck_fm2_process_user_capability',
  ),
)
Actual: array (
  'applied' => false,
  'schemaVersion' => 4,
  'reason' => 'SCHEMA_MIGRATION_CONFLICT',
  'conflictingTables' =>
  array (
    0 => 'historical_fm2_process_user_capabilities',
  ),
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_command_authorization_001_test.php(110): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`.

- **Historical traceability:** the exact v3 fixture uses safe MariaDB-generated-style names `CONSTRAINT_1` and `CONSTRAINT_2`, reverses only the two values in the capability `IN`, retains the exact engineer-position semantics, uses supported `utf8mb4_bin`, and seeds both valid capability categories. It directly realizes executable example A rather than inventing a broader legacy schema.
- **Narrow semantic equivalence:** success requires capability semantics to be an exact two-value set despite equivalent `IN` ordering. The expected post-state still requires the exact normative four-value v4 clause; an unknown, missing, extra, substring-matched, or broadened value cannot satisfy the strict CHECK assertion. Engineer semantics are independently classified and preserved.
- **Exactly one candidate:** the ambiguity fixture has two differently named capability CHECKs whose normalized sets are identical plus one engineer-position CHECK. It must return the exact conflict rather than choosing either candidate. Together with the successful one-candidate fixture and existing zero/unknown conflict coverage, this makes candidate cardinality observable.
- **Safe actual name and DDL target:** `CONSTRAINT_1` is a valid independently fixed catalog identifier. Success proves migration drops that actual historical capability constraint rather than assuming the normative name. The resulting exact CHECK list proves only `CONSTRAINT_1` was replaced; `CONSTRAINT_2` survives under its historical name beside new `ck_fm2_process_user_capability`. Unsafe-name rejection remains a production invariant inspectable at Gate 5; no unsafe identifier is user-derived or required as a separate executable example here.
- **Preservation and repeat:** `commandAuthNonCapabilityState(..., 'CONSTRAINT_1')` excludes only the intended old capability CHECK and strictly captures engine/table and column collations, AUTO_INCREMENT, all columns/index details, every other CHECK including its actual name/clause, foreign keys, and all rows. Equality plus exact post-CHECKs detects engineer rename/drop, row/schema/index/collation drift, or extra constraints. Completed historical-name v4 then requires exact no-op result and complete `SHOW CREATE TABLE`/rows/AUTO_INCREMENT equality.
- **Ambiguous conflict immutability:** full `commandAuthTableState` before/after equality preserves both duplicate constraints by exact `SHOW CREATE TABLE`, all rows, and metadata. Any preliminary DROP/ALTER before detecting ambiguity fails.
- **Isolation and prior sensitivity:** historical, ambiguous, binary, conflict, and primary fixtures have distinct safe prefixes in one uniquely named disposable database. The corrected catalog join includes table identity, so equal constraint names cannot cross-contaminate. All previously approved migration, authorization matrix, external equality, public chain, and reload assertions remain after these new cases.

The RED occurs after the ordinary exact-v4 path and its repeat pass, specifically because current production identifies v3 CHECKs by normative names/order rather than the approved unique semantic match.

**Fresh Gate 3 verdict for v0.2: `APPROVED`. Gate 4 may implement only safe semantic CHECK discovery/replacement, then independent Gate 5 must restart.**

## v0.2 adversarial semantic/name cases

The quoted-literal, 64-byte dollar-name, and wrong-name-v4 additions are approved.

Fresh RED:

```text
PHP Fatal error:  Uncaught mysqli_sql_exception: CONSTRAINT `ck_fm2_process_user_capability` failed for `t_pca001_<random>`.`quoted_fm2_process_user_capabilities` in /home/antropophag/code/fmonitor-2/app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php:24
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php(24): mysqli->query()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_command_authorization_001_test.php(110): FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration::apply()
#2 {main}
  thrown in /home/antropophag/code/fmonitor-2/app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php on line 24
```

Exit code: `255`.

- **Quoted-literal fixture validity and sensitivity:** `assignment_order. prepare` is a distinct literal value, not whitespace formatting around SQL tokens. Its table is a structurally exact near-v3 schema with one engineer constraint and sentinel rows valid under its own malformed capability CHECK. The required outcome is preflight conflict and complete `SHOW CREATE TABLE`/rows/AUTO_INCREMENT equality. Current production removes all spaces from the clause, misclassifies the quoted value as approved v3, starts ALTER, and fails on the sentinel row. This is a direct RED for over-broad normalization and proves semantic data inside quotes must be preserved.
- **Exact spec mapping:** the quoted case realizes the v0.2 rule that unknown capability semantics conflict before ALTER. It does not introduce a new migration result. Full state equality will reject preliminary constraint changes, row loss, or a table rebuild once the preflight returns normally.
- **64-byte `$` identifier boundary:** the test independently proves the constructed name length is exactly `64`; `cap$` plus 60 ASCII `x` bytes lies exactly within `[A-Za-z0-9_$]+`. Its exact v3 semantics must upgrade, excluding an implementation that rejects `$`, applies a `<64` off-by-one limit, assumes normative names, or fails to quote the catalog identifier. The before/after non-capability snapshot permits only that actual CHECK replacement, then exact repeat preserves full state.
- **Wrong-name completed v4:** four-value semantics under `completed_but_wrong_name` must not masquerade as completed state, because v0.2 requires normative `ck_fm2_process_user_capability` for v4. Exact conflict plus full table-state equality rejects silent acceptance, automatic rename, DROP/re-add, row mutation, or partial DDL.
- **Isolation:** `quoted_`, `dollar_`, and `wrongv4_` are distinct valid prefixes and tables inside the unique disposable database. The catalog join includes table identity, preventing same/related CHECK names from crossing fixtures. Each state snapshot targets only its exact table.
- **Prior coverage preserved:** these cases precede and complement the historical one-candidate, ambiguity, alternate-collation, generic conflict, missing/prefix, authorization matrix, external equality, public chain, and fresh reload assertions. Their expectations remain unchanged.

The RED occurs on the first new adversarial case after the normative primary v4/repeat path passes. It is not a setup or catalog-query failure and captures the unsafe broad-normalization behavior before Gate 4 correction.

**Fresh Gate 3 verdict: `APPROVED`. Gate 4 may narrow semantic parsing and support the exact safe-name boundary; independent Gate 5 must restart.**

## v0.2 engineer-position grouping adversary

The non-equivalent precedence fixture is approved.

Fresh RED:

```text
PHP Fatal error:  Uncaught TestFailure: Non-equivalent engineer CHECK grouping must conflict even when parenthesis-stripped text resembles the normative expression.
Expected: array (
  'applied' => false,
  'schemaVersion' => 4,
  'reason' => 'SCHEMA_MIGRATION_CONFLICT',
  'conflictingTables' =>
  array (
    0 => 'grouped_fm2_process_user_capabilities',
  ),
)
Actual: array (
  'applied' => true,
  'schemaVersion' => 4,
  'constraintsChanged' =>
  array (
    0 => 'ck_fm2_process_user_capability',
  ),
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_command_authorization_001_test.php(117): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`.

- **Non-equivalent precedence:** normative logic is `capability <> engineer OR (position non-null AND nonblank)`, so prepare rows may have null position. The adversary is `(capability <> engineer OR position non-null) AND nonblank`, which additionally requires a nonblank position for prepare. Removing parentheses makes their token sequences deceptively similar, but their truth tables differ materially.
- **Valid fixture and independent sentinel:** the exact v3 capability CHECK is valid. The prepare sentinel deliberately has a non-null/nonblank position, and the engineer sentinel also has a valid position, so MariaDB accepts both under the corrupt grouping. The test does not rely on inserting a row that violates its own fixture constraint.
- **Sensitivity:** current production strips parentheses during normalization, accepts the grouped engineer CHECK as normative, and returns applied success. The exact expected conflict therefore detects precisely the precedence loss rather than an unrelated schema mismatch.
- **No mutation:** complete pre/post `commandAuthTableState` equality follows the conflict assertion and covers `SHOW CREATE TABLE` (both CHECKs and names), rows, and AUTO_INCREMENT. Once the production preflight correctly rejects, any DROP/ADD, row change, rename, or other DDL before conflict will fail.
- **Isolation:** the valid `grouped_` prefix/table is distinct from quoted, dollar, historical, ambiguous, binary, conflict, and main fixtures inside the unique disposable database. Correct table-name catalog joins prevent cross-fixture clauses from influencing classification.
- **Spec mapping:** v0.2 requires the engineer CHECK to constrain position only for `construction_control_engineer` and declares altered position semantics a conflict. This fixture adds no new outcome and remains within the approved migration boundary.

The earlier quoted-literal adversary now passes before this new RED, showing the failure is specifically grouping/precedence classification. All subsequent safe-name, wrong-v4-name, historical, ambiguity, collation, authorization, public-chain, and reload coverage remains unchanged.

**Fresh Gate 3 verdict: `APPROVED`. Gate 4 may correct semantic parsing to preserve boolean grouping; independent Gate 5 must restart.**

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_command_authorization_001_test.php:72
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/process_command_authorization_001_test.php on line 72
```

Exit code: `255`.

The isolated database, production v1/v2 migrations, legacy user/role tables, and v3 capability migration all complete and v3 rows are seeded before the intended absent v4 migration class is referenced. The RED is not caused by MariaDB connectivity, prefix setup, or an inherited migration failure.

## Findings

- **V4 traceability and named CHECK replacement:** the test starts from production v3, records exact rows, requires the exact apply result, and then observes exactly the two normative constraint names with independently fixed normalized clauses. It detects failure to add either command capability, accidental broad values, loss/change of the engineer-position rule, extra CHECKs, or leaving anonymous constraints.
- **Schema preservation:** strict information-schema literals cover exact columns/order/types/nullability/charset, InnoDB/general collation, exact composite primary/index definitions with no extras, and no foreign keys. Existing v3 prepare/engineer rows must remain byte-for-byte equal. Repeat compares complete `SHOW CREATE TABLE`, ordered rows, and AUTO_INCREMENT state, making DDL/DML on exact v4 observable.
- **Conflict/missing/prefix:** an extra-column near-v3 table must return the exact conflict result and preserve complete schema/data state; an absent prefixed source must return its exact table name; an invalid prefix must throw before SQL. This provides a concrete non-exact-source sensitivity case without attempting to enumerate every catalog corruption already covered by strict production preflight requirements.
- **Capability converse and engineer separation:** users `91`–`94` independently carry only prepare, confirm, open, or engineer capability. Exact four-value rows reject using any command capability for another command, treating engineer as command authority, or treating command authority as engineer identity. Engineer `94` must return the exact canonical snapshot and command-only users must return `null`.
- **No `users_rights2roles`:** that table is deliberately absent. Any legacy-rights fallback query fails at MariaDB rather than silently satisfying an assertion.
- **Public production chain:** actor `18` receives the three distinct rows plus an active user/role. The real directory is routed into real MariaDB process persistence; exact prepare, confirm, and open results prove all production command entry points can use it. A new connection with an all-methods-forbidden delegate must hydrate the complete independently literal opened projection, including root fields, immutable history, gates, no tasks, and exactly three events.
- **Expected-value independence:** migration results/schema clauses, matrix booleans/snapshot, command results, and full reload projection are specification literals. They are not obtained from adapter output, SQL rows after behavior, command results, or production constants.
- **Resolved user-status sensitivity:** inactive user `97` otherwise satisfies active role and every capability/position precondition, yet all four directory outcomes must deny access/identity.
- **Resolved external immutability:** exact equality covers all five external source/configuration groups used or present in the production chain.
- **Determinism/isolation:** all data, renderer bytes, clock sequence, expected values, and prefix are fixed; only the exact randomly named database is created/dropped in `finally`. Parallel runs share no tables and no demo/legacy database state.
- **Scope:** capability administration UI/audit, legacy rights mapping, rejected process security-audit persistence, transport authentication, concurrency/failure semantics, and destructive rollback remain correctly excluded.

## Previously required changes (resolved)

1. Completed: user `97` isolates inactive user status with active role and sufficient command/engineer capabilities.
2. Completed: legacy and Workforce join users/roles/capabilities in exact external equality; no process-table assertion was added.
3. Completed: all prior strengths and the intended RED remain and were independently rerun.
