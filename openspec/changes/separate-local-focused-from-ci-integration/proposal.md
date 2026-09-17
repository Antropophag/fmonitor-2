## Why

Консервативная semantic integration closure из #153 сейчас дублирует всю integration-категорию в каждом локальном focused-цикле, хотя обязательный полный набор всё равно выполняется в exact-source CI. #181 разделяет обязательство и место исполнения без ослабления итоговой проверки.

## What Changes

- Сохранить direct acceptance/regression, изменённые зарегистрированные тесты, boundary checks и известные consumer verifiers в локальной focused-фазе.
- Пометить integration checks, выбранные только общей semantic closure, как CI-only; при нескольких основаниях сильное локальное основание побеждает.
- Провести это различие через существующие planner, runner, reviewer package и CI consumer, не создавая новый admission или registry.
- Сохранить fail-closed prepare и существующую обработку missing/failure/cancelled обязательного CI.

## Capabilities

### New Capabilities

- `verification/local-ci-check-placement`: детерминированное размещение обязательных checks между локальным focused и exact-source CI.

### Modified Capabilities

Нет.

## Impact

Только delivery/verification tooling, его executable regressions, OpenSpec и review evidence. Product code, FAST scope, роли, review rules и CI matrix не меняются.
