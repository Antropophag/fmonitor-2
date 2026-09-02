## Purpose

Определяет каноническое populated-preserving владение classification-provenance storage, необходимым native import, без утверждения migration taxonomy как target semantics.

## ADDED Requirements

### Requirement: Production runner владеет exact provenance table
Система SHALL создавать `fm2_migration_classification_provenance` одной
аддитивной canonical migration до native/historical import. Runtime consumers
SHALL проверять schema availability и SHALL NOT выполнять DDL.

#### Scenario: Clean migration
- **WHEN** авторизованный migration operator запускает production runner на чистой базе с допустимым prefix
- **THEN** runner создаёт exact table, сообщает literal v11 в per-run result и завершает до запуска import без durable ledger row

#### Scenario: Runtime с запрещённым DDL
- **WHEN** native или historical provenance reconcile выполняется после migration под DB principal без DDL privilege
- **THEN** характеризованная append/replay/conflict запись работает без schema mutation

#### Scenario: Optional active-baseline consumer compatibility
- **WHEN** literal `active_baseline` provenance reconcile выполняется на exact pre-existing table под DB principal без DDL privilege
- **THEN** append/replay/conflict storage работает без создания baseline/active-case tables и без утверждения legacy-active cutover

#### Scenario: Native runtime без schema
- **WHEN** native batch apply запускается без exact provenance table
- **THEN** он до source connection/fetch возвращает exit 2, exact `NATIVE_BATCH_UNAVAILABLE` JSON, empty stderr и zero output/provenance/schema mutation

#### Scenario: Historical runtime с drift
- **WHEN** historical batch apply запускается с incompatible provenance table
- **THEN** он до source connection/fetch возвращает exit 2, exact `HISTORY_BATCH_UNAVAILABLE` JSON, empty stderr и zero output/provenance/schema mutation

#### Scenario: Active runtime без schema
- **WHEN** active-baseline batch apply запускается без exact provenance table
- **THEN** он до source connection/fetch возвращает exit 2, exact `ACTIVE_BATCH_UNAVAILABLE` JSON, empty stderr, не создаёт optional tables и не мутирует output/provenance

### Requirement: Compatibility решается до mutation
Migration MUST семантически preflight существующую таблицу до target mutation.
Совместимость SHALL включать columns, types, nullability/default/extra, InnoDB,
approved explicit-utf8mb4 collation и ordered indexes; presentation index names
SHALL NOT быть контрактом.

#### Scenario: Table absent
- **WHEN** preflight не находит provenance table и остальная база совместима
- **THEN** migration создаёт только exact table и сохраняет ambient objects

#### Scenario: Table exact-compatible
- **WHEN** populated provenance table уже имеет exact semantic fingerprint
- **THEN** migration сохраняет каждый row и следующий AUTO_INCREMENT value, не пересоздавая таблицу

#### Scenario: Table incompatible
- **WHEN** существующая таблица имеет конфликт column, index, engine или collation
- **THEN** migration возвращает stable schema conflict до mutation таблиц, rows, counters или decoys

#### Scenario: Safe repeat
- **WHEN** завершённая migration запускается повторно
- **THEN** schema, rows, counter и decoys остаются неизменными, а per-run `appliedVersions` пуст

### Requirement: Exact observed manifest сохраняется
Table SHALL иметь десять NOT NULL columns без semantic defaults: auto-increment
unsigned bigint `id`; `output_kind VARCHAR(40)`; unsigned bigint
`legacy_object_id` и `output_id`; `source_cutoff_at DATETIME`;
`classification_version VARCHAR(80)`; `category VARCHAR(40)`;
plain `reason_codes_json TEXT`; `classification_sha256 CHAR(64)`;
`created_at DATETIME`. Она SHALL иметь primary `id`, unique ordered
`(output_kind,output_id)`, secondary `(legacy_object_id)`, InnoDB, explicit
approved utf8mb4 collation и no FK/CHECK.

#### Scenario: Information-schema fingerprint
- **WHEN** verifier читает clean-created или compatible-existing table через `information_schema`
- **THEN** semantic fingerprint совпадает с Gate 1 manifest независимо от index presentation names

#### Scenario: Plain text reasons
- **WHEN** verifier сравнивает `reason_codes_json`
- **THEN** column остаётся plain `TEXT NOT NULL` без JSON type/CHECK и без неутверждённого taxonomy validation

