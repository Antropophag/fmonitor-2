## 1. Контракт и Gate 2

- [x] 1.1 Root создаёт нормативную executable spec с полной матрицей этапов/прав, явными non-goals и acceptance mapping; проверить review-ready полноту против OpenSpec delta и пользовательской приёмки.
- [x] 1.2 Root создаёт `verification-input.json`, запускает `tools/delivery/harness.py prepare`, читает все обязательства planner и фиксирует lane/required reviews; unresolved obligations должны быть равны нулю до Gate 2.
- [x] 1.3 Root пишет детерминированные RED-тесты публичного `GET|HEAD /pilot/objects/{id}` и browser flow на изолированной task-owned DB: pending composition, applied-vs-pending, документы, полную action matrix, роли, refresh/return, #226 regressions и construction-control consumer; выполнить только planner-selected focused команды и сохранить intended RED evidence.
- [x] 1.4 Если plan требует Gate 3, независимый `gpt-5.6-sol/low` reviewer проверяет полный spec/test candidate и RED evidence; записать один полный verdict в `reviews/tests/` и устранить все findings до реализации.

## 2. Реализация отдельным executor

- [x] 2.1 Отдельный `gpt-5.6-sol/low` executor добавляет в `InstallationProcess` независимый read-model ожидающего состава, переиспользуя semantics существующего selection read; focused projection tests подтверждают, что `order`, `confirmedOriginal`, `opened` и applied crew не меняются.
- [x] 2.2 Executor передаёт существующий upload access и другие exact capability facts через controller без новых permissions или authorization rules; focused authorization tests проходят.
- [x] 2.3 Executor согласует верхний блок, «Команду» и «Документы» и реализует приоритетную матрицу одного главного действия без изменения layout/shared styles; focused HTML/browser tests подтверждают все этапы и отсутствие ложных документов/команд.
- [x] 2.4 Executor сохраняет редактор реквизитов, append-only историю и «Показать ещё» из PR #226; соответствующие focused regressions и refresh/return browser scenario проходят.

## 3. Проверка и PR-ready

- [x] 3.1 Выполнить все planner-selected bounded local checks на изолированной test DB, включая релевантные architecture/HTTP checks и неизменность construction-control readiness consumer; не запускать локально полный `make test`/`make verify`.
- [x] 3.2 Создать reconstructible exact-source snapshot либо checkpoint commit и подготовить final reviewer package через harness; source digest и candidate должны включать все specs/tests/code/review corrections.
- [ ] 3.3 Независимый `gpt-5.6-sol/low` reviewer выполняет planner-required Gate 5 по exact source и записывает полный verdict в `reviews/code/`; все findings исправлены и изменённый delta повторно reviewed.
- [ ] 3.4 Запустить ровно один требуемый exact-source GitHub CI consumer, собрать полный failed-job/`REGRESSION_FAILURE` inventory при сбое и получить GREEN без локального full-suite rerun.
- [ ] 3.5 Подготовить PR-ready branch/PR без merge и deployment; delivery record фиксирует baseline PR #226/main, авторов root/executor/reviewer, проверки, CI, elapsed/rework и отсутствие изменений ОТиЗ/#171/стройконтроля.
