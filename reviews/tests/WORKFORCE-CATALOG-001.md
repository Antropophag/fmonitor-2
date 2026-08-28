# Test review: WORKFORCE-CATALOG-001

- Reviewer: `Codex agent /root/migration_test_review` (independent; did not author the specification, test, migration, or delegate)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/WORKFORCE-CATALOG-001.md`](../../specs/WORKFORCE-CATALOG-001.md), version `0.1`, `APPROVED 2026-08-28`
- Inherited contracts: `MIGRATION-PROCESS-001`, `ORDER-PREPARE-003`, and `PERSISTENCE-PREPARE-001`
- Technical seam: `WorkforceCatalogSchemaMigration::apply(connection, tablePrefix)`
- Public behavior seam: `InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(...)` through production MariaDB process persistence and Workforce delegate
- Red command: `php tests/InstallationProcess/workforce_catalog_001_test.php`
- Initial verdict: `CHANGES_REQUESTED`
- Intermediate re-review verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`
- UNIQUE-vs-PK Gate 5 restart verdict: `APPROVED`

## UNIQUE-vs-primary-key Gate 5 restart review

The new adversarial fixture is independently sensitive to the exact normative constraint identity rather than merely an equivalent uniqueness index shape:

- it creates all eight approved columns, the required status index and exact employment-status check, but deliberately declares `UNIQUE KEY (installer_tab_id)` instead of `PRIMARY KEY`;
- a precondition assertion reads `information_schema.TABLE_CONSTRAINTS` and requires exactly one `UNIQUE` constraint named `installer_tab_id` and no `PRIMARY KEY`, proving MariaDB exposes the intended distinction before migration;
- because the remaining schema matches the approved contract, accepting it as compatible isolates the precise Gate 5 regression rather than a gross setup mismatch;
- the expected `SCHEMA_MIGRATION_CONFLICT` and exact affected prefixed table are direct literals from specification sections 2–3.

No-mutation sensitivity is adequate. `workforceDatabaseState()` captures every table's `SHOW CREATE TABLE` and complete rows before `apply`, including the UNIQUE constraint identity and fixed Cyrillic sentinel. Strict post-call equality detects replacing UNIQUE with PRIMARY, rebuilding the table, changing constraints/indexes, or any DML before returning conflict.

The case is deterministic and isolated. Fixed DDL/data/expectations determine behavior; `unique_only_` is isolated inside the test's random hexadecimal database, so simultaneous test processes cannot collide. Existing `finally` cleanup drops only that exact random database.

The reviewer reran `php tests/InstallationProcess/workforce_catalog_001_test.php`. All previously reviewed migration and public-command behavior reaches the new case; the current implementation incorrectly accepts UNIQUE-only as a compatible repeat:

```text
PHP Fatal error:  Uncaught TestFailure: UNIQUE NOT NULL must not substitute for the normative PRIMARY KEY.
Expected: array (
  'applied' => false,
  'schemaVersion' => 2,
  'reason' => 'SCHEMA_MIGRATION_CONFLICT',
  'conflictingTables' => array (
    0 => 'unique_only_fm2_workforce_catalog',
  ),
)
Actual: array (
  'applied' => false,
  'schemaVersion' => 2,
  'tablesCreated' => array (),
) in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:27
Stack trace:
#0 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/workforce_catalog_001_test.php(154): assertSameValue()
#1 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/bootstrap.php on line 27
```

Exit code: `255`. The complete-state immutability assertion passes before the intended exact-result assertion fails.

No blocking test findings remain. The UNIQUE-vs-PK Gate 3 restart is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Final re-review

The current test removes the final false-positive path. Immediately after additive v2 apply it now enumerates **all** tables in `DATABASE()` without a name filter and strictly requires exactly the six prefixed v1 process tables plus `process_fm2_workforce_catalog`. Because the database is newly created and isolated for this test, any hidden metadata, history, auxiliary, differently prefixed or unprefixed table makes the assertion fail. The separate zero-row assertion still proves the one new catalog starts empty.

All prior strengthened checks remain present: exact v2 schema, v1 schema/data/auto-increment preservation, complete database state around compatible repeat and conflict, catalog read-only equality around the command, explicit competing sync mutation, and complete independently literal persisted projection after reconnect with all external reads forbidden.

