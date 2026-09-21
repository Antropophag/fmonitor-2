# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v8

- Reviewer: independent reviewer `/root/gate3_review`
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T180638Z-cd84c814c4/snapshot/source.patch`, SHA-256 `09d2da72dbc98f0826393c3c3f9493de2954f76108229345e0d53621fd3ec4f9` (candidate `c9aa6ad619bf89c3f704c90307762b2bda414fbc326eacf201f2848a5efce370`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v7.md`
- Evidence: all six mapped commands are exact-source GREEN in package `20260921T180638Z-cd84c814c4`
- Verdict: `CHANGES_REQUESTED`

## Prior v7 disposition

1. **A9-A12 consumers/import — PARTIALLY FIXED.** ERP candidates and Bitrix remain covered. Import, card/queue, ERP matching/freshness and OTIZ still are not invoked. Null and factory `0` are now normalized/stored expectations only; downstream consumer behavior remains untested.
2. **A15 lifecycle — OPEN.** Recovery test is unchanged from v7: repeat and one incompatible index are covered, but genuine fresh/predecessor/concurrent migration and backup/restore/replay/next-write are absent; “no rebuild” remains table-existence-only.
3. **A5-A8 authorization/concurrency/history — PARTIALLY FIXED.** `object_details_editing_mariadb_001_test.php:23-29` now proves a second correction, immutable first event bytes, exact accepted event/request counts, revoked-capability replay denial and rollback. It still lacks distinct inactive/wrong-role/missing-read/unknown-object cases, real parallel connections, replay event identity, full snapshots and chronology.
4. **HTTP/browser — OPEN.** Tests are unchanged.
5. **Field rules — PARTIALLY FIXED.** Raw reference labels, explicit nullable normalization and exact stored factory `0` are now asserted. Per-field outputs/boundaries, consumer null clearing and zero exclusion remain.
6. **Traceability/CI — OPEN.** Full exact-source CI/import is absent and grouped mappings remain broader than execution.

## Findings

1. **BLOCKER — material consumer/import behavior remains uncovered.** Run identical import and public card, queue/search/filter/pagination, ERP matching/freshness and OTIZ operands/Kshah/evidence/hash cases. Prove downstream explicit-null clearing, factory `0` exclusion, old artifact preservation and no external call on Save.
2. **BLOCKER — A15 remains incomplete.** Add truly absent-schema creation, exact predecessor upgrade, representative incompatible shape no-mutation proof, concurrent runners and actual backup/restore preserving facts, replay, auto-increment and next revision.
3. **BLOCKER — real concurrency and full immutable history remain unproved.** Add deterministic two-connection one-winner/loser accounting, full replay outcome/event identity, distinct authorization denials, and complete case/actor/time/value/raw/display/unit/reference snapshots plus compound chronology.
4. **HIGH — HTTP/browser journey remains incomplete.** Cover duplicate/malformed payload, stale/request conflict, unavailable safe body, validation retention, backdrop/focus, cancel/no-write, submit/reload, escaping and >8 compound pagination.
5. **HIGH — field matrix remains incomplete.** Add independent canonical output and boundary neighbors for every field and verify clear/zero meaning at real consumers.
6. **MEDIUM — CI and traceability remain unresolved.** Exact full CI/import obligations must be GREEN after the acceptance mapping is corrected.

## Evidence assessment

The six GREEN records validly cover the expanded history/replay and truthful raw/null/zero assertions. Those additions are sensitive and independently meaningful, but they do not close the remaining high-risk acceptance surfaces.

## Required changes

Complete findings 1-5, refine traceability, regenerate the plan and return with exact-source GREEN evidence.
