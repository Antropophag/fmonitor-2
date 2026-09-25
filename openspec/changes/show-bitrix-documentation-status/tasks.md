## 1. Gate 1–3

- [x] 1.1 Зафиксировать BITRIX-DOCUMENTATION-INTEGRATION-STATUS-001, OpenSpec artifacts и verification input; проверить strict validation и прочитать все planner obligations.
- [x] 1.2 Написать focused HTTP/browser tests матрицы A1–A8 и сохранить intended RED от отсутствующего document status reader/UI.
- [x] 1.3 Получить planner-required независимый Gate 3 review спецификации/tests, если его выберет plan; исправить findings до реализации.

## 2. Gate 4

- [x] 2.1 Реализовать локальный bounded read adapter для filtered jobs/events, correlation attempts, validated last success и independent pagination; подтвердить focused test.
- [x] 2.2 Подключить adapter в IntegrationStatusController и реализовать document partial/аккуратную desktop+narrow композицию существующего view без общих assets; подтвердить HTTP/browser/read-only/security matrix.
- [x] 2.3 Выполнить planner-selected bounded local checks, HTTP qualification, architecture/OpenSpec checks и mechanical UI detector без полного локального make test/make verify.

## 3. Gate 5 и публикация

- [x] 3.1 Получить независимый final review exact candidate, устранить findings и повторно проверить изменённый delta.
- [ ] 3.2 Создать commit/PR без merge/deploy, запустить один exact-source CI consumer и записать SHA, review, CI и незакрытый остаток #251.
