## Purpose

Определяет canonical ownership и restartable compatibility contract шести таблиц migrated evidence без импорта данных, изменения reconciliation semantics или runtime DDL.

## ADDED Requirements

### Requirement: Canonical runner владеет exact six-table family

Migration SHALL создать exact source snapshots, import quarantine, projection,
conflicts, decisions и decision-state tables после реально зарегистрированных
predecessors. Она MUST NOT выполнять import/backfill/rebuild. Composed runner
SHALL принимать максимум 25
ASCII bytes и SHALL отклонять 26-byte/invalid/non-ASCII prefix до DB
connection/access. Собственный 28-byte family-local ceiling остаётся только
арифметикой longest 36-byte basename и не расширяет production configuration.

#### Scenario: Clean schema
- **WHEN** canonical runner применяется к clean database с landed predecessors
- **THEN** ровно шесть exact tables создаются в deterministic order и migration version записывается один раз

#### Scenario: Repeat
- **WHEN** тот же runner выполняется повторно
- **THEN** schema, rows, counters и migration ledger остаются неизменны

#### Scenario: Composed prefix boundary
- **WHEN** full runner получает valid 25-byte ASCII prefix
- **THEN** все landed catalogue identifiers, включая six-table family,
  укладываются в MariaDB 64-byte limit

#### Scenario: Next byte rejected before database access
- **WHEN** full runner получает otherwise-valid 26-byte prefix
- **THEN** он возвращает configuration failure до DB connection/access
- **AND** ledger, schemas, rows, counters и ambient objects неизменны

### Requirement: Compatibility preflight охватывает всю family

До первого DDL migration SHALL fingerprint все шесть members по columns/defaults/extra, engine, resolved split collations, indexes, JSON CHECK и отсутствию extra structures. Index/constraint presentation names MUST NOT определять compatibility.

#### Scenario: Все compatible partial states
- **WHEN** каждый из 64 present/absent combinations содержит только absent или exact-compatible members
- **THEN** создаются только absent members, а existing schemas/rows/auto-increment/decoys сохраняются

#### Scenario: Incompatible member
- **WHEN** любой member имеет representative column/index/check/engine/collation conflict
- **THEN** migration отклоняет всю family до DDL и не меняет другие schemas, rows, counters, ledger или decoys

### Requirement: Collation и JSON semantics воспроизводимы

Migration SHALL validate inherited database charset/collation для conflicts/state и MariaDB default collation for explicit utf8mb4 members, затем emit resolved values explicitly. Projection JSON column MUST сохранять binary JSON collation и semantic `json_valid` CHECK.

#### Scenario: Supported split collation environment
- **WHEN** оба resolved charset/collation sources valid и identifiers allowlisted
- **THEN** clean tables получают exact source-equivalent split collations без environment-name hard-code

#### Scenario: Unsupported charset/collation
- **WHEN** inherited database charset не utf8mb4 либо resolved collation invalid/unavailable
- **THEN** migration возвращает configuration/schema conflict до DDL без раскрытия unsafe identifier

### Requirement: Runtime consumers не владеют DDL

Importer, projection/backfill store, decision ledger и OTIZ request path SHALL быть DDL-free и fail closed при absent/incompatible family до source reads или data/business transaction.

#### Scenario: Runtime schema absent
- **WHEN** data/import/request consumer запускается без canonical family
- **THEN** он сообщает exact schema-unavailable outcome и не создаёт/repair-ит table

#### Scenario: Runtime schema exact
- **WHEN** canonical family exact
- **THEN** существующие import/projection/decision/read characterizations продолжают работать без schema mutation

### Requirement: History и derived data сохраняются

Migration SHALL сохранять source/quarantine rows, replaceable projection/conflicts, append-only decisions и mutable decision-state bytes/counters. Schema migration MUST NOT infer, rebuild or approve those facts.

#### Scenario: Populated upgrade
- **WHEN** exact-compatible family содержит literal rows во всех шести tables
- **THEN** migration/repeat сохраняют every ordered row and next auto-increment values byte-equivalent

#### Scenario: No semantic promotion
- **WHEN** ownership change завершён
- **THEN** migrated-evidence admission, outcomes, authority, corrections, retention and premium use remain unchanged and outside schema contract

### Requirement: Architecture ownership ratchet уменьшается

Change SHALL удалить шесть runtime-DDL ownership sites/statements without new exceptions and SHALL оставаться additive/no-down.

#### Scenario: Architecture verification
- **WHEN** `make architecture-check` выполняется после migration integration
- **THEN** runtime DDL debt decreases exactly for migrated-evidence family and no SQL/mutation/dependency hotspot grows
