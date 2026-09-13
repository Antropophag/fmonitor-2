## 1. Scope и Gates 1–3

- [x] 1.1 Утвердить `TEMPLATE-OFFER-REVEAL-001` как полный Gate 1 контракт #53 и проверить, что #52, domain/application seams и зоны #76 исключены; verification: независимый scope/spec verdict и OpenSpec strict validation.
- [x] 1.2 Сгенерировать и прочитать exact verification plan из `verification-input.json`, разрешить все UNKNOWN obligations до Gate 2; verification: `change-verification.py check` GREEN и сохранён digest package.
- [x] 1.3 Root пишет минимальные public-seam HTML/DOM/browser RED для A1–A6 и фиксирует intended failures без setup failure; verification: bounded plan commands дают ожидаемые RED/GREEN outcomes.
- [x] 1.4 Получить независимый Gate 3 review полного spec/test/plan candidate; verification: `reviews/tests/TEMPLATE-OFFER-REVEAL-001.md` содержит exact source и `APPROVED` до implementation.

## 2. Gate 4

- [x] 2.1 Отдельный executor реализует server-rendered readiness/snapshot contract в Yii2 selection view без изменения POST/domain behavior; verification: approved focused HTML contract GREEN.
- [x] 2.2 Executor связывает readiness и exact-snapshot state с существующим vanilla JS без таймеров/focus и добавляет accessible CSS motion/narrow behavior; verification: approved browser test и retained preopening browser regression GREEN.
- [x] 2.3 Выполнить обязательные focused boundary checks для изменённых `app/YiiRuntime`/общих assets и зафиксировать фактические результаты отдельно от approval; verification: generated focused plan GREEN, включая template negative boundary и HTTP qualification.

## 3. Gate 5 и Done

- [x] 3.1 Получить независимый Gate 5 review полного executable candidate; verification: `reviews/code/TEMPLATE-OFFER-REVEAL-001.md` содержит findings disposition, exact source и `APPROVED`.
- [ ] 3.2 Сверить отсутствие пересечения с актуальным #76, подготовить PR-ready exact commit и один GitHub full Quality Graph run; verification: harness связывает exact head/source, CI `SUCCESS` и literal `VERIFY_OK`, UNKNOWN не принимается.
- [ ] 3.3 Завершить delivery record/OpenSpec только после Gates 1–5 и GREEN; verification: поведение #53 поставлено без #52, deployment/merge выполняются только при отдельной действующей авторизации.
