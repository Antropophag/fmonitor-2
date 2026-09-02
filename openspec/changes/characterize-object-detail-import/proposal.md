## Why

Object-detail importer сейчас одновременно является единственным behavioral
oracle для immutable technical snapshot/quarantine и runtime owner двух tables.
До переноса schema ownership нужен маленький executable characterization,
который отделит наблюдаемую data semantics от DDL debt и не превратит UNKNOWN
transition/concurrency behavior в target requirement.

## What Changes

- Добавить deterministic PILOT_ONLY characterization операционного import seam
  `rapid-pilot/import-production-object-details.php` на private MariaDB fixtures.
- Зафиксировать clean accepted detail и missing-source quarantine в одном run,
  exact serial repeat с сохранением original capture evidence, changed-detail
  conflict с zero target-DML mutation и metadata/dictionary rejection до DML.
- Проверять реальный CLI result, source/target facts и cleanup/decoy preservation;
  отличать setup failure от behavioral regression.
- Подключить verifier к canonical characterization stage, не меняя importer,
  consumers, schema или product semantics.
- Явно оставить missing↔present transitions, concurrent runs, quarantine
  precedence/retention и target authorization/audit вне slice как UNKNOWN либо
  отдельные product decisions.

## Capabilities

### New Capabilities

- `verification/object-detail-import-characterization`: воспроизводимый PILOT_ONLY
  oracle текущих serial import, idempotency, quarantine и rejection outcomes.

### Modified Capabilities

Нет.

## Impact

- Новый stable spec `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001` и isolated verifier
  под `rapid-pilot/` с регистрацией в `tools/verification/run.sh`.
- Source oracle: `rapid-pilot/import-production-object-details.php` и текущие
  source/target SQL contracts.
- Actor: migration operator; seam: реальный CLI entrypoint с отдельными source и
  target DB principals, а не private PHP methods.
- Release value: следующий schema-ownership slice сможет удалить runtime DDL,
  сохранив independently proven data behavior.
- GRILL-004 не блокирует characterization: fixtures synthetic/private и не
  разрешают production-linked data population первого test contour.
