## 1. Gate 1 — executable contract

- [x] 1.1 Провести независимый review `specs/PILOT-ROUTE-CSP-001.md` против owner decision, route/render inventory и трёх известных regressions; verification: review перечисляет exact allowlist, success/error/redirect/asset/checklist cases и verdict без self-approval.
- [x] 1.2 Получить отдельное owner approval конкретной reviewed версии executable spec; verification: approval record называет `PILOT-ROUTE-CSP-001` и version/hash, после чего статус меняется с DRAFT на APPROVED без изменения требований.

## 2. Gates 2–3 — RED и независимый test review

- [x] 2.1 Написать минимальный black-box RED на public HTTP seam для scripted `GET/HEAD 2xx HTML`, successful scripted `POST /pilot/login`, script-free `2xx HTML`, login/other error, redirect, asset, exact near-miss и checklist variants; verification: captured command/output различает intended assertion failures от setup failure и ссылается на spec scenarios.
- [x] 2.2 Добавить RED для удаления inline CompletionFlow fragment и сохранения cap `85/100` через внешний behavior; verification: тест падает на существующем inline script либо отсутствующем external behavior, а не на DB/environment.
- [x] 2.3 Поручить независимому test reviewer проверить traceability, exact expected headers, route-inventory sensitivity, HEAD/body behavior и forbidden inline/eval/third-party cases; verification: final fresh approval recorded in `reviews/tests/PILOT-ROUTE-CSP-001-v4.md` after reviewed Gate 2 corrections.

## 3. Gate 4 — minimal GREEN

- [x] 3.1 Реализовать pure route/result CSP classifier в HTTP boundary с fail-closed base default и exact scripted/checklist patterns; verification: focused classifier/public response tests green, неизвестные/ошибочные/non-HTML paths не получают `script-src`.
- [x] 3.2 Подключить classifier к обоим coordinator response paths и ограниченно синхронизировать direct rapid-pilot responders без изменения status/body/cache/security contracts; verification: black-box matrix green для production entrypoint и direct routes.
- [x] 3.3 Удалить inline injection из `RapidPilotCompletionFlow::enhanceChecklist` и перенести cap behavior в существующий checklist asset; verification: inline count равен нулю, cap `85/100` verifier и completion characterization green, новая domain logic в rapid-pilot отсутствует.
- [ ] 3.4 Синхронизировать три известных CSP regression verifier только с утверждённой route-scoped matrix; verification: `pilot_demo_bootstrap_001_test.php`, `pilot_http_auth_001_test.php`, `pilot_shlz_assets_001_test.php` проходят без ослабления non-success/non-HTML assertions.

## 4. Regression, architecture и Gate 5

- [x] 4.1 Запустить focused CSP/auth/UI/checklist suites и зафиксировать команды/результаты; verification: все owned scenarios green, unrelated failures отдельно классифицированы.
- [x] 4.2 Запустить `make architecture-check` после hotspot/boundary edits; verification: green без расширения rapid-pilot mutation/SQL/DDL baseline и с записанным justification bounded presentation removal.
- [x] 4.3 Запустить `make verify`; verification: canonical stages green либо только заранее доказанные unrelated failures сохранены без изменения assertions и перечислены в evidence.
- [x] 4.4 Поручить независимому code reviewer проверить approved spec, approved tests, diff и verification evidence; verification: `reviews/code/PILOT-ROUTE-CSP-001.md` имеет `APPROVED`, test changes возвращают slice в Gate 2.

## 5. Done

- [ ] 5.1 Обновить operations status/backlog и закрыть slice только когда OpenSpec strict validation, Gates 1–5, focused regression, architecture check и full verification evidence согласованы; verification: `PILOT-ROUTE-CSP-001` отмечен DONE, три CSP regressions исчезли, inline CompletionFlow script отсутствует, а allowlist не шире утверждённой.
