# BITRIX-WORKFORCE-SCHEMA-001 — применить schema migration кадровой истории Bitrix v5

- Статус: `APPROVED`
- Версия: `0.3`
- Дата: `2026-08-28`
- Актор: оператор deployment FMonitor 2.0
- Публичный seam: `BitrixWorkforceHistorySchemaMigration.apply(connection, tablePrefix = '')`

## 1. Единичный срез

После совместимых production migrations v1–v4 seam приводит принадлежащие FMonitor 2.0 workforce-таблицы к точной schema v5. Он сохраняет строки текущего каталога, создаёт storage для будущих run audit, append-only observations и singleton freshness metadata, безопасно повторяется и восстанавливает совместимое частичное применение MariaDB DDL.

Срез содержит только schema inspection, DDL и начальную singleton metadata row. HTTP, Bitrix payload, sync orchestration, normalization, checksum, publication/upsert, missing reconciliation, runtime catalog/freshness reading, permissions и migration-runner composition находятся вне среза.

## 2. Preconditions и prefix

`connection` — открытое MariaDB connection с текущей database. Migrations v1–v4 уже применены: все их таблицы могут существовать, но этот seam инспектирует и меняет только четыре target names ниже.

`tablePrefix` обязан соответствовать `/^[A-Za-z0-9_]{0,37}$/D`; пустая строка допустима. Любое иное значение, включая значение длиннее 37 bytes, вызывает `InvalidArgumentException` до первого обращения к DB. Валидация завершается до первого обращения к DB. Разрешённый алфавит однобайтовый, поэтому byte length и character count для допустимого prefix совпадают.

Предел 37 выводится из MariaDB maximum identifier length 64: самый длинный target table token `fm2_workforce_sync_metadata` имеет 27 characters, поэтому `37 + 27 = 64`. Все constraint/index symbols из алгоритма ниже короче соответствующего предела при prefix длиной 37. Полные target names получаются буквальным добавлением prefix:

```text
fm2_workforce_catalog
fm2_workforce_observations
fm2_workforce_sync_runs
fm2_workforce_sync_metadata
```

Все таблицы v5 используют `ENGINE=InnoDB`, `DEFAULT CHARSET=utf8mb4`; каждый character column имеет charset `utf8mb4` и database-default `utf8mb4_*` collation. Совместимость требует точных column order/type/nullability/extra, keys, named indexes, named CHECK/FK semantics и отсутствия дополнительных columns, indexes, checks или FKs.

### 2.1 Deterministic constraint/index naming

Exact symbol является функцией raw validated `tablePrefix` и logical token. Пустой prefix сохраняет exact v0.2 unprefixed symbols. Для непустого prefix symbol равен category marker (`fk_` или `ck_`) + raw prefix без изменения + short logical token:

| Structure | Empty-prefix exact symbol | Non-empty-prefix exact symbol |
|---|---|---|
| catalog employment CHECK | `ck_fm2_workforce_employment_status` | `ck_{prefix}wf_cat_emp` |
| catalog dismissal-quality CHECK | `ck_fm2_workforce_dismissal_quality` | `ck_{prefix}wf_cat_dq` |
| catalog reconciliation CHECK | `ck_fm2_workforce_reconciliation_state` | `ck_{prefix}wf_cat_rec` |
| runs status CHECK | `ck_fm2_workforce_sync_run_status` | `ck_{prefix}wf_run_status` |
| observations employment CHECK | `ck_fm2_workforce_observation_status` | `ck_{prefix}wf_obs_status` |
| observations reconciliation CHECK | `ck_fm2_workforce_observation_reconciliation` | `ck_{prefix}wf_obs_rec` |
| observations dismissal-quality CHECK | `ck_fm2_workforce_observation_dismissal_quality` | `ck_{prefix}wf_obs_dq` |
| observations run FK | `fk_fm2_workforce_observation_run` | `fk_{prefix}wf_obs_run` |
| metadata singleton CHECK | `ck_fm2_workforce_sync_metadata_singleton` | `ck_{prefix}wf_meta_one` |
| metadata run FK | `fk_fm2_workforce_metadata_run` | `fk_{prefix}wf_meta_run` |
| metadata FK supporting index | `fk_fm2_workforce_metadata_run` | `fk_{prefix}wf_meta_run` |

