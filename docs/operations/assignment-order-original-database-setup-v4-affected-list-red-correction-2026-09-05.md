# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — V4 affected-list RED correction

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gap base: `ccf9643072da9b7429346f76145da40211efdec4`

All normal setup axes now establish exact V4 first. Every first `APPLIED`
expectation lists created original tables (or the missing leading suffix) in
manifest order followed by `fm2_process_user_capabilities`. Clean, populated,
near-CHECK, near-collation and fixture setup expect all seven plus capability;
leading partial expects six trailing tables plus capability. Exact V5 repeats
remain `UNCHANGED` with an empty affected list. Original-owned table inventory
is filtered independently from prerequisite process tables.

Both setup tests retain the explicit absent-prerequisite `CONFLICT`/zero-DDL
axis. Exact production `edbae87a46ff9d9abf0bda98dd411a6f4ba28aa5` was already
recorded by Gate 5 v2 returning `APPLIED` with seven tables, the intended RED.
The shared worktree currently contains parent-owned uncommitted production
corrections, so the corrected tests now pass there; those files were neither
staged nor edited by the RED author and this current GREEN is not presented as
Gate 4 evidence.

```text
$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK
$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK
$ independent SCHEMATA/PROCESSLIST query
NO_AOOU_DATABASE_OR_CONNECTION_LEAKS
$ git diff --check
PASS (no output)
```

Task 2.2 is checked. Tasks 2.3 and 3.1 are open for fresh review and committed
GREEN. No production or specification artifact changed.

```text
8557ddec86169836d30b0d43236b9f9cbd8e0214d712147f54d11ac150ba9f01  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
6e8d35d624018a344ef923eba64a7cd0f1927e2205d924a5cac301dd7e3ff4ec  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
19b7d819b4897ad28e1bcf7eee6e556b3c774a9cad65a46e838534786cc1af04  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
d364f820d32b21c57b832557f53b7d0334e77f641556060b4b7434e8a5669f50  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
9d88d856cca5d787d377a56df18f47b6a45d0b1bab9d7b898a74de75bec8624b  docs/operations/assignment-order-original-database-setup-v4-affected-list-test-gap-2026-09-05.md
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```
