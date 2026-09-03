# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — canonical runner v12 RED v5

Date: 2026-09-04 02:08:44 MSK (`2026-09-03T23:08:44Z`)
Gate: 2 correction after `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v4.md`
RED author: separately tasked agent `/root/original_upload_migration_red`
Base/review commit: `9cdc1e349d2d74e773d1ab0311d04959f11b153c`

## Corrected public-runner contract

The production migration runner verifier is now additive through canonical v12.
It expects:

- clean apply `schemaVersion=12` with versions `1..12`;
- exact repeat with no applied versions;
- recovery from the intentionally removed v3 table with versions `3..12`;
- empty-prefix/empty-password clean apply through v12;
- exactly 38 canonical tables rather than the v11 count of 31;
- the exact seven new original-evidence tables, their columns, indexes, FKs and
  CHECK constraints, and the expanded seven-literal process capability CHECK.

Every existing configuration, charset, v3/v4 short-circuit, adversarial
capability/engineer expression, DDL-failure, sentinel and no-mutation assertion
remains. Exact historical v4 capability states now have the one permitted
successor delta: v12 changes the capability CHECK and preserves their rows;
near-matches still stop at v3 without invoking v4 and remain byte-identical.

The companion schema verifier is unchanged from v4. Together the two tests
require both the exact v12 storage owner and its registration in the real
deployment CLI; neither a class-only nor runner-only implementation can pass.

## Exact corrected artifacts

```text
5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
1426e760b45457ef96d16e6a758085baac9d1de1f7c22e588905ad99eef5a7d2  tests/InstallationProcess/production_migration_runner_001_test.php
29fd4330fa64623789c4216c3f16a1c00fc58d224a882fa61a73ba7cc7daa9e6  tests/Support/ProductionMigrationRunnerCatalogContract.php
```

## Reproduced combined RED

```text
$ php -l tests/InstallationProcess/production_migration_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/production_migration_runner_001_test.php

$ php -l tests/Support/ProductionMigrationRunnerCatalogContract.php
No syntax errors detected in tests/Support/ProductionMigrationRunnerCatalogContract.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

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

Both failures are the same absent production behavior viewed at separate public
seams: the CLI still ends at v11 and the public v12 migration class does not
exist. MariaDB setup, v1-v11 migrations, approved hashes, PHP syntax and strict
OpenSpec validation all complete first. No production file was changed.

## Gate status

The complete successor runner/schema batch is demonstrably **RED** for missing
v12 only. Task 3.1 remains open. Fresh independent Gate 3 approval is required
before implementation.
