# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v10

- Reviewer: independent reviewer `/root/gate3_review`
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T183725Z-9d3c6e31aa/snapshot/source.patch`, SHA-256 `9150360c3d77929f7794ce62c855dda9f40df771c005d6d6cff0935c1f9943ce` (candidate `57dabb171919d5af36222592750be820fa1492de44892a0e0dee3dd669f23183`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v9.md`
- Evidence: thirteen mapped exact-source commands are GREEN in package `20260921T183725Z-9d3c6e31aa`
- Verdict: `CHANGES_REQUESTED`

## Prior v9 disposition

1. **Manual consumer propagation — PARTIALLY FIXED.** `object_details_effective_consumers_001_test.php:20` now drives the actual Yii card and queue search against the seeded manual factory number. Effective reader, ERP candidates and Bitrix remain covered. Manual ERP matching/freshness and, critically, manual `pitmaterial` reaching OTIZ/Kшах/evidence/hash remain untested.
2. **#222 recovery continuity — OPEN.** Schema and generic backup/restore commands are GREEN, but no test seeds override/event/request facts before backup and proves restored replay/history/auto-increment/next revision.
3. **History/HTTP edges — PARTIALLY FIXED.** Real race and Playwright save/no-write remain covered. New authorization cases improve A5. Complete event snapshot and >8 compound chronology plus several HTTP failure/retention/focus cases remain absent.
4. **Authorization/field bounds — SUBSTANTIALLY FIXED.** Test delta adds inactive, missing-read and unknown-object denials with no facts, plus exact per-field normalization and material bounds. Wrong-role/missing-edit is already represented by the denied actor/revoked replay paths.
5. **CI — OPEN AS PUBLICATION CONDITION.** Thirteen local exact-source commands, including import, are GREEN. The canonical full CI run is still required after Gate 3 but is not itself a test-completeness finding.

## Findings

1. **BLOCKER — A12 manual OTIZ propagation remains unproved.** The mapped OTIZ regression only tests imported technical payloads. Add a focused case that applies/seeds a manual `pitmaterial` override, invokes `MariaDbNativePremiumInputs`, and independently asserts derived Kшах, manual locator/content hash/date provenance, no attribution to the old payload hash and unchanged accepted/published financial artifacts.
2. **BLOCKER — A15 restore continuity remains unproved for this capability.** Seed at least two revisions plus accepted replay request, run the real backup/restore path, then prove override/event/request bytes, replay identity, restored auto-increment and a successful next revision. Generic empty-table inventory restoration is insufficient for the normative scenario.
3. **HIGH — complete immutable history/chronology remains incomplete.** Assert the full stored snapshot fields (case, actor/time, typed/raw/display/unit/reference) independently and exercise >8 mixed process/detail events through the compound cursor without omissions or duplicates.
4. **HIGH — remaining HTTP/browser rejection behavior is not fully tested.** Add malformed/duplicate request encoding, stale/request conflict and persistence-unavailable safe bodies; verify validation input retention and backdrop/focus behavior. Existing auth/CSRF/method/invalid/Cancel/Escape/Save coverage is otherwise sound.

## Evidence assessment

The thirteen GREEN commands now provide strong coverage of import, card/queue manual propagation, ERP candidate/Bitrix, schema frontier, backup/restore infrastructure, real concurrency and the main browser journey. Gate 3 is blocked by two focused normative gaps, not by the absence of broad regression evidence.

## Required changes

Add focused A12 and A15 cases and the bounded history/HTTP assertions above, regenerate the plan, and return for Gate 3 review. Run canonical exact-source CI only after approval.
