## 1. Evidence и executable Gate 1

Current scope2026-09-06: fresh launch без исторических данных/PDF. Старые notes
о legacy writer migration/N−1 совместимости superseded; новый optional PDF
не требует file/version storage. См. `docs/operations/fresh-launch-owner-scope-2026-09-06.md`.

- [x] 1.1 Сверить selection/prepare persistence с independent inventory; verification: exact seams и renderer coupling описаны, fixture SQL не принят за production путь. Evidence: independent inventory `16ba3e65401ab6b3acb883a96f590bc1e9eb7c3d`, projection gap `412efcb75bd88b454b2ba1ebbf9800937e96856b`, повторное чтение creator/persistence и authorization на base `71eea1b`.
- [x] 1.2 Написать `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001` с exact DTO/result/capability/date/composition identity, replay/conflict и correction rules; verification: owner-approved optional template truth соблюдена, все новые решения явно рассмотрены, expected values независимы от implementation.
- [ ] 1.3 Согласовать fresh selection, original command и HTTP contracts; verification: выбранный состав читается original command и locked validation, новые входы не вызывают старый prepare; PDF storage и перенос старых writers отсутствуют.
- [x] 1.4 Получить fresh independent Gate 1; verification: exact hashes APPROVED, unresolved product decisions закрыты владельцем, RED до approval не написан.

## 2. RED и Gate 3

- [x] 2.1 Доказать RED direct selection при отсутствующем/throwing renderer с exact persisted identity и no-template artifacts; verification: failure вызван отсутствующим behavior, не setup.
- [x] 2.2 Доказать RED authorization/eligibility/replay/concurrent stale/atomic persistence cases и no-opening/no-composition-application; verification: public seam, fictional fixtures, fixed expected values, bounded cleanup.
- [ ] 2.4 Доказать сохранение public projections при новом выборе поверх applicable order; verification: before/after directory assignments, assigned/free counters и inspection attribution byte-equivalent, existing rows-only oracle недостаточен; fresh Gate 3 до correction.
- [ ] 2.3 Получить fresh independent Gate 3; verification: reviewer не автор тестов, explicit APPROVED до production edits.

## 3. Minimal GREEN

- [ ] 3.1 Реализовать production selection owner и persistence handoff; verification: approved selection matrix GREEN без renderer dependency и private caller writes.
- [ ] 3.2 Подключить optional rendering к сохранённой identity; verification: PDF выдаётся без хранения файлов/версий, сохраняются дата последнего формирования и audit; render failure не разрушает selection, direct upload работает без render.

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

## Technical flow correction v0.6

По independent v0.5 rereview один invocation-owned clock читается lazily перед
первым необходимым audit/terminal fact; full matching replay clock не читает.
Clock failure до persistence даёт dependency_unavailable. Callback/UoW передают
closed rollback cause; stage не владеет commit/rollback. Полная таблица
stage→decision→UoW→public outcome закреплена в executable v0.6, включая
request race, invalid generated receipt и unknown acknowledgement. Это technical
уточнение прежних outcomes; Gate1 и P0 release dependencies остаются открыты.

## Schema constructibility correction v0.7

MariaDB AUTO_INCREMENT IDs не могут иметь CHECK на сам ID. В executable v0.7
registry/event/audit IDs сохраняют UNSIGNED physical type; bounds обеспечивает
public allocator и storage pre-commit/read validation с прежними failure
outcomes. Новый registry engine change готовит отдельный exact contract; его
planning не закрывает writer cutover, original reader или optional renderer.

## Counter outcome correction v0.8

Proven lossless registry/event/audit counter overflow сохраняет nonretryable
allocation_capacity_exhausted после confirmed rollback. Malformed/ambiguous
native receipt даёт persistence_failure; unknown acknowledgement остаётся
outcome_unknown. Public allocator возвращает closed allocation result, а не
невозможный overflowed int. Это уточняет прежний capacity contract и не вводит
новую policy; P0 release dependencies и full Gate1 остаются открыты.

## Actual fresh-core checkpoint — 2026-09-06

Controlling fresh scope supersedes dated legacy compatibility prerequisites.
v0.11 construction/no-case terminal route Gate1 APPROVED; pure application core
Gate3v2 and Gate5 APPROVED (reviews/tests/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001-core-v2.md,
reviews/code/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001-core-v1.md).
73core cases GREEN, source74ba2d0. Native adapters/SQL/UoW/races и wiring ещё
не доставлены: tasks2.2/2.3/3.1/4.x не объявляются полностью закрытыми по core
approval. Следующий пакет — native binding того же public command owner, без
legacy writer migration и без PDF storage. Existing approved schema/readers reuse.


