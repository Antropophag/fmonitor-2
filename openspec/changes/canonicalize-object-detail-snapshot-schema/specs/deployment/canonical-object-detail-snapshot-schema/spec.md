## Purpose

Гарантирует, что object-detail snapshot и quarantine schema создаётся только
canonical migrations до importer/consumer access и не зависит от наличия
внешнего production-like source.

## ADDED Requirements

### Requirement: Canonical migration owns the complete object-detail family

Canonical production migration sequence SHALL создать exact object-details и
object-detail-quarantine tables после фактически landed prerequisites. Migration
SHALL не читать external source и не создавать data rows.

#### Scenario: Clean deployment creates exact empty tables
- **WHEN** canonical runner применяется к clean compatible database
- **THEN** обе tables создаются с утверждёнными columns, primary keys, engine,
  charset и database-default collation
- **AND** обе tables пусты и runner сообщает sequential schema version

#### Scenario: Exact populated repeat is a no-op
- **WHEN** обе tables имеют exact fingerprint и содержат snapshot/quarantine rows
- **THEN** migration сообщает no-op
- **AND** schema, rows и captured/hash payload evidence остаются byte-identical

### Requirement: Exact-compatible partial family is restartable

Migration SHALL выполнить read-only preflight всей существующей family и SHALL
создать только отсутствующий member, если каждый существующий member exact.

#### Scenario: Details exists and quarantine is absent
- **WHEN** populated details table exact-compatible, а quarantine отсутствует
- **THEN** migration создаёт только пустую quarantine table
- **AND** existing details rows не изменяются

#### Scenario: Quarantine exists and details is absent
- **WHEN** populated quarantine table exact-compatible, а details отсутствует
- **THEN** migration создаёт только пустую details table
- **AND** existing quarantine rows не изменяются

### Requirement: Incompatible schema fails closed before mutation

Любой несовместимый column/default/generated metadata, primary/index structure,
constraint, engine, charset или collation MUST вернуть
`SCHEMA_MIGRATION_CONFLICT` до первой family mutation.

#### Scenario: Conflict coexists with an absent sibling
- **WHEN** один existing member несовместим, а второй отсутствует
- **THEN** conflict сообщает exact configured table name
- **AND** отсутствующая table не создаётся и existing schema/data не меняются

#### Scenario: Other namespaces contain decoys
- **WHEN** configured family compatible, а unprefixed/other-prefix tables имеют
  произвольную schema/data
- **THEN** migration инспектирует и изменяет только configured namespace
- **AND** decoys остаются byte-identical

### Requirement: Importer and consumers perform no schema mutation

После canonical migration object-detail importer, card projection, native
premium inputs и OTIZ consumers MUST NOT выполнять `CREATE`, `ALTER`, `DROP`
или schema repair этой family.

#### Scenario: Data import runs without DDL privilege
- **WHEN** importer principal имеет необходимые SELECT/INSERT права на exact
  migrated tables, но не имеет schema-mutation privilege
- **THEN** approved import characterization сохраняет current first-write,
  exact-hash replay и conflict outcomes
- **AND** schema fingerprint остаётся неизменным

#### Scenario: Required schema is absent or incompatible
- **WHEN** importer или consumer встречает отсутствующую/несовместимую family
- **THEN** он fail closed через deployment/schema precondition
- **AND** не создаёт, не исправляет и не заполняет schema

### Requirement: Composed process prefix is forward-compatible

Production runner and configuration seams MUST принимать только ASCII process
prefix длиной не более 25 bytes. Valid 26-byte, syntactically invalid и non-ASCII
input MUST отклоняться до DB connection/access и до ledger/schema mutation.
Object-detail family-local identifier arithmetic remains 30 bytes because its
longest basename is 34 bytes; that local result MUST NOT be exposed as composed
production configuration support.

#### Scenario: Composed boundary succeeds
- **WHEN** operator запускает полный sequential runner с valid 25-byte ASCII
  process prefix
- **THEN** object-detail family и каждый landed catalogue identifier укладываются
  в MariaDB 64-byte limit

#### Scenario: Next byte is rejected before database access
- **WHEN** operator передаёт valid-character 26-byte process prefix
- **THEN** runner возвращает configuration failure до DB connection/access
- **AND** migration ledger, schema, rows и ambient objects не изменяются

### Requirement: Ownership is independent from test-data population policy

Canonical migration SHALL быть одинаковой для empty synthetic/native contour и
для отдельно утверждённого source import. Она SHALL не выбирать source, не
содержать personal data и не seed-ить premium operands.

#### Scenario: Approved synthetic contour omits object-detail population
- **WHEN** approved GRILL-004 decision выбирает synthetic/native contour, а отдельный object-detail fixture seed не утверждён
- **THEN** canonical migration всё равно успешно создаёт empty exact family
- **AND** consumers fail closed на отсутствующем evidence без обращения к legacy
  source

#### Scenario: Population remains separately approved
- **WHEN** approved synthetic TEST-USER contour требует object-detail fixture либо будущий отдельно утверждённый contour требует source import
- **THEN** отдельный versioned seed/import contract определяет data provenance,
  privacy, hashes и reset semantics
- **AND** schema ownership change не считается разрешением такого population

### Requirement: Current storage shape is preserved without semantic promotion

Ownership migration SHALL сохранить PK-only two-table shape и SHALL не вводить
автоматическую exclusivity/repair между detail и quarantine rows. Existing
content SHA and LONGTEXT payload remain opaque importer evidence.

#### Scenario: Detail and quarantine coexist for one object
- **WHEN** compatible populated database содержит обе row identities для одного
  object
- **THEN** migration сохраняет обе rows без удаления или reconciliation
- **AND** отдельный content-integrity/cleanup slice может решить semantics позже
