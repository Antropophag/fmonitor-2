# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v18

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v18`
- Reviewed RED commit: `daa51b4390629b50da5f286e7b824eb07af48f27`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Gate 1 authority: `3a1be3cef02e2826b0afc1e80cf0da5e531ba7d9`
- Prior Gate 3: `2f802939dab3784f6b6ca8f8e358d6910d55d131` / v17
- Scope: replacement setup tests for tasks 2.2/2.3 only
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
support oracle, RED evidence, or production code. This append-only review is
the only authored artifact.

## Blocking findings

### G3-V18-1 — the claimed all-field fixture matrix still excludes normative non-identity fields

The new generated matrix is materially broader, but its `identityColumns`
exclusions classify three ordinary contract values as identities:

- `fm2_assignment_orders.installation_case_id`;
- `fm2_assignment_orders.previous_assignment_order_id`;
- `fm2_process_tasks.installation_case_id`.

Only order `id` and task `id` identify those owned rows in this fixture.
`installation_case_id` is a persisted relationship required by the literal
Example-A contract, and `previous_assignment_order_id=null` is explicitly
normative. An implementation that omits these values from byte-for-byte
comparison can therefore pass every new mutation. This leaves v17 finding 6
open. Add type-valid sensitivity for each field (with independently created FK
targets where necessary) for both seed and cleanup, retaining full-state
zero-DML assertions.

The matrix also takes each restore value from the row produced by the fixture
under test. The preceding literal assertions independently establish most
values, but not every selected `*` column (for example users `phone`, roles
`description` and user-role `origin`). To prove the v14 statement that the
listed values and only those values define repeat/conflict, the complete row
oracle used for enumeration/restoration must itself be test-owned and literal,
or every enumerated field must first be compared to such an oracle.

### G3-V18-2 — post-ALTER fault scenarios do not assert their complete observer transcripts

The ordinary post-ALTER case now checks the exact full trace, and the V4,
conflict and closed-connection callbacks indirectly prove that the terminal
observer invocation happened. The latter three scenarios never compare
`$observer->calls` with the required ordered trace, however. A verification
application may emit an extra/reordered phase before or after the intended
terminal phase while still arranging the asserted durable state and passing
these branches. V17 required exact phase/table transcripts, not only proof that
the callback ran. Assert the same full post-ALTER transcript independently in
the V4, conflict and reread-unavailable branches.

## Closure assessment of v17 findings

- G3-V17-1: closed. Partially absent cleanup removes every remaining exact
  owned row, preserves a foreign case, and repeats as a no-op.
- G3-V17-2: closed for all CREATE positions and pre-ALTER; still incomplete for
  three post-ALTER transcripts as G3-V18-2.
- G3-V17-3: closed. Exact V5 and engineer-position preservation are asserted;
  conflict snapshots contain complete `SHOW CREATE TABLE` definitions and rows.
- G3-V17-4: closed. The mixed two-original-plus-capability conflict requires the
  full binary-sorted union with a full zero-DDL snapshot.
- G3-V17-5: durable V5, forced V4, forced conflict and unavailable reread plus
  safe retries are exercised; transcript sensitivity remains incomplete as
  G3-V18-2.
- G3-V17-6: not closed, for G3-V18-1.

## Reproduced RED and isolation evidence

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: actor user drift was accepted by seedExampleA.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigrationVerificationFactory seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
exit 0

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
$ php -l tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php

$ independent information_schema SCHEMATA/PROCESSLIST query for t_aoou_%
0
0
```

Both REDs fail at intended missing behavior after live MariaDB setup, not at
bootstrap. Tests use the public migration/factory/fixture seams; metadata and
test-owned DML remain independent observations. No production/HTTP/runtime
path or `rapid-pilot/` domain owner is introduced, output is deterministic, and
the reproduced run leaves no task-owned database or connection.

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
f631e3c7f4278e44c890729245a6a9f3c5187bfd5dd12f1f726e22be856615ea  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
fbccb469b1364527a827e099fe91f327665cd8f54e75b81e53f0ee13f0a24fe4  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
b2a8f6dc68e1b2e33de3b3f569232e46334b80dde89fa943748034ac780df0a0  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v17.md
d42ff25b3dc96c95f96aabe9e4de023e3b9d0518fb94f28f71b21fd6956606a2  docs/operations/assignment-order-original-database-setup-red-v14-correction-v18-2026-09-05.md
75d1cc01f2e6d94504e9a266098de44ebcd5b2aa6d4ac743537cae75c519b016  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
7dcf210a033ed0c1a4723ab6b399eb22a2bfa33d8d921c53654c55824901fc5f  app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
```

This record omits its own circular hash. Gate 3 remains closed; production task
3.1 must not resume until a corrected RED receives fresh independent approval.
