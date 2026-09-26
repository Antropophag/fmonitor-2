## Why

После штатного завершения нового монтажного дела часть runtime-проекций всё ещё показывает `completed` как «В работе» или исключает его из завершённых метрик. ОТиЗ также ошибочно создаёт `INSTALLER_ATTRIBUTION_ABSENT`, когда учитываемый прогресс равен нулю.

## What Changes

- `completed` получает единое read-only представление «Работы завершены» в текущих статусах, operational dashboard и weekly FKR.
- Dashboard считает persisted `completed` завершённым и не включает его в active/overdue work.
- ОТиЗ не создаёт attribution blocker и не фабрикует allocation при нулевом прогрессе.
- При положительном прогрессе отсутствие attribution остаётся fail closed и называет каждого затронутого монтажника по safe tab/name в стабильном порядке.
- Historical reconciliation, deployment CLI, backfill, migration, backup/restore ceremony и production data mutation удалены из scope: production запускается с пустого контура.

## Capabilities

### New Capabilities

- `installation/completed-runtime-projections`: единая runtime-проекция persisted `completed` для новых дел.

### Modified Capabilities

- `otiz/snapshot-publication`: zero-progress attribution не является нарушением; positive-progress gap получает actionable diagnostic.

## Impact

- Затрагиваются только read/projection owners и native OTIZ input values, их executable tests и verification inventory.
- State writers, schema, migrations, `fm_maintable` passport join, premium formula/settlement и `rapid-pilot` не меняются.
