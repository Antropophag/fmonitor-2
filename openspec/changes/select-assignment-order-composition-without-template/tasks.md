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
and required owner decision are approved.
