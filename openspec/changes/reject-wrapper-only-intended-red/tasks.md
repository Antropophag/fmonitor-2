## 1. Contract and plan

- [x] 1.1 Зафиксировать `INTENDED-RED-OBSERVATION-PROVENANCE-001`, OpenSpec proposal/delta/design и границы slice B; проверить `openspec validate --strict`.
- [x] 1.2 Создать `verification-input.json`, построить и прочитать mandatory plan от base `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`; unresolved obligations должны блокировать Gate 2.

## 2. RED and Gate 3

- [x] 2.1 Root добавляет bounded executable matrix A–M и public harness/wrapper sensitivity fixture; focused test на base должен дать RED именно из-за ложного `INTENDED_RED`, не setup failure.
- [x] 2.2 Сохранить RED evidence через harness и получить независимый Gate 3 `APPROVED` для полного contract/tests/plan candidate.

## 3. Minimal implementation

- [x] 3.1 Отдельный executor добавляет минимальный structured observation provenance в существующий wrapper/runner без изменения product tests или evidence redesign; executable matrix должна стать GREEN.
- [x] 3.2 Проверить сохранение raw command verdict, child exit и full diagnostic evidence, а также healthy intended-RED/setup-failure lifecycle fixtures bounded focused commands.

## 4. Review and delivery

- [x] 4.1 Запустить planner-selected focused verification без local full suite и сохранить exact-source records.
- [x] 4.2 Получить независимый Gate 5 `APPROVED` по reconstructible exact source; corrections проходят требуемый rereview.
- [ ] 4.3 Выполнить один exact-source GitHub CI run выбранным existing consumer и подготовить PR-ready handoff без merge/deploy/settings.
