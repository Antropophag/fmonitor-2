## 1. Независимые контракты и RED

- [ ] 1.1 Провести независимый Gate1 review OpenSpec и `specs/OTIZ-EXCEL-OVERDUE-CERTIFICATES-001.md`; verification: APPROVED record содержит exact hashes либо точно оставляет blocked original-T/EU2 branches без ложного общего approval.
- [ ] 1.2 Зафиксировать Gate2 scenario matrix для certificate command/read/download, as-of operands, calculator, allocation, snapshot cutover и recovery; verification: независимый reviewer подтверждает все acceptance examples и failure boundaries.
- [ ] 1.3 Написать executable RED на публичных seams до реализации; verification: calculator literal examples, certificate initial/replay/collision/correction/race, PTO/certificate provenance, conservation и stale-version acceptance падают по отсутствующему поведению, а не setup.
- [ ] 1.4 Провести независимый Gate3 review тестов; verification: APPROVED hashes запрещают реализацию до принятого RED.

## 2. Schema v24 и recovery

- [ ] 2.1 Добавить owning definition migration v24 для certificate roots/revisions/attempts, OTIZ object-entitlement events и exact `deadline_certificate.write|read`; verification: schema tests проверяют columns, keys, checks, foreign keys, prefix isolation, default grants write=`fkr_operator|manager`, read=`fkr_operator|manager|otiz_specialist`, отсутствие implicit engineer/admin grants и DDL в HTTP. Original-deadline storage добавляется только после решения task4.1.
- [ ] 2.2 Включить migration в canonical catalogue/readiness/manifests без изменения старых таблиц; verification: чистая установка и additive v23→v24 дают один exact frontier.
- [ ] 2.3 Обновить backup/restore inventories и forward fixture; verification: v23 bundle восстанавливается и мигрирует в v24 с byte-identical прежними rows/counters/private state, v24 round-trip сохраняет certificate/entitlement rows/PDF, v23 tooling fail-closed до mutation.
- [ ] 2.4 Получить независимый schema/recovery review; verification: APPROVED record не принадлежит автору migration/application.

## 3. Certificate application slice

- [ ] 3.1 Реализовать typed commands/results и один application owner для initial/correction с authorization-first, request fingerprint, CAS и terminal attempts; verification: accepted/replay/collision/stale/concurrent tests GREEN.
- [ ] 3.2 Реализовать bounded passive-PDF acquisition и отдельное private storage/recovery namespace; verification: empty/unsafe/oversize/short-read/DB-failure/file-failure/ambiguous-outcome tests не активируют неполный срок и не теряют recoverability.
- [ ] 3.3 Реализовать history/read/download seam без private paths; verification: обе revisions скачиваются byte-identical, unauthorized request отказывает до bytes/history read.
- [ ] 3.4 Подключить карточку и HTTP как тонкий adapter с датой справки, новым сроком, PDF и correction reason; verification: focused HTTP/browser desktop+mobile проходит initial→history→correction, server roles и CSRF.

## 4. Versioned OTIZ operands

- [ ] 4.1 `NEEDS_GRILL`: получить решение owner о current-card capture, immutable selection/application capture или отдельном FKR confirmation для raw T; verification: обновлённые Gate1 specs называют exact public seam/storage и competing `workdateendadjusted` не подменяет T.
- [ ] 4.2 Реализовать current accepted certificate reader без report-date cutoff и fallback на утверждённый task4.1 original-plan evidence; verification: no certificate, future-dated certificate, current correction, missing/ambiguous deadline fixtures GREEN, а `DOn` не управляет выбором `DPn`.
- [ ] 4.3 Переключить PTO operand на effective completion root/correction chain с provenance; verification: corrected PTO и PTO после report date дают exact source/hash/date и не читают order snapshot как owner.
- [ ] 4.4 Составить полные typed operands/blockers для publication; verification: damaged/missing certificate or deadline blocks distribution without invented data, valid input carries every source locator/hash/version.
- [ ] 4.5 Ограничить future-date exception только certificate/PTO operands vNext; verification: future certificate/PTO участвуют, future checklist/payment facts исключаются прежними cutoffs, version-1 replay остаётся byte-identical.

## 5. Calculator, allocation и publication

- [ ] 5.1 Выпустить новую `PremiumCalculation` version с calendar days, Kss floor0, penalty-after-paid и HALF-UP на денежных границах; verification: 0/1/99/100/101, three workbook dates, half-cent boundaries and 49725000/9360000/9 literal example GREEN.
- [ ] 5.2 Реализовать largest-remainder allocation выбранного целого pool с tab identity tie-break; verification: unique/equal remainder tests, input-order invariance and `SUM(allocation)=pool` GREEN независимо от HALF-UP calculator tests.
- [ ] 5.3 Интегрировать calculator/allocation в atomic snapshot publication и content hash; verification: exact operands/formula trace/allocation order persist atomically, injected failure leaves no partial snapshot.
- [ ] 5.4 Перевести publication/acceptance на object-level candidate entitlement: publication немедленно supersede-ит accepted-unpaid, acceptance отдельно активирует fresh identity; verification: same-v2/cross-period snapshots, pre-acceptance payment denial, stale draft refusal и unchanged old amounts GREEN.
- [ ] 5.5 Реализовать один idempotent PaymentCompletion с object locks, fresh-entitlement check и object-wide signed closures; verification: old/current concurrent payments, replay/collision, reversal and response-loss tests prove at most one current entitlement payout without changing paid history.
- [ ] 5.6 Удалить заменённые snapshot-local payment writes из rapid-pilot; verification: architecture check maps every payment route to PaymentCompletion and no presentation SQL inserts closures/events.
- [ ] 5.7 Разделить ledger operands: paid-only before formula, discipline after pool, legacy deadline blocker until reversal; verification: discipline100/pool1000→payable900, nonzero old deadline blocks without refund/double penalty.
- [ ] 5.8 `NEEDS_GRILL`: утвердить recurring-snapshot result без нового progress для literal Excel `100×0.9` после paid90 versus interval no-new-amount; verification: Gate1 amendment and RED use owner-selected result before calculator integration.

## 6. Release evidence

- [ ] 6.1 Запустить focused calculator/application/storage/input/publication/HTTP/browser/recovery suites и architecture checks; verification: все changed-boundary checks GREEN, architecture baseline не расширен.
- [ ] 6.2 Провести независимые security/code/Gate5 reviews; verification: findings закрыты либо честно зафиксированы, reviewer не автор соответствующего slice.
- [ ] 6.3 Запустить один full CI на exact candidate; verification: exact SHA и полный job/failure inventory записаны до merge.
- [ ] 6.4 Обновить delivery record и выполнить owner-authorized PR merge без stand/import mutation; verification: merged SHA, checks and explicit no-backfill/no-waiver limitations recorded.

## 7. Условная работа вне scope

- [ ] 7.1 `NEEDS_GRILL`: после отдельного решения владельца создать новый change для управляемого waiver либо закрыть вопрос как ненужный; verification: #66 не получает скрытый `$EU$2` toggle независимо от ответа.
