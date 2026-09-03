# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema migration v5

- Reviewer: separately tasked agent `/root/original_upload_migration_gate3_v5`
- Test author: separately tasked agent `/root/original_upload_migration_red`
- Reviewed commit: `355916bdfc77abf207e1549ff27d47c7ad7e9981`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4, owner-approved hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec delta hash `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Public seams: `CanonicalMigrationApplication::run(...)`, `AssignmentOrderOriginalSchemaMigration::apply(...)`, and `bin/fmonitor2-migrate.php`
- Verdict: `APPROVED`

## Prior finding disposition

The append-only v4 `CHANGES_REQUESTED` record remains intact. Its only blocking
finding is closed by the reviewed successor RED: the production-runner verifier
now requires canonical version 12 everywhere that the public runner advances a
healthy catalogue. Clean apply expects versions `1..12`, repeat expects no
applied versions, v3 recovery expects `3..12`, and the empty-prefix/password
deployment case expects `1..12`. The companion schema verifier remains at its
previously reviewed hash and still requires the public v12 owner directly.

The historical-v4 compatibility matrix remains adversarial rather than being
relaxed. Its two exact historical expressions may advance by v12 only, preserve
all capability rows, and then satisfy the complete v12 catalogue. The six
near-matches still fail at schema version 3, do not invoke the instrumented v4
seam, and retain the complete pre-run schema and sentinel row. The original
malformed-v3 fixture still proves the same short circuit, exact no-mutation, and
successful recovery after removal of the hostile table. Configuration,
database-unavailable, charset-failure, restricted-DDL and secret-redaction
assertions are unchanged.

## Independent catalogue arithmetic and sensitivity

The literal runner catalogue grows coherently from 31 to 38 tables. The seven
added tables are exactly roots, revisions, terminal requests, accepted
fingerprints, accepted events, rejected/conflict attempt audits, and bounded
maintenance results. Independent counts show the predecessor/new totals as:

```text
tables:   31 + 7  = 38
indexes:  78 + 26 = 104
FKs:      17 + 17 = 34
CHECKs:   15 + 16 = 31
```

The added 26 indexes, 17 foreign-key column mappings and 16 CHECKs agree with
the unchanged seven-table schema manifest. The capability CHECK is replaced,
not duplicated, and contains exactly the four historical literals plus
`assignment_order.original.upload`, `assignment_order.original.correct`, and
`assignment_order.original.storage.reconcile`. Thus the runner oracle covers
the same ordered columns/types/nullability/extras, identities, one-current-leaf
constraint, lineage, delete rules, byte/hash bounds, terminal result shapes and
maintenance arithmetic as the dedicated schema verifier. An implementation
that only registers v12, only creates tables, weakens a constraint, omits an
index/FK, mutates historical grants, or accepts a near-match cannot satisfy the
combined batch.

Expected values remain independent of production SQL/classes. The catalogue is
a literal test-side contract transcribed from the approved persistence model;
the tests do not import a production manifest or expected digest. Random
database names and prefixes, bounded owned cleanup, exact snapshots and
sentinels preserve isolation and make repeat/conflict mutation observable.

## Independent reproduction

At `2026-09-04T02:12:29+03:00` on exact reviewed commit
`355916bdfc77abf207e1549ff27d47c7ad7e9981`, against the healthy repository
test MariaDB contour:

```text
$ sha256sum tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php \
  tests/InstallationProcess/production_migration_runner_001_test.php \
  tests/Support/ProductionMigrationRunnerCatalogContract.php
5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
1426e760b45457ef96d16e6a758085baac9d1de1f7c22e588905ad99eef5a7d2  tests/InstallationProcess/production_migration_runner_001_test.php
29fd4330fa64623789c4216c3f16a1c00fc58d224a882fa61a73ba7cc7daa9e6  tests/Support/ProductionMigrationRunnerCatalogContract.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
$ php -l tests/InstallationProcess/production_migration_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/production_migration_runner_001_test.php
$ php -l tests/Support/ProductionMigrationRunnerCatalogContract.php
No syntax errors detected in tests/Support/ProductionMigrationRunnerCatalogContract.php

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

The running `fmonitor2-test-test-db-1` container reported healthy before both DB
runs. Both failures occur after successful connection and are the same missing
production behavior at distinct public seams: the runner stops at v11 and the
public v12 migration owner is absent. No production or test artifact was edited
by this review.

## Gate decision

Fresh Gate 3 is `APPROVED`. Gate 4 may implement the minimal additive v12 owner
and canonical runner registration against this exact combined RED batch. Any
change to either test or the literal catalogue restarts Gate 2 and requires a
new independent Gate 3.
