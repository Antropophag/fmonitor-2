## 1. Candidate и Gate 1

- [x] 1.1 Интегрировать или явно включить exact-source predecessor `add-minimal-operational-dashboard` в отдельный чистый worktree, обновить current-delivery goal/авторизацию и проверить, что dashboard files достижимы из base без текущего WIP №157
- [x] 1.2 Root создаёт нормативный контракт `specs/YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001.md` с разделом «Простыми словами», тремя подтверждёнными public seams, полной acceptance matrix и independently calculated examples; проверить ручным сопоставлением со всеми OpenSpec requirements
- [x] 1.3 Root создаёт `verification-input.json`, запускает `python3 tools/delivery/harness.py prepare`, читает все planner obligations/selected checks и фиксирует отсутствие unresolved coverage до Gate 2

## 2. Root-authored RED candidate

- [x] 2.1 Root добавляет data-seam RED fixture для шести взаимоисключающих стадий, шести недель с переносом срока, пяти activity buckets, unknown dates и independently expected totals; проверить, что focused команда падает именно из-за отсутствующих диаграмм
- [x] 2.2 Root добавляет HTTP RED coverage для RBAC, `GET|HEAD`, stage/week/activity drill-down, invalid/conflicting filters, pagination total, atomic error/empty states и DB fingerprint read-only; проверить intended RED без setup failure
- [x] 2.3 Root добавляет browser RED coverage для visible values/labels, keyboard links/focus, accessible names, 1440/390 layout и отсутствия page overflow; проверить intended RED на predecessor UI
- [x] 2.4 Если planner требует Gate 3, подготовить reconstructible source snapshot и получить независимый `gpt-5.6-sol / low` APPROVED test review в `reviews/tests/YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001.md`; при замечаниях вернуть весь кандидат к соответствующему Gate 1/2 пункту

## 3. Executor — data и drill-down

- [ ] 3.1 Отдельный `gpt-5.6-sol / low` executor из prepared role package выделяет общий canonical status classification owner и подключает его к queue/dashboard; проверить parity fixture для всех шести статусов и сумму stage total
- [ ] 3.2 Executor добавляет bounded aggregates для шести календарных недель с актуальным подтверждённым переносом срока; проверить worked examples, query-count envelope и measured 30k fixture/`EXPLAIN`
- [ ] 3.3 Executor добавляет bounded activity aggregation по server-accepted checklist/photo/completion evidence и пяти buckets; проверить границы 7/8/14/15/30/31, no-activity и исключение неподтверждённого device state
- [ ] 3.4 Executor добавляет allowlisted stage/week/activity filters в существующий object-register seam с fail-closed `400`, обычным `objects.read`, search/page composition и server-derived cutoff; проверить HTTP RED из 2.2 становится GREEN

## 4. Executor — presentation

- [ ] 4.1 Executor расширяет dashboard DTO/controller/view тремя атомарными charts и безопасными empty/error states; проверить data/HTTP tests и отсутствие production writes
- [ ] 4.2 Executor переносит только нужные публичные `shlz-ui` Dashboard/Chart Widget contracts с provenance, добавляет application-owned semantic bar/list composition без chart dependency; проверить public-export/architecture guards
- [ ] 4.3 Executor реализует full-width stage widget и responsive paired widgets с visible values, text legend/summary, native links и focus-visible; проверить browser tests на 1440/390 и отсутствие horizontal page overflow
- [ ] 4.4 Выполнить одну batched desktop/mobile visual inspection, исправить найденные материальные дефекты одним пакетом, подтвердить не более чем одним дополнительным capture round и один раз запустить `impeccable detect --json` по изменённым UI targets

## 5. Gate 4–5 и публикация

- [ ] 5.1 Root сверяет полный candidate с prepared obligations, запускает только planner-selected bounded focused checks и OpenSpec strict validation; сохранить команды, elapsed time и полную failure inventory без локального `make test`/`make verify`
- [ ] 5.2 Зафиксировать reconstructible exact-source snapshot/commit и получить независимый `gpt-5.6-sol / low` Gate 5 APPROVED review в `reviews/code/YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001.md`, включая spec/tests/code/visual evidence и каждый изменённый boundary
- [ ] 5.3 После corrections при необходимости переподготовить plan/reviews по правилам процесса, затем опубликовать PR-ready candidate и выполнить один selected exact-source GitHub CI consumer; собрать полный failed-job/`REGRESSION_FAILURE` inventory при любом сбое
- [ ] 5.4 Обновить delivery record и OpenSpec task state точными source/review/CI ссылками, авторами root/executor/reviewers, повторами и оставшимися `UNKNOWN`; Done означает GREEN focused checks, required independent reviews и GREEN exact-source CI, но не merge/deploy