The reviewer reran `php tests/InstallationProcess/workforce_catalog_001_test.php` from the current shared file. The test remains RED for the intended absent additive migration behavior:

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/workforce_catalog_001_test.php:86
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/workforce_catalog_001_test.php on line 86
```

Exit code: `255`. The isolated database, v1 migration, process sentinel, and v1 schema capture complete before the missing v2 class is reached.

No blocking findings remain. Gate 3 is `APPROVED`; Gate 4 may proceed without changing the reviewed expectations.

## Re-review after Gate 2 changes

The revision resolves four substantive initial findings:

- it asserts the fresh Workforce table is empty before inserting the external-sync fixture;
- compatible repeat and conflict now compare `workforceDatabaseState()` before/after, covering every existing table's `SHOW CREATE TABLE` and complete rows, so ordinary DDL, DML, keys and auto-increment changes are observable;
- the simulated sync update is followed by an exact competing current-catalog row assertion;
- reload now uses the complete independently literal `PERSISTENCE-PREPARE-001` projection, including process state, object/installer/engineer snapshots, artifacts, assignments, event, tasks and work/checklist gates.

Expected Workforce and projection values remain fixed literals. The full projection helper does not compute values from SQL, the production delegate or command output. Strict equality preserves sensitivity to all inherited facts.

The reviewer reran `php tests/InstallationProcess/workforce_catalog_001_test.php`. The revised test remains RED at the missing additive migration seam:

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/workforce_catalog_001_test.php:86
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/workforce_catalog_001_test.php on line 86
```

Exit code: `255`. Production v1 migration, the process sentinel and v1 schema capture complete before the intended absent v2 class fails.

### Remaining blocking finding

The fresh-additive table-set assertion at lines 89–91 queries only names matching `{$prefix}fm2_%`. It proves the expected seven `process_fm2_*` tables exist, but it does not prove the migration created *only* `process_fm2_workforce_catalog`. In this otherwise empty isolated database, an implementation can additionally create `process_workforce_metadata`, `schema_versions`, or any other table outside that LIKE pattern, return the expected single `tablesCreated` entry, and pass every later assertion because `workforceDatabaseState()` merely snapshots that extra table equally before/after repeat.

This violates sections 2 and 3, which define one additive table and require creation of only that table. The isolation design makes the stronger assertion simple and deterministic.

### Required change

Immediately after first v2 apply, enumerate **all** tables in `DATABASE()` and require exactly the six prefixed v1 tables plus the one prefixed Workforce table. Do not filter the catalog query to names already expected by the implementation contract. Retain the existing empty-table assertion and all strengthened repeat/conflict/public-projection checks, rerun RED, and request another independent Gate 3 review. Gate 4 remains paused.

## Captured red result

```text
PHP Fatal error:  Uncaught Error: Class "FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration" not found in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/workforce_catalog_001_test.php:50
Stack trace:
#0 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/workforce_catalog_001_test.php on line 50
```

Exit code: `255`.

The independent run creates a uniquely named MariaDB database, applies production process schema v1, inserts the v1 sentinel case and captures the v1 schema before failing at the absent additive v2 migration class. The RED is caused by missing production behavior, not bootstrap, DB connectivity or fixture setup.

## Findings