Here and below `{prefix}` means the literal non-empty validated value, including any caller-supplied trailing underscore. Thus `qa_` produces `fk_qa_wf_obs_run`, while `stage5_` produces `fk_stage5_wf_obs_run`. Different valid prefixes produce different FK/CHECK symbols, so complete v5 namespaces for multiple prefixes may coexist in one database, including MariaDB versions before 12.1 where FK symbols are database-global. At maximum prefix length the longest generated non-empty-prefix symbol is under 64 characters; the table-name bound is therefore controlling.

All other named indexes remain the exact fixed names stated in section 3; MariaDB index names are table-scoped. The observations unique key remains its FK-supporting index and is not renamed or duplicated. Referenced table names always use the same raw `tablePrefix`.

## 3. Exact final schema

Constraint and metadata FK-supporting-index names printed in sections 3.1–3.4 are the empty-prefix exact symbols. For a non-empty prefix, each printed symbol is replaced by its exact non-empty-prefix symbol from section 2.1; clauses, columns and all other names remain unchanged. This substitution is normative, not an implementation shorthand.

### 3.1 `{prefix}fm2_workforce_catalog`

V5 сохраняет восемь v2 columns в прежнем порядке и добавляет columns после них:

| # | Column | Exact definition |
|---:|---|---|
| 1 | `installer_tab_id` | `BIGINT UNSIGNED NOT NULL` |
| 2 | `fio` | `VARCHAR(300) NOT NULL` |
| 3 | `position` | `VARCHAR(300) NOT NULL` |
| 4 | `employment_status` | `VARCHAR(40) NOT NULL` |
| 5 | `employed_from` | `DATE NULL` |
| 6 | `employed_to` | `DATE NULL` |
| 7 | `workforce_source` | `VARCHAR(80) NOT NULL` |
| 8 | `workforce_source_updated_at` | `VARCHAR(40) NOT NULL` |
| 9 | `delivery_system` | `VARCHAR(40) NULL` |
| 10 | `delivery_person_id` | `BIGINT UNSIGNED NULL` |
| 11 | `dismissal_effective_at` | `DATE NULL` |
| 12 | `first_observed_dismissed_at` | `VARCHAR(40) NULL` |
| 13 | `dismissal_time_quality` | `VARCHAR(40) NULL` |
| 14 | `reconciliation_state` | `VARCHAR(40) NULL` |
| 15 | `authority_system` | `VARCHAR(40) NULL` |
| 16 | `last_successful_sync_run_id` | `CHAR(36) NULL` |
| 17 | `last_successful_sync_at` | `VARCHAR(40) NULL` |

Keys and constraints are exact:

```text
PRIMARY KEY (installer_tab_id)
UNIQUE KEY uq_fm2_workforce_delivery_identity (delivery_system, delivery_person_id)
KEY ix_fm2_workforce_status_reconciliation_sync
  (employment_status, reconciliation_state, last_successful_sync_at)
CONSTRAINT ck_fm2_workforce_employment_status
  CHECK (employment_status IN ('employed','dismissed'))
CONSTRAINT ck_fm2_workforce_dismissal_quality
  CHECK (dismissal_time_quality IS NULL OR dismissal_time_quality IN ('observed_only','effective_from_source'))
CONSTRAINT ck_fm2_workforce_reconciliation_state
  CHECK (reconciliation_state IS NULL OR reconciliation_state IN ('delivered','missing_from_delivery'))
```

V5 replaces the v2 non-unique index `(employment_status, employed_to)` and its unnamed employment-status CHECK with the named final structures above. Catalog has no FK. Nullable new metadata is deliberate: every pre-v5 row receives `NULL` for columns 9–17, so migration asserts no Bitrix identity, authority, reconciliation fact, dismissal quality or successful sync. All eight pre-v5 values remain byte-for-byte equal.

### 3.2 `{prefix}fm2_workforce_sync_runs`

Columns in order:

```text
run_id CHAR(36) NOT NULL
status VARCHAR(20) NOT NULL
started_at VARCHAR(40) NOT NULL
observed_at VARCHAR(40) NULL
completed_at VARCHAR(40) NULL
failure_code VARCHAR(80) NULL
page_count INT UNSIGNED NULL
delivered_count INT UNSIGNED NULL
material_change_count INT UNSIGNED NULL
missing_count INT UNSIGNED NULL
normalized_checksum CHAR(64) NULL
```

Exact constraints: `PRIMARY KEY (run_id)` and named `ck_fm2_workforce_sync_run_status CHECK (status IN ('started','completed','failed'))`. There are no other indexes, checks or FKs.

