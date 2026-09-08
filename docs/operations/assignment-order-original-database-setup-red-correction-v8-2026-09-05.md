# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v8

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

GREEN-attempt gap base: `0acafccd80c697c309f41c09b321024381391e39`

Outcome: **INTENDED RED — public migration seam absent**

The CHECK normalizer now recognizes only the two precedence-equivalent MariaDB
forms of the exact approved revision-lineage expression: bare `left OR right`
and `(left) OR (right)`. Both are canonicalized to the existing Contract oracle
`((left) OR (right))`. The full literal left/right operands are matched before
canonicalization; a changed comparison, field, event or boolean operator is not
rewritten.

Before the missing migration guard, real MariaDB tables round-trip the exact
approved revision CHECK and a same-shape near-wrong expression using
`revision_number >= 1` instead of `> 1`. The approved expression equals the
Contract oracle after MariaDB removes redundant parentheses; the changed
operator remains unequal.

Task 2.2 is rechecked. No production, spec, support or other OpenSpec artifact
changed.

## Reproduced evidence

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

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
8561b072a0d0c6e9659ca8c8765d0fe0d6be9e658018e0f2bd63c43b36dbc1b2  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
d7aecc4523d642d44e9139e321345cdeb2b436d8711e7e27a8f9a2a53489e900  docs/operations/assignment-order-original-database-setup-green-attempt-check-parentheses-gap-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
