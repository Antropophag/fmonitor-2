## 1. Контракт и verification plan

- [x] 1.1 Создать нормативную executable spec `OTIZ-OBJECT-LEDGER-HISTORY-001` с простым описанием и полной acceptance matrix из delta spec; проверить traceability к #29/#169 и отсутствие изменений формулы.
- [x] 1.2 Создать `verification-input.json`, выполнить `harness.py prepare` для этого worktree и проверить, что planner obligations, lane, required reviews и команды полностью прочитаны и не содержат нерешённых coverage gaps.

## 2. Gate 2 — root-authored RED

- [x] 2.1 Добавить изолированные MariaDB fixtures и публичный HTTP test для A payment, B deductions, C reversal, соседнего объекта, XSS basis, пустого результата, invalid context, denied actor и before/after persistence inventory; подтвердить RED только на отсутствующей истории.
- [x] 2.2 Добавить проверки server pagination, full-set totals, stable ordering, out-of-range behavior, saved-versus-current labels, GET/HEAD и доступных source transitions; подтвердить RED и записать команду/вывод в `reviews/tests/OTIZ-OBJECT-LEDGER-HISTORY-001.md`.
- [x] 2.3 Если planner требует Gate 3, передать полный spec/test/source package независимому reviewer и устранить все findings до verdict `APPROVED`.

## 3. Gate 4 — реализация executor

- [ ] 3.1 Отдельному executor реализовать SQL-filtered consistent read одной страницы, count/full signed totals, source/reversal relations и object-in-snapshot validation; focused read-model/HTTP tests SHALL стать GREEN.
- [ ] 3.2 Отдельному executor добавить lazy transition и локальное представление в существующем snapshot/object context с escaped basis, distinct saved/current copy, pagination, empty/error states и без financial actions; focused HTTP tests SHALL стать GREEN.
- [ ] 3.3 Выполнить только planner-selected bounded local checks, обязательный architecture check и HTTP qualification при касании `app/PilotHttp/*.php`; полный локальный `make test`/`make verify` не запускать и сохранить точные результаты.

## 4. Browser и независимая приёмка

- [ ] 4.1 На disposable Compose project/ports/volumes пройти реальный browser flow из B через A/B/C, source links, pagination, empty/denied/XSS cases на desktop и narrow width; подтвердить отсутствие записей в DB/queue и сохранить evidence вне checkout.
- [ ] 4.2 Подготовить exact-source review package и получить planner-required независимый final review с одним полным findings list; исправления к production/test source SHALL быть повторно проверены независимо.

## 5. Exact-source CI и PR

- [ ] 5.1 Зафиксировать проверенный source, push отдельной branch и открыть PR к свежему `main` без merge/auto-merge; проверить реальные пересечения с #241/#30/#243.
- [ ] 5.2 Запустить один штатный exact-source GitHub CI consumer, собрать полный failed-job и `REGRESSION_FAILURE` inventory при любом сбое и довести exact PR SHA до обязательного GREEN без локального full suite.
- [ ] 5.3 Заполнить delivery record/handoff: PR/SHA, production paths, RED/GREEN, review verdicts, CI, пользовательский сценарий и явно незакрытый остаток полной #29; проверить, что issue #29 не закрывается автоматически.