### 3.3 `{prefix}fm2_workforce_observations`

Columns in order:

```text
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
sync_run_id CHAR(36) NOT NULL
delivery_person_id BIGINT UNSIGNED NOT NULL
employee_number BIGINT UNSIGNED NOT NULL
full_name VARCHAR(300) NOT NULL
position VARCHAR(300) NOT NULL
employment_status VARCHAR(40) NOT NULL
employed_from DATE NULL
dismissal_effective_at DATE NULL
authority_system VARCHAR(40) NOT NULL
delivery_system VARCHAR(40) NOT NULL
source_modified_at VARCHAR(40) NULL
reconciliation_state VARCHAR(40) NOT NULL
observed_at VARCHAR(40) NOT NULL
dismissal_time_quality VARCHAR(40) NOT NULL
```

Exact keys and constraints:

```text
PRIMARY KEY (id)
UNIQUE KEY uq_fm2_workforce_observation_run_person
  (sync_run_id, delivery_system, delivery_person_id)
KEY ix_fm2_workforce_observation_person_time
  (delivery_system, delivery_person_id, observed_at)
KEY ix_fm2_workforce_observation_employee_time
  (employee_number, observed_at)
CONSTRAINT ck_fm2_workforce_observation_status
  CHECK (employment_status IN ('employed','dismissed'))
CONSTRAINT ck_fm2_workforce_observation_reconciliation
  CHECK (reconciliation_state IN ('delivered','missing_from_delivery'))
CONSTRAINT ck_fm2_workforce_observation_dismissal_quality
  CHECK (dismissal_time_quality IN ('observed_only','effective_from_source'))
CONSTRAINT fk_fm2_workforce_observation_run
  FOREIGN KEY (sync_run_id) REFERENCES {prefix}fm2_workforce_sync_runs(run_id)
  ON UPDATE RESTRICT ON DELETE RESTRICT
```

The unique key `uq_fm2_workforce_observation_run_person` already supplies the
required leftmost supporting index for the child FK column `sync_run_id`;
there is no additional FK-generated index on observations.

### 3.4 `{prefix}fm2_workforce_sync_metadata`

Columns in order:

```text
singleton_id TINYINT UNSIGNED NOT NULL
last_successful_run_id CHAR(36) NULL
last_successful_at VARCHAR(40) NULL
```

Exact constraints:

```text
PRIMARY KEY (singleton_id)
KEY fk_fm2_workforce_metadata_run (last_successful_run_id)
CONSTRAINT ck_fm2_workforce_sync_metadata_singleton CHECK (singleton_id = 1)
CONSTRAINT fk_fm2_workforce_metadata_run
  FOREIGN KEY (last_successful_run_id) REFERENCES {prefix}fm2_workforce_sync_runs(run_id)
  ON UPDATE RESTRICT ON DELETE RESTRICT
```

The metadata FK supporting index is exactly one non-unique `BTREE` index over
the full `last_successful_run_id` column, ascending and visible (not ignored).
Its exact prefix-derived name intentionally equals the metadata FK constraint
name according to section 2.1. No other metadata indexes exist.

Freshly completed v5 state contains exactly one row `{singleton_id: 1, last_successful_run_id: null, last_successful_at: null}`. Later runtime publication may replace both nullable values while retaining `singleton_id=1`; that one-row state is also compatible and migration leaves it byte-for-byte unchanged. An exact metadata table with zero rows is a compatible partial state; apply inserts the null-valued singleton. Zero or one row with `singleton_id=1` are the only compatible cardinalities/identity.

## 4. Preflight, ordering and partial recovery

Before DDL or DML, seam inspects all four target names and classifies each:

- catalog: exact v2 source or exact v5 target;
- each new table: absent or exact v5 target;
- metadata data: absent table, exact empty table, or exactly one constraint-valid `singleton_id=1` row are compatible; existing run/observation rows are data, not schema conflicts.

Every other state is incompatible. This includes a missing catalog, a partly altered catalog, wrong defaults/types/order/names/constraints, unexpected metadata rows, an observation/metadata FK targeting another table, and any extra target-table structure. The seam collects every incompatible full target name, sorts names ascending by binary string comparison, and returns before any DDL/DML:

```text
applied = false
schemaVersion = 5
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [sorted full names]
```