### Requirement: Полный catalogue prefix ограничен 25 байтами
Runner MUST принимать синтаксически валидный ASCII process prefix длиной не
более 25 байт и MUST отклонять больший/невалидный prefix до DB
connection/access и до DB mutation.

#### Scenario: Boundary prefix
- **WHEN** clean migration получает валидный 25-byte prefix
- **THEN** 39-byte provenance basename образует допустимый 64-byte MariaDB identifier

#### Scenario: Oversized prefix
- **WHEN** runner получает 26-byte или синтаксически невалидный prefix
- **THEN** он возвращает setup/configuration failure до DB connection/access
- **AND** не выполняет DB access или ambient mutation

### Requirement: Bounded race и history безопасны
Migration SHALL NOT изменять существующие append-only provenance rows или
генерировать business audit facts. Два runner могут конкурировать только в
bounded acceptance scenario; общий lock/ledger contract не вводится. Для
детерминированного verifier scenario migration SHALL принимать injected
before-create barrier, вызываемый только после того, как preflight установил
отсутствие v11 table, и непосредственно перед plain `CREATE TABLE`.
Production CLI и production catalogue/factory SHALL всегда компоновать no-op
barrier и SHALL NOT предоставлять argv, environment или configuration switch
для его включения. Production migration SHALL NOT использовать `GET_LOCK`,
`SLEEP`, durable/ephemeral ledger или иную скрытую serialization.

#### Scenario: Two verifier-controlled real subprocesses
- **WHEN** verifier запускает два отдельных процесса через test-only composition с одним barrier coordinator на одной exact populated v1–v10 базе/prefix с absent target
- **AND** каждый процесс завершил absent-v11 preflight и сообщил arrival до того, как verifier одновременно разрешил обоим выполнить plain `CREATE TABLE`
- **THEN** один возвращает exit 0/schemaVersion11/appliedVersions[11], второй exit 70 exact MIGRATION_FAILED JSON/empty stderr, final table exact empty, predecessor/decoys неизменны
- **AND** следующий ordinary repeat возвращает exit 0 и empty appliedVersions

#### Scenario: Production composition cannot enable barrier
- **WHEN** production CLI запускается с произвольными argv, environment и
  поддерживаемой configuration
- **THEN** он не может выбрать verifier barrier и выполняет обычный semantic
  preflight непосредственно перед plain `CREATE TABLE`
- **AND** production path не выполняет advisory lock, artificial delay,
  migration-ledger write или иную serialization

#### Scenario: Populated history
- **WHEN** compatible table содержит native и historical literal provenance rows
- **THEN** migration сохраняет exact bytes, ids and next counter и не интерпретирует taxonomy

### Requirement: Optional legacy-active storage не входит в migration
Эта change SHALL NOT создавать или проверять
`fm2_legacy_active_baselines`/`fm2_active_case_provenance` и SHALL NOT требовать
выбора legacy-active cutover.

#### Scenario: Native-only database
- **WHEN** migration выполняется без legacy-active tables
- **THEN** она успешно canonicalizes classification provenance и не добавляет optional cutover storage

#### Scenario: Ambient legacy-active tables
- **WHEN** такие таблицы уже присутствуют как ambient objects
- **THEN** migration оставляет их schema/data/counters неизменными

### Requirement: Taxonomy и output atomicity остаются PILOT_ONLY
Schema ownership SHALL NOT утверждать output kinds, categories, reason codes,
classification policy или текущий multi-step import transaction как target
behavior.

#### Scenario: Literal provenance fixtures
- **WHEN** tests используют `operational_case`, `historical_snapshot`, `active_baseline` или category/reason literals
- **THEN** они доказывают только storage preservation/runtime compatibility, а не product approval этих значений

#### Scenario: Наблюдаемое output-without-provenance окно
- **WHEN** PILOT_ONLY verifier на одном literal eligible native object доказывает отсутствие case, затем создаёт operational case и вызывает conflict с заранее подготовленным mismatched proof той же output identity
- **THEN** native CLI возвращает exit 2 exact `NATIVE_BATCH_UNAVAILABLE` JSON/empty stderr, ровно один новый case сохраняется, conflicting proof неизменен и matching provenance row отсутствует
- **AND** transcript классифицируется `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE`, не утверждая окно target behavior; historical/active contrasts не требуются этим slice
