# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v3

- Reviewer: independent joint Gate 3/Gate 5 reviewer `/root/gate3_review`; authored neither specification/tests nor production implementation
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T171446Z-02427b7fd8/snapshot/source.patch`, SHA-256 `148f11d26567fd7496ae6cb34fa48a2bd8b7624a09aae92f806c6bc54c60125a` (candidate `5be9d45e49f1ce2b00ea0379be3c77bf07bf5ef6227faaa5ec832f4d78ef4e57`)
- Agreed review scope / prior findings disposition: owner-authorized one-time joint review of the ready implementation; both prior `CHANGES_REQUESTED` records remain authoritative history and every finding is disposed below
- Specification: `specs/OBJECT-DETAILS-EDITING-001.md`
- Public seams: application command, canonical Yii HTTP/card, effective import/consumer reads, migration/recovery, and canonical Playwright UI
- Evidence: all six mapped commands are exact-source GREEN in package `20260921T171446Z-02427b7fd8`; package-selected consumer/schema/architecture evidence reported GREEN except local `pilot_case_import_001_test.php`, which remains `UNKNOWN` from environment DB credential mismatch and is still a CI obligation
- Verdict: `CHANGES_REQUESTED`

## Prior findings disposition

1. **Complete A1-A15 matrix — OPEN.** The finished implementation makes the six tests GREEN, but tests still omit object scope, inactive/missing-capability distinctions, real concurrency, complete replay/history identity, actual import repeatability, named Bitrix/document consumers, incompatible/partial migration and restore continuation.
2. **Wrapper-only RED evidence — FIXED for implementation review, not retroactively.** Original RED remains wrapper/first-failure evidence as recorded. The same complete bodies now execute GREEN on the exact candidate, proving setup/body validity, but does not replace missing acceptance scenarios.
3. **Real browser seam — PARTIALLY FIXED.** Chromium drives canonical Yii and verifies modal groups, Kshah absence, target size, Cancel and Escape. Backdrop, focus, validation retention, submit/reload, zero writes and history pagination remain untested.
4. **Concurrency/replay/rejection discrimination — OPEN.** `tests/InstallationProcess/object_details_editing_mariadb_001_test.php:19-24` still has no two-connection race, stable per-reason assertions, request/event identity on replay, inactive actor, missing exact capability, or object-scope denial.
5. **Migration/recovery lifecycle — OPEN.** `tests/Runtime/object_details_editing_recovery_001_test.php:6-8` checks class existence and recovery source strings only; no migration state or restore round-trip executes.
6. **Independent complete history expectations — OPEN.** The test checks ordered field names only, not case/object identity, actor snapshot/time, old/new typed/raw/display/reference/unit facts or immutability after later edits.
7. **Consumer public seams — OPEN.** `object_details_effective_consumers_001_test.php` directly inserts an override and exercises only `MariaDbEffectiveObjectDetails`; no import, card/queue, Bitrix, ERP or OTIZ seam is called.
8. **HTTP security/no-write matrix — PARTIALLY FIXED.** Guest, wrong methods, missing CSRF and three invalid fields are covered. Inactive/missing-capability/object-scope, duplicate/malformed encoding, conflict/stale, unavailable safe body and validation retention remain absent.
9. **Quality Graph owner verifier — FIXED.** Policy registers application/MariaDB and canonical HTTP tests plus the consumer frontier.
10. **Static substring weakness — OPEN.** Recovery remains source-token-only and Yii history/modal assertions remain broad body substring checks; only the bounded modal interactions use DOM semantics.
11. **Acceptance traceability compression — OPEN.** Grouped mappings still claim A9-A12 and A15 although mapped tests never invoke those public seams.

## Findings

1. **BLOCKER — tests do not catch missing object-scope authorization.** A5 explicitly requires scope, yet no test creates an actor with capability but without access to object 4512. The implementation consequently authorizes any existing object for any active business-role holder. Correction: add application and HTTP scope-before-existence tests with exact denial and zero facts.
2. **BLOCKER — declared consumer coverage is materially false.** The A9-A12 mapping points to a projection-only test; no Bitrix seam is tested at all, no identical import is run, and ERP/OTIZ public consumers are not invoked. This missed a real implementation omission: no Bitrix reader was changed to effective `zavnumber`. Correction: exercise every named consumer and import with independently expected effective/provenance/preservation outcomes.
3. **BLOCKER — schema/recovery test cannot detect invalid partial schema handling.** The migration merely creates absent tables and the test never applies it. Add fresh/repeat/exact/compatible-partial/incompatible/concurrent migration tests and a backup/restore/replay/next-write round-trip.
4. **BLOCKER — concurrency and immutable history remain unproved.** Add deterministic two-connection one-winner coverage, loser/request-row accounting, exact replay event/outcome identity, second edit immutability, complete event snapshot and rollback assertions.
5. **HIGH — field tests do not validate reference membership/coherent display or nullable consumer behavior.** They accept arbitrary numeric reference strings and assert only key survival for most fields. Add real reference allowlist/coherence cases, exact boundaries, correction from existing values, explicit null clearing and downstream factory-number `0` exclusion.
6. **HIGH — browser/HTTP tests are incomplete.** Extend Playwright through backdrop/focus, invalid input retention, cancel/no-write, successful submit/reload, escaped old→new history and >8 pagination; add missing capability/inactive/scope/conflict/unavailable HTTP cases and safe response-body assertions.
7. **MEDIUM — local import regression is unresolved.** `pilot_case_import_001_test.php` is `UNKNOWN`, not GREEN. The credential mismatch is plausibly environmental, so it is not itself evidence of a product defect, but exact-source CI must execute the selected import obligation successfully before PR-ready.

## Required changes

Complete the six blocking/high coverage groups above, correct item-level traceability, regenerate the plan, and obtain GREEN exact-source CI including import. Gate 3 remains blocked.
