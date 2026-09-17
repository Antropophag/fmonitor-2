# Gate 3 test review — LEGACY-CONTROL-ENGINEER-MIGRATION-001

- Reviewer: `issue20-gate3-sol`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T164014Z-2e3f6fd0b9/package.json`
- Candidate source: `f76b0dd3655696cf3a769e1625940043b8e363702cb86ec14d4764eb92cf4d9d`
- Snapshot commit: `0b88adf796c51e24fbe46752684e75644e8326e7`
- Contract: `specs/LEGACY-CONTROL-ENGINEER-MIGRATION-001.md`
- Planner lane/reviews: `CRITICAL`; `gate3`, `final`

## RED evidence

The three package-linked integration-profile records are deterministic intended REDs on the missing public behavior, not setup failures:

- `tests/IdentityAccess/legacy_identity_link_001_test.php`: owner absent (`INTENDED_RED`), exit 255.
- `tests/InstallationProcess/legacy_control_engineer_migration_001_test.php`: migration CLI absent (`INTENDED_RED`), exit 255.
- `tests/Yii2/yii2_legacy_identity_link_001_test.php`: link control absent (`INTENDED_RED`), exit 255.

The tests use the declared IdentityAccess owner, real Yii HTTP, and public CLI process seams against isolated MariaDB. No production implementation was reviewed or written at Gate 3.

## Findings

1. **BLOCKER — acceptance C–L is not completely or sensitively covered.** `tests/InstallationProcess/legacy_control_engineer_migration_001_test.php` covers one ready row, deterministic repeat, successful apply/replay/reconcile, and a single source-drift rejection. It does not exercise: explicit-ID bounds and exclusion of legacy-only objects (C); name/email collision (D); any of the required skipped/conflict reason matrix or ambiguous/corrupt inputs (E); equivalent prior migration versus manual/different current assignment (F); canonical JSON bytes/counts/source and process fingerprints (G); multi-row provenance and atomic owner handoff (H); drift of each authority input and whole-batch no-first-mutation behavior (I); reconcile agreement for all terminal states (J); commit failure, unknown outcome, and concurrent commands (K); or error-path redaction and byte identity of historical process documents/facts (L). Correction: add deterministic MariaDB/CLI cases for every C–L branch, including a multi-row batch and fault/concurrency fixtures, and assert no facts for every rejection.

2. **BLOCKER — acceptance A–B is only partial.** `tests/IdentityAccess/legacy_identity_link_001_test.php` starts from already-active local users and covers neither invite→role→link→activate nor creation of a new local credential without legacy credential access (A). It omits inactive local identity, inactive/missing engineer role, invalid request ID, ambiguous persisted identity state, and append-only superseding correction with mandatory reason (B). `tests/Yii2/yii2_legacy_identity_link_001_test.php` covers authorized success, one unauthorized request, CSRF, escaping, and password hiding, but not inherited method/content bounds or the specified 400/409/422/503 mappings, replay, and activation flow. Correction: extend the owner and real HTTP tests through the existing public invitation/role/activation seams and cover the complete rejection/replay/correction/status matrix with no-fact assertions.

3. **MAJOR — several normative outputs lack independent oracles.** The migration test accepts any 64-hex digest instead of independently deriving the SHA-256 from specified canonical bytes, asserts only four assignment fields instead of the complete provenance/fingerprint/actor/time contract, and checks disclosure only across the happy preview/apply outputs. The identity test checks only the legacy name snapshot and detects forbidden words in stored JSON rather than proving legacy credentials were never read. Correction: derive canonical expected JSON and digest independently from fixtures, assert the full immutable fact payload, instrument/deny credential-column reads, and apply redaction assertions to every rejection/fault/unknown response on stdout and stderr.

## Scope assessment

No test scope creep was found: all three files target the declared identity-link, Yii administration, and preview/apply/reconcile seams. The problem is under-coverage and insufficient sensitivity, not added behavior.

## Gate 3 rereview — delta package 20260916T164850Z-a96040e8e9

- Reviewer: `issue20-gate3-sol-r2`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Candidate source: `6ff2a03bc46120a6899309ae6620c7e1a2b86f408bed73d4c70c1d3686c83866`
- Snapshot commit: `59e9aaade1507b86e5b5eef235ba22977d5a4ba9`
- Scope: delta against the first Gate 3 findings only

The refreshed package retains three setup-clean intended REDs: the owner, public migration CLI, and Yii link control are each absent at the first behavioral assertion. Source drift is false for all three records.

### Finding closure

1. **BLOCKER C–L: PARTIAL, remains open.** The delta adds explicit-ID exclusion/bounds, the five skipped reasons, a name/email-collision fixture, an output-derived digest comparison, fuller provenance, digest/source drift, broader fact hashing, and more redaction checks. It still lacks the three required conflict reasons (`IDENTITY_LINK_AMBIGUOUS`, `CURRENT_ASSIGNMENT_DIFFERS`, `SOURCE_CORRUPT`), equivalent prior migration versus manual/different current assignment, a batch with multiple ready rows proving all-or-nothing mutation, drift across every authority fact, reconcile results for skipped/conflict/unknown, commit rollback versus unknown outcome, concurrent preview/apply/reconcile behavior, and fault/unknown-path redaction. Acceptance F, material parts of H–J, and all of K therefore remain insensitive.

2. **BLOCKER A–B: PARTIAL, remains open.** The delta now covers invite→role→link→activate with an independent local credential, inactive/wrong-role local identities, and append-only correction lineage. It still does not create or detect ambiguous persisted link state, inactive `construction_control_engineer` role state, HTTP replay/conflict, content bounds, or the required 409 and 503 mappings. The HTTP cases cover some 400/422 outcomes but not the complete inherited status/replay matrix required by the contract.

3. **MAJOR independent oracles: PARTIAL, remains open.** Full provenance and additional response redaction assertions are improvements. However the digest expected value is still computed from `$one`, the system output under test, after merely removing its `digest`; it does not independently construct the specified canonical version/operation/sourceFingerprint/counts/rows document from fixture inputs. The adjacent `.canonical` expectation is also not stated by the normative spec. Sentinel absence from returned/stored JSON does not demonstrate that legacy password columns were never read and discarded. Fault/unknown outputs remain untested.

### New blocker check

No new scope creep or independent new blocker was found in the delta. Gate 3 remains blocked by the unclosed original findings above.

## Gate 3 formal rereview — package 20260916T175205Z-0ed052c713

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T175205Z-0ed052c713/package.json`
- Candidate source: `25e5e569f41231a0f2ef5ce623b8d57dd5db1299252348e211f0e635576dd8a2`
- Snapshot commit: `59e9aaade1507b86e5b5eef235ba22977d5a4ba9`
- Snapshot patch SHA-256: `8b8dd36bef9c999c7af4b5d54a517159aab6fafd7eefb200dd1ef716961dcd9c`
- Scope: executable RED completeness and sensitivity against contract/OpenSpec A–L and the two earlier Gate 3 returns

