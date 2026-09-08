# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — setup Gate 2 v14 correction v19

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 3 v18: `50212193987acc1636f7e0f89b3bde274db7c0b2`, verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — current fixture accepts actor drift; v14 verification factory absent**

Corrections:

- exhaustive generated field matrix now treats only row primary-key columns as
  identities; order/task `installation_case_id` and order
  `previous_assignment_order_id` receive type/FK-valid seed and cleanup drift;
- every enumerated value is first compared to a literal oracle, including the
  formerly omitted user phone, role description and role-assignment origin;
- partially absent cleanup follows v14: removes remaining exact owned rows,
  preserves foreign row and repeats as a no-op;
- forced post-ALTER V4, conflict and reread-unavailable branches each require
  the exact complete seven-CREATE, pre-ALTER and post-ALTER observer transcript
  with phase/table arguments and no extra callback;
- CREATE faults require exact durable leading prefix and exact retry suffix;
  pre-ALTER requires full schema/V4, and ordinary post-ALTER requires exact V5.

Task 2.2 remains checked; 2.3 and 3.1 remain open. No production or specification
artifact changed.

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
$ git diff --check
PASS (no output)
```

## Exact hashes

```text
e7dc55d9cc02815631020ffbf8ccf103be2b2b90b049bc8e3eb72790922b2ea3  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
865513ce563cac157d64324c51f597024ffd1cc19456bce9ea5456d3e96c714c  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
a4dd8075880fe05c5a1281eadb0c0977e99dc77fc50e49a54e4b940c68ae54af  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v18.md
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

Fresh independent Gate 3 is required.
