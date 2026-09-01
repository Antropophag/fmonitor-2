## Purpose

Гарантировать, что чистое production/test развёртывание получает всю утверждённую workforce schema через один canonical migration contract без runtime DDL.

## ADDED Requirements

### Requirement: Canonical runner applies workforce schema v5 in order
Canonical migration command SHALL применять утверждённые migrations v1–v5 строго по порядку и при успехе возвращать `schemaVersion=5`. Workforce v5 MUST сохранять все результаты, конфликты и recovery semantics `BITRIX-WORKFORCE-SCHEMA-001` без переопределения.

#### Scenario: Clean canonical migration
- **WHEN** deployment operator запускает canonical migration command на чистой совместимой database
- **THEN** команда возвращает success, `schemaVersion=5`, `appliedVersions=[1,2,3,4,5]`, а exact workforce v5 schema доступна importer-у

#### Scenario: Completed repeat
- **WHEN** та же команда повторена на exact completed v5 schema
- **THEN** она возвращает success, `schemaVersion=5`, `appliedVersions=[]` и не изменяет schema или workforce rows

### Requirement: Workforce conflict fails closed at canonical seam
Если workforce migration возвращает `SCHEMA_MIGRATION_CONFLICT`, canonical command SHALL завершаться conflict result с `schemaVersion=5`, не запускать runtime repair и не скрывать несовместимость как environment failure.

#### Scenario: Incompatible workforce table
- **WHEN** v1–v4 совместимы, но одно target workforce table несовместимо с approved v5 manifest
- **THEN** canonical command возвращает `ok=false`, `reason=SCHEMA_MIGRATION_CONFLICT`, `schemaVersion=5` и не изменяет конфликтную schema

### Requirement: Runtime callers do not own workforce DDL
После canonical deployment bootstrap, importer, HTTP/UI и workers MUST NOT вызывать workforce migration или выполнять `CREATE`, `ALTER`, `DROP`/collation repair. Они SHALL fail with a setup/deployment reason, если required v5 schema отсутствует или несовместима.

#### Scenario: Importer sees missing v5
- **WHEN** importer запущен без предварительно применённой workforce v5 schema
- **THEN** он прекращает работу до business data mutation с setup/deployment failure и не выполняет DDL

#### Scenario: Architecture ownership
- **WHEN** change проверен `make architecture-check`
- **THEN** direct workforce migration/DDL debts удалены из bootstrap/importer и baseline не расширен
