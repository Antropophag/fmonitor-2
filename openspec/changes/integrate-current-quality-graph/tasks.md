## 1. Contract and independent review

- [ ] 1.1 Зафиксировать executable spec и точные report/native/provenance outcomes; проверить OpenSpec strict validation.
- [ ] 1.2 Написать публичные CLI/transport/workflow negative tests, получить intended RED и сохранить source/commands.
- [ ] 1.3 Получить независимый Gate3 APPROVED на spec/tests/RED до implementation.

## 2. Implementation

- [ ] 2.1 Добавить report CLI и declaration/compiler validation; focused full/docs-only/negative tests GREEN.
- [ ] 2.2 Подключить pinned collect к нынешнему CI без повтора категорий; проверить workflow/old matrix tests.
- [ ] 2.3 Подключить штатный trusted publisher с явно разрешёнными permissions; transport matrix GREEN без PR execution.
- [ ] 2.4 Выполнить focused regression/architecture, независимый Gate5 и один полный CI на exact head; сохранить результаты.

## 3. Actual delivery

- [ ] 3.1 Выполнить reviewed bootstrap merge и проверить реальный positive publisher run на representative PR, exact source/digest/attempt/check/comment/labels.
- [ ] 3.2 Проверить actual negative matrix: failed command, missing/malformed/duplicate/stale evidence, повтор события и changed head; сохранить URLs и expected/actual results.
- [ ] 3.3 Сверить Done, сохранить retained/deferred boundaries честно, синхронизировать specs/архив и закрыть #25 связанными PR после всех доказательств.
