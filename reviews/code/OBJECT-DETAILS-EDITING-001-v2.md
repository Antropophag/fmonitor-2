# Code review: OBJECT-DETAILS-EDITING-001 — v2

- Reviewer: independent reviewer `/root/gate3_review`
- Specification/test author: root delivery agent
- Implementation author: separate executor
- Reviewed source: base `9523bca002eec559db14ecf946d3c297989cb18e` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T173853Z-e4cc899a3f/snapshot/source.patch`, SHA-256 `e838496f38300b22a3413afaeb95860fbeab6bb91690b913ee7fe3cac4b3c5e6` (candidate `cbc6907002decd0e734b2cda38a22b4991cba4378c54bf4aa1d9320bb405bdd6`)
- Prior review: `reviews/code/OBJECT-DETAILS-EDITING-001.md`; Gate 3 v4 remains `CHANGES_REQUESTED`
- Verification: six mapped exact-source GREEN records; corrected behavior lacks the required tests described in Gate 3 v4; authoritative exact-source CI/import GREEN not supplied
- Verdict: `CHANGES_REQUESTED`

## Prior code findings disposition

1. **Object-scope authorization — OPEN.** `ObjectDetailsEditApplication.php:21` names `MAX(permission='objects.read')` as `in_scope`, but it is a global role permission with no relation to `$c->objectId` or case membership. The case query at line 12 only proves object existence. Horizontal authorization remains unresolved unless the contract is changed to define all readable objects as in-scope.
2. **Bitrix effective factory number — FIXED.** `MariaDbBitrixOrderDocumentLinksRead.php:15-18` resolves through `MariaDbEffectiveObjectDetails`, and card composition passes the effective factory number. Missing regression coverage remains a Gate 3 blocker.
3. **Migration compatibility — PARTIALLY FIXED, still blocking.** Named locking and substantial column/collation/event-index/trigger/check validation were added. However `ObjectDetailsEditingSchemaMigration.php:20` alters/backfills an existing event table before validating its other columns/indexes/constraints, so an incompatible partial schema can be mutated before fail-closed rejection. `assertCompatible()` validates required indexes only on the events table, not the primary/`object_requests` indexes of edits/requests. No executable migration test protects these paths.
4. **Case identity and event metadata — FIXED in code.** Schema/application now persist `installation_case_id`; registry snapshots include typed value/raw/display/unit/referenceLabel. Missing immutable-history tests remain.
5. **Reference catalogue/selects — FIXED in code.** Registry validates `ObjectDetailsReferenceCatalogue`, and the view renders `shlz-select` controls. Catalogue correctness/coherence is not tested.
6. **Nullable queue projection — FIXED.** Queue uses `JSON_CONTAINS_PATH` and honors present JSON null rather than falling back to legacy. No regression test exists.
7. **Compound chronology — FIXED in code.** `MariaDbYiiObjectCardProjection.php:112-115` unions process/detail events with a deterministic tuple cursor. Pagination/escaping/no-duplicate behavior is untested.
8. **Replay current authorization — FIXED in code.** Authorization executes before request replay lookup. Object-specific scope remains open under finding 1.
9. **Permission migration side effect — FIXED structurally.** Permission provisioning is separated into `ObjectDetailsEditPermissionProvisioning` and the canonical role catalogue contains the capability. It is invoked by the explicit composite migration only when schema changes; repeat exact-schema runs do not re-grant.
10. **Controller SQL/raw connection duplication — FIXED.** Controller delegates write composition through `PreopeningResources`; read overlays/history live in the canonical card reader/projection.
11. **Evidence/CI — OPEN.** Six mapped tests are GREEN, but import/exact-source CI completion is not established and Gate 3 is not approved.

## Findings

1. **BLOCKER — object scope is still implemented as a global capability.** `ObjectDetailsEditApplication::authorization()` has no object/case input and therefore cannot enforce the specification's access-to-this-object precondition. Correction: use the canonical object authorization seam keyed by actor and object/case, or explicitly amend/approve the contract if all `objects.read` users intentionally access every object.
2. **BLOCKER — migration can modify incompatible schema before rejection.** Line 20 performs three DDL/DML steps before full compatibility inspection. If another required event column/index/check is malformed, the migration adds/backfills `installation_case_id` and then throws, violating non-destructive fail-closed behavior. Preflight the whole existing shape first, classify the one approved upgrade shape exactly, then apply it; validate all required indexes for all three tables.
3. **HIGH — substantial corrected behavior is unprotected by tests.** Bitrix effective lookup, reference catalogue/display, null clearing, compound chronology, current-auth replay, case identity/snapshots and migration validation can regress while all six mapped tests remain GREEN. This requires test changes and Gate 3 restart, so final approval is impossible on this candidate.
4. **MEDIUM — reference catalogue embeds ambiguous labels.** `ObjectDetailsReferenceCatalogue.php:7-8` maps codes `7` and `9` to display strings identical to their codes, which does not demonstrate coherent human-readable code/value mapping and may merely encode fixture artifacts. Confirm these labels against the canonical imported reference evidence and use truthful display values or explicitly model unknown display provenance.
5. **MEDIUM — exact-source CI/import remains unresolved.** UNKNOWN is not GREEN. Run the selected CI consumer after corrections and inspect the complete failure inventory if needed.

## Required changes

Fix findings 1-2, add the Gate 3 coverage for all corrected behavior, regenerate/review the plan, obtain Gate 3 approval and authoritative exact-source CI, then return for final review. Candidate is not PR-ready.
