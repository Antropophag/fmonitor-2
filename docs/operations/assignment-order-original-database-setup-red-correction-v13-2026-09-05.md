# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v13

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

GREEN-attempt gap base: `71acf6595d05f90b5859a5a7980d6eac03a56c97`

Outcome: **INTENDED RED — public migration seam absent**

The boolean canonicalizer no longer lower-cases the complete CHECK string. A
quote/escape-aware byte scanner folds only unquoted ASCII `A..Z`, after lexical
validation and before parsing. Quoted literal and regex bytes, including doubled
and backslash-escaped quotes, remain exact.

Pre-RED controls prove mixed-case unquoted keyword/identifier equivalence while
quoted status and regex case changes remain distinct. The existing real
same-count uppercase hash CHECK therefore remains a conflict-sensitive mutation
instead of collapsing to the approved lower-hex oracle.

All seven-table MariaDB round trips, malformed grammar controls, intended RED
and bounded cleanup remain. Task 2.2 is rechecked. No production, specification
or other OpenSpec artifact changed.

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
60cc60e9354353e3ade47bc7a2c38dac5b1c64e339cdc9d7df756e9305d3dda3  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
8266980f6721562ed770fc50957d4bbfa5d85936e68ebeec8fe6cabd3a7727ae  docs/operations/assignment-order-original-database-setup-green-attempt-quoted-case-gap-2026-09-05.md
37da4aa579d9c7e216bd6ea923f4b74b1084df643927765d97a68b12395c881e  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v11.md
37cf186ca9dc4707d471c76022b79cb353817e33c8f6e5a4e7e9102217b92503  docs/operations/assignment-order-original-database-setup-red-correction-v12-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
