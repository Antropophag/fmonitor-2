## 1. Контракт и RED

- [x] 1.1 Зафиксировать `OTIZ-FINANCIAL-AMOUNT-PRESENTATION-001`, OpenSpec scope и семантику обоих полей по production readers/builders; проверить `openspec validate --strict`.
- [x] 1.2 Подготовить verification plan, дополнить зарегистрированный authenticated browser test изолированными fixtures и получить причинный RED на scoped label/value, zero copy, multi-snapshot, reversal и desktop/narrow behavior.
- [x] 1.3 Получить независимый planner-required Gate 3 review спецификации, теста и RED; проверить явный `APPROVED` до production implementation.

## 2. Минимальная реализация

- [x] 2.1 Исправить только подписи и пояснения в существующем financial drawer и zero state; проверить focused browser GREEN без изменений чисел, фактов, действий и истории.
- [x] 2.2 Выполнить planner-selected bounded checks, syntax, `git diff --check` и релевантную architecture verification; полный локальный suite не запускать.

## 3. Независимое завершение и PR-ready

- [x] 3.1 Получить независимый final review exact candidate; проверить явный `APPROVED` и отсутствие scope creep.
- [ ] 3.2 Создать отдельный PR и выполнить один exact-source GitHub CI через выбранный consumer; при сбое собрать полный failure inventory, GREEN не выводить из `UNKNOWN`.
- [ ] 3.3 Подтвердить PR/HEAD, изменённые подписи, проверенные примеры и byte-equivalent числовые результаты; merge/deploy/реальные финансовые операции не выполнять.
