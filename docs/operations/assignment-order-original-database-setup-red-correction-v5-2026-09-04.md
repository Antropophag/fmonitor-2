# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v5

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red2`

GREEN-attempt gap base: `acc2931c0c6e414b8d0b9bb77a1881f10559ec65`

Outcome: **INTENDED RED — public migration seam absent**

## Observer correction

CHECK observation now queries `information_schema.CHECK_CONSTRAINTS` directly
by exact `CONSTRAINT_SCHEMA=DATABASE()` and exact `TABLE_NAME`. It no longer
joins reusable generated constraint names across tables. The focused
wrong-CHECK sensitivity lookup uses the same table-owned identity.

The normalizer maps only MariaDB's canonical `!(value REGEXP 'pattern')` form
to the semantically identical approved `value NOT REGEXP 'pattern'` form after
case/whitespace/backtick normalization. It does not alter the value or pattern.
Pre-RED sensitivity requires the approved control-character pattern to match
and a materially weakened pattern to remain different.

Task 2.2 is checked again because its reviewed setup scope is fully executable
after this test-only correction. No production, specification or other
OpenSpec artifact changed.

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
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
95e6228d9423f245ad870a2a26098fdab3faaafe404dc3282ec28e4798f5abf0  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
c7538c01ccda072604358af8e922c514ed41b3d6d575ed216a4ec94437950628  docs/operations/assignment-order-original-database-setup-green-attempt-test-gap-2026-09-04.md
1e6133adc30dbef87ee774e4d065e82b5c3d2c72adde32ed4b9bb20ba4faf696  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v3.md
```

The record omits its own circular hash. Fresh independent Gate 3 review is
required before setup implementation resumes.
