## 1. Gate 1 — contract and verification plan (root)

- [x] 1.1 Создать normative executable spec `YII2-ORIGINAL-OBJECT-IDENTITY-001` с полной acceptance matrix для initial/correction/history, effective/manual fallback, missing values, escaping/responsive behavior, authorization, replay и неизменности документов; проверить traceability к #243.
- [x] 1.2 Создать `verification-input.json`, выполнить штатный `harness.py prepare`, прочитать все planner obligations/commands и устранить unresolved coverage до Gate 2; зафиксировать выбранные lane и `required_reviews`.

## 2. Gate 2/3 — complete RED candidate (root + independent reviewer)

- [x] 2.1 Написать focused real HTTP/browser tests на disposable fixtures для initial upload, correction после manual edits, history, missing numbers, long/hostile address, denied access, narrow viewport и отсутствие side effects; показать intended RED на выводе internal ID/effective mismatch без изменения product code.
- [ ] 2.2 Если planner требует Gate 3, подготовить exact-source package и получить независимый sol/low `APPROVED` review спецификации, acceptance mapping и RED evidence; иначе записать planner-selected отсутствие Gate 3.

## 3. Gate 4 — minimal implementation (separate executor)

- [ ] 3.1 Отдельным sol/low executor реализовать минимальный авторизованный read adapter через существующий effective-details owner и передать закрытую identity projection в original form/history controllers; focused tests подтверждают отсутствие второго resolver и deny-before-read.
- [ ] 3.2 Обновить только original form/history views: primary regnumber, address/entrance context, secondary factory number, explicit missing labels и escaping при сохранении технических IDs и document identifiers; focused HTTP/browser tests становятся GREEN.
- [ ] 3.3 Выполнить planner-selected bounded local checks, architecture/related regressions и реальные desktop/narrow browser сценарии на собственных disposable resources; подтвердить upload/correction/replay и неизменность PDF/hash/history bytes, не запуская локальный полный `make test`/`make verify`.

## 4. Gate 5 and publication (independent reviewer + root)

- [ ] 4.1 Захватить exact candidate source и получить planner-required независимый sol/low final review с одним полным findings list и явным `APPROVED`; исправления к code/tests повторно проверить по процессу.
- [ ] 4.2 Root проверяет полноту candidate, commits/authorship/evidence, rebase/conflicts с актуальным `origin/main` и отсутствие изменений параллельных boundaries; все OpenSpec tasks и delivery record соответствуют фактическому состоянию.
- [ ] 4.3 Push exact committed candidate, открыть PR с `Closes #243`, запустить один штатный exact-source CI consumer, собрать полный failed-job/`REGRESSION_FAILURE` inventory при сбое и довести PR до green либо указать конкретный внешний blocker; merge/deploy не выполнять.
