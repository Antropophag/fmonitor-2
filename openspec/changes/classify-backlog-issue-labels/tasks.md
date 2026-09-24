## 1. Контекст и Gate 1

- [x] 1.1 Материализовать отдельный task context для issue #256 от свежего `origin/main`, зафиксировать owner authorization и root/executor/reviewer authorship; проверить `harness.py state`, что source/worktree не пересекается с чужим WIP.
- [x] 1.2 Создать canonical executable contract и root-authored acceptance tests для exact label schema, cardinality, сохранности unrelated labels, issue/PR boundaries, idempotency comments, repository rules и итогового report; получить честное RED на отсутствующем результате.
- [x] 1.3 Создать `verification-input.json`, вычислить и прочитать mandatory verification plan по `tools/delivery/change-verification.md`; устранить unmapped obligations и пройти требуемый независимый Gate 1 review до внешних записей.

## 2. Полный snapshot и классификационный план

- [x] 2.1 Получить с пагинацией before snapshot всех repository labels и открытых issues без pull requests, включая descriptions, существенные comments, relations/PR и timestamps; сохранить компактный evidence вне checkout и проверить counts/API pagination.
- [x] 2.2 Проверить актуальный `main`, #169, относящиеся к labels части #95 и фактические consumers всех предлагаемых имён; доказать отсутствие конфликта с `quality-graph:*`, automation и queue authorization либо остановиться с конкретным конфликтом.
- [x] 2.3 Подготовить полный compact plan `{number,type,prep,statuses,reasons,observedUpdatedAt}`; проверить ровно один type, prep только для executable issues, конкретные причины needs-work/blocked и отсутствие status без актуального основания.
- [x] 2.4 Независимо проверить coverage и спорные строки плана до mutation; неизвестную readiness вернуть в `prep:triage`, а неясный обязательный type вынести владельцу как blocker.

## 3. Применение GitHub labels

- [x] 3.1 Выполнить conflict preflight и создать/обновить только десять labels с точными русскими descriptions и group colors; повторным чтением проверить exact metadata и неизменность сторонних/`quality-graph:*` labels.
- [x] 3.2 Для каждой запланированной issue перечитать current state/updatedAt/labels, пропустить закрытую, переоценить изменившуюся и применить только точечные add/remove operations десяти labels; проверить response каждой mutation.
- [x] 3.3 Добавить только недублирующие краткие comments для актуальных `prep:needs-work` и `status:blocked`; проверить существующие существенные comments до записи и сохранить append-only history.
- [x] 3.4 Повторно получить полный open set, классифицировать новые issues или явно зафиксировать границу, затем проверить schema cardinality, preservation, отсутствие PR/closed mutations и идемпотентность dry rerun.

## 4. Репозиторные правила и отчёт

- [x] 4.1 Добавить компактный `docs/issue-labels.md` со схемой, readiness/status правилами, authorization warning и требуемыми filters; focused documentation test должен пройти.
- [x] 4.2 Добавить в `AGENTS.md` одну ссылку на `docs/issue-labels.md` без изменения queue/delivery полномочий; focused link/policy test должен пройти.
- [x] 4.3 Добавить короткий delivery report с UTC time/coverage, before/after counts, distributions, needs-work/blocked reasons, stale/duplicate findings, точными GitHub/repository mutations и неприменёнными UNKNOWN/failed operations; явно указать, что GitHub mutations не откатываются исходом PR.

## 5. Проверка и PR-ready

- [x] 5.1 Запустить только planner-selected bounded focused checks и `git diff --check`, записать результаты отдельно от formal approvals; локальные полные `make test`/`make verify` не запускать.
- [x] 5.2 Подготовить exact-source harness package и получить все planner-required независимые Gate 3/final reviews отдельными gpt-5.6-sol/low reviewers; исправления executor не должен self-approve.
- [ ] 5.3 Опубликовать один bounded PR и запустить ровно один selected exact-source GitHub CI consumer; при failure сначала собрать полный failed-job/`REGRESSION_FAILURE` inventory и не выдавать UNKNOWN за GREEN.
- [ ] 5.4 Повторно сверить GitHub labels/open issues после CI, обновить factual report при drift и довести harness state до PR-ready без merge/deployment; Done означает применённую и перепроверенную разметку, repository candidate, требуемые reviews и exact-source CI evidence.
