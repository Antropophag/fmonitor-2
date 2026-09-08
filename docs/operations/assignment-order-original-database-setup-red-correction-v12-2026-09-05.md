# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v12

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 3 v11 base: `a31b0a11247563c691c5a72fd4b027a746ead9fd`,
verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — public migration seam absent**

The pre-RED lexical controls now include an open quoted SQL literal whose final
byte is exactly one backslash. It requires fixed
`CHECK_NORMALIZATION_FAILURE: invalid lexical structure.`, directly exercising
the validator's dangling-escape branch. Existing unmatched-open, unmatched-
close, unterminated-quote, semicolon, XOR and valid doubled/backslash quote
controls remain unchanged.

All seven-table CHECK round trips, mutation sensitivity and bounded MariaDB
cleanup remain. Task 2.2 remains checked after the successful run. No support,
production, specification or OpenSpec bytes changed.

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
98c2ad61488f539de62ef1de60992568a0203edcec18eec82459f657cd5859b7  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
37da4aa579d9c7e216bd6ea923f4b74b1084df643927765d97a68b12395c881e  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v11.md
d39fb56e1f5a7878e20d54ce6126fefc1dbd4e24df6d1f7bd933f380f1a91856  docs/operations/assignment-order-original-database-setup-red-correction-v11-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