The three package-linked records remain source-exact intended REDs at the absent owner, CLI, and HTTP control. The expanded assertions close most earlier matrix, canonical-oracle, credential-read, drift, rollback, and concurrency gaps, but the candidate is not yet a complete executable Gate 2 specification.

### Findings

1. **BLOCKER — the HTTP 503 fixture is unreachable for a conforming owner.** `tests/Yii2/yii2_legacy_identity_link_001_test.php:33` posts the fault case for local user `9401`. `tests/Yii2/UserAccessFixture.php:25-31` gives that user only role `9210` (`user`), while the target of a link must have the active construction-control-engineer role. The command must therefore return the eligibility rejection (mapped to 422) before attempting the INSERT guarded by `legacy_link_fault`; expecting 503 makes the test fail after implementation for setup rather than missing persistence-failure behavior. Use an otherwise eligible, unlinked local engineer (or attach the active engineer role before the request), retain the trigger, and prove 503 plus no facts and sanitized output.

2. **BLOCKER — reconciliation remains insensitive to several normative terminal states.** `tests/InstallationProcess/legacy_control_engineer_migration_001_test.php:52` proves applied+skipped; line 53 only previews `already_applied`; lines 54/58/59 only preview conflicts; and line 77 makes a lost commit response resolve to `applied`. No operation is applied/reconciled with `already_applied` or `conflict` rows, and no non-forwarded/otherwise unresolved commit fixture proves a canonical `unknown` row/count. This leaves the exact `reconcile` contract in §3 and acceptance J–K only partially executable. Add independent exact reports (key order/counts/rows/reasons) for already_applied, all conflict reasons, and a genuinely unresolved unknown outcome, with reconcile read-only/no-false-success assertions.

3. **MAJOR — the IdentityAccess owner matrix still does not exercise two stated fail-closed authorization/cardinality inputs.** `tests/IdentityAccess/legacy_identity_link_001_test.php:38-48` covers duplicate links, an unauthorized actor ID, target-user/target-role eligibility, and ordinary replay/correction, but not an inactive actor or inactive actor role inside the owning transaction, nor detection of an already-corrupt ambiguous persisted current-link state. Those were part of acceptance B and the previous rereview's open ambiguity finding. Add deterministic corrupted-copy/actor-state fixtures and assert stable rejection with no facts.

### Closure assessment

Prior canonical digest/source-fingerprint independence, exact output bytes, credential-column read guards, conflict preview classifications, multi-row rollback, authority drift, HTTP method/content bounds, replay/conflict mappings, real activation/first login, and apply/reconcile concurrency are materially improved. No scope creep was found. The three findings above prevent Gate 3 approval.

