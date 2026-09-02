## 1. Gate 1 — executable combined artifact contract

- [x] 1.1 Подготовить полный executable amendment `PILOT-E2E-FLOW-001 v0.5` и supporting `PILOT-E2E-COMBINED-PDF-001` с заменой всех two-HTML clauses, one-artifact projection, exact HTTP mappings/HEAD, decoded PDF semantics, authorization/integrity/fault/reload/concurrency and cleanup; verification: independent review READY_FOR_OWNER_APPROVAL.
- [x] 1.2 Получить explicit owner approval exact reviewed hash до test changes.

## 2. Gates 2–3 — RED и independent test review

- [ ] 2.1 Исправить/добавить E2E test expectation на один combined PDF так, чтобы current stale two-artifact oracle дал intended RED без setup/RBAC failure; verification: captured public seam output.
- [ ] 2.2 Добавить authorization-first, exact 403/404/503/HEAD, digest/shard fault,
  semantic/page decode, concurrent reads, repeat/fresh reload/full DB/storage
  snapshot and cleanup-failure sensitivity; verification: independent reviewer APPROVED exact hashes.

## 3. Gate 4 — minimal GREEN

- [ ] 3.1 Синхронизировать golden/fault fixtures с combined projection либо исправить только доказанное production несоответствие; verification: approved tests GREEN без legacy appendix creation.
- [ ] 3.2 Проверить content-addressed store, PDF renderer, process projection и HTTP download regressions; verification: no mutation/leak and exact bytes/metadata.

## 4. Verification, Gate 5 и Done

- [ ] 4.1 Запустить focused E2E, DB, architecture, lint, built-image/fresh lifecycle и full verify; verification: duplicated combined-PDF failure исчезает.
- [ ] 4.2 Получить independent code review APPROVED; test changes возвращают Gate 2.
- [ ] 4.3 Обновить operations status и Done только после Gates 1–5, strict OpenSpec и durable GREEN evidence.