A complete or partial v5 namespace created before v0.3 under a **non-empty** prefix with the old prefix-independent named CHECK, FK or metadata supporting-index symbols is an incompatible state and is reported through this same conflict result; this slice provides no implicit rename/compatibility path. The empty-prefix v0.2 names are deliberately preserved and remain compatible when every other exact requirement holds.

Exact CHECK inspection is table-qualified. Any catalog comparison joins `information_schema.TABLE_CONSTRAINTS` to `information_schema.CHECK_CONSTRAINTS` on `CONSTRAINT_SCHEMA`, `CONSTRAINT_NAME`, **and `TABLE_NAME`**, and filters the inspected full target `TABLE_NAME`. FK inspection likewise remains qualified by schema, child table and constraint name. A same-named constraint on another table can neither satisfy nor contaminate a target table's exact manifest.

After a compatible preflight, missing structures are applied in dependency-safe operation order:

1. create `fm2_workforce_sync_runs`;
2. create `fm2_workforce_observations` (its run FK can now resolve);
3. create `fm2_workforce_sync_metadata` (its run FK can now resolve);
4. alter exact v2 `fm2_workforce_catalog` to exact v5 in one `ALTER TABLE`;
5. insert singleton metadata row if absent.

MariaDB DDL commits per statement; the seam therefore promises recoverability, not cross-table atomicity. If connection/process failure stops after any operation, a later apply accepts the exact completed subset plus the still-exact v2 catalog/empty metadata table and performs only missing operations. It never drops and recreates a target table and never rewrites an existing non-null value.

## 5. Exact observable results

Arrays list logical target names in this fixed reporting order, independent of DDL order:

```text
fm2_workforce_observations
fm2_workforce_sync_runs
fm2_workforce_sync_metadata
```

The caller prefix is present in every returned table name.

Clean apply from exact v2 catalog:

```text
applied = true
schemaVersion = 5
tablesCreated = [{prefix}fm2_workforce_observations,
                 {prefix}fm2_workforce_sync_runs,
                 {prefix}fm2_workforce_sync_metadata]
tablesAltered = [{prefix}fm2_workforce_catalog]
```

Compatible repeat from completed v5 performs no DDL/DML:

```text
applied = false
schemaVersion = 5
tablesCreated = []
tablesAltered = []
```

Compatible partial recovery returns `applied=true`; `tablesCreated` contains only tables created by that invocation in fixed reporting order and `tablesAltered` contains catalog only when that invocation upgraded it. Inserting the missing singleton row counts as application: if it is the only work, exact result is `applied=true`, with both arrays empty.

An unexpected MariaDB failure is not converted to a business result; the DB exception propagates. A subsequent call follows the same preflight/recovery contract.

## 6. Preservation and rejected cases

Before upgrading catalog, let `R` be its ordered set of rows projected onto the eight v2 columns. After success, the same projection is exactly `R`; row count, primary identities and auto-increment state of unrelated v1–v4 tables are unchanged. New tables contain no run or observation rows, and only the metadata singleton exists.

Exact rejected outcomes:

- invalid prefix: `InvalidArgumentException`, zero DB calls;
- one or more incompatible target states: `SCHEMA_MIGRATION_CONFLICT` result of section 4, zero DDL/DML;
- database execution failure after compatible preflight: propagated DB exception, with only already committed exact operations eligible for partial recovery.

This deployment action has no FMonitor user authorization or process/security audit event. DB deployment credentials and SQL/error details are not returned in the structured success/conflict result. No `down()` migration exists.

## 7. Independently determined examples

### A. Prefix and preservation

Exact v2 `qa_fm2_workforce_catalog` contains:

```text
1042 | Иванов Иван Иванович | Электромеханик по лифтам | employed |
2024-02-01 | null | one_c_zup_via_bitrix | 2026-08-26T18:00:00+03:00
```

`apply(connection, 'qa_')` returns the clean result of section 5 with `qa_` names. Its v5 catalog row has those same eight values and nine trailing `NULL` values. The three new tables exist; runs and observations contain zero rows; metadata contains exactly `(1,null,null)`.

### B. Compatible interrupted apply

Initial state has exact v5 `qa_fm2_workforce_sync_runs`, exact empty v5 `qa_fm2_workforce_sync_metadata`, no observations table, and the exact v2 catalog/row from A. Apply creates observations, upgrades catalog, inserts singleton, and returns:

```text
applied = true
schemaVersion = 5
tablesCreated = [qa_fm2_workforce_observations]
tablesAltered = [qa_fm2_workforce_catalog]
```

