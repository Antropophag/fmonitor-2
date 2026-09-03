# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — FK oracle correction RED v6

Date: 2026-09-04 02:17:15 MSK (`2026-09-03T23:17:15Z`)
Gate: 2 correction after the second Gate 4 attempt
RED author: separately tasked agent `/root/original_upload_migration_red`
Base: `3626c3f5e2577c65ad6376da950eebda697c9060`

## Corrected defect

The v5 runner contract contained all 34 correct FK mapping signatures but relied
on a manually chosen list order. The observation query ordered table names using
the database collation, under which `fm2_assignment_orders` sorted before the
new `fm2_assignment_order_original_*` names. The expected list used byte order.
A conforming v12 therefore failed on ordering alone.

The corrected oracle now:

- orders catalog rows by binary table name, binary constraint name and ordinal
  position, preserving the column sequence of each composite FK;
- converts each observed column mapping to its canonical signature;
- sorts both actual and independently specified signatures with `SORT_STRING`
  before exact equality.

A pre-database sensitivity check proves reversed input normalizes to the same
set while a changed referenced-column mapping remains different. No FK mapping,
delete rule, table, schema, capability or other expected behavior changed.
Production was clean at the approved review commit before this correction and
remains untouched.

## Exact artifacts

```text
f8f9509007d4ffae9372fd65573f372f8c9560e7ade2c873ccd96798d996c1dd  tests/InstallationProcess/production_migration_runner_001_test.php
29fd4330fa64623789c4216c3f16a1c00fc58d224a882fa61a73ba7cc7daa9e6  tests/Support/ProductionMigrationRunnerCatalogContract.php
5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
```

## Reproduced intended RED

```text
$ php -l tests/InstallationProcess/production_migration_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/production_migration_runner_001_test.php

$ git diff --check
PASS (no output)

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
```

Both corrected-oracle sensitivity assertions execute before runner example A.
The only remaining failures are the absent v12 CLI frontier and missing public
v12 migration class.

## Gate status

Gate 2 v6 is demonstrably RED for the intended missing behavior. Task 3.1 stays
open; fresh independent Gate 3 approval is required before implementation.
