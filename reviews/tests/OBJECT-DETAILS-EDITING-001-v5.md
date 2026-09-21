# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v5

- Reviewer: independent reviewer `/root/gate3_review`
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T174914Z-3b0e490222/snapshot/source.patch`, SHA-256 `46b2b022d57d6123af6438dbfe19c727fbc4ee4edef9bf9c8fae5743b4abeeb1` (candidate `17e705a78895945295c8e92a42dfa41333fb0dfe0ec6eeb057979279ee447684`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v4.md`
- Evidence: all six mapped commands are exact-source GREEN in package `20260921T174914Z-3b0e490222`
- Verdict: `CHANGES_REQUESTED`

## Prior v4 findings disposition

1. **Object-specific authorization test — OPEN.** No test distinguishes an actor with global `objects.read`/edit capability from one authorized for the particular object. The revised SQL accepts any qualifying actor whenever the target case exists.
2. **Consumer frontier tests — OPEN.** The projection-only consumer test is unchanged; import, card/queue, Bitrix, ERP and OTIZ public seams remain unexercised.
3. **Migration/recovery tests — OPEN.** Production inspection was corrected, but the mapped test still checks only class existence and three source substrings. No schema state or restore round-trip executes.
4. **Concurrency/immutable history tests — OPEN.** No real two-connection race, replay/request/event accounting, second-edit immutability or complete snapshot assertion was added.
5. **UI/HTTP tests — OPEN.** Playwright and HTTP bodies remain unchanged and omit the previously listed security, validation, save/reload and chronology paths.
6. **Field expectations — OPEN.** Reference catalogue, display coherence, null clearing, exact boundaries, correction and downstream factory `0` behavior remain untested.
7. **Traceability — OPEN.** A9-A12 and A15 mappings still claim public seams the mapped files do not invoke.

## Findings

1. **BLOCKER — A5 object scope has no valid test oracle.** Add an actor who has the two global permissions but lacks the target object's canonical assignment/scope, then assert application and HTTP denial before existence disclosure and zero override/request/event facts. The current actor-agnostic case-existence join must fail this test.
2. **BLOCKER — A9-A12 consumer/import behavior remains untested.** Invoke identical import plus card, queue/search/filter/pagination, Bitrix, ERP candidates/matching/freshness and OTIZ calculation/evidence public seams with independent expected outcomes, including explicit null and factory `0`.
3. **BLOCKER — A15 remains untested despite the production correction.** Execute fresh, repeat, exact-current, exact predecessor upgrade, incompatible columns/indexes/checks/triggers, concurrent migration, and recovery/replay/next-write cases. Verify incompatible inspection performs no mutation.
4. **BLOCKER — A6-A8 concurrency and history remain incomplete.** Add deterministic parallel one-winner behavior, exact request/event identity on replay, revoked replay denial, rejection fact counts, rollback, second edit immutability, complete case/actor/time/value/raw/display/unit/reference snapshots and compound chronology beyond eight.
5. **HIGH — HTTP/browser coverage remains incomplete.** Cover missing capability/inactive/object scope, duplicate/malformed payload, stale/request conflict, unavailable safe response, backdrop/focus, validation retention, cancel/no-write, submit/reload, escaping and pagination.
6. **HIGH — field-rule assertions remain too shallow.** Assert independently expected canonical output and invalid neighbors for every field, catalogue membership/display, byte boundaries, explicit clear and omitted-neighbor semantics.
7. **MEDIUM — exact-source CI/import evidence is absent.** Six mapped GREEN results are valid but do not establish the selected full CI/import obligations.

## Evidence assessment

The six GREEN records are valid for the narrow unchanged bodies. They provide no new evidence for the corrected authorization or inspect-first migration and do not resolve the acceptance gaps above.

## Required changes

Complete the acceptance matrix, correct traceability, regenerate the plan, capture exact-source GREEN evidence and return for Gate 3 review.
