# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — schema-v2 RED evidence

- Date: `2026-09-05`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Approved exact HEAD before RED authorship: `54e7cb2cbf6dc1ff2967b3161220a4e840ebe4db`
- RED author: separately tasked agent `/root/assignment_shared_content_gate1`,
  switched from its earlier Gate 1 role and therefore ineligible to review these tests
- Gate: task 2.5 RED authored; fresh independent Gate 3 remains required

## Covered public behavior

The real-MariaDB verifier calls only the approved public migration seam and
independently checks:

- clean V4 deployment returns `APPLIED`, schema version 2, all seven logical
  tables in manifest order and capability publication last;
- the revisions manifest has exactly one physical non-unique sole-column index
  named `idx_aoou_revision_content`, and exact v2 repeat is
  `UNCHANGED` with no state change;
- roots plus an exact v1 revisions table upgrades at the revisions manifest
  position, creates the missing suffix, then publishes capability last;
- full populated exact v1 with an arbitrary safe unique-index name upgrades
  only revisions and preserves the complete existing row byte-for-byte;
- after upgrade, two immutable revisions can reference the same exact
  `private_content_identity`;
- failure at `AFTER_SCHEMA_V2_REVISION_INDEX_ALTER` exposes the fixed unavailable
  exception, leaves only exact v1 or exact v2, and retry performs at most one
  required upgrade without duplicate indexes;
- wrong v2 physical name, unsafe v1 name, multiple content indexes,
  wrong-column index and mixed table drift return exact conflict names and
  preserve a full schema-plus-rows snapshot, proving zero DDL/DML;
- the pre-existing broad setup verifier now uses schema version 2 and checks the
  exact named index in addition to all established columns, keys, checks, FKs,
  clean/repeat/partial/conflict and fixture behavior.

Historical v1 setup support remains at its existing path/class name because it
is imported by the command/race verifiers, but its active manifest constant is
now version 2. The test constructs historical v1 predecessors explicitly by
changing only the one approved index relationship; it does not infer the v1
oracle from future production implementation.

## Demonstrated intended RED

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
Fatal error: Uncaught TestFailure: Clean V4 applies schema v2 in manifest order with capability last.
Expected: status APPLIED, schemaVersion 2, seven manifest tables, capability last
Actual:   status APPLIED, schemaVersion 1, seven manifest tables, capability last
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
exit 0

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: Migration reports exact version 2.
Expected: 2
Actual: 1
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0
```

Both failures are caused by the missing v2 migration behavior. MariaDB was
reachable, the public migration applied the full existing v1 schema, and status
plus affected ordering already matched. Neither failure is setup, connectivity,
fixture, syntax or cleanup failure.

## Isolation and cleanup

```text
$ php -l tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ php -l tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php

$ independent mysqli information_schema query after both RED runs
t_aoou_v2_% / t_aoou_% schemas: 0
t_aoou_v2_% / t_aoou_% connections: 0

$ git diff --check
PASS (no output)
```

## Exact hashes before this evidence/tasks annotation

```text
e65db5d13acaecd37f266eaf68e5569f53963fc687679262978de590e2c94992  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
6a9953989ca2707d695c66a2c8c652e67985a33ed7aa8aba137478691796414a  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
8c6feadaa89fc50003b0ec58be5613001c7ba034548498f67dad9e6f5c59a825  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
fae0e934c8fd75d5046928a4314c21ffa0afc1585c3767f74fc92855138dfa25  docs/operations/assignment-order-original-shared-content-schema-technical-approval-2026-09-05.md
```

The task file hash is intentionally omitted because this evidence is referenced
from that file. This record omits its own circular hash.
