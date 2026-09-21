# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v11

- Reviewer: independent reviewer `/root/gate3_review`
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T190009Z-48a44f2c91/snapshot/source.patch`, SHA-256 `84b7c2656e24768caf1e972b09305d1e0114e6889bf981b44f5701f6067cd517` (candidate `52b116209dc48779ac6b690b77b451f22ead12285207af15d4f0fcd67f5e7008`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v10.md`
- Evidence: thirteen mapped exact-source commands are GREEN in package `20260921T190009Z-48a44f2c91`
- Verdict: `CHANGES_REQUESTED`

## Prior v10 disposition

1. **A12 manual OTIZ propagation — FIXED.** `tests/Otiz/excel_inputs_001_test.php:21` seeds manual `pitmaterial=86` and independently asserts Kшах `11500`, effective date, manual revision locator/hash and inequality with the imported payload hash.
2. **A15 restored #222 continuity — FIXED.** `object_details_editing_restore_roundtrip_001_test.php` wraps the production recovery test with two owner-created revisions. The recovery assertions verify exact relational rows, schema inventory/auto-increment, restored exact replay, next revision/event and the advanced event sequence.
3. **Complete history/chronology — PARTIALLY FIXED.** Stored event immutability, counts, race, replay and visible history are covered. The normative >8 mixed process/detail compound cursor remains unexecuted.
4. **HTTP/browser edge behavior — PARTIALLY FIXED.** Auth, CSRF, methods, invalid fields, Cancel/Escape, Save/reload/history and no-write accounting are covered. Failed-validation input retention, duplicate wire key rejection, persistence-unavailable safe body and backdrop/focus remain untested.
5. **Full CI — OPEN AS NEXT PUBLICATION GATE.** This is not counted against Gate 3 completeness.

## Findings

1. **BLOCKER — A8's explicit >8 compound chronology acceptance has no executable test.** Seed interleaved process and detail events beyond the initial page, traverse every cursor page and assert deterministic global ordering, no duplicates/omissions, preserved immutable snapshots and access no broader than the card. Existing history assertions only prove one visible event.
2. **HIGH — required failed-validation retention is untested.** Submit invalid data through canonical Yii/browser and prove the modal retains submitted values with field-aware errors and zero writes. The current HTTP test sees only status 422 and does not inspect returned UI state.
3. **HIGH — fail-closed HTTP parsing/unavailable output remains incomplete.** Add duplicate wire keys/malformed nested encoding and injected persistence failure; assert stable 400/503 behavior, zero facts where applicable and absence of SQL/schema/credentials/stack in the response. Add backdrop/focus assertions to complete A13/A14 interaction coverage.

## Evidence assessment

The two v10 blockers are fully and convincingly resolved. The thirteen GREEN commands now cover nearly the whole slice, including real import, changed consumers, true concurrency, actual Playwright save and capability-specific restore. Gate 3 remains blocked by one explicit acceptance row and bounded HTTP/UI assertions, not by CI.

## Required changes

Add the compound chronology and bounded validation/parsing/unavailable UI cases, refresh the plan/evidence and return for Gate 3 review. Canonical CI follows approval as the next publication gate.
