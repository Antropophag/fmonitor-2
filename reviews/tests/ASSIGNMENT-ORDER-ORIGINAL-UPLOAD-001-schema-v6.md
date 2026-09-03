# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema migration v6

- Reviewer: separately tasked agent `/root/original_upload_migration_gate3_v6`
- Test author: separately tasked agent `/root/original_upload_migration_red`
- Reviewed commit: `e4031545c4f6ab86d2fde420e5c6c791ea43ee69`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4, owner-approved hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec delta hash `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Public seams: `CanonicalMigrationApplication::run(...)`, `AssignmentOrderOriginalSchemaMigration::apply(...)`, and `bin/fmonitor2-migrate.php`
- Verdict: `APPROVED`

## Prior finding disposition

The append-only v5 `APPROVED` record remains intact. This rereview covers the
subsequent FK-oracle correction only. The earlier expected list and the MariaDB
catalog observation used different valid orderings: the contract list used
byte order, while the query inherited the database collation. The corrected
comparison treats the 34 independently specified FK column mappings as an
order-independent set of exact signatures.

The observation query also orders by binary table and constraint names and by
`ORDINAL_POSITION`, so database retrieval is deterministic and the source
column order of a composite constraint is stable before signature projection.
Both expected and observed signatures are then sorted with `SORT_STRING` before
exact equality. The pre-database sensitivity assertion independently proves
that reversing input order is immaterial while changing a referenced column is
still detected. Table, source column, referenced table, referenced column and
delete rule all remain present in every compared signature.

## Retained combined coverage

The correction changes no expected FK, index, column, CHECK, engine, collation,
capability literal, schema version or runner result. The dedicated seven-table
schema verifier and literal runner catalogue retain their previously reviewed
hashes. Clean, repeat, populated, hostile capability, malformed root, hostile
revision, historical-v4 compatibility/near-match, v3 short-circuit/recovery,
configuration, unavailable-database, charset, restricted-DDL and secret
redaction coverage is unchanged.

The runner contract therefore remains sensitive to all 38 tables, 104 indexes,
34 FK mappings and 31 CHECKs approved in v5, including the complete v12
original-evidence family and preservation of predecessor facts. The new
normalization removes only an accidental ordering requirement; a missing,
additional or changed FK signature still changes exact array equality. Expected
values remain test-side literals rather than production manifests or SQL.

## Independent reproduction

At `2026-09-04T02:19:14+03:00` on exact reviewed commit
`e4031545c4f6ab86d2fde420e5c6c791ea43ee69`, against the healthy
`fmonitor2-test-test-db-1` MariaDB contour:

```text
$ sha256sum tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php \
  tests/InstallationProcess/production_migration_runner_001_test.php \
  tests/Support/ProductionMigrationRunnerCatalogContract.php
5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
f8f9509007d4ffae9372fd65573f372f8c9560e7ade2c873ccd96798d996c1dd  tests/InstallationProcess/production_migration_runner_001_test.php
29fd4330fa64623789c4216c3f16a1c00fc58d224a882fa61a73ba7cc7daa9e6  tests/Support/ProductionMigrationRunnerCatalogContract.php

$ php -l tests/InstallationProcess/production_migration_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/production_migration_runner_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/production_migration_runner_001_test.php
PHP Fatal error: Uncaught TestFailure: example A
Expected: schemaVersion 12, appliedVersions [1,2,3,4,5,6,7,8,9,10,11,12]
Actual:   schemaVersion 11, appliedVersions [1,2,3,4,5,6,7,8,9,10,11]

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical additive
assignment-order-original schema migration v12 is missing.

$ git diff --check
PASS (no output before this review record)
```

Both FK sensitivity assertions execute before runner example A. The healthy DB
accepted the connection and the runner successfully applied canonical v1-v11;
the observed failures are therefore the intended absent v12 frontier and absent
public v12 migration owner, not setup or FK ordering noise. No production or
test artifact was edited by this review.

## Required changes

None.

## Gate decision

Fresh Gate 3 is `APPROVED`. Gate 4 may implement the minimal additive v12 owner
and canonical runner registration against exact commit
`e4031545c4f6ab86d2fde420e5c6c791ea43ee69`. Any subsequent test or literal
catalogue change restarts Gate 2 and requires another independent Gate 3.
