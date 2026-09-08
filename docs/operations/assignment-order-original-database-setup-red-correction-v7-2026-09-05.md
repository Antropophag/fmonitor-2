# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v7

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

GREEN-attempt gap base: `708a395b02b1e83e9b28153c19cde7b5b3a67022`

Outcome: **INTENDED RED — public migration seam absent**

The column observer now canonicalizes raw `COLUMN_DEFAULT === 'NULL'` to PHP
`null` only when the same row has exact `IS_NULLABLE === 'YES'`. All non-null,
non-string-NULL and non-nullable defaults remain byte-observable.

Before the missing migration guard, a real MariaDB table proves that implicit
nullable NULL and explicit `DEFAULT NULL` both canonicalize to null, while a
nullable non-null default remains a non-empty distinct string. The probe stays
inside the already bounded task-owned database and is removed by the same
attempt-always database cleanup.

Task 2.2 is rechecked. No production, spec, support or other OpenSpec artifact
changed.

## Reproduced evidence

```text
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
3fa6ac18b0f78abfb2769a1eca577f5998166c2de6a9a36c36d2df7d4f248153  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
8562dbc725c0391d36622b11d8c27951e1605969a5b95cf2734c0a8e24c24693  docs/operations/assignment-order-original-database-setup-green-attempt-null-default-gap-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
