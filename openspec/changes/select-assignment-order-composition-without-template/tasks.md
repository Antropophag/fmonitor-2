## 1. Evidence и executable Gate 1

- [x] 1.1 Сверить selection/prepare persistence с independent inventory; verification: exact seams и renderer coupling описаны, fixture SQL не принят за production путь. Evidence: independent inventory `16ba3e65401ab6b3acb883a96f590bc1e9eb7c3d`, projection gap `412efcb75bd88b454b2ba1ebbf9800937e96856b`, повторное чтение creator/persistence и authorization на base `71eea1b`.
- [ ] 1.2 Написать `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001` с exact DTO/result/capability/date/composition identity, replay/conflict и correction rules; verification: owner-approved optional template truth соблюдена, все новые решения явно рассмотрены, expected values независимы от implementation.
- [ ] 1.3 Согласовать existing prepare, original command и HTTP contracts; verification: выбранный состав читается original command без обхода renderer или mutation назначений, необходимые schema changes отдельно специфицированы.
- [ ] 1.4 Получить fresh independent Gate 1; verification: exact hashes APPROVED, unresolved product decisions закрыты владельцем, RED до approval не написан.

## 2. RED и Gate 3

- [ ] 2.1 Доказать RED direct selection при отсутствующем/throwing renderer с exact persisted identity и no-template artifacts; verification: failure вызван отсутствующим behavior, не setup.
- [ ] 2.2 Доказать RED authorization/eligibility/replay/concurrent stale/atomic persistence cases и no-opening/no-composition-application; verification: public seam, fictional fixtures, fixed expected values, bounded cleanup.
- [ ] 2.4 Доказать сохранение public projections при новом выборе поверх applicable order; verification: before/after directory assignments, assigned/free counters и inspection attribution byte-equivalent, existing rows-only oracle недостаточен; fresh Gate 3 до correction.
- [ ] 2.3 Получить fresh independent Gate 3; verification: reviewer не автор тестов, explicit APPROVED до production edits.

## 3. Minimal GREEN

- [ ] 3.1 Реализовать production selection owner и persistence handoff; verification: approved selection matrix GREEN без renderer dependency и private caller writes.
- [ ] 3.2 Подключить optional rendering к сохранённой identity; verification: render failure не разрушает selection, старые rendered orders/history byte-preserved, direct upload работает без render.

## 4. Review и integration

- [ ] 4.1 Выполнить selection/prepare/original regression, architecture-check и diff-check; verification: один owner facts, no runtime DDL, no new rapid-pilot domain logic, exact SHA evidence.
- [ ] 4.2 Получить fresh independent Gate 5; verification: reviewer не автор tests/production, explicit APPROVED охватывает invariants, rollback/replay, history и authorization.
- [ ] 4.3 Пройти real public HTTP selection → direct original upload и optional-template parity с approved HTTP slice; verification: renderer не требуется для первого пути, upload не открывает работы.
- [ ] 4.4 Выполнить full make verify и requirement-by-requirement Done audit; verification: literal VERIFY_OK, exact gate/evidence hashes, все artifacts согласованы, direct upload не подменён hidden prepare/render.

Drafting disposition 2026-09-05: task1.3 must include the separate selection
ledger and shared identity registry/allocator, preserved historical IDs, legacy
writer cutover, original-reader source discriminator, and fail-closed deployment
compatibility specified in the appended design decision. This is planning
progress only; task1.2–1.4 remain unchecked until the complete executable batch
are technically approved; pending-replacement owner policy is already approved in1842Z record.

2026-09-05 drafting inputs (not Gate1 approval):
`docs/operations/selection-identity-storage-contract-candidate-2026-09-05.md`
and `docs/operations/selection-result-replay-contract-candidate-2026-09-05.md`
provide concrete storage/cutover and typed replay candidates for tasks1.2/1.3.
Unresolved: complete audit/ports manifest, precise exhaustion/result mapping,
legacy version/predecessor compatibility with mandatory same-identity optional
render. Owner disposition of REPLACE_PENDING/history is now APPROVED (1842Z). Existing manager role
identity is already owner-evidenced and must not be re-questioned. No new RED,
production change, task completion or migration version reservation follows.

Further 2026-09-05 technical drafting: audit/exhaustion candidate and independent
bounded consistency receipt are committed at7a38f4f. Exact
allocation_capacity_exhausted is failed/retryable=false and uncached; denial
attempts use independent audits without terminal lookup/overwrite. Transaction
ports candidate `docs/operations/selection-transaction-ports-candidate-2026-09-05.md`
defines one transaction owner, read-only observed-terminal outcome and distinct
request-race resolution. These must be consolidated into the normative spec;
closed DTO constructors/lookup payloads, complete schema/fact inventory and
combined Gate1 review remain required. This note advances no checkbox.

## v0.5 reconciliation state — 2026-09-05

Owner REPLACE_PENDING policy закрыта; повторно её не спрашивать. v0.5 уточняет
result/lookup closure, validation owner, generated event/audit ID receipts и
legacy-status truth table по independent v0.4 findings. Fresh independent review
ещё нужен; tasks1.2–1.4 остаются открытыми, поскольку P0 migration/cutover/
original-reader/optional-render contracts не завершены. Production/tests для
selection не написаны. Deadline9 сентября09:00МСК не меняет gates или scope.
