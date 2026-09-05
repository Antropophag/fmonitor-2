# Code review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 observer/interruption tranche

- Reviewer: `/root/seed_original_planning_review` (independent of implementation and tests)
- Implementer: `/root`
- Reviewed implementation commit: `d741286e11d988d87efd3422908b96d8ce2148b3`
- Approved RED commit: `cc02608f5b71323434ce4a2765d5a7b692a5cd92`
- Independent Gate 3 commit: `d8f98831d5601ad2f616bdcc1f5cb3b1267ac03e`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Verdict: **APPROVED_FOR_BOUNDED_TRANCHE**

## Exact implementation artifacts

```text
35c6b3abe183b2af5e89cfca6e3e6a2631669d715e63e9c227aa65f37d5e788b  app/InstallationProcess/NoOpObjectDetailSnapshotSchemaObserver.php
9c0b0a4135781dc32a81ded2e4408713a4e61b12bcd05509e5e1f6c7a48219dc  app/InstallationProcess/ObjectDetailSnapshotEngineSchemaMigration.php
2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
1429730d809589007de7194c7a63a168a266926818971fcb71e7f2b94d24df14  app/InstallationProcess/ObjectDetailSnapshotSchemaMigrationVerification.php
1a5bd07834f10e9e7139c071df8499cfed60a12aa8e502624cacc2fa95c389fd  app/InstallationProcess/ObjectDetailSnapshotSchemaObserver.php
46caa922424386ee207ba50601239d52708a5c885e81293b468a319d5a776ff4  app/InstallationProcess/ObjectDetailSnapshotSchemaPhase.php
f0736960b15bce85a936dd84614f09b91a6aa10cf2741bea5b8d9c58ec6f5914  docs/operations/object-detail-schema-observer-green-2026-09-05.md
```

Reviewed test SHA-256:

```text
e966f3b40fc10b043878fdeec7462eba81600913925bade8254f094b86c4e763  tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php
```

## Findings

No blocking finding within this tranche.

- The production facade and explicit verification facade delegate to one
  `ObjectDetailSnapshotEngineSchemaMigration`; schema inspection, conflict
  handling, DDL, post-verification, sorting, and result construction therefore
  retain a single owner.
- Production `ObjectDetailSnapshotSchemaMigration::apply()` always constructs
  the inert no-op observer. No environment, CLI, request, global, or runtime
  selector can inject the verification observer into the production entrypoint.
- The enum, interface, and final verification facade exactly match the reviewed
  public declarations. The verification facade only supplies the explicit
  observer to the same engine.
- `LOCK_ACQUIRED` is emitted only after successful real lock acquisition.
  `DETAILS_CREATED` and `QUARANTINE_CREATED` are emitted only after their
  corresponding real CREATE succeeds and before the next inspection query.
  All occur while the engine's acquired-lock flag remains active.
- Observer exceptions and errors from a deliberately closed mysqli connection
  are converted to `DatabaseUnavailable`. The single `finally` release path is
  retained; release failure also prevents a success result, while durable table
  state remains available to a fresh retry.
- Extraction preserves the pre-existing metadata fingerprint, conflict,
  prefix, collation, table ordering, and exact return semantics, as confirmed
  by the basic schema, held-lock, and composed runner regressions.

## Independent verification

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 observer phases and interruption recovery

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real held-lock rejection and retry

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 canonical migration contract

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

git diff d741286^ d741286 --check
exit 0
```

A fresh read-only residue inventory returned `[]` for `fm2_ods_%` and
`t_pmr_%` after the focused runs.

## Authority boundary

This approval is limited to the reviewed observer API, phase emission,
observer-fault/closed-connection handling, lock release, and fresh retry
tranche. Actual DDL privilege-denial/retry, two-creator IPC and namespace
concurrency fixtures, importer behavior, full regression/integration, overall
migration Gate 5, and Done remain separately required and unapproved.
