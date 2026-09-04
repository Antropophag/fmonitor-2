# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v14

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

GREEN-attempt gap base: `37084442405a3da372b571ef89c0ca9caa98fce6`

Outcome: **INTENDED RED — public migration seam absent**

The contention child is now a separately executed PHP helper started through
bounded `proc_open`; it inherits no PHP mysqli objects or socket-owning
destructors. It opens exactly one own database connection and communicates only
through its own stdin/stdout/stderr descriptors.

The exact `READY <mode> <connection-id>`, `ENTER`, `ENTERED`, independent
InnoDB child→parent lock-wait observation and terminal `OK` protocol is
preserved. Parent cleanup closes all pipes/observer connections and performs
bounded TERM, then KILL if needed, followed by `proc_close` reaping. Worker
errors expose only fixed `FIXTURE_WORKER_FAILED`.

After both seed and cleanup workers terminate, the verifier requires the
original parent fixture and admin mysqli connections to answer `SELECT 1`,
making shared-socket ownership regressions observable.

Task 2.2 is rechecked. No production or specification artifact changed.

## Reproduced evidence

```text
$ php -l tests/Support/assignment_order_original_fixture_worker.php
No syntax errors detected in tests/Support/assignment_order_original_fixture_worker.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema query for SCHEMA_NAME LIKE 't_aoou_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK

$ git diff --check
PASS (no output)
```

## Exact hashes

```text
08d97ed4e88a4c813f2ea9d0c25b5f87f5f062613958a6e25189103d48a37534  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
4e33e35191cec91fd37a03f8c66004a0a5e5bbf1208f6939e7e8cb6ad8f3fca9  tests/Support/assignment_order_original_fixture_worker.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
60c9077b89f237ab679650873c38d5d12441c5789847aa45412138af43ea69fa  docs/operations/assignment-order-original-database-setup-green-attempt-fork-connection-gap-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