The pre-existing runs and metadata tables are unchanged apart from the required singleton insertion.

### C. Complete conflict discovery

With prefix `bad_`, catalog is exact v2, observations has an extra column, runs has a wrong status CHECK, and metadata is absent. Apply returns:

```text
applied = false
schemaVersion = 5
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [bad_fm2_workforce_observations,bad_fm2_workforce_sync_runs]
```

Metadata remains absent and catalog remains v2: conflict discovery preceded every DDL/DML.

### D. Two complete prefixed namespaces in one database

One MariaDB database initially contains exact v2 catalogs `blue_fm2_workforce_catalog` and `green_fm2_workforce_catalog`, and no other target tables for either namespace. Sequential calls `apply(connection, 'blue_')` and `apply(connection, 'green_')` both return their clean prefix-specific result from section 5. Both complete v5 schemas coexist in that database.

The `blue_` observations FK is exactly `fk_blue_wf_obs_run` and references `blue_fm2_workforce_sync_runs`; the `green_` counterpart is exactly `fk_green_wf_obs_run` and references `green_fm2_workforce_sync_runs`. Their metadata FK/supporting-index pairs are respectively `fk_blue_wf_meta_run` and `fk_green_wf_meta_run`. For example, runs status CHECKs are `ck_blue_wf_run_status` and `ck_green_wf_run_status`. Independently inspecting each of the eight full target tables with the table-qualified catalog contract produces its own exact section 3 manifest and no rows belonging to the other prefix.

## 8. Gate 2 scope

All v0.2 tests, support manifests and prior Gate 3 approvals are stale and do not authorize Gate 4. Fresh Gate 2 must update the existing deterministic MariaDB integration test and independent schema oracle to cite `BITRIX-WORKFORCE-SCHEMA-001 v0.3`, including exact prefix-derived symbols, the 37-character boundary, longer-prefix zero-DB rejection, and table-qualified CHECK catalog matching. It may exercise clean apply, exact catalog/table metadata, row preservation, compatible repeat, one compatible partial-recovery fixture, complete sorted conflict preflight and invalid-prefix zero-DB behavior in one migration test file.

Gate 2 must also reach the migration seam twice with two distinct non-empty prefixes in the same isolated MariaDB database, successfully create both complete v5 namespaces, and independently assert the exact catalog for both as in example D. Splitting those namespaces into separate databases does not prove this contract.

The test must not instantiate HTTP clients, sync/publication services or runtime catalog readers, and must not assert behavior from `BITRIX-WORKFORCE-HISTORY-001`. Expected schema literals and results come from this document, not production implementation constants or generated SQL.

## 9. Решения и доказательства

- `WORKFORCE-CATALOG-001 v0.1` fixes exact v2 catalog source schema and prefix behavior.
- `MIGRATION-PROCESS-001` fixes additive migration and preservation conventions.
- `PRODUCT.md` and `CONTEXT.md` require provenance and append-only workforce history without rewriting historical facts.
- `docs/bitrix-workforce-integration-research.md` supports separate run audit, observation history and honest absence of a source-effective dismissal date.
- MariaDB foreign-key dependency determines creation of runs before observations/metadata; stable result ordering is separately specified and is not inferred from implementation order.
- MariaDB requires child foreign-key columns to be indexed. The observations unique key already covers `sync_run_id` as its leftmost column; metadata therefore declares the exact separate supporting index required for `last_successful_run_id`.
- MariaDB versions before 12.1 require each FK symbol to be unique per database. Prefix-derived FK symbols permit two supported prefixed v5 namespaces in one database; concise prefix-derived CHECK names also make table-qualified exact catalog inspection unambiguous.
- MariaDB identifiers are limited to 64 characters. The 37-character prefix ceiling is determined by the 27-character longest target table token; every generated constraint/index symbol remains shorter than 64 at that ceiling.

## 10. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь утвердил fresh Gate 1 correction v0.3: prefix-derived database-unique FK/CHECK symbols, exact table-qualified catalog matching, a prefix limit derived before DB access, and coexistence of two complete non-empty-prefix v5 namespaces in one MariaDB database.

Gate 2 разрешён только для `BITRIX-WORKFORCE-SCHEMA-001 v0.3`. All v0.2 tests/manifests and Gate 3 decisions are stale; a fresh independent Gate 3 `APPROVED` review is mandatory before Gate 4. Tests, implementation and reviews не входят в этот Gate 1 artifact.
