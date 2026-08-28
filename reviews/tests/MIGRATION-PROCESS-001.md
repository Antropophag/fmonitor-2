# Test review: MIGRATION-PROCESS-001

- Reviewer: `Codex agent /root/migration_test_review` (independent initial review and re-review; did not author the specification, test, fixture changes, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/MIGRATION-PROCESS-001.md`](../../specs/MIGRATION-PROCESS-001.md), version `0.1`, `APPROVED 2026-08-28`
- Inherited behavior: [`specs/PERSISTENCE-PREPARE-001.md`](../../specs/PERSISTENCE-PREPARE-001.md), version `0.3`, Gate 3 and Gate 5 `APPROVED`
- Public seam: `FMonitor2\InstallationProcess\ProductionProcessSchemaMigration::apply(connection, tablePrefix)`; inherited post-migration observation through `InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(...)`
- Red command: `php tests/InstallationProcess/migration_process_001_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- First re-review verdict: `APPROVED`
- Gate 5 restart intermediate verdict: `CHANGES_REQUESTED`
- Current Gate 5 restart verdict: `APPROVED`
- Index-fingerprint Gate 5 restart verdict: `APPROVED`
- Ignored-index Gate 5 restart verdict: `APPROVED`
- Per-column-charset Gate 5 restart verdict: `APPROVED`
- Check-isolation/utf8mb4-collation Gate 5 restart verdict: `APPROVED`

## Check-isolation and alternate-utf8mb4-collation Gate 5 restart review

The current revision correctly separates character-set compatibility from non-normative collation strictness and fixes the test's own parallel-prefix catalog isolation:

- `migrationSchemaFingerprint()` now joins `CHECK_CONSTRAINTS` to `TABLE_CONSTRAINTS` by constraint schema, table name, and constraint name. Repeated MariaDB JSON-check names on concurrently existing prefixed tables can no longer cross-match into another fixture's fingerprint;
- the test keeps all independently randomized schemas alive through the combined assertion, so successful completion of every earlier fingerprint/no-mutation assertion exercises concurrent-prefix catalog isolation in one database;
- a new isolated schema changes only `fm2_installation_cases.process_state` to the still-valid `utf8mb4_bin` column collation. The test first proves the catalog reports exactly `CHARACTER_SET_NAME = utf8mb4` and `COLLATION_NAME = utf8mb4_bin`;
- the expected compatible-repeat result is the independent specification literal `applied = false`, `schemaVersion = 1`, `tablesCreated = []`. Section 3 requires `utf8mb4` and does not prescribe one exact `utf8mb4_*` collation, while section 6 rejects charset incompatibility; therefore the earlier latin1 rejection and this utf8mb4 acceptance are complementary rather than contradictory.

The compatible case proves no mutation. A fixed installation-case sentinel is inserted before `apply`; the complete charset/collation-sensitive catalog fingerprint and complete row are strictly equal afterward. An implementation that normalizes the collation, recreates schema, or changes data before returning a no-op fails. Random hexadecimal naming isolates the table set, and `finally` removes exactly that prefix.

The reviewer reran `php tests/InstallationProcess/migration_process_001_test.php`. All six incompatible-schema cases now produce their expected conflicts. The single RED is the new compatible `utf8mb4_bin` case, which production incorrectly reports as a conflict:

```text
PHP Fatal error:  Uncaught TestFailure: Compatibility must reject non-utf8mb4 columns while accepting an alternate utf8mb4 collation.
Expected result 6:
  applied = false
  schemaVersion = 1
  tablesCreated = []
Actual result 6:
  applied = false
  schemaVersion = 1
  reason = SCHEMA_MIGRATION_CONFLICT
  conflictingTables = [t_mp001_utf8coll_6394ffc55211_fm2_installation_cases]
in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php(415): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. The displayed prefix is only the isolated namespace from this run. Catalog observation and both no-mutation assertions complete before the intended aggregate assertion fails.

No blocking test findings remain. The CHECK-isolation/utf8mb4-collation Gate 3 restart is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Per-column-charset Gate 5 restart review

The revised test is sensitive to a character column that overrides the normative `utf8mb4` table character set:

- the reusable column fingerprint now includes MariaDB's literal `CHARACTER_SET_NAME` and `COLLATION_NAME` for every column;
- an isolated migrated schema changes only `fm2_assignment_orders.control_engineer_fio_snapshot` to `CHARACTER SET latin1`, a text snapshot required to preserve Cyrillic production names;
- before `apply`, the test independently locates exactly one affected column and requires `CHARACTER_SET_NAME = latin1` plus a `latin1_` collation;
- the expected outcome is the specification's stable `SCHEMA_MIGRATION_CONFLICT` naming only the deliberately changed assignment-orders table.

The sentinel is valid and meaningful for the no-mutation proof. It creates the required parent installation case and a complete assignment order with fixed ASCII values, including `Sentinel Engineer`; ASCII is intentionally representable in both encodings, so fixture insertion succeeds and does not confuse charset incompatibility with invalid setup. The complete assignment-order row and full charset/collation-sensitive catalog fingerprint are compared before and after the migration. An implementation that converts the column, recreates the table, or changes/deletes the row before returning conflict fails.

The scenario is deterministic and isolated. All schema/data/expected literals are fixed; a hexadecimal random prefix only isolates table names; cleanup drops exactly that prefix in `finally`. It does not touch the unprefixed schema, legacy tables, or the separate foreign database fixture.

The reviewer reran `php tests/InstallationProcess/migration_process_001_test.php`. All five earlier adversarial cases now return the reviewed conflicts; only the new latin1 column is incorrectly accepted as a compatible repeat:

```text
PHP Fatal error:  Uncaught TestFailure: Compatibility must reject non-normative checks, FK schemas, index properties and per-column character sets.
Expected result 5:
  applied = false
  schemaVersion = 1
  reason = SCHEMA_MIGRATION_CONFLICT
  conflictingTables = [t_mp001_latin_ee951a9f7186_fm2_assignment_orders]
Actual result 5:
  applied = false
  schemaVersion = 1
  tablesCreated = []
in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php(403): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. The random prefix is only this run's isolated namespace. Charset observation and both immutability assertions complete before the intended final assertion fails.

No blocking test findings remain. The per-column-charset Gate 3 restart is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Ignored-index Gate 5 restart review

The revised test closes the MariaDB `IGNORED`-index sensitivity gap from the current Gate 5 review:

- the all-absent schema assertion and reusable catalog fingerprint now append the literal `information_schema.STATISTICS.IGNORED` value to every indexed column and require `NO` for every normative index;
- an isolated migrated schema marks the required `(status, assignee_role, due_date)` task index `IGNORED` through MariaDB's public DDL;
- before invoking the migration, the test independently requires the catalog literal `status:FULL:A:YES`, proving that the pinned MariaDB runtime supports `ALTER INDEX ... IGNORED` and exposes the changed optimizer state;
- the expected result is the specification's literal `SCHEMA_MIGRATION_CONFLICT` naming only the deliberately incompatible `fm2_process_tasks`, not a value derived from production behavior.

Sensitivity and preflight immutability are adequate. The complete pre/post fingerprint includes the ignored attribute along with table, column, key, prefix/direction, referenced-schema and check data. A fixed installation-case parent and task sentinel are persisted before `apply`, and the complete task row is asserted unchanged afterward. An implementation that unignores/recreates the index or changes process data before returning conflict fails the test.

The scenario is deterministic and isolated. Its DDL, expected catalog marker and sentinel values are fixed; the random hexadecimal prefix only prevents namespace collisions; `finally` drops exactly that prefix. It does not require the cross-schema admin connection used by the separately approved FK fixture.

The reviewer reran `php tests/InstallationProcess/migration_process_001_test.php`. All four previously reviewed incompatibilities now return their expected conflicts; only the new ignored-index case remains RED:

```text
PHP Fatal error:  Uncaught TestFailure: Compatibility must reject non-normative checks, FK schemas, prefix, descending and ignored indexes.
Expected result 4:
  applied = false
  schemaVersion = 1
  reason = SCHEMA_MIGRATION_CONFLICT
  conflictingTables = [t_mp001_ignored_c83ae8d7e9fe_fm2_process_tasks]
Actual result 4:
  applied = false
  schemaVersion = 1
  tablesCreated = []
in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php(390): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. The displayed random prefix is only this run's isolated namespace. The runtime-support observation and no-mutation assertions complete before the intended result assertion fails.

No blocking test findings remain. The ignored-index Gate 3 restart is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Index-fingerprint Gate 5 restart review

The current Gate 2 revision closes the `SUB_PART`/direction sensitivity gap identified by the updated Gate 5 review:

- the normative all-absent catalog assertion now encodes every non-unique index column as `COLUMN_NAME:SUB_PART:COLLATION` and independently requires full-column (`FULL`) ascending (`A`) entries;
- the prefix-index fixture replaces the contracted task index with the fixed incompatible `status(1), assignee_role, due_date`, then explicitly proves MariaDB reports `status:1:A` before exercising the migration;
- the descending-index fixture replaces the contracted event index with `installation_case_id, occurred_at DESC`, then explicitly proves MariaDB reports `occurred_at:FULL:D` before exercising the migration;
- both expected outcomes are literal `SCHEMA_MIGRATION_CONFLICT` results naming the one deliberately changed table, derived from the section 3 full ascending index contract rather than current production output.

Both rejected cases prove preflight immutability. Their complete catalog fingerprints include tables, columns, keys (including referenced schema), indexes (including prefix length and direction), and checks before and after `apply`. Fixed sentinel task/event rows are also compared exactly. An implementation that repairs, drops or recreates the incompatible index, or mutates process data before returning a conflict, fails these assertions.

The scenarios are deterministic and isolated. Fixed DDL and sentinel literals determine behavior; random hexadecimal prefixes only provide collision-free table namespaces. Cleanup targets the two exact new prefixes in `finally`, alongside the previously approved isolated fixtures. The pinned MariaDB runtime demonstrably supports and exposes descending index direction because the independent precondition assertion reaches `occurred_at:FULL:D` before the migration call.

The reviewer reran `php tests/InstallationProcess/migration_process_001_test.php`. Existing extra-`CHECK` and cross-schema-FK conflicts pass, while both new index cases remain RED for the intended missing compatibility behavior:

```text
PHP Fatal error:  Uncaught TestFailure: Compatibility must reject non-normative checks, FK schemas, prefix indexes and descending indexes.
Expected results 2 and 3:
  prefix index => SCHEMA_MIGRATION_CONFLICT, conflictingTables = [t_mp001_subpart_474d446ef8fd_fm2_process_tasks]
  descending index => SCHEMA_MIGRATION_CONFLICT, conflictingTables = [t_mp001_desc_f8298de1dd5c_fm2_process_events]
Actual results 2 and 3:
  array ('applied' => false, 'schemaVersion' => 1, 'tablesCreated' => array ())
  array ('applied' => false, 'schemaVersion' => 1, 'tablesCreated' => array ())
in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php(375): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. The generated prefixes shown above identify only this isolated run. All catalog-observation and no-mutation assertions complete before the final result assertion fails.

No blocking test findings remain. The index-fingerprint Gate 3 restart is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Final Gate 5 restart re-review

The reviewer reopened the current shared test after the author completed the last edit. The earlier second-re-review blocker below was a shared-filesystem timing race and is superseded: the current `migrationSchemaFingerprint()` does include both required catalog dimensions:

- `k.REFERENCED_TABLE_SCHEMA` in the key fingerprint at test line 72;
- normalized table identity and `cc.CHECK_CLAUSE` for every prefixed `CHECK` constraint at test line 74.

The exact pre/post fingerprint comparisons therefore detect removal or alteration of `restrictive_process_state`, redirection of the cross-schema FK, or any other change to the captured tables, columns, keys, indexes and checks. The fixed sentinel comparisons independently detect DML against the local installation case, local process event, and foreign parent. Together they prove the section 6 no-DDL/no-DML preflight invariant for both new rejected cases.

The fixtures remain deterministic and isolated: fixed constraint/data literals determine behavior; generated hexadecimal names only provide collision-free namespaces; the admin fixture creates and finally removes one exact random database; local cleanup targets only exact prefixes. The expected `SCHEMA_MIGRATION_CONFLICT` results and affected-table lists are literal consequences of the approved schema rather than implementation output.

The reviewer reran `php tests/InstallationProcess/migration_process_001_test.php` against this current file. It remains RED solely because the implementation accepts both incompatible schemas as compatible repeats:

```text
PHP Fatal error:  Uncaught TestFailure: Compatibility must reject extra restrictive checks and same-named foreign-key targets in another database.
Expected: array (
  0 => array (
    'applied' => false,
    'schemaVersion' => 1,
    'reason' => 'SCHEMA_MIGRATION_CONFLICT',
    'conflictingTables' => array (
      0 => 't_mp001_check_b17e08df157c_fm2_installation_cases',
    ),
  ),
  1 => array (
    'applied' => false,
    'schemaVersion' => 1,
    'reason' => 'SCHEMA_MIGRATION_CONFLICT',
    'conflictingTables' => array (
      0 => 't_mp001_cross_7fb49be9383d_fm2_process_events',
    ),
  ),
)
Actual: array (
  0 => array ('applied' => false, 'schemaVersion' => 1, 'tablesCreated' => array ()),
  1 => array ('applied' => false, 'schemaVersion' => 1, 'tablesCreated' => array ()),
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php(345): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. Generated prefixes differ per run and are namespace details, not expected domain values. All immutability assertions pass before the intended final result assertion fails.

No blocking findings remain. Gate 3 is `APPROVED`; Gate 4 may resume against the current reviewed test without changing its expectations.

## Second Gate 5 restart re-review

The latest revision partially resolves the prior immutability blocker:

- the extra-`CHECK` fixture now contains a fixed installation-case sentinel and compares its complete row before and after `apply`;
- the cross-schema fixture now contains a fixed foreign parent and a fixed local event child and compares both complete rows before and after `apply`.

These assertions are deterministic, isolated by the existing random hexadecimal namespaces, and sensitive to deletion or rewriting of the relevant process data. Their values are fixture literals rather than implementation output.

The reviewer reran `php tests/InstallationProcess/migration_process_001_test.php`. The intended compatibility RED remains reproducible:

```text
PHP Fatal error:  Uncaught TestFailure: Compatibility must reject extra restrictive checks and same-named foreign-key targets in another database.
Expected: array (
  0 =>
  array (
    'applied' => false,
    'schemaVersion' => 1,
    'reason' => 'SCHEMA_MIGRATION_CONFLICT',
    'conflictingTables' =>
    array (
      0 => 't_mp001_check_4a85e163d46d_fm2_installation_cases',
    ),
  ),
  1 =>
  array (
    'applied' => false,
    'schemaVersion' => 1,
    'reason' => 'SCHEMA_MIGRATION_CONFLICT',
    'conflictingTables' =>
    array (
      0 => 't_mp001_cross_c0fa14cab5e9_fm2_process_events',
    ),
  ),
)
Actual: array (
  0 => array ('applied' => false, 'schemaVersion' => 1, 'tablesCreated' => array ()),
  1 => array ('applied' => false, 'schemaVersion' => 1, 'tablesCreated' => array ()),
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php(345): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. The generated prefixes vary by isolated run and are not expected domain values.

### Remaining blocking finding

The catalog immutability assertions at test lines 339 and 341 claim to include `CHECK` clauses and the referenced schema, but `migrationSchemaFingerprint()` at lines 66–74 includes neither:

- its `keys` query selects referenced table and column but omits `REFERENCED_TABLE_SCHEMA`;
- it has no query for `TABLE_CONSTRAINTS`/`CHECK_CONSTRAINTS` and therefore records no `CHECK_CLAUSE` at all.

Consequently, an implementation that drops `restrictive_process_state`, redirects the cross-schema FK back to the same-named local table, and then returns the expected conflict leaves the current fingerprint and sentinel rows equal. It would pass despite performing forbidden DDL during preflight. The assertion messages do not create the missing sensitivity.

### Required change

Extend `migrationSchemaFingerprint()` itself (or use equally strict dedicated pre/post catalog queries) to include normalized complete `CHECK` constraints and `REFERENCED_TABLE_SCHEMA`, then retain the existing exact pre/post comparisons and sentinel-row assertions. Rerun the focused test and request another independent Gate 3 review. Gate 4 remains paused.

## Gate 5 restart re-review

The Gate 2 revision adds two independently motivated incompatible-schema fixtures from the Gate 5 findings:

- a complete migrated schema whose `fm2_installation_cases` receives the fixed additional restrictive constraint `CHECK (process_state = 'needs_assignment_order')`;
- a separately prefixed complete schema whose `fm2_process_events.installation_case_id` foreign key is replaced with a reference to an identically named installation-case table in a different, uniquely named database.

The expected conflict reason and each single affected table are literal consequences of sections 3 and 6 of `MIGRATION-PROCESS-001`, not values obtained from the implementation. Both mutations are observable, plausible incompatibilities while preserving the otherwise approved table shape.

The reviewer reran `php tests/InstallationProcess/migration_process_001_test.php`. The test is RED because the current implementation incorrectly accepts both schemas as compatible:

```text
PHP Fatal error:  Uncaught TestFailure: Compatibility must reject extra restrictive checks and same-named foreign-key targets in another database.
Expected: array (
  0 =>
  array (
    'applied' => false,
    'schemaVersion' => 1,
    'reason' => 'SCHEMA_MIGRATION_CONFLICT',
    'conflictingTables' =>
    array (
      0 => 't_mp001_check_89e0d1cce0d4_fm2_installation_cases',
    ),
  ),
  1 =>
  array (
    'applied' => false,
    'schemaVersion' => 1,
    'reason' => 'SCHEMA_MIGRATION_CONFLICT',
    'conflictingTables' =>
    array (
      0 => 't_mp001_cross_07b5851c480c_fm2_process_events',
    ),
  ),
)
Actual: array (
  0 =>
  array (
    'applied' => false,
    'schemaVersion' => 1,
    'tablesCreated' =>
    array (
    ),
  ),
  1 =>
  array (
    'applied' => false,
    'schemaVersion' => 1,
    'tablesCreated' =>
    array (
    ),
  ),
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php(325): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. The random prefixes in this captured run are test-namespace details; the expected semantic values are the fixed reason and the independently selected affected table in each isolated namespace.

### Findings

- **Sensitivity to the Gate 5 false positives:** strict paired result equality fails if either extra `CHECK` or cross-schema FK is treated as a compatible repeat. The observed RED proves both current false-positive paths reach the final assertion.
- **Determinism:** all schema definitions, constraint expressions, target columns and expected outcomes are fixed. The existing FK is discovered by its unique table/column relationship before replacement; there is exactly one such row. Random bytes affect only collision-resistant valid identifiers, not behavior or business expectations.
- **Isolation and cleanup:** each case has its own hexadecimal prefix. The cross-schema case uses a separately randomized database through explicit test-admin credentials. `finally` drops exact prefixed local tables before dropping the exact foreign database, including after the observed assertion failure. No legacy, demo-contract, or unprefixed process table is modified.
- **Blocking no-mutation sensitivity gap:** section 6 requires a conflict to be detected before any DDL or DML and requires existing schema and data to remain unchanged. The two new scenarios assert only the returned arrays after calling `apply`. They do not snapshot/re-observe the extra `CHECK`, referenced schema, or any sentinel data. An implementation can drop the restrictive check, redirect the FK back to the local schema, delete rows, or otherwise mutate either fixture and then return the exact expected conflict; the revised test still passes. This also falls short of the Gate 5 request to prove rejection *before* DDL/DML.

### Required changes

1. Before each conflicting `apply`, capture an independent catalog fingerprint that includes the complete `CHECK` clauses and, for foreign keys, `REFERENCED_TABLE_SCHEMA`; after the expected conflict, assert exact equality with the corresponding pre-call fingerprint. A focused literal assertion that the extra check and foreign schema still exist may supplement but should not replace an unchanged-schema comparison.
2. Add fixed sentinel rows valid under each conflicting fixture and assert they remain exactly unchanged after `apply`, so DML before returning `SCHEMA_MIGRATION_CONFLICT` is observable. For the cross-schema case, seed the referenced foreign parent and local child consistently.
3. Rerun the focused test and retain RED on the two missing compatibility checks, not on fixture construction, permissions, cleanup, or the new immutability assertions. Obtain a fresh independent Gate 3 approval before amending implementation.

## Re-review after requested changes

The revised test resolves every blocking finding from the initial review:

- it creates only the installation-case precondition after migration, invokes the independently approved `PERSISTENCE-PREPARE-001` command through `InstallationProcess`, then closes the original connection and reconstructs the module with a new MariaDB connection and an external delegate that throws on every read;
- it asserts the complete independent command result and complete inherited public projection, including the immutable object, installer and engineer snapshots, both artifact hashes, preliminary assignments, absence of tasks/opening/checklist access, and the one append-only event;
- its compatible partial-deployment fixture pre-creates only the complete normative `fm2_installation_cases`, asserts that exactly the remaining five tables are returned in dependency order, and compares the recovered full catalog fingerprint with the independently checked all-absent migration;
- column assertions now cover `EXTRA` and require `auto_increment` on all four normative generated identifiers; a catalog check requires MariaDB `JSON_VALID(payload_json)` enforcement;
- exact literal assertions now cover every primary-key and unique-key column sequence, all six foreign-key mappings and `RESTRICT` delete rules, the three contracted secondary indexes, and the two explicitly accounted MariaDB foreign-key support indexes. Extra keys or non-unique indexes cause strict equality to fail.

The inherited expected values remain literal approved values rather than values read from SQL or migration output. The repeat is placed after the successful process write and before reconstruction, so clearing/recreating tables or duplicating facts is detected by the full reloaded projection. Random hexadecimal prefixes affect only isolated table namespaces, while fixed facts, clock and renderer bytes keep business expectations deterministic.

The reviewer reran `php tests/InstallationProcess/migration_process_001_test.php`. The revised test remains RED for the intended absent production migration seam:

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProductionProcessSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php:131
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php on line 131
```

Exit code: `255`.

MariaDB connection and bootstrap complete before the missing-class failure. No fixture or catalog assertion is reached prematurely, and inspection confirms that each strengthened assertion observes the public migration/catalog/persistence seams rather than production internals. Gate 3 is now `APPROVED`; Gate 4 may proceed without changing the reviewed specification expectations or test.

## Initial review history (`CHANGES_REQUESTED`)

### Captured red result

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\ProductionProcessSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php:116
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/migration_process_001_test.php on line 116
```

Exit code: `255`.

The independent run reproduced a RED caused by the absent production migration class. Bootstrap and the MariaDB connection complete before the failure, so this is the intended missing production seam rather than broken database setup. The RED is exact but is not sufficient for approval because several normative outcomes are currently untested.

### Findings

- **Traceability present:** the test cites `MIGRATION-PROCESS-001 v0.1` and exercises the approved migration seam with an isolated prefix. It covers the all-absent success result, a compatible repeat, an incompatible-table preflight rejection, and invalid-prefix rejection.
- **Blocking inherited-seam gap:** sections 4 and 7 require the migrated schema to support the independently approved `PERSISTENCE-PREPARE-001` example through `InstallationProcess`, followed by reload through a new module and DB connection without external fact reads. The test instead inserts a hand-written order and event directly and only counts those two rows. An implementation can create catalog-compatible tables that are unusable by `MariaDbInstallationProcessEnvironment` and still pass. It also does not prove the required complete public business projection or expected-value independence from migration/adapter output.
- **Blocking interrupted-deployment gap:** section 6 requires recovery from a compatible partial prefix by creating only the missing tables in dependency order and returning only those names. No such setup, result assertion, or final full-schema assertion exists. An implementation that treats every partial schema as a no-op or conflict can pass the current test.
- **Blocking exact-schema sensitivity gaps:** column checks do not assert `AUTO_INCREMENT`, so ordinary integer primary keys pass despite the literal contract. `payload_json` normalization treats any `LONGTEXT` as `JSON`, so a column without MariaDB's JSON validity constraint passes. Key checks assert only counts: they do not verify primary-key columns/order, unique-key columns, foreign-key column-to-target mappings, or the required default rejecting delete behavior. The secondary-index check filters for three desired shapes but does not reject extra explicit secondary indexes. These false-positive implementations violate section 3 while satisfying the test.
- **Schema coverage otherwise present:** all 68 column names, order, base types, unsigned markers and nullability are literal; six prefixed tables, InnoDB, utf8mb4, and aggregate key counts are observed via MariaDB's public catalog. The repeat scenario's fixed one-order/one-event counts are sensitive to table clearing, recreation, or duplicate data.
- **Rejected cases:** the incompatible single-table case checks the exact stable reason and normative conflict list and proves the other five tables remain absent. Invalid-prefix rejection checks the exception type, but does not prove the required “before MariaDB access” ordering; this is a smaller sensitivity gap than the missing normative success and recovery paths.
- **Determinism and isolation:** fixed catalog literals and data make assertions deterministic. Random safe prefixes isolate concurrent runs, and `finally` removes only the two exact test prefixes. The test does not modify unprefixed production, demo, or legacy tables.
- **Expected-value independence:** schema and migration-result literals come from the approved specification. The two preservation counts are also fixed literals. The required inherited business expectation is absent rather than derived incorrectly.

### Required changes

1. After applying the migration, create only the documented installation-case precondition, then execute the approved `PERSISTENCE-PREPARE-001` command through `InstallationProcess` and assert its full independent literal result and full projection after reconstructing the module with a new DB connection and an external delegate that fails on every read. Do not create target schema, order, installer, artifact, task, or event rows in fixture SQL.
2. Add a compatible partial-prefix scenario. Pre-create a dependency-safe compatible prefix of the normative tables, call `apply`, assert `applied = true` and `tablesCreated` contains exactly the missing tables in dependency order, then assert the complete section 3 catalog contract.
3. Strengthen catalog assertions to prove every literal key and column property in section 3: `AUTO_INCREMENT`; actual MariaDB JSON enforcement; exact primary and unique column sequences; exact foreign-key mappings and rejecting delete rules; and absence of uncontracted explicit secondary indexes (while accounting explicitly for indexes MariaDB necessarily creates to support foreign keys).
4. Rerun `php tests/InstallationProcess/migration_process_001_test.php` and retain a RED caused by missing migration behavior rather than fixture/schema setup. Gate 4 must not begin until the revised test receives a fresh independent Gate 3 approval.
