# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v11

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 3 v10 base: `798dfda2618d62d814f4ca4b1a74d3660785c36b`,
verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — public migration seam absent**

A single total lexical validator now runs before every boolean parse path. It
handles doubled-quote and backslash quote escapes, rejects a dangling escape,
never permits parenthesis depth below zero, requires final depth zero and a
terminated quote, and rejects semicolons before AST construction.

Executable pre-RED controls require the fixed
`CHECK_NORMALIZATION_FAILURE: invalid lexical structure.` for unmatched open,
unmatched close, unterminated quote and semicolon. Valid doubled-quote and
backslash-quote literals are preserved byte-for-byte. XOR remains rejected by
the bounded grammar as the separate fixed unsupported-atom outcome.

All seven-table MariaDB CHECK round trips, mutation sensitivity, setup RED and
bounded database cleanup remain unchanged. Task 2.2 remains checked after the
successful run. No support, production, specification or OpenSpec bytes were
edited.

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
2a7f8ca5870995df3f3caa7cd3db281f0f9570e1e4503f5a7cc826b1832f0433  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
fddd780785ae250758754c0c9fc4da9ab15c108fe33d38a9316cc1f6c60e9bcc  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v10.md
5bd32471ea529fcd05cc5c6c1113681d0741217d7cba8513f47b1c113fbae4ec  docs/operations/assignment-order-original-database-setup-red-correction-v10-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
