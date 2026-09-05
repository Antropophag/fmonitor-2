# Combined code review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 data-free schema engine

- Reviewer: `/root/object_detail_schema_gate3` (independent; did not author production or tests)
- Exact reviewed commit: `fd0487410d595f506adee7c3457698bf2a10c34e`
- Native-false RED / Gate 3 / correction: `dc4b701cee160166edad31ad27c592e4bf20a980` / `1e7e43a4375faab3c08d07236c2d40fc54e20c0a` / `b7bc649cb6d918dffa947ff0068bddfe17d31d5b`
- Owner approval: `e8f17b63a3c93e8f4be5664c309b435fde3318e9`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Verdict: **APPROVED_SCHEMA_ENGINE_ONLY**

## Exact production artifacts

```text
2483e6458819fcc8b61d9f272730494aa15222a2a64d5732b4ed621a90084317  app/InstallationProcess/MariaDbObjectDetailSnapshotSchemaFingerprint.php
35c6b3abe183b2af5e89cfca6e3e6a2631669d715e63e9c227aa65f37d5e788b  app/InstallationProcess/NoOpObjectDetailSnapshotSchemaObserver.php
2ae45e43084c56858d589eb98362c61415b13a92cf1ffba0781a9936c9b82eff  app/InstallationProcess/ObjectDetailSnapshotEngineSchemaMigration.php
2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
1429730d809589007de7194c7a63a168a266926818971fcb71e7f2b94d24df14  app/InstallationProcess/ObjectDetailSnapshotSchemaMigrationVerification.php
1a5bd07834f10e9e7139c071df8499cfed60a12aa8e502624cacc2fa95c389fd  app/InstallationProcess/ObjectDetailSnapshotSchemaObserver.php
46caa922424386ee207ba50601239d52708a5c885e81293b468a319d5a776ff4  app/InstallationProcess/ObjectDetailSnapshotSchemaPhase.php
6d4e6d6e89bf462524a8793e12d3ab69bd1d15125a5956ac52412fa2ad5f36e6  bin/fmonitor2-migrate.php
```

## Verification

All six object-detail schema tests and the canonical runner were rerun at the
exact reviewed commit:

```text
object_detail_snapshot_schema_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 canonical migration contract

object_detail_snapshot_schema_lock_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real held-lock rejection and retry

object_detail_snapshot_schema_observer_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 observer phases and interruption recovery

object_detail_snapshot_schema_ddl_denial_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real DDL-denial partial recovery

object_detail_snapshot_schema_concurrency_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 causal two-creator serialization and namespace independence

object_detail_snapshot_schema_native_false_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 native-false CREATE has no false success event

production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
```

Every command exited 0. PHP lint passed for all seven production schema files,
the runner, and all six tests. `make architecture-check` passed all seven rules;
`git diff --check` passed. A fresh read-only inventory returned
`{"schemas":[],"users":[]}` for all `fm2_ods_%` databases and owned denial/
native-false users.

Exact test hashes:

```text
b0dc9cf7d87c275c201409f6933924c7d8a275b6d43da137f84c025bac884ed6  object_detail_snapshot_schema_001_test.php
b0f0bbc6b830bdcf37dddef8c07d72ce1e1016991586f29dbc86415c78b8c1f2  object_detail_snapshot_schema_lock_001_test.php
e966f3b40fc10b043878fdeec7462eba81600913925bade8254f094b86c4e763  object_detail_snapshot_schema_observer_001_test.php
19db7462e488b1eb0f17b59425260a40d42204a5363e9e6cf36fce9a41b86b7d  object_detail_snapshot_schema_ddl_denial_001_test.php
92f241e3f89c525eb07af07a84ede4a15cf62dbfbc4b353bbbc1737108075955  object_detail_snapshot_schema_concurrency_001_test.php
706756f7f55fea544952fe0aa7c0ad29b9c601d71ad5667a5ea70392b0cd6d7a  object_detail_snapshot_schema_native_false_001_test.php
```

## Findings

No blocking defect was found in the owned data-free schema engine.

The production facade always supplies an inert observer, and the explicit
verification facade delegates to the same engine without a runtime selector.
The public enum/interface/factory declarations match the exact approved API.
Prefix validation precedes database access. The engine derives the specified
database/prefix SHA-256 named lock, accepts only acquisition result 1, holds it
across preflight, DDL, phase observation, final verification, and result
formation, and attempts release only after acquisition. Acquisition, observer,
inspection, DDL, final-verification, and release failures cannot publish
success and map to `DatabaseUnavailable`.

Family inspection is read-only and exact. The fingerprint covers base table,
InnoDB, validated database-default utf8mb4 collation, ordered columns and exact
types/unsigned semantics, per-column charset/collation, null/default/extra/
generated metadata, the sole primary index, and absence of FK/CHECK constraints.
Family-wide conflicts precede mutation; absent members are created details then
quarantine; results are sorted; exact and partial existing rows are opaque and
preserved; no DML or source access exists in the engine.

The native-false correction is necessary and correct: a CREATE must return
native `true` before its table is recorded or its phase emitted. The dedicated
real privilege test with mysqli exception reporting disabled proves a denied
CREATE returning `false` emits no false `QUARANTINE_CREATED` success event,
retains the durable details table, releases the lock, and returns typed
unavailability.

The accumulated independent coverage now demonstrates clean/repeat/partials,
metadata drift and two database collations, conflicts and decoys, prefix limits
and zero-DB validation, data-free CLI v12 creation, held-lock timeout/retry,
observer and closed-connection interruption, exception-mode and native-false
DDL denial, causal same-family two-creator serialization, and different-prefix
independence.

No required mutation or recovery scenario in v0.4's data-free schema-engine
acceptance remains wholly unproved. A live `GET_LOCK` NULL/query-error result
and a live connected `RELEASE_LOCK` non-1 result do not have dedicated fixture
cases; their required fail-closed behavior is directly enforced by the same
strict result checks and catch/finally paths reviewed here. Timeout acquisition
and release-via-closed-connection are exercised. This residual lack of separate
stimuli is recorded but is not a code-review blocker for the engine.

## Scope boundary and incomplete work

This verdict approves the data-free schema engine and its v12 CLI registration
only. It does not make the entire OpenSpec change Done. Still separate and
incomplete are importer characterization and removal of runtime/importer family
DDL, fail-closed importer schema preconditions, any required consumer/composed
fixture updates outside this engine, full repository `make verify`, operations
and OpenSpec completion accounting, and verification on the eventual exact
integration SHA. Source import, fixture population, importer DML semantics,
quarantine lifecycle redesign, full migration integration, and archive are not
authorized by this review.

## Required changes

None within the data-free schema-engine scope.
