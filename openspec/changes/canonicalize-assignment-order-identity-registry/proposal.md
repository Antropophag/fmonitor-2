## Why

Выбор состава без шаблона требует стабильного ID распоряжения, совместимого с
историческими документами. Сейчас физическая таблица распоряжений сама
назначает ID; независимый allocator selection мог бы создать collision или
перенумеровать историю.

## What Changes

- Добавить отдельный production migration owner registry и immutable backfill
  receipts, сохраняющий existing ID/case/version, prepared timestamp и frontier.
- Определить exact schema, prefix25, preflight, interrupted repeat и conflict
  outcomes до RED; backfill не создаёт domain events или selection facts.
- Дать публичную read-only проверку завершённости backfill.
- Разделить готовность migration engine и разрешение включения новых writers.
  Canonical registration/deployment остаются закрытыми до совместимого writer
  cutover, original reader и следующей selection-family migration.

## Capabilities

### New Capabilities

- `deployment/assignment-order-identity-registry`: metadata-preserving registry
  migration и проверка immutable receipt при остановленных application writers.

### Modified Capabilities

Нет. Existing order commands не меняются этим engine slice.

## Impact

Slice: `ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001`. Actor — deployment operator.
Source oracle — physical order/case schema и independent source inventory
`selection-writer-reader-cutover-inventory-2026-09-05.md`. Public seam —
`AssignmentOrderIdentityRegistryMigration::apply` и `isBackfillComplete` в
production InstallationProcess migration area. Release value — исторические ID
сохраняются перед подключением dateless selection.

Non-goals: selection command, UI, original bytes, applicability/opening,
optional rendering, включение mixed N-1/N writers, реальные данные или deploy.
Writer cutover и canonical registration — обязательные release dependencies,
не возможности отключить эти проверки ради engine GREEN. Product policy
REPLACE_PENDING уже утверждена; новых продуктовых решений этот slice не вводит.
