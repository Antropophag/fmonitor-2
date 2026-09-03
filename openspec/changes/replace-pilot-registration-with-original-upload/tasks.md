## 1. Coherent contract и Gate 1

- [x] 1.1 Coherently supersede canonical active truth в `CONTEXT.md`, pilot spec и pilot data model без изменения historical evidence; verification: hashes `3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09`, `25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb`, `10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d` фиксируют original PDF + separate opening и только out-of-pilot future 1С ДО
- [x] 1.2 Завершить disposition inventory `docs/installation-process-interface.md`, behavior inventory, active E2E/RBAC/PDF changes/specs/tests и downstream historical registered-crew characterization; verification: manifest `docs/operations/pilot-assignment-order-original-active-contract-disposition-2026-09-02.md` hash `511e27b3dd7ab87d0c510b947ff1afa7825afe71c9e8b587556e651c9730035c`, target specs имеют explicit predecessor/superseded notices, historical reviews/tests неизменны
- [x] 1.3 Зафиксировать executable spec только для secure initial upload/correction command seam с exact DTO/result-status mapping, capabilities, PDF bytes/parser policy, request/fingerprint precedence, audit и pre-commit-finalize/commit/response-loss fault matrix; verification: exact hash одобрен владельцем и spec не утверждает HTTP/read/download/composition/opening GREEN
- [x] 1.4 Поручить fresh independent planning reviewer проверить текущие exact hashes, owner traceability, pre-Gate supersession, one-seam scope и все former review findings; verification: новый immutable review record имеет explicit `APPROVED`, reviewer не редактировал artifacts
- [x] 1.5 Получить owner approval exact-hash OpenSpec/executable-spec batch; verification: append-only decision перечисляет hashes, а production/tests до approval не изменены
- [x] 1.6 Поручить fresh independent Gate 1 reviewer проверить v4 lease-conflict amendment и весь current contract: CAS loser holds lease through fingerprint/current-lineage rereads, releases exactly once, release failure preserves selected result и не пропускает audit; verification: новый immutable review имеет explicit `APPROVED` и reviewer не редактировал artifacts
- [x] 1.7 Получить новый owner exact-hash approval v4 executable/OpenSpec batch; verification: append-only decision перечисляет новые hashes и только после него Gate 2 tasks разрешены

## 2. Minimal RED и test review

- [x] 2.1 Поручить RED author написать smallest public-seam test initial valid direct upload с immutable composition/date/upload-time/hash evidence и no composition/opening mutation; verification: canonical command падает только из-за отсутствующего production seam и transcript классифицирован intended RED
- [ ] 2.2 Расширить approved RED на post-template parity, exact process authorization, owned PDF algorithm, staged chunk/abort/events, typed commit/audit, request-ID retry, root/current/target concurrency, five-FD two-worker barrier, maintenance candidate/lock/replay и commit/response-loss faults; verification: каждый expected value независим от будущей реализации
- [ ] 2.3 Поручить fresh independent test reviewer проверить sensitivity и zero-public-orphan cleanup; verification: `reviews/tests/` содержит explicit `APPROVED`, reviewer не писал tests/production

## 3. Minimal GREEN

- [ ] 3.1 Добавить additive canonical capability/schema migration на актуальном frontier без runtime DDL и без изменения historical registration facts; verification: clean/repeat/populated/conflict migration fixtures GREEN
- [ ] 3.2 Реализовать `submitAssignmentOrderOriginal` DTO/result, authorization, semantic fingerprint и append-only CAS lineage; verification: approved command/replay/correction tests GREEN при неизменных composition/opening snapshots
- [ ] 3.3 Реализовать bounded staging, exact 20 MiB counter, owned inspector, private finalize lease через commit/unknown/CAS-conflict rereads, exactly-once release/failure mapping, orphan reconciliation в общем exclusion domain и injected outcomes; verification: adversarial/fault/retry/CAS-loser/maintenance-race fixtures GREEN, каждый lease released once либо recovery-owned, accepted blob не удаляется

## 4. Integration и Done

- [ ] 4.1 Запустить focused suites, `make architecture-check`, `git diff --check`, затем `make verify`; verification: literal `VERIFY_OK`, без переклассификации failures
- [ ] 4.2 Поручить fresh independent code reviewer проверить approved spec/tests, exact grants, parser/storage boundary, DB/filesystem failure protocol, immutable lineage и scope exclusions; verification: `reviews/code/` содержит explicit `APPROVED`, reviewer не был RED author/implementer
- [ ] 4.3 Интегратор сверяет tasks/spec/reviews/tests и обновляет operations status; verification: slice Done только после Gates 1–5, а `expose-assignment-order-original-http`, composition и opening остаются явно READY/BLOCKED отдельными named future OpenSpec changes
