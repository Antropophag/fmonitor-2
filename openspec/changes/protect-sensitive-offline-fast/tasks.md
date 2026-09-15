## 1. Contract and Gate 2

- [x] 1.1 Зафиксировать normative spec, gap-check A–D и verification input; проверить `openspec validate --strict` и planner plan.
- [x] 1.2 Добавить зарегистрированный public-planner regression A–L против shipped policy; сохранить intended RED до policy change.
- [x] 1.3 Получить независимый Gate 3 review specification/tests/RED для planner-selected CRITICAL route.

## 2. Minimal implementation

- [x] 2.1 Перенести exact sensitive assets из `bounded-ui` в `sensitive-offline-ui`, связать boundary с existing oracle и CRITICAL; focused regression должен стать GREEN.
- [x] 2.2 Проверить неизменность presentation FAST и #153A regressions только planner-selected focused commands.

## 3. Delivery

- [x] 3.1 Получить независимый Gate 5 review exact candidate и устранить findings без scope growth.
- [ ] 3.2 Запустить один exact-source GitHub CI через существующий consumer и подготовить PR-ready handoff без merge/deploy/settings.
