# Gate 5 final review — LEGACY-CONTROL-ENGINEER-MIGRATION-001

- Reviewer: `issue20-gate5-final`
- Artifact authors: root (scope/spec/tests), delegated executor (implementation)
- Verdict: `CHANGES_REQUESTED`
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T190954Z-4f707e4666/package.json`
- Candidate source: `00d7c050dba500018e792281627ea660134dc7b4cd534b91b76376189855059a`
- Snapshot commit: `59e9aaade1507b86e5b5eef235ba22977d5a4ba9`
- Planner lane/reviews: `CRITICAL`; `gate3`, `final`

## Standards

1. **BLOCKER — the migration CLI does not preserve the configured legacy/process boundary.** `bin/fmonitor2-control-engineer-migration.php:7` constructs the owner with only `FMONITOR_PROCESS_TABLE_PREFIX`, and `app/InstallationProcess/LegacyControlEngineerMigration.php:10` consequently reads `fm_maintable`, `users`, and `users_roles` through that process prefix. The adjacent identity-link seam correctly accepts a distinct legacy prefix. This violates the inherited `PILOT-CASE-IMPORT-001` DB/prefix contract and can read the wrong tables in a real deployment. Pass and validate the legacy source configuration independently, and add a differing-prefix public-process case.

2. **BLOCKER — application provenance is fixture data, not production provenance.** `LegacyControlEngineerMigration.php:19` hard-codes actor `94` and time `2026-09-16 12:00:00` into both assignments and the operation row. Every production apply would falsely attribute facts to the fixture administrator at a stale time. Introduce an explicit authenticated/operator identity source and a clock at the application seam, validate them, and persist their actual values.

3. **MAJOR — the append-only identity-link uniqueness invariant is raceable.** `MariaDbLegacyIdentityLink.php:9,14-16` uses `READ COMMITTED`, checks absence through non-unique indexes, then inserts; the v29 schema has no constraint or locked singleton/current-key projection preventing two concurrent first links for the same local or legacy identity. Both transactions can observe no current row and commit, contradicting “maximum one current link.” Add a database-enforced/serialized current-key ownership design and a genuine concurrent first-link test.

4. **MAJOR — the implementation contains duplicated and internally inconsistent local-role reads.** `LegacyControlEngineerMigration.php:10-13` performs the local lookup twice, discards the first result, derives the fingerprint’s role status from the first row of all roles, and then requires `count($local) === 1`. A valid engineer with any additional active role is therefore classified ineligible, while the authority fingerprint may describe a different role. Query the exact construction-control role once and fail closed only on actual cardinality corruption.

## Spec

1. **BLOCKER — apply does not verify exact canonical preview bytes/schema.** `bin/fmonitor2-control-engineer-migration.php:9` decodes arbitrary JSON and hashes its re-encoding. Reordered/pretty-printed input, a missing trailing newline, duplicate keys, wrong value types, or additional keys can be accepted when paired with the re-encoded digest, although §2/§3 require exact key order, exact bytes, one trailing newline, and canonical-byte validation. Validate the complete shape/types/key order and compare the input bytes to the one canonical serialization before any database access.

2. **BLOCKER — an existing operation is not an exact replay.** `LegacyControlEngineerMigration.php:18` selects only `report_json`, ignores the persisted `preview_digest` and `source_fingerprint`, compares only each fresh row’s legacy/local IDs, and returns success. The same operation ID can therefore be replayed with changed counts, statuses/reasons, source fingerprint, or digest and be reported successful. Bind replay to the exact stored operation fingerprint/digest and reject every non-exact reuse.

3. **BLOCKER — preview/apply do not implement the required snapshot and authority locking protocol.** `LegacyControlEngineerMigration.php:8-15` builds preview through many autocommit reads, so it is not one snapshot. During apply, `:19` reruns the same plain reads; only the later assignment batch locks cases/current assignments/local eligibility. Legacy responsibility/user/role rows, current links, operation state, and all target facts are not explicitly locked in deterministic order as §3 requires. A concurrent authority change can be observed inconsistently or happen after validation. Establish the documented transaction/snapshot boundary and lock every authority row before classifying/mutating.

4. **MAJOR — confirmed rollback can leave a false UNKNOWN reconciliation marker.** `LegacyControlEngineerMigration.php:19` writes the marker before commit but its catch path rolls back without removing it. A deadlock or other confirmed rollback after marker creation makes apply return a non-UNKNOWN failure while later `reconcile` reports all rows `unknown`, contrary to the required confirmed-rollback/unknown distinction. Remove/fsync the marker on confirmed rollback and retain it only when commit outcome is genuinely unknowable.

5. **MAJOR — exact engineer eligibility is implemented as single-role eligibility.** `LegacyControlEngineerMigration.php:13` rejects any linked local user having more than one role (`count($local)!==1`), although the product model explicitly permits multiple roles and the contract requires an active `construction_control_engineer` role, not exclusivity. This blocks valid migration rows and also makes the preview authority tuple unstable with unrelated role ordering.

## Evidence assessment

The package’s three focused exact-source records are GREEN, and the prior Gate 3/test-delta approvals establish strong fixture sensitivity, including real Yii activation/first-login and credential-read guards. Those records do not override the executable discrepancies above: the migration fixture deliberately uses one shared prefix and asserts the hard-coded actor/time, while no exact-byte rejection, non-exact operation replay, concurrent first-link, multi-role engineer, or confirmed-post-marker rollback case closes these findings. CI and PR remain `UNKNOWN`, which is acceptable before publication but cannot be treated as Gate 5 GREEN.

`CHANGES_REQUESTED`
