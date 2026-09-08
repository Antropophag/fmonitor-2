# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — seed contention portability RED

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gap base: `a78972cd784c5f3df93e58c2ae2db7192c6eb32e`

Outcome: **INTENDED RED — remaining fixture drift acceptance**

Cleanup contention continues to require exact child→parent
`INNODB_LOCK_WAITS`, `LOCK WAIT`, `SERIALIZABLE` and task-table query.

Seed empty-range contention now uses the worker's exact published MariaDB
connection ID and requires two consecutive bounded `PROCESSLIST` observations
with exact task database, `STATE=Update`, SQL targeting the prefixed
`fm2_pilot_users` table and `user_id`. Before parent release no terminal worker
line may exist; after release exact `OK seed` remains required. Negative
sensitivity independently changes connection ID, database, state and query/
table and requires every mutation to fail the matcher.

The test does not assume portable exposure of an empty-range gap lock in
`INNODB_LOCK_WAITS`. No production path or selector changed. Task 2.2 is
rechecked; 2.3 and 3.1 remain open.

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: role assignment drift was accepted by seedExampleA.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
$ independent SCHEMATA/PROCESSLIST query
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
$ git diff --check
PASS (no output)
```

```text
ee16b431fd28fc0897f5e3755321408f76b53deeb52df0fc64a39c7929a3f67d  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
0581baea8a2fb914e971b4a5a14507d0c01176d994fc3d90a1f4ea9b59d1aadb  docs/operations/assignment-order-original-database-setup-seed-gap-lock-observer-gap-2026-09-05.md
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```
