## 1. Scope, executable contract and RED

- [x] 1.1 Root обновляет `docs/operations/current-delivery-goal.md`, создаёт normative `specs/MINIMAL-OPERATIONAL-DASHBOARD-001.md` и `verification-input.json`, фиксируя owner authorization, base/source, actor, public seam, четыре формулы, A–N acceptance matrix, explicit non-goals и фактических авторов; verification: `python3 tools/delivery/harness.py prepare` создаёт свежий план без unresolved obligations.
- [x] 1.2 Root добавляет executable RED через публичные data/HTTP/browser seams: авторизация/return path/navigation, independently computed counts и date boundaries, canonical-status parity, stable top-5, empty/error, GET/HEAD/repeat/concurrent read-only fingerprint, constant query/materialization bound, 30k fixture, `shlz-ui` public-export provenance и desktop/mobile behavior; verification: selected focused command падает только из-за отсутствующего dashboard behavior.
- [x] 1.3 Независимый gpt-5.6-sol/low reviewer проверяет complete spec/test/RED candidate по planner-required Gate 3 и записывает один полный findings list/verdict; verification: unresolved finding блокирует executor, APPROVED связывается с exact reconstructible source.

## 2. Bounded read model и публичный маршрут

- [x] 2.1 Отдельный gpt-5.6-sol/low executor реализует canonical read model в `InstallationProcess` и factory wiring: одна дата среза, permission/schema fail-closed, четыре server-side aggregate и два stable `LIMIT 5` списка без DDL/cache; verification: data/parity/boundary/read-only/query-bound/30k focused tests GREEN.
- [x] 2.2 Executor добавляет Yii `GET|HEAD /pilot/dashboard`, access/error handling, первый RBAC-скрытый navigation item без перестановки существующих ссылок и корректные переходы в реестр/карточки; verification: HTTP auth, HEAD, navigation-order, return-path и no-disclosure focused tests GREEN.

## 3. shlz-ui экран и демонстрация

- [x] 3.1 Executor переносит только публичный выпущенный Dashboard/Chart Widget CSS contract из `../shlz-ui` в закреплённый consumer asset с source/version/hash evidence и строит server-rendered Operate view на Dashboard, Chart Widget, status/link/button/empty-state без chart/alternative UI dependency; verification: provenance/static contract tests GREEN.
- [x] 3.2 Executor реализует четыре текстово объяснённых показателя, два bounded списка, unified empty/error states и one-column mobile composition; verification: focused browser flow проходит на 1440 и 390 без horizontal overflow и без hidden full dataset.
- [x] 3.3 Executor добавляет короткий demo script с четырьмя основаниями расчёта, переходом к объекту и отдельными feedback questions; verification: documentation contract подтверждает только реально реализованные возможности.
- [x] 3.4 После завершения UI запустить один mechanical Impeccable detector по изменённым targets и bounded screenshot pass desktop+mobile, исправить findings одной пачкой и выполнить не более одной confirmation pass; verification: сохранены валидные captures и detector output для независимого review package.

## 4. Проверка и независимое решение

- [x] 4.1 Запустить только planner-selected focused checks, OpenSpec strict validation, `make architecture-check` и `git diff --check`; canonical full `make test`/`make verify` локально не запускать; verification: harness records привязаны к неизменному exact source, повторы и причины записаны.
- [x] 4.2 Независимый gpt-5.6-sol/low final reviewer проверяет exact complete candidate, contract mapping, security/no disclosure, data semantics, bounded queries, read-only facts, public `shlz-ui`, responsive screenshots и demo honesty; verification: все findings разрешены до APPROVED Gate 5 и verdict называет exact source/snapshot.
- [ ] 4.3 Подготовить PR из `codex/issue-21-minimal-dashboards`, запустить один selected exact-source GitHub CI consumer и при failure сначала собрать полный failed-job/`REGRESSION_FAILURE` inventory; verification: delivery record честно различает implementation, PR, CI, merge/deploy и сохраняет UNKNOWN там, где live evidence отсутствует.

## 5. Done definition

- [ ] 5.1 Change завершён только когда все требования `MINIMAL-OPERATIONAL-DASHBOARD-001` наблюдаемы на public seam, focused evidence GREEN, Gate 3/Gate 5 APPROVED согласно planner, exact-source CI GREEN и PR готов к ручному решению владельца; merge/deploy/settings не выполнять без отдельного поручения.
