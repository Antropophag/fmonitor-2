# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v15

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 3 v14 base: `50b4a9bc48ecf141914924e9c124faba05c0dd8c`,
verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — public migration seam absent**

The exec helper now has two verifier-only controls before either public fixture
seam. `fail_before_seam` returns only fixed `FIXTURE_WORKER_FAILED` on stderr
and exit `70`. `hang_before_seam` installs TERM-ignore before publishing
`ENTERED` and then remains live until killed.

Both controls execute before the missing production-class guard. The parent
requires bounded READY/ENTER/ENTERED, exact failure diagnostic/exit, and for the
hang path performs TERM, bounded live poll, KILL, bounded stopped poll and
`proc_close` reaping. Generic `finally` independently closes every descriptor
and repeats bounded termination/reaping if any assertion fails.

After each failure cleanup, both already-open parent fixture and admin mysqli
connections must answer `SELECT 1`. The final observer requires neither a
bounded database nor a process connection referencing it to remain.

Task 2.2 remains checked. No production, specification or OpenSpec bytes
changed.

## Reproduced evidence

```text
$ php -l tests/Support/assignment_order_original_fixture_worker.php
No syntax errors detected in tests/Support/assignment_order_original_fixture_worker.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema SCHEMATA/PROCESSLIST query for t_aoou_%
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS

$ git diff --check
PASS (no output)
```

## Exact hashes

```text
a7666501a304264cfe6947191fc0f3039921bd2042b3f20ae768f28e2632e479  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
3480ea78ee679fd7ba9cd667bebaf5ddb7a71925eac318b42dbb22984753cd37  tests/Support/assignment_order_original_fixture_worker.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
7850d6569598c6073d4047ba3f178e4b44cd6a172dee726cd6c212e0d3eddb3b  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v14.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
60c9077b89f237ab679650873c38d5d12441c5789847aa45412138af43ea69fa  docs/operations/assignment-order-original-database-setup-green-attempt-fork-connection-gap-2026-09-05.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
