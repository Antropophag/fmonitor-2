# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v4

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red2`

Prior Gate 3 v3: `c25a4cac82baaa40e7af624d72de9f76a7522e8f`,
verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — public migration seam absent**

## Causal contention correction

The seed/cleanup child now publishes exact positive MariaDB `CONNECTION_ID()`
with `READY <mode> <id>`. After bounded `ENTER/ENTERED`, the parent opens a
separate admin observer connection and bounded-polls `INNODB_LOCK_WAITS`,
`INNODB_TRX` and `PROCESSLIST` joined by that exact ID and the exact parent
blocking connection ID.

Before releasing its lock the verifier requires:

- a lock-wait row for child → parent, not merely absent child output;
- child transaction state `LOCK WAIT`;
- child isolation `SERIALIZABLE`;
- current child transaction SQL names the exact `fm2_pilot_users` seed or
  `fm2_process_tasks` cleanup identity table;
- no terminal result is readable.

Only then does the parent commit, require exact `OK <mode>` and reap the child.
The observer is closed in `finally`; transaction rollback, descriptor closure
and bounded kill/reap remain attempt-always. A child descheduled before the
public call cannot create an InnoDB wait row and therefore cannot pass.

No support, production, spec or OpenSpec bytes changed.

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
c3978e87c4849bfb9ac8f8d0ed3c705c64f681258e6518830e0d995a39982a9d  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
1e6133adc30dbef87ee774e4d065e82b5c3d2c72adde32ed4b9bb20ba4faf696  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v3.md
06457cce6ccd1edf2ccf7e0adb15bc491d8d57143ca30e2f0505d57f2766dcab  docs/operations/assignment-order-original-database-setup-red-correction-v3-2026-09-04.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required.
