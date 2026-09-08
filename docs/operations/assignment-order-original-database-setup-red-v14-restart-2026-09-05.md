# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — setup Gate 2 v14 restart

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved v14 base: `487bf37413368c0180569de9968b86bbc8a2254b`

Gate 5 restart: `c40c0101f46cbbee0187ea48f569999c3a0c49f1`

Outcome: **INTENDED RED — current fixture accepts drift; v14 migration verification seam absent**

The public fixture test now drives representative independent drift across every
owned row family: both users, both roles, role assignment, actor and engineer
capabilities, target and decoy cases, full order snapshots, both installers and
task. Seed and cleanup each require fixed conflict plus byte-identical zero-DML
state. It also proves partially occupied users are never filled and partially
missing cleanup never deletes remaining rows before conflict; exact/absent
repeat and full bounded cleanup remain.

The separate public migration verifier covers exact V4→V5 capability-last
upgrade and V5 repeat; upload-only, correct-only, unexpected superset/subset,
multiple candidates and unsafe name conflicts with exact binary affected table
and zero DDL. Existing engineer-position CHECK remains present and is not
selected as a candidate.

Through `AssignmentOrderOriginalSchemaMigrationVerificationFactory`, source
assertions inject after each of seven durable CREATEs, after full schema
revalidation before ALTER, and after ALTER before reread. They require fixed
unavailable shape, V4 while schema is partial/pre-publication, exact leading-
partial recovery, capability-last ordering, and post-ALTER fresh V5 resolution
plus safe retry.

Task 2.2 is checked. Tasks 2.3 and 3.1 remain open for fresh Gate 3 and corrected
GREEN. No production or specification artifact changed.

## Reproduced RED

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: actor user drift was accepted by seedExampleA.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigrationVerificationFactory seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php

$ independent information_schema SCHEMATA/PROCESSLIST query for t_aoou_%
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
$ php -l tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
$ git diff --check
PASS (no output)
```

## Exact hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
5069ae841105f2146923ac950941da7877bd0ea2ba7ba8ce4cc3f6f2b550514b  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
677c44c98a382a115783aa0114ad1a8706d6db8d201afc4107c4a865141fad2a  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
adb816d5819cbd00d8593b161b35e4c1e6920e582e9b3c246a78139bb748929e  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
is required before implementation resumes.
