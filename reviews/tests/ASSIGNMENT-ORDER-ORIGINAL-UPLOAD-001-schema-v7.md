# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema migration v7

- Reviewer: separately tasked agent `/root/original_upload_migration_gate3_v7`
- Test author: separately tasked agent `/root/original_upload_migration_red`
- Reviewed commit: `4055834df061a326c7c3f5a2cb16e64102b75a83`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4, owner-approved hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec delta hash `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Public seams: `CanonicalMigrationApplication::run(...)`, `AssignmentOrderOriginalSchemaMigration::apply(...)`, and `bin/fmonitor2-migrate.php`
- Verdict: `APPROVED`

## Prior finding disposition

The append-only v6 `APPROVED` record remains intact. This rereview covers only
the subsequent CHECK-oracle correction. MariaDB returned the revision
`char_length(pdf_sha256)=64` CHECK before the `byte_size` CHECK under its
catalogue collation, while the independently specified contract listed those
two tuples in byte order. That incidental row order failed before the intended
missing-v12 frontier could be observed.

The corrected comparison sorts both actual and expected tuple lists with
`strcmp()` over the complete byte signature `table NUL constraint-or-empty NUL
clause`. The NUL separators make field boundaries unambiguous; table identity,
the sole normative capability constraint name, and the complete normalized
clause remain in each exact array element. Exact post-sort array equality still
rejects missing, additional, duplicated, reassigned, renamed or changed CHECK
tuples. Equal complete signatures are interchangeable copies, so `usort()`'s
unspecified order for comparator equality cannot affect the multiset result.

The pre-database sensitivity assertion executes the same test-owned helper. It
proves reversed input is normalized to the canonical order and a changed clause
remains unequal. The helper body independently shows that table and constraint
are also sorting-key components; the final exact tuple comparison retains all
three fields rather than reducing them to a clause-only set.

## Retained combined coverage

The correction changes no expected table, column, index, FK, CHECK, capability
literal, schema version or migration result. The dedicated seven-table schema
verifier and literal catalogue retain their v6 hashes. The runner still checks
all 38 tables, 104 indexes, 34 FK mappings and 31 CHECK tuples, including the
complete v12 original-evidence family and all predecessor facts.

Clean, repeat, populated, hostile-capability, malformed-root,
hostile-revision, exact historical-v4 compatibility, six historical-v4
near-matches, v3 short-circuit/recovery, configuration, unavailable-database,
charset, restricted-DDL and secret-redaction coverage is unchanged. Expected
catalogue values remain test-side literals and neither production manifests nor
production migration SQL are used to derive them.

## Independent reproduction

At `2026-09-04T02:25:44+03:00` on exact reviewed commit
`4055834df061a326c7c3f5a2cb16e64102b75a83`, the repository test MariaDB
container `fmonitor2-test-test-db-1` reported `healthy` and accepted the
administrative connection:

```text
$ sha256sum tests/InstallationProcess/production_migration_runner_001_test.php \
  tests/Support/ProductionMigrationRunnerCatalogContract.php \
  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
1ad72d2f5ebc96a86cfa2f96c9b29bf45ff00221eac93cf3bbbd72b59fdf75c9  tests/InstallationProcess/production_migration_runner_001_test.php
29fd4330fa64623789c4216c3f16a1c00fc58d224a882fa61a73ba7cc7daa9e6  tests/Support/ProductionMigrationRunnerCatalogContract.php
5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

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

The CHECK ordering and changed-clause sensitivity assertions pass before runner
example A. The runner then successfully applies canonical v1-v11 and fails only
on the absent v12 registration; the companion verifier independently fails on
the absent public v12 migration owner. These are the intended REDs, not a
catalogue-order or setup failure. No production or test artifact was edited by
this review.

## Required changes

None.

## Gate decision

Fresh Gate 3 is `APPROVED`. Gate 4 may implement only the minimal additive v12
owner and canonical runner registration against exact commit
`4055834df061a326c7c3f5a2cb16e64102b75a83`. Any subsequent test or literal
catalogue change restarts Gate 2 and requires another independent Gate 3.
