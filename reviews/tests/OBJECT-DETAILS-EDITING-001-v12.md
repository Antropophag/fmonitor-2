# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v12

- Reviewer: independent reviewer `/root/gate3_review`
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T191033Z-8a74ae30db/snapshot/source.patch`, SHA-256 `de7b540addeb516c39884086ee9d31247996dca4cb8881e824544f805544ee1b` (candidate `2d5177cb904a591f9cf98717036fdae7aa3f82d8f12e82057691e9ff78bbd587`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v11.md`
- Evidence: thirteen mapped exact-source commands are GREEN in package `20260921T191033Z-8a74ae30db`
- Verdict: `CHANGES_REQUESTED`

## Prior v11 disposition

1. **A8 >8 compound chronology — FIXED.** `yii2_object_details_editing_001_test.php:11` creates nine detail revisions, proves the initial page is bounded to eight, extracts the compound cursor and proves the older event is reachable. The implementation already unions process/detail events with deterministic tuple ordering, and existing card access tests constrain history to card access.
2. **Failed-validation input retention — OPEN.** The test still asserts only HTTP 422 and zero facts; it does not prove submitted values are retained or that field-aware errors render as required by section 7/A13.
3. **Duplicate/malformed/unavailable safe response and backdrop/focus — OPEN.** No corresponding test delta is present. Existing tests cover CSRF, method, forbidden/invalid fields, generic card 503, Cancel/Escape and target size, but not these exact editor outcomes.
4. **Full CI — OPEN AS NEXT PUBLICATION STEP.** It is not part of this Gate 3 verdict.

## Findings

1. **BLOCKER — failed-validation retention remains an explicit untested acceptance requirement.** Submit an invalid edit containing recognizable valid sibling values, assert 422 (or the approved render convention), field-specific error association, exact submitted values retained in the modal and zero override/request/event facts.
2. **BLOCKER — fail-closed details POST parsing and unavailable response remain unproved.** Exercise duplicate keys and nested/malformed form encoding plus injected persistence failure; assert stable 400/503 outcomes, no partial facts, and no SQL/schema/credential/stack leakage. This is bounded to the already agreed canonical HTTP seam.
3. **MEDIUM — backdrop/focus behavior remains unverified.** Add the specified backdrop close/no-write path and accessible focus behavior to the existing Playwright test. This may accompany the two blocking HTTP/UI cases without broadening scope.

## Evidence assessment

All earlier v1-v11 BLOCKER findings except the two bounded A13 HTTP/validation cases above are fixed, superseded or inapplicable. The thirteen GREEN records now provide strong direct evidence for field rules, authorization/replay, true concurrency, history pagination, consumers/import, OTIZ provenance, schema and capability-specific restore. Gate 3 is not blocked by CI or production defects.

## Required changes

Add only the bounded validation-retention and fail-closed POST/unavailable tests, plus backdrop/focus in the existing browser flow; refresh exact-source evidence and return for Gate 3 approval. Full CI follows as the publication step.
