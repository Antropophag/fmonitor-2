# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v9

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

GREEN-attempt gap base: `dad4c425637c0100322ca913a2f88be68fea4e07`

Outcome: **INTENDED RED — public migration seam absent**

The independent key manifest now includes every unavoidable InnoDB FK support
index not already covered by a leading PK/unique/business index:

- requests: `root_original_id` and `current_revision_id`;
- events: `revision_id`.

Before the missing migration guard, the verifier walks the FK and key oracles
for all seven tables and requires every FK local column to lead an expected
index. Real MariaDB probes then prove the normalized exact automatic support
index and preserve sensitivity to missing, extra and wrong-column alternatives.
All probes live only in the bounded task-owned database removed in `finally`.

Task 2.2 is rechecked. No production, specification or other OpenSpec artifact
changed.

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

$ independent information_schema query for SCHEMA_NAME LIKE 't_aoou_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK

$ git diff --check
PASS (no output)
```

## Exact hashes

```text
7b87a611fe771fb5a602be4cd22587bcc517aaeacbe4f613fd3cd91f76eadb06  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
3d3a8ba8b2b8dc0724f42c05495b5deb5690e0dc2e3c3d4c47c564dcefab5a6c  docs/operations/assignment-order-original-database-setup-green-attempt-fk-index-gap-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
