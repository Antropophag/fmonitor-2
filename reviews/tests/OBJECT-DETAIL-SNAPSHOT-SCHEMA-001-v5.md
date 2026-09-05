# Test review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 composed runner fixture amendment

- Reviewer: `/root/seed_original_planning_review` (independent of this amendment)
- Amendment author: `/root`
- Reviewed commit: `14350e51aea2873ccccf83692031446ae19b4211`
- Exact preimplementation RED base: `26bed9af6c70aefc690e89b5965246ea26628e1c`
- Exact production candidate used by the evidence: `877f52993d8ddf4570990874800306241581a120`
- Approved specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Composed runner test: `tests/InstallationProcess/production_migration_runner_001_test.php`, SHA-256 `c75ba4b017bae4e6ef2be25dfb1c9f3a859d70d2afdbb4ecf843e836aeb9399e`
- Catalogue fixture: `tests/Support/ProductionMigrationRunnerCatalogContract.php`, SHA-256 `dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3`
- RED evidence: `docs/operations/object-detail-composed-runner-fixture-red-2026-09-05.md`, SHA-256 `2121dba91a47b5b4a84f9c5d72a0ce89a242b51d4be60665ca9f59169fd13b29`
- Verdict: **APPROVED** for the composed v12 runner fixture amendment only

## Findings

The fixture literals are independently traceable to the approved v0.4
manifest, not to production implementation: the details table has the five
specified ordered columns; quarantine has its five specified ordered columns;
both use unsigned object identity and add only `PRIMARY(object_id)` to the
index catalogue. The existing runner verifier continues to assert InnoDB,
database-default table/column collation, null/default/extra metadata,
constraints, exact table set, indexes, and preservation.

Only `production_migration_runner_001_test.php` selects the explicit
`columnsV12()` and `indexesV12()` extensions. The default v1-v11
`columns()`, `indexes()`, `foreignKeys()`, and `checks()` outputs remain
byte-identical; their combined JSON SHA-256 was independently recomputed as
`380b1de99c8a1b8d3c204126c2b6805bfbaaeab9c9b59a63862b1b8899e87c4f`.
The reviewed commit touches no protected E2E/caller, production, configuration,
specification, or planning file.

The success, exact-repeat, compatible completed-v4 fixtures, conflict recovery,
and valid empty-password/prefix cases now require terminal schema version 12
and the exact appropriate applied-version suffix. Physical catalogue checks
therefore prevent a JSON-only version bump from satisfying the verifier.
Evidence from the byte-identical two-file patch on the detached
preimplementation base records successful actual v11 versus expected v12,
which is the intended missing-successor RED rather than setup failure. The same
bytes pass against the named production candidate.

## Independent verification

```text
php -l tests/InstallationProcess/production_migration_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/production_migration_runner_001_test.php

php -l tests/Support/ProductionMigrationRunnerCatalogContract.php
No syntax errors detected in tests/Support/ProductionMigrationRunnerCatalogContract.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
exit 0
```

A subsequent read-only database inventory returned `[]` for `t_pmr_%`.
`git diff 14350e5^ 14350e5 --check` also passed. Existing randomized database,
user, and temporary-file cleanup remains unchanged by this amendment.

## Authority boundary

This verdict approves only the explicit composed v12 catalogue and runner
expectation amendment. It does not approve or complete the broader v12
migration, interruption/failure-retry behavior, named-lock or concurrency
fixtures, importer behavior, protected E2E changes, Gate 5, integration, or
Done.

Required changes: none within this bounded amendment.
