# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — CHECK oracle correction RED v7

Date: 2026-09-04 02:23:27 MSK (`2026-09-03T23:23:27Z`)
Gate: 2 correction after the third Gate 4 attempt
RED author: separately tasked agent `/root/original_upload_migration_red`
Base: `a500c12909d5aacfd30c45629117d03551dfd205`

## Corrected defect

The v6 runner contract held all 31 correct normalized CHECK tuples, but compared
them in a manually selected order against MariaDB's collation-ordered
`CHECK_CLAUSE` result. A conforming schema returned the revision
`char_length(pdf_sha256)=64` tuple before the `byte_size` tuple, while the
expected list had the reverse order.

`pmrSortedChecks()` now sorts both actual and expected tuples bytewise by the
complete signature `table NUL constraint-or-empty NUL clause`. Table identity,
the one normative named capability constraint and the full normalized clause
remain part of exact equality. A sensitivity probe proves reordered input
normalizes and a changed clause remains unequal.

No one of the 31 CHECK clauses, capability literal, table, index, FK, column,
fixture or expected migration outcome changed. Production was clean at the base
and remains untouched.

## Exact artifacts

```text
1ad72d2f5ebc96a86cfa2f96c9b29bf45ff00221eac93cf3bbbd72b59fdf75c9  tests/InstallationProcess/production_migration_runner_001_test.php
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

$ ... php tests/InstallationProcess/production_migration_runner_001_test.php
PHP Fatal error: Uncaught TestFailure: example A
Expected: schemaVersion 12, appliedVersions [1,2,3,4,5,6,7,8,9,10,11,12]
Actual:   schemaVersion 11, appliedVersions [1,2,3,4,5,6,7,8,9,10,11]

$ ... php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical additive
assignment-order-original schema migration v12 is missing.
```

The new ordering and changed-clause sensitivity assertions pass before runner
example A. The only remaining failures are the absent v12 CLI frontier and
missing public v12 migration class.

## Gate status

Gate 2 v7 is demonstrably RED for the intended missing behavior. Task 3.1 stays
open; fresh independent Gate 3 approval is required before implementation.
