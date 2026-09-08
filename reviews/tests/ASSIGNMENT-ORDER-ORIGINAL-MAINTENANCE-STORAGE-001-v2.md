# Независимый Gate 3 rereview: MAINTENANCE storage v2

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed source HEAD: `9c48b442dd18769ba6b933df15901a1347df0612`
- Specification SHA-256: `d4712f8e82cc7c2f9865bd61924ef24b0cd2640ddbbde3d96be4f5dc9cc7c72e`
- Test SHA-256: `21cacb45499f6333dcbfbbc2aaae34a997cfa58ac3b50a091c33a32566947118`
- Verdict: **APPROVED**

Rereview ограничен тремя cases, закрывающими finding v1. Reviewer не писал
test/source; остальные storage oracles используют прежнюю оценку.

Каждый case создаёт реальную stage и throwing storage observer на одной exact
phase. DIGEST_LOCK_ACQUIRED failure возвращает total FAILED lock и сохраняет
inventory. DELETE_BEGIN failure возвращает FAILED до удаления и сохраняет
inventory. DELETE_DONE failure возвращает OK после фактического удаления и не
восстанавливает/не повторяет irreversible effect.

Expected event list exact и содержит каждую достигнутую callback один раз.
Двойной public release не повторяет native unlock; released lock не допускает
повторный delete и не испускает events. Независимый storage instance затем
успешно получает тот же native lock, доказывая освобождение exclusion. Before/
after inventory различает pre-delete failures и post-delete completion.

Retained RED archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-maintenance-storage-observer-red-jvzstr_r`.

```text
e36903e5ccc19c95efd79ee9419ab49223f0c2e8d300be303e066ce1757b32da  evidence.json
059104ad9e78c098f05929f1b769cffeade128274f34de355655cb50c4245045  storage.log
```

Manifest `complete=true`, exact test/spec/source SHA, exit255. Три существующих
controls PASS; 11 failures включают три новые observer RED и прежнее отсутствующее
storage behavior. Setup syntax failure отсутствует.

**APPROVED** разрешает minimal native storage GREEN на exact expectations.
Owner/repository имеют отдельный scoped Gate5; весь maintenance Gate5,
regression, combined command и launch readiness не утверждаются.