- **Traceability and seams:** the test cites `WORKFORCE-CATALOG-001 v0.1`, invokes both approved migration and public command seams, and uses production MariaDB process persistence plus the proposed production Workforce delegate. Only other external boundaries are deterministic literals permitted by the spec.
- **Schema exactness that is present:** literal assertions cover all eight ordered columns, MariaDB types, nullability, absence of auto-increment, character sets, InnoDB, utf8mb4 table charset, exact primary/status indexes including prefix/direction/ignored attributes, the exact normalized employment-status check, and absence of foreign keys. Plausible wrong columns, keys, status values or operational index shapes are detected.
- **Blocking creation-result gap:** section 3 requires the new catalog to be empty and the migration to create one table only. The test does not assert an empty catalog before fixture insertion and does not enumerate new/prefixed tables after apply. An implementation can create the contracted table prepopulated with unrelated rows, or create additional v2 tables while returning only the expected `tablesCreated`, and pass all current assertions.
- **Additive v1 preservation:** the existing installation-case row and `SHOW CREATE TABLE` text for all six v1 tables are strictly equal before/after v2. Because a case row advances its generated ID, this also makes ordinary changes to v1 keys, table definitions and auto-increment visible. This is a strong additive-migration check.
- **Blocking repeat no-mutation gap:** compatible repeat verifies the return and catalog rows only. It captures no catalog/schema/auto-increment fingerprint around the repeat. An implementation that performs DDL (changes and restores data, index/check shape, or table auto-increment) can satisfy the current assertions despite the explicit no-DDL/no-DML contract. Capture exact schema/catalog state before and after repeat, not only row values.
- **Blocking conflict preflight gap:** the conflict scenario preserves its sentinel row but does not compare schema/catalog before and after `apply`. An implementation can alter the incompatible table into the normative schema and then return the expected conflict while retaining row `777`; the test passes despite forbidden DDL. A complete pre/post fingerprint must include columns/charset, engine, checks, indexes, keys and auto-increment-relevant table definition.
- **Prefix and conflict result:** the test uses distinct valid `process_` and `conflict_` prefixes inside its isolated database, expects exact prefixed `tablesCreated`/`conflictingTables`, and verifies `InvalidArgumentException` for invalid syntax. This is sensitive to ignoring the prefix. As with the inherited migration test, “before DB access” for invalid input is asserted only by exception type, not by an inaccessible connection.
- **Independent Workforce literals and command sensitivity:** the catalog fixture is the exact approved row. The public command must find numeric tab ID `1042`, and exact accepted result proves the row passes inherited employment-period rules. Fixed source/name/position/status/dates/freshness in the final installer expectation are not computed through SQL or delegate output.
- **Read-only current catalog:** exact equality of the complete catalog row before compatible repeat, after repeat, and after the preparation command detects ordinary inserts, updates or deletes by migration, command or delegate. The later UPDATE is explicit fixture simulation of a future sync rather than a production write.
- **Persisted snapshot immutability that is present:** after success, fixture changes name, position, status, employment end and freshness to competing literals, closes the originating connection and destroys module/delegate references. A new module/connection receives a delegate that throws on every external call, so the original installer snapshot must be read from `fm2_order_installers`, not the current catalog or PHP memory.
- **Blocking inherited-projection gap:** section 5 requires the complete `PERSISTENCE-PREPARE-001` projection after reconnect. The test extracts and asserts only `assignmentOrders[0].installers[0]`. An implementation can preserve the Workforce row while losing or changing the object/engineer snapshots, artifacts, assignments, event, process state, tasks, or work/checklist gates and still pass. Use the full independently literal projection already approved by the inherited spec.
- **Mutation sensitivity detail:** the fixture does not explicitly assert the post-UPDATE current row before reconnect. The SQL is deterministic and targets an existing primary key, but an exact competing-row assertion would make the intended source mutation self-proving and distinguish an unchanged fixture from persisted immutability.
- **Isolation and determinism:** one random hexadecimal database contains all prefixed schemas and facts; parallel runs cannot share tables. IDs, timestamps, renderer bytes, schema expectations and catalog values are fixed. `finally` closes the active connection and drops only that exact database, including after the observed RED. No legacy production or demo table is touched.
- **Scope and rejected behavior:** importer/upsert, stale-data policy, UI, legacy `fm_installators` and new public failures remain correctly outside the test. The successful tracer is sufficient for the Workforce lookup itself; existing missing/dismissed cases are inherited from `ORDER-PREPARE-003` rather than reimplemented here.

## Required changes

1. Immediately after first v2 apply, enumerate the prefixed tables to prove exactly the six preserved v1 tables plus `fm2_workforce_catalog` exist, and assert the new catalog has zero rows before inserting the external-sync fixture.
2. Capture and strictly compare the complete Workforce catalog schema/fingerprint before and after compatible repeat, including table definition/engine/charset, ordered columns, exact check, indexes and absence of FKs; keep the row comparison.
3. Capture and strictly compare the incompatible table's complete schema/fingerprint before and after the conflict result, not only its sentinel row, so forbidden preflight DDL is observable.
4. After fixture mutation, assert the exact changed current-catalog row, then reconnect with forbidden external reads and assert the complete independent `PERSISTENCE-PREPARE-001` public projection, not only its nested installer.
5. Rerun `php tests/InstallationProcess/workforce_catalog_001_test.php`, retain a RED caused by the absent v2 migration/delegate behavior, and request a fresh independent Gate 3 review. Gate 4 must not begin yet.
