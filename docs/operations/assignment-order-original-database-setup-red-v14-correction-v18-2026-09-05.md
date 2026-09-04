# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — setup Gate 2 v14 correction v18

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 3 v17: `2f802939dab3784f6b6ca8f8e358d6910d55d131`,
verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — current fixture accepts drift; v14 verification factory absent**

Corrections retain full schema tests and add:

- v14-compatible partially absent cleanup: every remaining exact row is
  deleted, absent row stays absent, foreign case survives, repeat is no-op;
- generated field sensitivity over every non-identity column of both users,
  both roles/assignments, all three capabilities, both cases, full order, both
  installer rows and task; seed and cleanup require conflict plus full-state
  zero DML for each type-valid mutation;
- full structural+row snapshots for capability conflicts and combined binary
  union of two original conflicts plus capability conflict;
- exact V5 candidate classifier and independent engineer-position CHECK
  preservation on upgrade/repeat/post-ALTER resolution;
- exact observer phase/table traces, durable leading prefix after every CREATE
  fault, and exact retry suffix plus capability publication;
- pre-ALTER full seven-table/V4 proof and exact trace;
- post-ALTER exact V5 success, forced V4 unavailable then safe publication,
  forced conflict unavailable/no-further-DDL retry, and closed-connection reread
  unavailable followed by fresh-connection safe V5 retry.

Task 2.2 remains checked. Tasks 2.3 and 3.1 remain open. No production or
specification artifact changed.

## Reproduced RED

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: actor user drift was accepted by seedExampleA.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigrationVerificationFactory seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php

$ independent SCHEMATA/PROCESSLIST query
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
$ php -l tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
$ git diff --check
PASS (no output)
```

## Exact hashes

```text
f631e3c7f4278e44c890729245a6a9f3c5187bfd5dd12f1f726e22be856615ea  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
fbccb469b1364527a827e099fe91f327665cd8f54e75b81e53f0ee13f0a24fe4  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
b2a8f6dc68e1b2e33de3b3f569232e46334b80dde89fa943748034ac780df0a0  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v17.md
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

Fresh independent Gate 3 is required.
