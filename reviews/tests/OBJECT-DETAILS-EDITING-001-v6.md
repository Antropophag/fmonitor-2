# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v6

- Reviewer: independent reviewer `/root/gate3_review`
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T175504Z-9409e0c39b/snapshot/source.patch`, SHA-256 `521d8157abec88732524ecb79d786459679cb6e5ba0a3a1a947bf88ee87a52ef` (candidate `9f4a590ead5646b458f26d47b3042b1fa7494a595d9aa59b2553a58473e2cd3c`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v5.md`
- Evidence: six mapped exact-source commands are GREEN in package `20260921T175504Z-9409e0c39b`
- Verdict: `CHANGES_REQUESTED`

## Prior v5 disposition

1. **Actor-object assignment/scope test — INAPPLICABLE by owner decision.** The exact specification now defines global pilot-object scope for `fkr_operator`/`manager` when exact `objects.read` and `objects.details.edit` are both present; no actor↔object assignment exists or belongs in #222. Tests need capability/role/read/inactive/existence cases, not a nonexistent assignment denial.
2. **Consumer/import tests — OPEN.** Projection-only test remains unchanged and does not invoke import, card/queue, Bitrix, ERP or OTIZ seams.
3. **Migration/recovery tests — OPEN.** Production inspect-first migration is corrected, but the mapped recovery test still executes no migration or restore.
4. **Concurrency/history tests — OPEN.** No parallel race, complete replay/request/event accounting, second-edit immutability or complete event snapshot coverage exists.
5. **HTTP/browser tests — OPEN.** Missing role/capability/read/inactive, malformed/conflict/unavailable, validation retention, submit/reload, backdrop/focus and chronology cases remain.
6. **Field-rule tests — OPEN.** Catalogue/display, null clearing, boundaries, correction and downstream factory `0` behavior remain untested.
7. **CI/import — OPEN.** Six GREEN records do not establish selected full CI/import obligations.

## Findings

1. **BLOCKER — A9-A12 mapping still has no consumer/import coverage.** Invoke identical import and each named card, queue/search/filter/pagination, Bitrix, ERP candidate/matching/freshness and OTIZ calculation/evidence public seam with independent expectations, source preservation, explicit null and factory `0` cases.
2. **BLOCKER — A15 still has no executable migration/recovery coverage.** Exercise fresh/repeat/current/predecessor/incompatible/concurrent migration and real backup/restore preserving overrides, events, requests, replay, auto-increment and next write; prove incompatible preflight makes no mutation.
3. **BLOCKER — A5-A8 authorization, concurrency and immutable history coverage remains incomplete.** Add distinct inactive, wrong business role, missing edit, missing read and unknown-object denial before replay/existence disclosure; deterministic two-connection one-winner behavior; request/event counts and identity; revoked replay; rollback; second edit immutability and complete case/actor/time/value/raw/display/unit/reference snapshots.
4. **HIGH — canonical HTTP/browser journey remains incomplete.** Add duplicate/malformed payload, stale/request conflict, unavailable safe body, backdrop/focus, validation retention, cancel/no-write, save/reload, escaped history and compound pagination beyond eight.
5. **HIGH — field normalization assertions remain shallow.** Assert independent outputs and boundary neighbors for every field, reference membership/display, UTF-8 byte bounds, explicit clearing, omitted neighbors and factory `0` exclusion.
6. **MEDIUM — traceability and verification remain incomplete.** Grouped A9-A12/A15 mappings overclaim exercised seams; exact-source full CI/import GREEN is absent.

## Evidence assessment

The six GREEN records are valid for their narrow bodies. The owner decision removes only the actor-assignment scenario; it does not waive the remaining acceptance matrix.

## Required changes

Complete findings 1-5, correct item/subcase traceability, regenerate the plan, capture exact-source GREEN evidence and return for Gate 3 review.
