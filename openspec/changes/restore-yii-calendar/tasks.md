## 1. Gate 1 и verification planning

- [x] 1.1 Создать stable executable contract `YII2-CALENDAR-003` из approved OpenSpec requirements и проверить полную трассировку actor/seam, projection ordering, authorization, failures, history/no-write и adjacent scheduling compatibility.
- [x] 1.2 Создать `verification-input.json`, выполнить `python3 tools/delivery/harness.py prepare` от exact base и проверить, что planner разрешил все mandatory obligations и зафиксировал единственный authoritative lane/review set.

## 2. Gate 2 — focused RED

- [x] 2.1 Добавить real-HTTP test с независимо рассчитанной fixture для непоследовательных schedule rows, out-of-range row, permission-negative user, path variants, selected/invalid date, schema failure и byte-equivalent facts/schema; доказать intended calendar `404` RED через подготовленный harness profile.
- [x] 2.2 Добавить focused browser test для перехода из группы `Монтаж`, chronological month/date/object rendering, current item, selected agenda и read-only object return path; сохранить intended RED или документированный environment blocker без представления UNKNOWN как RED.
- [x] 2.3 Выполнить planner-required independent Gate 3 review, если он указан планом; исправить всю совокупность findings до `APPROVED` и переподготовить package при изменении spec/tests/input. Planner Gate 3 не назначил.

## 3. Gate 4 — Yii implementation

- [x] 3.1 Реализовать bounded read-only Yii calendar projection с explicit date/object/schedule ordering, readiness guard и safe `400`/`503`; focused HTTP test должен стать GREEN без DDL/DML.
- [x] 3.2 Добавить Yii route/controller/view с существующими calendar/shlz assets и общим authentication/RBAC flow; HTTP и browser tests должны стать GREEN.
- [x] 3.3 Добавить permission-aware пункт `Календарь` после `Объекты` в группе `Монтаж` и exact current state; focused navigation regression должен стать GREEN.

## 4. Focused verification и Gate 5

- [x] 4.1 Выполнить только выбранные planner commands, применимые Yii/navigation/planning regressions и `make architecture-check` (плюс mandatory PilotHttp auth check при изменении `app/PilotHttp/*.php`); сохранить bounded evidence, не запуская локально полный `make test`/`make verify`.
- [x] 4.2 Подготовить exact-source Gate 5 package и получить независимый final review `APPROVED`, исправляя findings с delta review по изменённому source.
- [ ] 4.3 Выполнить один exact-source GitHub CI run выбранным existing consumer, собрать полный failure inventory при сбое и записать PR/CI/UNKNOWN state без merge/deploy/settings.

## 5. Done definition

- [ ] 5.1 Подтвердить, что `/pilot/calendar[/]` возвращает детерминированную разрешённую проекцию, `403` без `objects.read`, не пишет facts/schema, ссылка `Календарь` находится и выделяется в `Монтаж`, focused checks и planner-required review/CI GREEN; обновить delivery record и отметить выполненные OpenSpec tasks.