## Gate 3 formal rereview — package 20260916T180241Z-50da7aa14b

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T180241Z-50da7aa14b/package.json`
- Candidate source: `47ffa8ad2e61f991663a0f8fb53cc87b2433d67bdc728c4253cde1ff14b9f014`
- Executable source: `799d32a3b1a99a52c0586471d6a80ff53178bc5553f92c33d93ffd997208d416`
- Snapshot commit: `59e9aaade1507b86e5b5eef235ba22977d5a4ba9`
- Snapshot patch SHA-256: `b0939f0a6f22f337f2b39147b6aed041390c404e4ee7fbebd4c0ca088fad1ce8`
- Planner lane/reviews: `CRITICAL`; `gate3`, `final`

### Evidence and closure

The three package-linked records are exact-source, fixture-applicable intended REDs: the IdentityAccess owner is absent, the public migration CLI is absent, and the Yii link control is absent. Each fails at its first missing public behavior rather than infrastructure setup.

The complete A–L matrix now has sensitive public-seam coverage. In particular, the candidate independently constructs canonical preview bytes, source fingerprint and digest; covers every skipped/conflict classification, already-applied/native conflict, multi-row atomic provenance, all authority drift, confirmed rollback, forwarded and unresolved commit outcomes, concurrent apply/reconcile, exact reconciliation reports, read-only preservation and fault-path redaction. IdentityAccess covers typed input, authorization including inactive actor/role rereads, target eligibility, duplicate/ambiguous state, replay and append-only correction. Real Yii HTTP covers invite/role/link/activate/first-login, admission/status mappings, reachable sanitized 503, and credential-column read denial.

All prior Gate 3 findings are closed. Expected values are independently derived from fixtures and the normative contract; no private implementation seam or scope creep was introduced. Gate 3 is approved for this exact candidate source.

## Narrow Gate 3 test-delta review — legacy source fixture setup

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Baseline approval: package `20260916T180241Z-50da7aa14b`, candidate `47ffa8ad2e61f991663a0f8fb53cc87b2433d67bdc728c4253cde1ff14b9f014`
- Reviewed delta: two `CREATE TABLE IF NOT EXISTS` fixture statements for prefixed legacy `users_roles` and `users` in `tests/InstallationProcess/legacy_control_engineer_migration_001_test.php`
- Scope: test setup only; production WIP was not inspected or approved

The statements execute through the isolated fixture's administrative connection after the existing `INTENDED_RED migration CLI absent` assertion and before the legacy seed inserts. Therefore the historical missing-CLI RED remains unchanged, while an implemented CLI can reach the already-approved behavioral matrix without requiring production migrations to create source-owned legacy tables.

The schemas contain exactly the columns consumed by this test's legacy fixtures and authority oracle. `IF NOT EXISTS` does not weaken a malformed existing table into success: incompatible columns or constraints still fail at the seed/read steps. No expected value, rejection, public seam, mutation assertion, canonical oracle, concurrency/fault case, or A–L sensitivity is removed or bypassed.

This narrow setup correction is approved. Delivery harness v1 cannot bind a historical RED baseline and current production-WIP source as one exact-source review, so no harness verdict is recorded for this test-delta review; this section is the explicit independent delta decision.

## Second narrow Gate 3 test-delta review — fixture clones and denied-session CSRF

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Baseline approval: package `20260916T180241Z-50da7aa14b`, candidate `47ffa8ad2e61f991663a0f8fb53cc87b2433d67bdc728c4253cde1ff14b9f014`
- Reviewed delta: explicit fixture-column clone INSERTs in the migration CLI test; pre-login CSRF capture for the unprivileged Yii request
- Scope: test setup only; production WIP was not inspected or approved

The three object-clone sites now name only columns present in the canonical pilot legacy-object fixture: `id`, address/entrance/registration fields, the six date fields, and `responsstroicontrol`. Removing nonexistent `installator*`, `ctime` and user-audit columns fixes fixture SQL reachability without changing the selected IDs, responsibility values, authority tuples, classifications, expected canonical documents, or mutation assertions.

The denied HTTP case now obtains its CSRF value from `/pilot/login` before authentication using the same `$denied` cookie jar, then authenticates that jar and submits the protected mutation with the captured token. This preserves CSRF validity while ensuring the request reaches authorization and still proves exact 403 plus no identity facts. It does not bypass CSRF or weaken the separate bad-CSRF assertion.

Both corrections are setup-only and preserve the approved RED/acceptance sensitivity. Delivery harness v1 cannot bind the historical RED baseline with the current production-WIP mixed source, so no `record-review` verdict is written; this section is the independent narrow delta decision.

## Third narrow Gate 3 test-delta review — legacy object provenance oracle

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Baseline approval: package `20260916T180241Z-50da7aa14b`, candidate `47ffa8ad2e61f991663a0f8fb53cc87b2433d67bdc728c4253cde1ff14b9f014`
- Reviewed delta: one expected provenance value in `tests/InstallationProcess/legacy_control_engineer_migration_001_test.php`
- Scope: test oracle only; production WIP was not inspected or approved

The corrected expected tuple now distinguishes the two required lineage fields: `source_legacy_object_id` is the selected legacy object `4512`, while `source_legacy_user_id` remains the responsible legacy engineer `73`. This matches §3 of the normative contract and the preview fixture mapping, and it is consistent with the request fingerprint input `[$operation, 4512, 73, 73, 94]`.

Replacing the erroneous object expectation `73` with `4512` corrects and strengthens the full immutable-provenance oracle; it removes no assertion and introduces no implementation assumption beyond the public persisted facts required by the contract. Verdict is `APPROVED` for this narrow test delta.

Delivery harness v1 cannot bind the historical RED baseline with the current production-WIP mixed source, so no `record-review` verdict is written for this section.

## Current-frontier v29 test-delta review

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Scope: root-owned test expectations for additive schema v29 only; production WIP was not inspected or approved
- Harness: no `record-review`, because this is a mixed-source post-Gate-3 test delta

The dedicated recovery inventory update is internally correct: `jrExpectedTables()` contains 82 unique names with exactly `fm2_legacy_identity_links`, `fm2_legacy_identity_link_events`, and `fm2_control_engineer_migration_operations` added; `jrExpectedAuto()` contains 45 unique names with the two link/event allocators added. Both functions prefix every name and then apply `SORT_STRING`, matching the binary-ordered manifest query.

### Findings

1. **BLOCKER — several current-catalogue assertions still require v28 while simultaneously expecting v29 migrations.** Correct the terminal result literals in `tests/InstallationProcess/inspection_evidence_schema_001_test.php:312`, `tests/InstallationProcess/assignment_order_original_attempt_audit_schema_001_test.php:93`, `tests/InstallationProcess/otiz_settlement_schema_001_test.php:19`, `tests/Otiz/runtime_schema_001_test.php:114-115`, and `tests/Jobs/jobs_schema_001_test.php:80`. These are current `ProductionPilotMigrationCatalogue` runs, not historical-v28 class tests; as written they either directly fail or assert an impossible combination such as schema version 28 with `range(1,29)`.

2. **BLOCKER — current exact table inventories remain at v28 in two consumers.** `tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php:11` still omits all three v29 tables from `iicTables()`, so its exact catalogue comparison cannot pass after a v29 migration. `tests/Yii2/feedback_schema_matrix.php:5-8` still expects replay version 28 and compares the live current schema/AUTO inventories to `RuntimeRecoverySchemaV28`; it must use the current v29 profile while retaining the separate v24 historical assertion on line 9.

3. **BLOCKER — clean-stand current observations were only half updated.** `tests/Support/yii2_clean_stand_acceptance.php:158` still requires observed schema/ledger `28/28`, while line 188 reports schema 29 but `migrationCount` 28. A fresh v29 catalogue must consistently require and report `29/29`.

4. **MAJOR — historical forward-update coverage preserves the old-image boundary but stops its current upgrade at v28.** Keep the exported v22/v23 manifests and `RuntimeRecoverySchemaV22/V23` checks unchanged. However `tests/Runtime/runtime_recovery_forward_update_001_test.php:55` invokes the current catalogue and therefore must expect terminal v29. Its newly-created-empty table inventory should also include the three v29 tables, while the filtering assertions must continue proving every retained old-image row/AUTO/state byte unchanged.

5. **MAJOR — the deadline certificate test conflates immutable v28 with current recovery.** In `tests/InstallationProcess/deadline_transfer_certificate_schema_001_test.php:21`, the literal `RuntimeRecoverySchemaV28` count assertion `79/43` is valid historical coverage and should remain. The comparison of the freshly migrated current database to a recovery profile must be moved to `RuntimeRecoverySchemaV29` with `82/45`, rather than relabelling or overwriting the v28 class expectation.

There are also stale v28 labels in otherwise updated current assertions (for example deadline-transfer and production-frontier messages). Those do not change executable sensitivity, but should be renamed to v29 to keep failure evidence unambiguous. The accidental/missing literals above block approval; historical v28 and retained-old-image boundaries themselves should not be mechanically rewritten.

## Current-frontier v29 test-delta rereview

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Scope: corrections to root-owned current-frontier tests only; production WIP was not inspected or approved
- Harness: no `record-review`, because this remains a mixed-source post-Gate-3 test delta

The prior inspection-evidence, assignment-original, OTIZ, Jobs, inspection-item literal inventory, clean-stand, and forward-update findings are closed. In particular, the retained v22/v23 image profiles remain historical, the current forward migration reaches v29, the three new tables are asserted empty after that forward migration, and old rows/AUTO/state remain protected. The standalone `RuntimeRecoverySchemaV28` inventory test at `tests/InstallationProcess/deadline_transfer_certificate_001_test.php:96` also correctly remains `79/43`.

Two executable current-frontier mismatches remain:

1. **BLOCKER — feedback current replay still expects v28.** `tests/Yii2/feedback_schema_matrix.php:4` requires catalogue keys `range(1,29)` and lines 7-8 correctly use `RuntimeRecoverySchemaV29`, but line 5 still expects replay schema version `28`. Change only that current replay result to `29`; retain the independent historical V24 assertion on line 9.

2. **BLOCKER — deadline live current inventory is still compared to V28.** `tests/InstallationProcess/deadline_transfer_certificate_schema_001_test.php:21` correctly preserves the standalone V28 literal counts `79/43`, but the same line still asserts `V28::tables($p) === Fixture::tables($db)` after the current catalogue has migrated the database to v29. Import/use `RuntimeRecoverySchemaV29`, assert its `82/45` counts, and compare the live database to V29 while leaving the V28 counts intact as historical coverage.

`tests/Runtime/deadline_transfer_certificate_recovery_001_test.php:23` now has only a stale `canonical v28` message on a current successful migration; update the label for clarity, but it is not itself an executable blocker. Approval remains withheld until the two literal mismatches above are corrected.

## Final current-frontier v29 test-delta rereview

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Scope: complete root-owned current-frontier v29 test delta; production WIP was not inspected or approved
- Harness: no `record-review`, because this is a mixed-source post-Gate-3 test delta

Both remaining blockers are closed. The feedback matrix now requires a current v29 replay and compares current tables/AUTO_INCREMENT inventory to `RuntimeRecoverySchemaV29`, while its historical V24 exclusion remains intact. The deadline schema test retains the immutable V28 `79/43` assertion, adds the V29 `82/45` assertion, and compares the live post-migration database to V29.

The full delta now consistently expects catalogue keys and terminal results through v29 across the named current consumers. Exact current recovery inventories contain 82 unique tables and 45 unique AUTO_INCREMENT tables, including the three/two v29 additions, with explicit prefixing and deterministic sorting. Inspection-item inventory, clean-stand ledger/count, OTIZ owner/concurrency fixtures, Jobs replay, forward update and current backup/restore expectations are aligned.

Historical boundaries remain meaningful and separate: standalone V28 stays `79/43`; V24 feedback exclusion is unchanged; retained v22/v23 images still create and restore their own literal manifests before the current catalogue advances them to v29; old rows, allocator frontiers and private-state bytes remain asserted unchanged. No accidental historical-profile upgrade was found.

All changed PHP tests pass syntax checks and `git diff --check` is clean. No unresolved current-frontier test finding remains; verdict is `APPROVED` for this test delta only.

## Post-execution current-frontier test corrections review

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Scope: test-only execution corrections after the v29 frontier review; production rename/implementation was not inspected or approved
- Harness: no `record-review`, because this is a mixed-source post-Gate-3 test delta

The deadline upgrade preservation oracle now excludes exactly the four newly allocated successor tables from the old-counter comparison: the v26 certificate revision allocator, v28 control-assignment allocator, and the two v29 identity-link/event allocators. The non-AUTO_INCREMENT migration-operation table is correctly absent from that exclusion. Existing predecessor allocator values remain compared byte-for-byte.

The maximal-prefix case now removes versions 26–29 to create a genuine v25 predecessor, then independently adds the four v26 certificate tables, v27 document links, v28 control assignments, and all three v29 tables to its exact expected current inventory. Sorting remains explicit and the coexisting-prefix replay checks are unchanged.

The physical table name `fm2_engineer_migration_operations` is consistently reflected in both 82-table exact inventories and the forward-update empty-successor list; the obsolete longer name is absent from tests. The operation table remains non-AUTO_INCREMENT, so the 45-table allocator inventory correctly adds only links and link events.

`SelectionNativeFixture` once again expects the direct `ControlEngineerAssignmentDefinitionSchemaMigration` result at its own historical version 28; this is distinct from a full current catalogue run. Likewise, the installation-completion arithmetic assertion is restored to the invariant values `[37,27,28]` derived solely from identifier lengths and is not a schema-frontier counter.

These corrections remove execution setup/oracle errors without weakening current v29 coverage or changing historical version meaning. Focused PHP syntax checks and `git diff --check` pass, and the reported deadline schema/runtime-jobs recovery evidence is consistent with the corrected expectations. Verdict is `APPROVED` for these test-only deltas.

## Gate 5 finding-driven contract/test delta review

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Scope: contract and executable-test sensitivity to the recorded Gate 5 findings; production WIP was not inspected or approved
- Harness: no `record-review`, because this is a mixed-source post-Gate-3 test delta

### Closed findings

The public CLI fixture now uses distinct process and `legacy_` prefixes throughout environment, source tables, fingerprints and mutations. The linked engineer has a second unrelated active role and remains ready. Same-operation changed preview is required to return `OPERATION_REPLAY_CONFLICT` without facts. Confirmed rollback is followed by exact `OPERATION_NOT_FOUND`. These additions sensitively cover the corresponding Gate 5 findings.

### Open findings

1. **BLOCKER — explicit operator/clock provenance is not sensitive to the prior hard-code.** The new environment contract supplies actor `94` and time `2026-09-16 12:00:00`, exactly the two values previously hard-coded by the rejected implementation, and the complete provenance assertion expects those same values. An implementation that ignores both environment inputs and retains the hard-code still passes. Add a separate operation with a different eligible positive actor and a distinct valid UTC instant, then assert both assignment and operation provenance/fingerprint use those alternate inputs. Also cover missing/invalid actor and clock as pre-mutation configuration failures.

2. **BLOCKER — strict preview schema coverage is incomplete and does not prove rejection before database access.** The new cases cover pretty bytes, reordered top-level keys, duplicate `version`, and missing/extra newline. The Gate 5 finding and amended contract also require wrong value types and additional keys to be rejected; neither has a fixture. Moreover every invalid call receives working DB credentials and only checks unchanged facts, so an implementation may connect/read first and reject later while passing the test. Run canonical-byte invalid cases with deliberately unusable DB configuration (or an equivalent connection/read denial witness) and still require exit 64, and add wrong-type plus unknown-key cases with correct-looking digest inputs.

3. **BLOCKER — the identity-link race finding still lacks a genuine concurrent first-link test.** The existing sequential duplicate/ambiguous owner matrix and migration-apply race do not exercise two transactions concurrently claiming the same unlinked local or legacy identity. A schema-shape review can establish intended uniqueness but cannot prove the public owner maps the real race to one success, one stable conflict and exactly one current link/audit lineage. Add the concurrent public-owner process/connection case requested by Gate 5.

4. **BLOCKER — snapshot/authority-locking behavior remains untested.** Sequential drift between preview and apply proves stale detection before mutation, and two competing apply operations prove assignment serialization. Neither changes a legacy responsibility/user/role, identity link, local eligibility or current assignment while apply is between validation and commit, nor proves preview observes one consistent snapshot. Add controlled concurrency barriers for at least the distinct authority families and assert a consistent preview plus whole-batch fail-closed/no partial assignment when authority changes race apply.

The newly added unused object `4531` does not supply the alternate operator-provenance case described in the handoff and should either be exercised or removed to keep the fixture intentional. No production conclusion is made. The four sensitivity gaps above prevent approval of this Gate5-driven test delta.

## Gate 5 finding-driven test delta rereview

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Scope: executable-test closure of prior Gate 5 findings; production WIP was not inspected or approved
- Harness: no `record-review`, because this remains a mixed-source post-Gate-3 test delta

The alternate operator case now applies object `4531` with actor `97` and time `2026-09-16 13:14:15`, then asserts the persisted assignment provenance. Empty/zero actor and malformed clock inputs exit 64 with unchanged facts. This is sensitive to the rejected actor-94/stale-time hard-code. Strict-preview coverage now includes wrong type and additional key, and every malformed representation is processed with an unusable DB host/port while still requiring exit 64, which proves canonical validation precedes DB access. These two prior blockers are closed.

The identity test now launches two separately connected processes against one fresh local/legacy pair and requires one `linked`, one stable duplicate conflict, and exactly one current link fact. This is a genuine concurrent first-link witness for the ownership constraint. To make the append-only audit half of acceptance B equally sensitive, also assert exactly one successful link event for the winning link/request (and no event for the losing request); this is a focused strengthening rather than the remaining primary blocker.

### Remaining blocker

**BLOCKER — no executable witness distinguishes the required snapshot/authority locking protocol from the rejected plain-read implementation.** The existing drift matrix changes each authority fact completely before apply starts, so both a correctly locking implementation and the rejected autocommit/plain-read implementation return `PREVIEW_STALE`. The competing-operation race changes only assignment creation through two apply commands; it does not race a legacy responsibility/user/role, identity link, local eligibility or current assignment writer while apply is validating and committing. Likewise, deterministic output alone does not prove preview used one consistent snapshot.

Add a controlled concurrent authority witness. A minimal sensitive pattern is: create a ready preview; hold/update one authority row in a separate transaction; start apply; prove the apply connection waits on the authority lock rather than passing validation; commit the changed authority; then require `PREVIEW_STALE` and zero assignment/operation facts. Repeat for a legacy-source authority and a process-side authority (identity/current-assignment or local eligibility) so both independently configured connections are covered. For preview snapshot sensitivity, pause it after its first authority read, commit a change to a later/earlier authority row, and require the result to correspond wholly to one database snapshot rather than a mixed tuple. The barrier may be established through observable MariaDB lock/process state; it need not add a production test hook.

Until such a witness exists, the exact Gate 5 snapshot/locking finding can regress without failing this suite. Verdict remains `CHANGES_REQUESTED` for the test delta only.

## Gate 5 locking/snapshot witness rereview

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Scope: new concurrent audit/apply-lock tests and remaining preview-snapshot sensitivity; production WIP was not inspected or approved
- Harness: no `record-review`, because this remains a mixed-source post-Gate-3 test delta

The concurrent first-link oracle is now complete for its bounded case: one link fact and exactly one joined audit event are required, so the losing request cannot append an event. That prior strengthening is closed.

The two new apply cases cover one legacy-source authority and one process-side eligibility authority, but their current wait assertion is not sensitive to locking. `proc_get_status(...)[running]` after a fixed 150 ms proves only that the CLI has not exited; process startup or unrelated work can consume that interval. A rejected plain-read implementation that reaches its SELECT only after the parent commits the update will also observe the changed value, return `PREVIEW_STALE`, and create zero assignments, satisfying every current assertion.

**BLOCKER — require an observable database lock wait before releasing each authority update.** Poll MariaDB lock-wait evidence (`information_schema.INNODB_LOCK_WAITS`/`INNODB_TRX`, or the available equivalent) and bind the waiting transaction to the child apply connection and the blocking transaction to the fixture connection. Assert the waited record/table is `legacy_fm_maintable` in the legacy case and the prefixed `fm2_pilot_users` row in the process case. Only after that evidence is sustained should the parent commit and the test assert `PREVIEW_STALE`/zero facts. This distinguishes `SELECT ... FOR UPDATE` authority locking from slow startup and plain reads without a production hook.

Final code inspection can confirm transaction construction, but it cannot by itself make the test suite catch a later loss of preview snapshot semantics. A feasible no-hook snapshot witness exists:

1. On a coordinator connection, acquire a metadata write lock on a table read after preview's first authority read, for example legacy `users`.
2. Start preview and poll `PROCESSLIST` until its exact SELECT is blocked on that metadata lock, proving earlier reads have occurred.
3. While holding the lock, change the relevant later-read legacy user status on the coordinator connection, then release the lock.
4. Require preview to report the wholly pre-change authority tuple/classification from its already-established consistent snapshot; an autocommit sequence instead observes the newly committed later-row value and produces a mixed result.
5. Restore the fixture and assert preview remains read-only.

The specific table can follow the documented query order, and the test should assert the blocked SQL/table through `PROCESSLIST` rather than rely on timing. Thus neither a test-only production hook nor invasive instrumentation is necessary. Until the real lock waits and one-snapshot witness are executable, the original Gate 5 snapshot/locking finding remains open and the delta is `CHANGES_REQUESTED`.

## Final Gate 5 locking/snapshot witness rereview

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Scope: final InnoDB-lock and preview-snapshot test witnesses; production WIP was not inspected or approved
- Harness: no `record-review`, because this remains a mixed-source post-Gate-3 test delta

The apply-lock half is now sensitive. Each parent transaction holds an uncommitted authority-row update, and the test polls `INNODB_LOCK_WAITS` joined to `INNODB_LOCKS` for the requested exact table/key before committing. A plain non-locking read cannot create that requested record wait. The legacy `fm_maintable/4522` and process `fm2_pilot_users/73` cases therefore distinguish real authority locking from startup delay, then verify stale rejection and no assignment. This prior blocker is closed.

The preview test uses a feasible no-hook metadata-lock/`PROCESSLIST` barrier, but its mutation ordering currently contradicts the expected oracle. `LOCK TABLES ... WRITE` is acquired and `fm2_pilot_users.status=0` is executed before the preview child starts. With the fixture connection in autocommit mode, that UPDATE is already committed while the metadata lock merely prevents the child from opening/reading the table. A correct consistent snapshot established by the child's earlier reads must therefore include the already-committed inactive local user and classify `LOCAL_ENGINEER_INELIGIBLE`, not the asserted pre-change `ready`.

**BLOCKER — move both authority mutations after the metadata wait is observed.** The sensitive sequence is:

1. Acquire the metadata WRITE lock on prefixed `fm2_pilot_users` without changing its row.
2. Start preview and wait until `PROCESSLIST` shows its exact `fm2_pilot_users` SELECT blocked, proving its earlier reads established the snapshot.
3. While it remains blocked, update/commit legacy user status through the separate mutator and update local user status through the lock-holding connection.
4. Release `LOCK TABLES` and require `ready`: a consistent snapshot sees both pre-change active values, while autocommit sees the earlier legacy value and newly inactive local value and returns `LOCAL_ENGINEER_INELIGIBLE`.
5. Restore both rows and retain the read-only assertions.

Also bind the metadata-wait observation to the preview child's connection ID if the fixture can expose it; exact table/SQL matching in this isolated database is useful but a connection-bound waiter is stronger. After correcting the mutation order, the witness can close the remaining snapshot finding without production hooks. As written, the expected `ready` would reject a correct snapshot implementation, so verdict remains `CHANGES_REQUESTED`.

## Final Gate 5 concurrency test-delta verdict

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Scope: corrected preview snapshot witness plus previously reviewed apply-lock and first-link concurrency tests; production WIP was not inspected or approved
- Harness: no `record-review`, because this is a mixed-source post-Gate-3 test delta

The snapshot sequence is now correct and discriminating. The fixture acquires only the metadata WRITE lock, starts preview, and observes its exact `fm2_pilot_users` metadata wait before committing either authority change. While preview remains blocked, a separate connection commits the legacy user inactive state and the lock holder commits the local user inactive state; only then is the metadata lock released.

A single consistent snapshot established before those commits must retain both pre-change active values and return `ready`. The rejected autocommit/plain-read sequence can read the earlier legacy authority before the mutation and the later local authority after release, producing the mixed `LOCAL_ENGINEER_INELIGIBLE` result and failing this oracle. Both rows are restored afterward. The exact table-matched `PROCESSLIST` wait in the isolated fixture is sufficient for this bounded witness; binding a connection ID would be an optional hardening, not an approval blocker.

Together with the two exact `INNODB_LOCK_WAITS` apply cases and the concurrent first-link fact/audit oracle, the suite now exercises preview snapshot consistency, legacy and process authority locking, uniqueness serialization, stale rejection and no partial mutation. All previously recorded Gate 5 finding-driven test gaps are closed. PHP syntax and `git diff --check` pass; verdict is `APPROVED` for this contract/test delta only.

## Narrow post-fork connection fixture review

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Scope: parent mysqli recovery after the concurrent identity-link fork; production WIP was not inspected or approved
- Harness: no `record-review`, because this is a mixed-source post-Gate-3 test delta

Reopening `$f->db` after both children join is correctly placed before the result/fact/audit assertions and preserves the concurrent outcome oracle. However, `$owner` was created before the fork with the original mysqli object and is reused after the race for the corrupt-ambiguous and correction cases. Reassigning `$f->db` does not replace the connection retained inside that already-created production owner.

**BLOCKER — recreate the owner after reopening the fixture connection.** Immediately after assigning the new admin mysqli, call `ProductionLegacyIdentityLinkFactory::create($f->db,$p,$p,$clock)` again and replace `$owner`. The `$facts` closure dereferences the fixture object and therefore follows the new `$f->db`, but the owner does not. Without recreation, child destructor effects can still surface later as a stale/closed connection even though the immediate parent SQL assertions use the reopened handle.

No race expectation needs to change. Verdict is `CHANGES_REQUESTED` for this narrow fixture correction only.

## Narrow post-fork connection fixture rereview

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Scope: parent mysqli and owner reconstruction after the concurrent identity-link fork; production WIP was not inspected or approved
- Harness: no `record-review`, because this is a mixed-source post-Gate-3 test delta

After both children join, the fixture now opens a fresh parent admin mysqli and immediately reconstructs `ProductionLegacyIdentityLinkFactory` with that connection, the same process/legacy prefixes and the existing deterministic clock. This occurs before fact/audit queries and before every subsequent ambiguous/correction owner call.

The `$facts` closure follows the replaced fixture connection, while the recreated owner no longer retains the inherited pre-fork socket. Race outcomes, exactly-one link/event assertions and later behavior assertions are unchanged. PHP syntax and `git diff --check` pass; verdict is `APPROVED` for this narrow fixture correction.

## Post-race fixture scoping rereview

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Scope: reconnected fixture references/charset and post-race assertion scoping; production WIP was not inspected or approved
- Harness: no `record-review`, because this is a mixed-source post-Gate-3 test delta

The fresh parent mysqli is configured for `utf8mb4` and assigned to both `$f->db` and `$f->auth->db`, so fixture helpers, authentication support and the recreated owner share one valid post-fork connection. Setting the charset after owner construction is effective because the owner retains the same mysqli object, not a copied connection.

The former global link-count assertion now counts only original local identities 7301/7302, preserving its name/email-no-auto-link meaning without treating the deliberate 7310 winner as a failure. Correction history is filtered to local identity 7301 before asserting two append-only rows and exact supersession lineage, so the concurrent fact cannot perturb ordering or count.

Concurrent winner/conflict, exactly-one fact/event, ambiguous corruption and correction behavior remain independently asserted. PHP syntax and `git diff --check` pass; verdict is `APPROVED` for this narrow fixture follow-up.

## Fourth narrow Gate 3 test-delta review — HTTP body-bound fixture

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Baseline approval: package `20260916T180241Z-50da7aa14b`, candidate `47ffa8ad2e61f991663a0f8fb53cc87b2433d67bdc728c4253cde1ff14b9f014`
- Reviewed delta: supported `UserAccessFixture::request` invocation for the Yii 413 case
- Scope: test setup only; production WIP was not inspected or approved

`UserAccessFixture::request` accepts five arguments and serializes its `$fields` array with `http_build_query`. Moving CSRF, request ID, legacy user ID and the 9000-byte padding into that supported array therefore sends a real oversized `application/x-www-form-urlencoded` request and exercises the intended 413 admission boundary. It removes the prior unsupported sixth raw-body argument without changing the expected status or protected mutation target.

The adjacent 415 case still deliberately supplies `Content-Type: application/json` with an empty body, independently exercising unsupported media-type admission. The separate bad-CSRF and domain/status cases remain unchanged. This correction restores setup reachability and strengthens rather than weakens HTTP boundary sensitivity; verdict is `APPROVED`.

Delivery harness v1 cannot bind the historical RED baseline with the current production-WIP mixed source, so no `record-review` verdict is written for this narrow delta.

## Fifth narrow Gate 3 test-delta review — Yii rejection data provider

- Reviewer: `issue20-gate3-formal-r3`
- Artifact author: `root`
- Verdict: `APPROVED`
- Baseline approval: package `20260916T180241Z-50da7aa14b`, candidate `47ffa8ad2e61f991663a0f8fb53cc87b2433d67bdc728c4253cde1ff14b9f014`
- Reviewed delta: normalized `[fields, expectedStatus]` cases in `tests/Yii2/yii2_legacy_identity_link_001_test.php`
- Scope: test setup/data-provider correction only; production WIP was not inspected or approved

Each case now has an explicit associative request-fields array paired with its expected status, and the loop destructures that pair directly. This removes the invalid lookup of numeric index `2` from a mixed associative array while preserving the exact matrix: malformed request ID → 400, zero legacy ID → 422, and missing legacy user → 422.

The before/after `$facts()` snapshot remains inside every iteration, so all three rejection paths still require atomic no-write behavior through the real Yii POST helper. No admission, authorization, domain, or response assertion is removed or relaxed. Verdict is `APPROVED` for this narrow test delta.

Delivery harness v1 cannot bind the historical RED baseline with the current production-WIP mixed source, so no `record-review` verdict is written for this section.
