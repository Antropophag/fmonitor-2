# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 v4

- Reviewer: independent reviewer `/root/gate3_review`; authored neither specification/tests nor implementation
- Test author: root delivery agent
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T173853Z-e4cc899a3f/snapshot/source.patch`, SHA-256 `e838496f38300b22a3413afaeb95860fbeab6bb91690b913ee7fe3cac4b3c5e6` (candidate `cbc6907002decd0e734b2cda38a22b4991cba4378c54bf4aa1d9320bb405bdd6`)
- Prior review: `reviews/tests/OBJECT-DETAILS-EDITING-001-v3.md`
- Evidence: all six mapped commands are exact-source GREEN in package `20260921T173853Z-e4cc899a3f`
- Verdict: `CHANGES_REQUESTED`

## Prior v3 findings disposition

1. **Object-scope authorization coverage — OPEN.** No test creates an actor with `objects.details.edit`/`objects.read` who lacks access to object 4512. The production correction labels global `objects.read` as `in_scope`; the test suite cannot distinguish global read capability from object-specific scope.
2. **Consumer frontier — OPEN.** Production now adds `MariaDbBitrixOrderDocumentLinksRead::forObject()`, but `object_details_effective_consumers_001_test.php:10-16` still invokes only `MariaDbEffectiveObjectDetails`. It runs no importer, card/queue, Bitrix, ERP or OTIZ public seam.
3. **Migration/recovery lifecycle — OPEN.** `object_details_editing_recovery_001_test.php:6-8` remains class/source-string inspection and executes neither migration nor backup/restore.
4. **Concurrency/immutable history — OPEN.** The MariaDB test remains unchanged: no two-connection race, replay event identity/request counts, second-edit immutability, case identity, complete snapshots or concurrent loser facts.
5. **Reference/null/boundary matrix — PARTIALLY FIXED.** The implementation now validates a reference catalogue and fixes queue null projection, but the root test delta did not add expectations for either behavior. Exact field boundaries, correction, explicit clears and factory `0` downstream exclusion remain untested.
6. **Browser/HTTP matrix — OPEN.** Browser and HTTP tests are unchanged and still omit backdrop/focus, retained invalid values, submit/reload, escaped/paginated history, inactive/missing capability/scope, stale/conflict and unavailable responses.
7. **Import UNKNOWN / CI — OPEN.** The package contains six mapped GREEN records only; it does not establish authoritative exact-source CI or a GREEN import obligation.

## Findings

1. **BLOCKER — corrected authorization semantics are not tested and still lack an object-specific oracle.** Add application and HTTP tests where an active actor has both global permissions but is outside the target object's scope; assert indistinguishable denial before existence and zero facts. If the intended current model makes `objects.read` global scope to all objects, amend the normative contract explicitly rather than silently equating the two.
2. **BLOCKER — corrected consumer code has no acceptance coverage.** Add public-seam tests for identical import preservation, card, queue search/filter/pagination including explicit null, Bitrix lookup, ERP candidates/matching/freshness and OTIZ operands/evidence/hash. Direct override-table insertion plus one projection is not A9-A12 coverage.
3. **BLOCKER — migration/recovery corrections have no executable test.** Add fresh/repeat/exact/compatible-partial/incompatible/concurrent schema cases and actual backup/restore preserving override/event/request facts, replay, auto-increment and the next write.
4. **BLOCKER — concurrency and complete immutable event behavior remain untested.** Add deterministic two-connection one-winner coverage, exact rejection/request/event counts, replay identity after current authorization, second edit immutability, case identity and complete typed/raw/display/unit/reference snapshots.
5. **HIGH — UI/HTTP corrections are not protected.** Extend Playwright through backdrop/focus, validation retention, cancel/no-write, save/reload and compound chronology beyond eight; add missing HTTP security/conflict/unavailable cases and safe-body assertions.
6. **HIGH — unit field expectations remain too weak.** `object_details_editing_001_test.php:14-23` checks key survival for most fields. Add independently expected normalized values, catalogue membership/display, min/max neighbors, null clears, correction from existing values and downstream `zavnumber='0'` exclusion.
7. **MEDIUM — grouped traceability still overclaims coverage.** A9-A12 and A15 remain mapped to files that do not invoke their declared seams. Restore item/subcase-level traceability and regenerate the plan.

## Evidence assessment

The six GREEN records are source-bound, deterministic and show the existing bodies execute. They do not cover the open scenarios above, and their unchanged narrow assertions cannot validate the executor's substantial corrections.

## Required changes

Add the missing acceptance tests, regenerate the plan, obtain new exact-source GREEN evidence and return for independent Gate 3 review.
