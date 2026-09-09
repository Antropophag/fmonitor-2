## 1. Gate 1–3 и exact v23 oracle

- [x] 1.1 Получить независимый Gate1 review `PRODUCTION-JOBS-RECOVERY-001`: exact 69/39 inventory, ordered quiesce, zero-transition restore, fake-only resume и forward-only compatibility; verification: review record содержит hashes и verdict APPROVED.
- [x] 1.2 Добавить независимо authored public RED для v23 backup/restore exact Jobs rows/history/state/AUTO values; verification: RED причинно падает на отсутствующем v23 recovery contract, а не setup.
- [x] 1.3 Добавить preflight RED для wrong frontier, missing/extra Jobs table и omitted/extra AUTO key; verification: каждый case возвращает `BUNDLE_INVALID` с zero target DDL/state.
- [x] 1.4 Добавить fake-only resume RED для pending/no-job sweep, delivered/dead exclusion, unexpired/expired/attempt5 leases, stale token, ambiguous provider identity и linked retry; verification: transport fake фиксирует только явно разрешённые calls и domain facts неизменны.
- [x] 1.5 Получить independent Gate3 всех executable artifacts; verification: `reviews/tests/PRODUCTION-JOBS-RECOVERY-001.md` фиксирует exact hashes, demonstrated RED и bounded approval.

## 2. V23 backup и restore без transition

- [x] 2.1 Добавить отдельный `RuntimeRecoverySchemaV23` с literal 69 tables, 39 AUTO families и `deferred=[]`, не изменяя V22; verification: independent manifests совпадают, а v22 hashes/contract остаются exact.
- [x] 2.2 Подключить fail-closed выбор exact current source-image recovery contract; verification: v23 принимает только exact v23 manifest, v22/v23 mismatch отказывает до target mutation.
- [ ] 2.3 Расширить backup attestation/runbook ordered stop `jobs-scheduler` → `jobs-worker` → `web/php`; verification: graceful и forced-worker private evidence сохраняют соответственно terminal либо unknown leased state.
- [x] 2.4 Восстановить все шесть Jobs tables и AUTO values через существующий standard data-only pipeline без вызова Jobs seams; verification: exact before/after rows/history/counters равны и transport/queue spies имеют zero calls.
- [ ] 2.5 Разделить terminal schema/storage readiness и operational JobsHealth; verification: valid restore со stale heartbeat/expired lease/dead backlog возвращает `RESTORE_COMPLETED`, затем health честно unhealthy.

## 3. Явный recovery после restore

- [x] 3.1 После explicit start выполнить pending-intent sweep и repeat; verification: committed pending/no-job получает один dispatch, delivered/dead не получают jobs.
- [x] 3.2 Проверить lease recovery через public queue: unexpired exclusion, attempts1–4 reclaim/new token/old stale, attempt5 expired/dead/no6; verification: append-only event sequence и rows exact.
- [x] 3.3 Проверить ambiguous outbox fake retry и authorized linked recovery; verification: один intent-wide provider reference, ни одного повторного domain fact и никаких real sends.
- [x] 3.4 Выполнить resume под DML-only principal; verification: queue/outbox flows GREEN, MariaDB CREATE denied, public evidence не содержит payload/token/provider bytes.

## 4. Forward update и rollback boundary

- [x] 4.1 Восстановить approved v22 bundle exact v22 image и снять rows/state/35 AUTO snapshot; verification: v22 recovery остается GREEN без правки `RuntimeRecoverySchemaV22`.
- [x] 4.2 Применить migration23 и доказать additive preservation; verification: прежние 63 tables/rows/state/AUTO exact, шесть Jobs tables exact и initially empty.
- [ ] 4.3 Создать populated v23 bundle, восстановить его exact v23 image и выполнить fake-only resume; verification: 69/39 snapshot и recovery matrix GREEN.
- [ ] 4.4 Проверить v23 bundle через v22 tooling и rollback boundary; verification: `BUNDLE_INVALID`/zero mutation, no downgrade, jobs services stopped и pending/leased/ambiguous full-contour rollback не заявлен.

## 5. Integration, evidence и Done

- [ ] 5.1 Обновить production runbook точными v23 backup/restore/quiesce/resume командами и private evidence policy; verification: docs check не содержит secrets и сохраняет NEEDS_GRILL retention/RPO/RTO.
- [ ] 5.2 Выполнить isolated Compose drill без real transport; verification: exact source/image IDs, elapsed, bundle hashes, rows/state/AI и safe recovery outcomes сохранены private mode0600.
- [ ] 5.3 Выполнить focused recovery/Jobs/runtime/browser regression checks, architecture check и полный approved CI matrix; verification: результаты привязаны к exact candidate SHA без reset рабочего stand/volumes.
- [ ] 5.4 Получить independent Gate5 production diff/evidence; verification: `reviews/code/PRODUCTION-JOBS-RECOVERY-001.md` содержит findings, reviewed SHA и APPROVED.
- [ ] 5.5 Сверить Done: v22 history preserved, v23 exact restored, restore zero-transition, fake resume deterministic, DML-only, cross-version fail-closed, no real sends/downgrade; verification: OpenSpec strict valid и все пункты1–5 фактически завершены.
