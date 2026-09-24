## 1. Gate 1 и executable RED

- [x] 1.1 Root зафиксировать canonical `specs/INSPECTION-PLANNING-001.md`, owner decisions и delivery record для issue #14; verification: contract acceptance matrix явно покрывает object-bound identity, engineer/FKR scope, no-reason reschedule/cancel, planning-only non-goals и current `origin/main` source.
- [x] 1.2 Root создать `verification-input.json`, построить mandatory Quality Graph plan через documented `tools/delivery/change-verification.md` route и проверить freshness; verification: план перечисляет все изменяемые boundaries и acceptance obligations до написания RED.
- [x] 1.3 Root добавить executable RED через public application/migration/read seams: create, смена инженера, engineer/FKR scope denial, reschedule/cancel без причины, replay/concurrency, current-plan read, past-plan no-outcome и schema conflicts; verification: focused suite падает только на отсутствующем issue #14 owner behavior.
- [x] 1.4 Независимый `gpt-5.6-sol/low` reviewer проверить scope/spec/verification plan/RED и записать Gate 3 APPROVED либо findings; verification: unresolved finding блокирует executor.

## 2. Canonical owner и persistence

- [x] 2.1 Отдельный `gpt-5.6-sol/low` executor реализовать object-bound planning aggregate и единственный public seam create/reschedule/cancel с expected version, opaque request identity и atomic append-only events; verification: domain/replay/concurrency RED становится GREEN без результата инспекции и без обязательной причины.
- [x] 2.2 Executor добавить additive canonical migration/preflight/fingerprint/restore inventory для object-bound uniqueness, versions и receipts, сохранив legacy ids/history и fail-closed multi-current conflict; verification: clean, compatible populated, conflict, repeat, rollback и no-runtime-DDL fixtures проходят.
- [x] 2.3 Executor реализовать server-side exact capability + current object-scope reauthorization для инженера стройконтроля и Руководителя ФКР; verification: allowed, denied, stale-scope и no-disclosure HTTP/application cases проходят.

## 3. Read seam и planning-only boundary

- [x] 3.1 Executor реализовать canonical object current-plan read seam поверх plan/events; verification: create/reschedule/cancel/past-date scenarios возвращают одну детерминированную current projection без DML.
- [x] 3.2 Executor сохранить planning-only boundary: прошедшая дата не пишет outcome/missed/checklist/progress/evidence facts и допускает новый будущий plan; verification: exact unrelated-facts snapshots остаются byte-equivalent.
- [x] 3.3 Зафиксировать issue #255 как единственный зависимый Yii presentation slice; verification: первый candidate не меняет Yii routes/views/assets, calendar/queue sorting или pagination.

## 4. Проверка и доставка

- [ ] 4.1 Запустить planner-selected bounded focused checks, обязательный PilotHttp auth check при изменении `app/PilotHttp/*.php`, `make architecture-check`, `openspec validate complete-object-bound-inspection-planning --strict` и `git diff --check`; verification: все выбранные checks GREEN, локальные full `make test`/`make verify` не запускались.
- [x] 4.2 Независимый planner-required final reviewer проверить exact candidate, acceptance mapping, migration preservation, authority/scope, concurrency, current-plan read seam и planning-only boundary; verification: все findings разрешены до APPROVED.
- [ ] 4.3 Подготовить PR exact source и запустить один exact-source GitHub CI consumer; verification: полный job/`REGRESSION_FAILURE` inventory собран при failure, CI status не подменён локальными checks, merge/deploy/settings не выполняются.
