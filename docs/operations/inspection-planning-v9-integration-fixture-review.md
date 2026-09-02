# Inspection planning v9 — integration fixture review

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED**

## Scope

Independent review of the current v9 fixture-alignment edits against the
owner-approved `INSPECTION-PLANNING-SCHEMA-001` contract and its approved Gate 3
artifacts. The reviewer did not author the reviewed edits.

Reviewed scope:

- canonical production-runner catalogue gains the exact two v9 planning table
  column families, indexes and semantic JSON CHECK tuple;
- runner expectations advance from terminal v8 to terminal v9 while preserving
  the existing conflict, prefix, redaction, recovery, repeat fingerprint and
  sentinel-preservation assertions;
- case-import migration precondition advances from exact v8 to exact v9;
- in `pilot_http_auth_001_test.php`, only the migration fixture expectation
  advancing from v8 to v9 is approved by this record.

Explicit exclusion: process-prefix wiring, canonical local users/roles/grants,
authorization outcomes, environment-read expectations and all other local RBAC
edits in `pilot_http_auth_001_test.php` are subsequent RBAC work and are not
approved or rejected by this v9 fixture verdict.

## Reviewed hashes

```text
c51e4e1f51577254dfbe79732e1d3249cfbee4471aba49e919beaf4f613aaf90  tests/Support/ProductionMigrationRunnerCatalogContract.php
c021014135ae56f6994bd590608f011a645e82cd6311ebad77951a824217dbbc  tests/InstallationProcess/production_migration_runner_001_test.php
5eb2e5d66e177dd9e630977d0f25bf42a35519a0f64ac6ddb3fb7a280bb53930  tests/InstallationProcess/pilot_http_auth_001_test.php
9980b65f1fd15e0d5b02c4a7e056e27291107d027f411aafe9473fbea0fbea42  tests/InstallationProcess/pilot_case_import_001_test.php
```

## Review result

The catalogue additions match the v9 schedule/event table names, ordered
column types, nullability and generated/extra state represented by the shared
runner contract. They add the exact primary/unique/secondary index column
orders and the sole normalized `json_valid(payload_json)` CHECK tuple. Catalogue
cardinality and assertion labels consistently advance to 28 tables and 12
CHECK tuples.

No existing integration assertion was removed or broadened. Clean runner,
repeat fingerprint, prior-version conflict stop, recovery, unrelated sentinel
preservation, configuration/redaction, empty-prefix and failure behavior retain
their prior sensitivity while their successful terminal catalogue is v9.
Dedicated approved `INSPECTION-PLANNING-SCHEMA-001` tests remain the owner of
the deeper v9-only metadata/default/collation, partial-state, mutation and
25/26-byte boundary matrix; this integration alignment does not replace or
weaken those tests.

The case-import change only makes its canonical schema prerequisite truthful
for the landed v9 runner. It does not alter import inputs, expected facts,
idempotency, race handling, failure classification or preservation assertions.

## Independent verification

The default password embedded in these older fixtures does not match the
active local test database, so the successful executions explicitly supplied
the established test-harness administrator password through
`FMONITOR_TEST_DB_ADMIN_PASSWORD`. The initial runner attempt without that
override failed before test behavior with MariaDB access denied and was
classified as environment setup, not a regression.

```text
php -l tests/Support/ProductionMigrationRunnerCatalogContract.php
No syntax errors detected in tests/Support/ProductionMigrationRunnerCatalogContract.php

php -l tests/InstallationProcess/production_migration_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/production_migration_runner_001_test.php

php -l tests/InstallationProcess/pilot_http_auth_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_http_auth_001_test.php

php -l tests/InstallationProcess/pilot_case_import_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_case_import_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_case_import_001_test.php
PASS: PILOT-CASE-IMPORT-001 CLI contract

git diff --check -- \
  tests/Support/ProductionMigrationRunnerCatalogContract.php \
  tests/InstallationProcess/production_migration_runner_001_test.php \
  tests/InstallationProcess/pilot_http_auth_001_test.php \
  tests/InstallationProcess/pilot_case_import_001_test.php
exit 0, empty output
```

The reviewed v9 fixture alignment is approved at the exact hashes above. Any
subsequent change to those aligned expectations requires a new review; this
record grants no verdict on the explicitly excluded RBAC edits.
