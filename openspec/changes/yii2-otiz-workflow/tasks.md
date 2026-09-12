## 1. Scope и Gates 1–3

- [x] 1.1 Снять полный method/path/form/asset/owner inventory OTIZ из Yii2, `RapidPilotOtiz`, JS и всех `verify-otiz-*`; проверить, что каждый production route классифицирован как переносимый или явно вне среза.
- [x] 1.2 Создать нормативный `specs/YII2-OTIZ-WORKFLOW-001.md` с public seam, observable results, persisted/no-new-fact outcomes, permissions, CSRF, replay/concurrency, return path, export и adjacent settlement flows; проверить review-ready completeness по Gate 1.
- [x] 1.3 Создать `verification-input.json`, выполнить `python3 tools/delivery/harness.py prepare`, прочитать полный обязательный Quality Graph plan и закрыть либо обосновать каждую категорию до Gate 2.
- [x] 1.4 Написать complete intended RED через реальный Yii2/public HTTP/browser и runtime dependency seam; выполнить только bounded focused команды и записать детерминированную intended failure в `reviews/tests/YII2-OTIZ-WORKFLOW-001.md`.
- [x] 1.5 Получить независимый sol/low Gate 3 по exact prepared source; при `CHANGES_REQUESTED` исправить весь findings list и повторно проверить Gate 1/2, при `APPROVED` зафиксировать reconstructible source/package.

## 2. Yii2 OTIZ journey

- [x] 2.1 Добавить Yii2 calculate route/controller wiring к `SnapshotPublication::buildAndPublish`; проверить success, invalid date/operation, replay/concurrency, rollback и отсутствие прямых controller SQL writes focused-тестами.
- [x] 2.2 Добавить Yii2 accept route/controller wiring к `SnapshotPublication::accept`; проверить blockers, duplicate/already accepted, unauthorized/CSRF, no-new-fact rejection, audit/append-only history и redirect contracts focused-тестами.
- [x] 2.3 Завершить Yii2 overview/snapshot/history/register presentation и navigation с существующими read owners; проверить текущие filters/pagination/status/trace/issue browser contracts без изменения формул.
- [x] 2.4 Завершить Yii2 XLSX export через существующий exporter/read seam; проверить accepted-only denial, workbook sheets/metadata/amounts и response headers на публичном HTTP seam.
- [x] 2.5 Сохранить уже поставленные discipline/payment/reverse actions как adjacent flow; выполнить их regression matrix вместе с calculate → inspect → accept → export и доказать единый финансовый owner.

## 3. Runtime frontier и проверка

- [x] 3.1 Удалить только заменённые production route dispatch/includes/assets `RapidPilotOtiz`, сохранив characterization/verifiers; проверить dependency scan, architecture checks и отсутствие runtime load на каждом перенесённом URL.
- [x] 3.2 Выполнить bounded focused/fast checks из verification plan, `git diff --check` и обязательные visual/focus проверки для изменённого frontend; записать команды, source digest и результаты без локального full `make test`/`make verify`.
- [ ] 3.3 Получить независимый sol/low Gate 5 по полному reconstructible candidate; исправить полный findings list, повторно проверить затронутые boundaries и добиться явного `APPROVED`.
- [ ] 3.4 Актуализировать candidate от текущего `origin/main` после параллельного imports/workforce slice, разрешая только фактические roster/web-config пересечения; повторно выполнить harness prepare и review изменившейся дельты.
- [ ] 3.5 Создать PR и запустить один exact-source Quality Graph CI; проверить все jobs и `REGRESSION_FAILURE` inventory, получить `VERIFY_OK`, merge only reviewed matching source и записать delivery checkpoint. Deployment оставить `UNKNOWN` без отдельной авторизации.

## 4. Done definition

- [ ] 4.1 Подтвердить, что полный существующий OTIZ calculate → inspect → accept → export → payment → reverse обслуживается Yii2, state changes идут через `app/Otiz`, formulas/history/roles сохранены, production не dispatch-ит заменённый `RapidPilotOtiz`, Gates 3/5 APPROVED и exact-source CI GREEN; общий cutover и нерешённый acceptance redesign явно остаются открыты.
