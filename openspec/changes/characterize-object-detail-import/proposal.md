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
- Подключить verifier к canonical characterization stage без redesign serial
  DML/consumers; shared no-DDL/precondition correction принадлежит отдельно
  gated `canonicalize-object-detail-snapshot-schema`.
- Создавать family через landed canonical v12 в новом private disposable server;
  проверять DDL-denied apply/dry-run и exact schema refusal до source connection.
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
  `tests/Verification/characterize_object_detail_import_001_test.php` с
  регистрацией в `tools/verification/run.sh`.
- Source oracle: `rapid-pilot/import-production-object-details.php` и текущие
  source/target SQL contracts.
- Actor: migration operator; seam: реальный CLI entrypoint с отдельными source и
  target DB principals, а не private PHP methods.
- Release value: schema-ownership slice с landed v12 сможет удалить runtime DDL,
  сохранив independently proven data behavior.
- GRILL-004 не блокирует characterization: fixtures synthetic/private и не
  разрешают production-linked data population первого test contour.

Gate 1 candidate v0.2 требует fresh technical review и exact owner approval
serial oracle. Table-transfer approval не спрашивается повторно. UNKNOWN
semantics остаются исключёнными; checked tasks не означают готовность v0.2.
