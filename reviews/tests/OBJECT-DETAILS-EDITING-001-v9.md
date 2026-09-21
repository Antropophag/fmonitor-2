# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v9

- Reviewer: independent reviewer `/root/gate3_review`
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T182750Z-a5b3740ccf/snapshot/source.patch`, SHA-256 `6dc80a31f98043e0a5c10b97871d9f22e9cc6268189f0be2f000cb5a682575ac` (candidate `231fa5dda09b487ca3b891d8c04584b6f8111616245f7682531415180ec2be1b`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v8.md`
- Evidence: thirteen acceptance-mapped exact-source commands are GREEN in package `20260921T182750Z-a5b3740ccf`
- Verdict: `CHANGES_REQUESTED`

## Prior v8 disposition

1. **Consumer/import coverage — PARTIALLY FIXED.** Actual pilot import, card, queue, ERP read and OTIZ inputs now run GREEN in addition to effective reader/ERP candidates/Bitrix. However those existing tests do not seed a manual override and therefore do not prove override propagation, explicit-null semantics, factory `0` exclusion, ERP rematching/freshness or OTIZ manual evidence/hash.
2. **A15 lifecycle — PARTIALLY FIXED.** Schema frontier and full runtime backup/restore now run GREEN, reducing integration risk. The #222-specific facts are not seeded before backup and verified after restore/replay/next write; exact predecessor/concurrent migration remain untested.
3. **Concurrency/history — PARTIALLY FIXED.** `object_details_editing_mariadb_001_test.php:28` is a genuine two-process/two-connection barrier race with one winner and exact no-loser facts. Second-event immutability, revoked replay and rollback remain covered. Full event snapshot values and compound chronology beyond eight remain absent.
4. **HTTP/browser — PARTIALLY FIXED.** Playwright now covers Cancel, Escape, Save, reload, visible history and exact one-event accounting. Backdrop/focus, validation retention, conflict/unavailable safe body and >8 pagination remain absent.
5. **Field matrix — PARTIALLY FIXED.** Prior raw/null/zero additions remain; per-field boundaries and real consumer clear/zero semantics remain incomplete.
6. **CI/import — PARTIALLY FIXED.** Local exact-source import and twelve other mapped commands are GREEN. The canonical full CI obligation is still not supplied.

## Findings

1. **BLOCKER — effective consumer integration is not proved by the newly mapped generic regressions.** The card, queue, ERP read and OTIZ tests never insert/apply `fm2_object_detail_edits`; they can remain GREEN while manual overrides are ignored. Add focused cases that drive the public command and assert card/queue/search/filter/pagination, ERP matching/freshness, OTIZ effective material/Kshah/manual locator/hash, explicit null, factory `0`, and old artifact preservation.
2. **BLOCKER — recovery continuity for #222 facts remains unproved.** The generic backup/restore test confirms frontier v32 inventory, but it does not create override/event/request facts and then assert restored replay, immutable history, auto-increment and next revision. Add that focused round-trip plus exact predecessor/concurrent migration coverage.
3. **HIGH — history and HTTP edge coverage remains incomplete.** Assert the complete event snapshot independently (case, actor/time, typed/raw/display/unit/reference), deterministic compound process/detail pagination beyond eight without duplication, malformed/duplicate/stale/request-conflict/unavailable HTTP outcomes, validation retention, backdrop and focus.
4. **HIGH — authorization and field boundaries remain incomplete.** Add distinct inactive, wrong-role, missing edit, missing read and unknown-object no-write cases plus canonical output/min/max neighbors for every editable field.
5. **MEDIUM — authoritative CI is still required.** Thirteen focused GREEN records are strong local evidence, including import, but do not replace the planner-selected full exact-source CI run.

## Evidence assessment

The simultaneous race and expanded Playwright evidence are valid, deterministic and materially close prior high-risk gaps. The thirteen-command mapping is a meaningful integration improvement. Approval remains blocked only by the focused override-consumer/recovery gaps and the remaining security/history matrix above.

## Required changes

Complete findings 1-4, update traceability, regenerate the plan and return with exact-source evidence; then run the selected full CI consumer.