## Native tracer checkpoint — 2026-09-06

Native source21b4ad0: eight bindings assembled for approved tracer; real new_order,
replace_pending, silent replay и no-case terminal GREEN. Gate5v2 APPROVED:
`reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-tracer-v2.md`.
Authority corrections have own RED/Gate3; SQL ownership narrowed to MariaDb files,
baseline unchanged. Exact clean archive `selection-native-authority-green-5hgvkalc`
manifest ec4898324447d863557e76568abf713917a24f732873a147cc3f409cee2468f2:
8native cases/architecture/lints/diff PASS. Core73, reader15, tool35 reused at
unchanged sources as recorded by reviews. Task2.1 now has native persistence proof.
Full native Gate5 remains open: denial/conflict/eligibility, locked-state and
request corruption, races/rollback/unknown, capacity, session echoes/ambient,
prefix/readiness matrix still require their RED/Gate3/GREEN reviews. No HTTP
binding, migration reservation, full VERIFY_OK or launch claim follows.

Native denial-audit follow-up source373a9d1 Gate5 APPROVED:
`reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-denial-v1.md`.
Audit-only metadata readiness removes confidential-ledger dependency; native12
PASS archive `selection-native-denial-green-t9q9_wr_`, manifest
698edcdab77ab6f21c92eab58ed98ebef9406dd83b65cf8633042984d7aac12b.
Denial/revoke/restore/conflict checks delivered; other native obligations above
remain open. Package observation32842 tokens at measurement, within45k cap.

Native terminal-stage/transaction follow-up sourcebd2f26c Gate5 APPROVED:
`reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-transaction-v1.md`.
Terminal-only stage rejects selected results before mutation; no-stage/double-stage,
wrong audit echo, typed rollback, no-case reason and ambient transaction checks
PASS. Native19 archive `selection-native-transaction-green-7nz1mera`, manifest
94181e2372382acf2436199c7006983153b183647632e918b42a745cc81198f6.
Remaining native package: eligibility/state/request corruption, real races and
interruption/recovery, allocation capacity, accepted-payload/session edges and
prefix/readiness. No full native binding/portal completion claim. Package
observation23318 tokens, below45k cap; review and fixation included in scope.

Native accepted-persistence/outcomes sourcec93c27a Gate5 APPROVED:
`reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-persistence-v1.md`.
Pre-write snapshot validation, native eligibility/pending/stale/no_changes/missing
selection/request-corruption and three counter-exhaustion cases proven. Native41
PASS archive `selection-native-persistence-green-mqw2elh6`, manifest
9b13a673900535a43d6996e6d2cf988dff930a482b555055a0d67e12cb3bf73e.
Remaining: real races/interruption/recovery, prefix/readiness and locked original
root edges; do not infer these from pure-core or sequential native evidence.
Cost observation59760 tokens near60k cap: scope frozen for final review/record,
no extra work added. Time stayed within20min; full native/portal remains open.

Native races/interruption verification source50e44bd, Gate5 reviewe5b7c04 APPROVED:
`reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-races-v1.md`.
Production unchanged fromc93c27a; existing41 + new6 cases PASS. Archive
`selection-native-races-evidence-kpmr7mpc` records owned two-worker blocked SQL,
same-case stale, same-request collision, cross-case allocation, post-commit
response loss/restart replay, exception/boolean connection interruption and
fresh absent lookup. Delivery loss is not claimed as native COMMIT-ack loss.
Prefix/readiness and locked original-root edges/final native audit remain open.
No source fix was needed; Gate3 approved verification-only/no-op Gate4 extension.

Final standalone eight-port native Gate5 APPROVED, testedbe0fe02/sourcec93c27a:
`reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-native-v1.md`.
Combined56native/architecture/OpenSpec/diff PASS, clean archive
`selection-native-combined-rny_xymw`, manifest
236a6c5b71b364d3bc9493ef729b57c0a47b47f3da1ef8c873da89730eb90bd2.
Task2.2 complete. Original factory/locked reader, public applicable projections,
PDF/date/audit, HTTP/application/opening remain integration work; effective owner
must be bound there. Migration frontier13 unchanged. Next: original registry-reader
and locked validation binding through gates; standalone native audit is not launch
approval and does not supply full VERIFY_OK.

On-demand PDF task3.2 candidate: `ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001`.
Reuses existing renderer and fm2_process_events for date/audit projection; no PDF
files/versions/new schema. Gate1 pending; no implementation or checkbox advance.
