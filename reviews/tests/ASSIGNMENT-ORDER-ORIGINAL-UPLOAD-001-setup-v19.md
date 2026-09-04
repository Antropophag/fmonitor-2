# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v19

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v19`
- Reviewed RED commit: `9d2a615257fb0de174626e2eb36ad66811260530`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Gate 1 authority: `3a1be3cef02e2826b0afc1e80cf0da5e531ba7d9`
- Prior Gate 3: `50212193987acc1636f7e0f89b3bde274db7c0b2` / v18
- Scope: replacement setup tests for tasks 2.2/2.3 only
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
support oracle, RED evidence or production code. This append-only review is the
only authored artifact.

## Independent assessment

The v19 correction closes both blocking findings from v18 without changing an
approved expectation.

- The generated seed/cleanup sensitivity matrix excludes only actual primary
  key columns. `fm2_assignment_orders.installation_case_id`, its nullable
  `previous_assignment_order_id`, and
  `fm2_process_tasks.installation_case_id` are now perturbed with existing,
  FK-valid identities. Each perturbation must produce the fixed fixture
  conflict and exact full-state zero-DML equality for both public operations.
- Every non-identity field restored by the generated matrix is first covered by
  a test-owned literal row oracle. In particular, the previously omitted user
  `phone`, role `description`, and user-role `origin` values now have explicit
  literal assertions. The complete `SELECT *` oracles cover orders and tasks;
  the remaining families' complementary literal queries cover every selected
  column. Thus the dynamic enumeration/restoration cannot conceal fixture
  drift: a wrong seeded value fails before the matrix begins.
- Partially absent cleanup removes all remaining exact owned rows in reverse
  dependency-compatible behavior, preserves the foreign case, and repeats as
  an exact no-op. Occupied drift remains a fixed zero-DML conflict.
- Each forced post-ALTER result—exact V4, conflicting capability, and closed
  reread—now compares the observer calls to the complete ordered transcript:
  seven exact CREATE callbacks, the pre-capability callback, then the one
  post-ALTER callback with exact phase/table arguments and no extras. The
  ordinary post-ALTER case already had the same assertion.
- Every CREATE fault retains the exact durable leading table prefix and its
  retry reports only the exact missing suffix plus capability publication.
  Pre-ALTER retains the complete schema and exact V4; ordinary post-ALTER
  proves exact V5. V4, conflict and unavailable post-ALTER outcomes are
  fail-closed and their later retries prove the allowed durable recovery.

All prior reviewed coverage remains: complete DDL properties and CHECK
sensitivity, exact V4/V5 capability classification and engineer-position
preservation, combined binary-sorted original/capability conflicts with full
zero-DDL snapshots, all fixture families, contention/serialization, bounded
cleanup, no credentials or original facts, stable projection digests, prefix
isolation, idempotency, and fixed redacted exceptions. Both tests exercise the
approved public migration/factory/fixture seams; test-side metadata and DML are
independent observations rather than production implementation coupling.

## Reproduced RED and isolation evidence

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
$ php -l tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: actor user drift was accepted by seedExampleA.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigrationVerificationFactory seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
exit 0

$ docker exec fmonitor2-test-test-db-1 mariadb ...
SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 't\\_aoou\\_%';
0
SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE DB LIKE 't\\_aoou\\_%';
0
```

Both failures occur after a live MariaDB preflight and isolate the missing
production behavior rather than setup. Independent post-run catalog inspection
proves no task-owned schema or database connection remains.

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
e7dc55d9cc02815631020ffbf8ccf103be2b2b90b049bc8e3eb72790922b2ea3  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
865513ce563cac157d64324c51f597024ffd1cc19456bce9ea5456d3e96c714c  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
a4dd8075880fe05c5a1281eadb0c0977e99dc77fc50e49a54e4b940c68ae54af  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v18.md
5f2de457937978c195f80a7b2fefdeff7a7e57d318b597d667054681d794c127  docs/operations/assignment-order-original-database-setup-red-v14-correction-v19-2026-09-05.md
75d1cc01f2e6d94504e9a266098de44ebcd5b2aa6d4ac743537cae75c519b016  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
7dcf210a033ed0c1a4723ab6b399eb22a2bfa33d8d921c53654c55824901fc5f  app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
```

This record omits its own circular hash. Fresh Gate 3 is **APPROVED**. Task 3.1
may resume with only the minimal production setup implementation required by
these exact reviewed tests; independent Gate 5 remains mandatory.
