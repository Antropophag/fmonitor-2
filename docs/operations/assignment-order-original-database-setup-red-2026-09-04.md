# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — version-1 database setup RED

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved Gate 1 base: `b46c5e7c79ee2994bfc6e157096bfcdb3eb3807e`

Outcome: **INTENDED RED — public migration seam absent**

## Scope

This record closes only redefined OpenSpec task 2.2: the independently staged
MariaDB setup Gate 2. It does not claim or test the deferred command, PDF,
worker, maintenance or fault matrix now assigned to task 4.1.

The verifier owns five random `t_aoou_<axis>_<12hex>` databases and fixed prefix
`aoou_`. Before its missing-class assertion it successfully connects to real
MariaDB, creates every bounded database, recomputes all six approved fixture
projection hashes from adjacent literal JSON and proves cleanup leaves no
matching database.

Code after the missing public seam covers:

- clean `APPLIED`, exact schema version `1`, exact seven-table affected order;
- exact manifest tables, ordered columns/types/nullability, PK/unique/index
  column order, complete FK target/action set, CHECK count, InnoDB and database
  default `utf8mb4` collation;
- exact repeat `UNCHANGED`, empty affected list and byte-identical schema;
- independently created compatible leading roots table, preservation of that
  table and creation of only the six trailing manifest members;
- populated exact root/revision preservation through repeat migration;
- incompatible owned roots conflict with exact logical name and zero DDL;
- deterministic Example-A seed values for fictional users, roles, exact actor
  grants, case, order, installer snapshots and task;
- all six canonical projection digest literals;
- seed repeat no-op, different occupied identity conflict with fixed exception
  and zero DML, and zero original/request/event/audit/maintenance facts;
- cleanup repeat, exact-identity absence and full prerequisite database
  restoration; finally drops only prevalidated task-owned databases.

Expected values are literal test support and do not load migration definitions
or production manifests. The only test-owned DDL is the owner-approved leading
roots manifest member required to make partial recovery observable.

## Reproduced evidence

```text
$ php -l tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema leak query for SCHEMA_NAME LIKE 't_aoou_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK

$ execute AssignmentOrderOriginalDatabaseSetupV1::rootsDdl('aoou_') in a fresh owned database, then drop the database in finally
ROOTS_DDL_OK

$ git diff --check
PASS (no output)
```

The RED is not a setup failure: MariaDB connection, database creation, literal
hash checks and attempt-always cleanup all complete. The first unavailable
approved production symbol is exactly
`FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigration`.

## Exact hashes

```text
62b42d5b957dd628d09c13d6864152401c54998a5607b1c1835cc8a93ab9c3dd  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0208895b4a605381ece9cc0bba4cee49ac79c1b17ffa1939f62601c05144051f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a061abc535528436d3caaadd0f34e7618793fd3ae76f5e7ccb16fd40fbbf43b5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
8e533ff36104d6b1b01e4deaf0a695a938c5ff90e78d4c0d861ac736e1edb06d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
72d4252762912d5c26dfb35ec0008f4d74a2e9e477ec89a601cbf8ee077ef400  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
35afcdd180441a2bf3631c6715dac225135df5bdc25011f31abfe319ef5c69ee  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
```

The evidence record path is metadata because a self-hash is circular. No
production, executable spec, approved OpenSpec proposal/design/delta spec,
prior test/review or command-matrix artifact was edited.
