## Why

Выбор состава без шаблона требует отдельного dateless ledger; physical orders
с обязательной датой и registration lifecycle не могут хранить этот факт.
Уже проверенный общий registry engine даёт основу для независимой проверки
пяти таблиц до включения writers и cutover.

## What Changes

- Deployment actor получает standalone `AssignmentOrderSelectionSchemaMigration::apply`
  и read-only readiness; verification composition добавляет только fixed phase observer/snapshot.
- Exact executable contract `ASSIGNMENT-ORDER-SELECTION-SCHEMA-001` задаёт пять
  таблиц selection/member/request/event/audit, prefix0..25, fingerprints и recovery.
- Перед DDL проверяется completed compatible registry; новый owner его не создаёт,
  не изменяет и не ремонтирует. Новых selection/domain facts миграция не создаёт.
- Oracle: selection spec v0.8 sections7–8 и Gate5-approved registry contract;
  primary/real data и legacy source не используются.
- Non-goals: canonical migration registration/version, allocator/application writer,
  original reader, optional render, HTTP, effective composition, opening и deployment.
  Ни один READY marker не включается; N−1 exclusion остаётся отдельным gate.

## Capabilities

### New Capabilities

- `pilot/assignment-order-selection-schema`: standalone additive dateless ledger
  migration с exact registry prerequisite и проверяемыми partial/repeat/failure states.

### Modified Capabilities

Нет. Registry и selection command contracts сохраняют свои scope/gates.

## Impact

Owning module `InstallationProcess`; DDL только в его `*SchemaMigration.php`.
Public standalone seam, metadata inspection, migration tests и independent reviews;
canonical runner, startup, application factories, protected E2E не меняются.
Технический scope не требует нового продуктового решения. Full selection Gate1
остаётся закрыт до остальных exact compatibility contracts.

## Exact executable contract

ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 v0.1 и normative schema/example JSON fixtures
задают exact public API, fingerprints и populated-data proof. Это draft technical
Gate1 batch; никакое planning approval не разрешает RED до независимого verdict.
