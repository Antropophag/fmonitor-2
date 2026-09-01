# Canonical Inspection Evidence Schema Specification

## Purpose

Гарантирует, что fresh deployment создаёт совместимую inspection-evidence schema только canonical migrations, до первого checklist-запроса и без runtime DDL.

## Requirements

### Requirement: Canonical migration owns the complete inspection-evidence family

Canonical production migration v8 SHALL создать точные prefixed tables revisions, operations, operation installers и photos непосредственно после exact landed catalogue v1–v7. Database-default collation validation SHALL наследовать approved v6/v7 UCA-alias normalization. Fresh operations SHALL сразу содержать immutable template identity, а installer evidence — `assignment_source`.

#### Scenario: Clean deployment creates exact final schema
- **WHEN** canonical runner применяется к clean compatible database
- **THEN** все четыре таблицы, columns, keys, indexes, engine и collation присутствуют в final runtime-compatible form
- **AND** runner сообщает применённую literal schema version `8` после v1–v7

#### Scenario: Repeat migration is idempotent
- **WHEN** migration повторно применяется к exact final family
- **THEN** она сообщает no-op
- **AND** не изменяет rows, indexes или auto-increment state

### Requirement: Existing compatible pilot forms upgrade without data loss

Migration SHALL распознавать отдельно pre-template operations form и pre-assignment-source installers form, добавлять только отсутствующие final columns и сохранять существующие evidence rows.

#### Scenario: Operations gains immutable template identity
- **WHEN** compatible operations table не содержит трёх template identity columns
- **THEN** migration добавляет nullable `template_snapshot_id`, `template_snapshot_version` и `template_content_sha256`
- **AND** не переписывает существующие operation facts

#### Scenario: Installer evidence gains explicit assignment source
- **WHEN** compatible operation-installers table не содержит `assignment_source`
- **THEN** migration добавляет non-null column и маркирует существующие rows literal legacy backfill source
- **AND** сохраняет прежний primary key и personnel snapshots

#### Scenario: Compatible partial family is completed
- **WHEN** часть exact tables уже существует, а остальные отсутствуют
- **THEN** migration детерминированно создаёт только отсутствующие tables после preflight всей family
- **AND** сохраняет все существующие rows

### Requirement: Incompatible inspection schema fails closed before mutation

Migration MUST проверить всю owned family до первой DDL mutation. Несовместимый column, key, index, engine, collation или constraint SHALL вернуть `SCHEMA_MIGRATION_CONFLICT` с детерминированно отсортированными prefixed table names.

#### Scenario: One table is incompatible
- **WHEN** хотя бы одна owned table имеет неразрешённый fingerprint
- **THEN** migration сообщает conflict
- **AND** ни одна таблица или row family не изменена

#### Scenario: Prefix isolates another schema family
- **WHEN** database содержит похожие unprefixed или иначе prefixed tables
- **THEN** migration читает и изменяет только validated configured prefix

### Requirement: Runtime checklist paths perform no schema mutation

После canonical migration checklist HTTP, sync, photo и characterization paths MUST NOT выполнять production `CREATE`, `ALTER`, `DROP` или repair owned family.

#### Scenario: Migrated checklist behavior remains compatible
- **WHEN** существующие item completion, installer attribution, duplicate/conflict и photo paths выполняются на canonical schema
- **THEN** их наблюдаемые результаты и append-only evidence остаются прежними
- **AND** runtime не выполняет DDL

#### Scenario: Required schema is absent or incompatible
- **WHEN** runtime consumer встречает отсутствующую или несовместимую owned schema
- **THEN** он fail closed как deployment/schema precondition
- **AND** не создаёт и не исправляет schema самостоятельно
