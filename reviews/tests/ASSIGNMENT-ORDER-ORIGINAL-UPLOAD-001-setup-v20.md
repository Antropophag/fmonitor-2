# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v20

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v20`
- Reviewed RED commit: `f3cce30d963ac655fadabef9f622a448d4c44108`
- Gap base: `a78972cd784c5f3df93e58c2ae2db7192c6eb32e`
- Prior approved Gate 3: `c024c64f37977e8883c020b0aa5de2b50caaba93` / v19
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Gate 1 authority: `3a1be3cef02e2826b0afc1e80cf0da5e531ba7d9`
- Scope: replacement setup tests for tasks 2.2/2.3 only
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
support oracle, RED evidence or production code. This append-only review is the
only authored artifact. Parent-owned uncommitted production corrections visible
in the shared worktree were not edited, staged, committed or included in the
reviewed diff.

## Independent assessment

The correction is narrow and closes the MariaDB empty-range observer portability
gap without weakening the cleanup contention proof or changing an approved
expectation.

- Seed still holds the test-owned parent `SERIALIZABLE` transaction over the
  absent `user_id=18` range and binds observation to the exact connection ID
  published by the worker. The observer requires two consecutive observations
  before a five-second deadline with the exact task database, `STATE=Update`,
  SQL mentioning both the prefixed `fm2_pilot_users` table and `user_id`.
- Mutating connection ID, database, state, or the SQL/table independently makes
  the seed matcher reject the row. The matcher therefore cannot pass on a
  merely unrelated process, database, wait state, or statement.
- A non-blocking pre-release probe still requires no terminal worker output.
  Parent commit is the only release, after which the worker must emit exact
  `OK seed`, close without stderr and exit zero. Parent fixture and admin
  connections must remain usable.
- Cleanup retains the stronger exact child-to-parent `INNODB_LOCK_WAITS` join,
  `trx_state=LOCK WAIT`, `trx_isolation_level=SERIALIZABLE`, and the prefixed
  `fm2_process_tasks` query identity. It retains the same pre-release silence,
  exact post-release `OK cleanup`, bounded worker termination/reaping and
  connection health assertions.
- The enclosing `finally` remains bounded and fail-closed: it rolls back the
  parent transaction, closes the observer and pipes, terminates then kills a
  surviving worker within fixed deadlines, and reaps it. Independent post-run
  catalog inspection found neither task-owned schemas nor connections.

The diff from the gap base changes no support worker or production seam. All
v19 replacement coverage remains unchanged: complete DDL/CHECK/index/FK
properties and sensitivity; exact V4/V5 publication and observer transcripts;
clean/repeat/leading-partial/populated/conflict families; binary-sorted combined
conflicts with zero DDL; exact fictional seed literals and no credentials;
generated non-identity drift matrix with full-state zero-DML conflicts;
partial cleanup, repeat no-op and occupied-drift conflict; projection digests,
prefix isolation, deterministic redaction, worker failure/hang handling and
bounded cleanup. The new portability observer is restricted to empty-range seed
contention; cleanup's exact InnoDB lock-wait requirement is not relaxed.

## Reproduced RED and isolation evidence

Reproduced from the shared worktree at exact test `HEAD`; its parent-owned
production changes were present but remained outside this review and commit.

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: role assignment drift was accepted by seedExampleA.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ docker exec fmonitor2-test-test-db-1 mariadb ...
SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 't\\_aoou\\_%';
0
SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE DB LIKE 't\\_aoou\\_%';
0

$ git diff --check
PASS (no output)
```

MariaDB connectivity and the full predecessor matrix complete before the fixed
fixture-drift assertion. The failure is the intended missing production
behavior, not a database, contention-observer, worker lifecycle or cleanup
setup failure.

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
ee16b431fd28fc0897f5e3755321408f76b53deeb52df0fc64a39c7929a3f67d  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
865513ce563cac157d64324c51f597024ffd1cc19456bce9ea5456d3e96c714c  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
3480ea78ee679fd7ba9cd667bebaf5ddb7a71925eac318b42dbb22984753cd37  tests/Support/assignment_order_original_fixture_worker.php
ce4e47f916879bee82e2c91c584b33becd4e1339dd8d1aa368ef1273e59a7035  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v19.md
0581baea8a2fb914e971b4a5a14507d0c01176d994fc3d90a1f4ea9b59d1aadb  docs/operations/assignment-order-original-database-setup-seed-gap-lock-observer-gap-2026-09-05.md
7d05ac764d729d5909f9bb0f718990be5b0408bc81ea407294ab6c6a3efd53ec  docs/operations/assignment-order-original-database-setup-seed-contention-portability-red-2026-09-05.md
```

This record omits its own circular hash. Fresh Gate 3 is **APPROVED**. Task 3.1
may resume with only the minimal production setup implementation required by
these exact reviewed tests; an independent Gate 5 remains mandatory.
