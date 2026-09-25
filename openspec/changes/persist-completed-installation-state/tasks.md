## 1. Gate 1 и verification plan

- [x] 1.1 Сузить нормативный контракт до новых завершений, corrections, checklist admission и OTIZ inclusion; historical reconciliation/CLI/recovery связать с follow-up issue #276.
- [x] 1.2 Создать `verification-input.json`, выполнить `python3 tools/delivery/harness.py prepare` для нового контракта и прочитать все planner obligations/selected checks; unresolved coverage должно быть пустым до Gate 2.
- [x] 1.3 Инвентаризировать exact `process_state` consumers, schema constraints/frontier, fixtures, runtime dependencies, backup/restore и CI suites; зафиксировать применимые проверки и обоснованные N/A в нормативном контракте/design.

## 2. Gate 2 — RED по публичным seams

- [ ] 2.1 Добавить HTTP/application integration test: первая валидная декларация атомарно сохраняет root, переводит exact `working → completed` и создаёт одно событие; отказ и инъецированный persistence-сбой не оставляют частичных фактов. Запустить focused command и сохранить RED именно на отсутствующем `completed`/event поведении.
- [x] 2.2 Добавить concurrency test двух первых деклараций: один success, один conflict, одна root declaration, один переход и одно событие; запустить и сохранить детерминированный RED.
- [ ] 2.3 Добавить публичные regression cases: checklist complete/retract/attribution запрещены после `completed`, а `correct_pto` и `correct_declaration` доступны с прежними exact grants, добавляют revision и не меняют state/event count; запустить и сохранить RED.
- [ ] 2.4 Добавить OTIZ input/publication case: новое `completed` даёт реальный snapshot-прирост 85→100%; migration predicates не меняются.

## 3. Gate 3 — независимая проверка тестов

- [ ] 3.1 Подготовить reconstructible exact source snapshot с нормативным контрактом и полным RED candidate, передать отдельному gpt-5.6-sol/low reviewer package и получить `APPROVED` в `reviews/tests/PERSIST-COMPLETED-INSTALLATION-STATE-001.md`; при возврате исправить весь foreseeable matrix и повторить Gate 2/3.

## 4. Gate 4 — минимальная реализация

- [ ] 4.1 Передать approved package отдельному gpt-5.6-sol/low executor; реализовать в InstallationProcess owner атомарные declaration root + conditional state transition + append-only completion event и доказать GREEN тестов 2.1–2.2.
- [ ] 4.2 Разрешить document corrections для `working|completed` и сохранить checklist fail-closed для exact `working`; доказать GREEN теста 2.3.
- [ ] 4.3 Расширить native OTIZ selection до `working|completed`, не меняя legacy join, migration eligibility, cutoff или формулу; доказать GREEN теста 2.4 и existing focused OTIZ regressions.
- [ ] 4.4 Выполнить все planner-selected bounded local checks через harness; не запускать локально полный `make test`/`make verify`.

## 5. Gate 5 и поставка

- [ ] 5.1 Захватить финальный reconstructible exact source, передать отдельному независимому gpt-5.6-sol/low reviewer и получить `APPROVED` в `reviews/code/PERSIST-COMPLETED-INSTALLATION-STATE-001.md` по spec/tests/code/evidence.
- [ ] 5.2 Зафиксировать meaningful checkpoint, запустить один exact-source GitHub CI через выбранный planner consumer и проверить полный failed-job/`REGRESSION_FAILURE` inventory до любых коррекций; CI должен быть GREEN для того же source.
- [ ] 5.3 Обновить delivery record и OpenSpec task status с авторами, review returns, elapsed/repeated checks, source digest, CI и известными UNKNOWN; не объявлять merge/deploy без отдельного подтверждения.

## 6. Done definition

- [ ] 6.1 На exact reviewed/CI-green source новая декларация переводит дело в persisted `completed` с одним событием, checklist после завершения закрыт, corrections работают append-only, OTIZ видит snapshot-прирост 85→100%; legacy-привязка и формула не изменены.
