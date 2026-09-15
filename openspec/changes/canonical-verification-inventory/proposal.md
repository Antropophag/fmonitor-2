## Why

Текущий verification inventory требует вручную синхронизировать `suites.tsv` и `categories.json`, а одна содержательная governance-проверка дополнительно исполняется в fast job. Это создаёт повторяющиеся correction loops при каждом добавлении теста и допускает drift до поздних delivery gates.

## What Changes

- Сделать `tools/verification/suites.tsv` единственным вручную редактируемым источником legacy suite, runtime, test path и CI category.
- Удалить `categories.json` и перевести подтверждённых consumers на один общий stdlib parser/validator.
- Добавить атомарный repository-owned registration command, который изменяет только canonical manifest и fail-closed валидирует результат.
- Обнаруживать незарегистрированные canonical `tests/**` в существующем public prepare/planner seam до Gate 3 с точной диагностикой `UNREGISTERED_TEST`.
- Оставить в fast только дешёвую inventory/schema/discovery validation; содержательные governance tests продолжат исполняться в governance без повторного запуска.
- Сохранить существующие Quality Graph categories, fail-closed aggregation, FAST classification и test semantics.

## Capabilities

### New Capabilities

- `verification/canonical-inventory`: единый canonical verification manifest, атомарная регистрация, ранняя discovery validation и однократная CI category composition.

### Modified Capabilities

Нет.

## Impact

Изменяются только verification infrastructure, её executable contracts и delivery planning artifacts: `tools/verification`, существующий change-verification planner, Make registration seam, Quality Graph policy/workflow wiring и focused verification tests. Product code, test coverage, CI performance/sharding, FAST classifier, rapid-pilot cleanup, architecture policy и deployment не изменяются. Source oracle — принятый owner contract issue #135 и текущий `main`; public seams — canonical inventory CLI/Make registration route, `ci.py` category composition и существующий change-verification prepare path. Release value — одно атомарное изменение при регистрации теста и устранение повторного governance execution.
