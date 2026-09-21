# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v7

- Reviewer: independent reviewer `/root/gate3_review`
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T175940Z-ec1ac34f2f/snapshot/source.patch`, SHA-256 `ba2172821aedb891fdb76985db5ad0d0892400f60ff30365be80fef6269a0ed1` (candidate `fd2f06dd0e9ef76e452654bc029ebdc3fca35dca40998cc371a215de8a202247`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v6.md`
- Evidence: all six mapped commands are exact-source GREEN in package `20260921T175940Z-ec1ac34f2f`
- Verdict: `CHANGES_REQUESTED`

## Prior v6 disposition

1. **Consumer/import coverage — PARTIALLY FIXED.** `object_details_effective_consumers_001_test.php:12-21` now verifies effective projection, ERP candidates, Bitrix `forObject` and unchanged legacy/detail rows. It still does not run identical import, card, queue/search/filter/pagination, ERP matching/fact freshness, OTIZ calculation/evidence/hash, explicit null or factory `0`.
2. **Migration/recovery coverage — PARTIALLY FIXED.** `object_details_editing_recovery_001_test.php:10` executes exact repeat and incompatible missing-index rejection. Its “fresh” assertion only counts tables already created by `PreopeningFixture`; it does not apply the migration to an absent schema. Its no-rebuild assertion only proves the table still exists, not that the incompatible shape/data was untouched. Predecessor upgrade, other incompatible shapes, concurrency and backup/restore remain absent.
3. **Authorization/concurrency/history — OPEN.** Test is unchanged; distinct role/read/edit/inactive/unknown denial, real race, complete replay/event accounting and immutable second history are absent.
4. **HTTP/browser — OPEN.** Tests remain unchanged and omit the previously listed rejection and journey cases.
5. **Field rules — OPEN.** Unit test remains unchanged and shallow.
6. **Traceability/CI — OPEN.** Grouped mappings still exceed exercised seams; exact-source full CI/import evidence is absent.

## Findings

1. **BLOCKER — material A9-A12 seams remain untested.** Add actual identical import preservation plus card, queue/search/filter/pagination, ERP matching/freshness and OTIZ operands/Kshah/evidence/hash tests. Cover explicit null, factory `0`, old document/snapshot/payment preservation and no external call on Save.
2. **BLOCKER — A15 evidence does not yet establish the lifecycle.** Run canonical migration against genuinely absent tables, exact predecessor schema and representative incompatible columns/indexes/checks/triggers; prove rejection leaves complete schema/data bytes unchanged; add deterministic concurrent runners and real backup/restore with replay and next revision. Counting fixture-created tables is not fresh-migration evidence.
3. **BLOCKER — A5-A8 authorization, concurrency and immutable history remain incomplete.** Add inactive, wrong business role, missing edit/read and unknown-object denial before replay; deterministic two-connection one-winner behavior; exact override/request/event counts and replay identity; revoked replay; rollback; second-event immutability and complete snapshots.
4. **HIGH — HTTP/browser journey remains incomplete.** Add malformed/duplicate input, stale/request conflict, unavailable safe body, validation retention, backdrop/focus, cancel/no-write, save/reload, escaping and compound pagination.
5. **HIGH — field expectations remain incomplete.** Assert independent normalized values/bounds for every field, catalogue membership/display, byte limits, null clearing, omitted neighbors and factory `0` exclusion.
6. **MEDIUM — exact CI and traceability remain unresolved.** The existing consumer frontier obligations are appropriate CI closure, but they are regression checks, not substitutes for missing acceptance assertions. Full exact-source CI/import must be GREEN after corrections.

## Evidence assessment

The expanded consumer and recovery commands are genuine exact-source GREEN and reduce risk in their named paths. They do not justify the broader A9-A12/A15 mapping or close the remaining contract matrix.

## Required changes

Complete findings 1-5, refine acceptance traceability, regenerate the plan and return with exact-source GREEN evidence.
