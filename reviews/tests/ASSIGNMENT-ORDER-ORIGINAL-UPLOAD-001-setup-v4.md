# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v4

- Date: `2026-09-04`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v4`
- Reviewed commit: `88bf415c1f886440378502a83df375871137d12e`
- Prior Gate 3 v3 record: `c25a4cac82baaa40e7af624d72de9f76a7522e8f`, verdict `CHANGES_REQUESTED`
- Scope: OpenSpec database-setup tasks 2.2/2.3 only
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, test, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Findings

### Prior blocking finding is closed

`G3-v3-1` is closed. The parent records its exact positive MariaDB connection
ID after acquiring the exact seed/cleanup identity lock. The child opens a
separate connection and publishes its exact positive connection ID before the
bounded enter barrier. After `ENTERED`, an independent observer bounded-polls a
join of `INNODB_LOCK_WAITS`, both `INNODB_TRX` identities and `PROCESSLIST`
restricted to that exact child → parent connection pair.

Before releasing the parent transaction, the test requires all of:

- a real InnoDB wait row for the exact child and blocker connection IDs;
- requesting transaction state `LOCK WAIT` and isolation `SERIALIZABLE`;
- current transaction SQL containing `aoou_fm2_pilot_users` for seed or
  `aoou_fm2_process_tasks` for cleanup;
- no readable `OK`/`ERR` terminal frame.

A child descheduled after `ENTERED` but before entering the public fixture call
cannot produce the required server-side lock-wait row. A fixture that omits the
required identity lock likewise cannot satisfy the row. Only after those
causal observations does the parent commit, require exact `OK <mode>`, verify
the child exit status and reap it.

The observer connection is closed attempt-always. Parent rollback, both socket
closures, bounded child termination and reaping remain in `finally`; a failure
before normal release therefore cannot leave the held transaction or child
process behind. Database names are random but regex-bounded, every created
database is dropped attempt-always in reverse order, and an independent
post-run `information_schema` query found no `t_aoou_%` database. Cleanup is
portable across the reviewed POSIX/PHP/MariaDB test environment and fails
closed when process control, connection identity or lock instrumentation is
unavailable.

### Traceability, sensitivity and coverage

The test cites `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12 and exercises the
approved public version-1 migration and deterministic verification-fixture
seams. The captured RED occurs only after live MariaDB preflight and independent
projection-literal hashes, at the missing
`AssignmentOrderOriginalSchemaMigration` public seam. It is therefore
sensitive to the absent production behavior rather than a PHP, database,
fixture or cleanup setup failure.

All previously reviewed coverage remains present: exact seven-table manifest;
engine/collation, ordered column/default/extra, PK/unique/secondary index,
FK/action and normalized CHECK equivalence; clean, repeat, leading-compatible
partial and populated preservation; complete multi-conflict enumeration;
wrong opaque collation/default and same-count wrong-CHECK conflicts; and full
pre/post structural plus row snapshots proving zero DDL/DML on conflict.

Fixture coverage retains independently hashed literals and exact users, roles,
role assignments, no credentials, capabilities, case/order/composition,
workforce, task, checklist and unrelated decoy projections. It requires zero
original roots/revisions/requests/events/audits/maintenance facts, exact
seed/repeat/conflict rollback, reverse cleanup, repeat cleanup and unchanged
unrelated decoy state. No production secret, runtime DDL, original-fact
fabrication, private implementation method or production system is used.

Expected manifests, rows and SHA-256 literals come from the approved support
oracle and normative setup contract rather than the future implementation.
The test is deterministic apart from collision-resistant owned database names,
and every wait/read/reap path is bounded.

## Reproduced real-MariaDB RED and cleanup

Against the healthy disposable MariaDB exposed at `127.0.0.1:23306`:

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

$ docker exec fmonitor2-test-test-db-1 mariadb ... -Nse
  "SELECT SCHEMA_NAME ... WHERE SCHEMA_NAME LIKE 't_aoou_%' ..."
<no rows>
exit 0

$ git diff --check
PASS (no output)
```

This is the intended missing public migration behavior. Gate 3 authorizes the
minimal setup implementation in task 3.1 without changing the approved test
expectations.

## Required changes

None.

## Exact reviewed SHA-256 inputs

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9e2c354069c3b62f5d65eea37dd0417b6bbd8ef78d804f870882beda537dbbc1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
c3978e87c4849bfb9ac8f8d0ed3c705c64f681258e6518830e0d995a39982a9d  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
cc65deb0130a24e9f019b0605b78a6707a2671a75f686899dc16ecad8bbe5192  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
0399ea3dd131a70bb1b814e2d03fcdc133f73448ae18efb4877255ef8412af6f  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
1e6133adc30dbef87ee774e4d065e82b5c3d2c72adde32ed4b9bb20ba4faf696  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v3.md
f123a5938488573a934cbb3edbdf06519e5c1f81b43107e908094fa878cabadb  docs/operations/assignment-order-original-database-setup-red-correction-v2-2026-09-04.md
06457cce6ccd1edf2ccf7e0adb15bc491d8d57143ca30e2f0505d57f2766dcab  docs/operations/assignment-order-original-database-setup-red-correction-v3-2026-09-04.md
c01affec62a9046bf786ea26984e6c1d9a95b2f0947f4f75de21d64b4b1c0863  docs/operations/assignment-order-original-database-setup-red-correction-v4-2026-09-04.md
```

The review path is metadata because a self-hash is circular.
