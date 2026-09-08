# Assignment-order original database setup — replacement GREEN v2

Date: `2026-09-05`

Status: **GREEN / READY FOR FRESH INDEPENDENT GATE 5**.

Owner-approved technical contract: v14 reviewed commit
`08f1afd7c6dc4d9d8d9db30aa6d5af2d56b04cd2`, Gate 1 `APPROVED` commit
`3a1be3cef02e2826b0afc1e80cf0da5e531ba7d9`.

Replacement RED: `f3cce30d963ac655fadabef9f622a448d4c44108`, fresh Gate 3 v20
`APPROVED` commit `2ab6e511ff7a704cb54ebfdfa74ff7bf0f33d51d`.

Minimal correction replaces permissive fixture writes/deletes with complete
family and byte-value validation, exact/empty state classification and
transactional cleanup. Capability migration now has exact V4/V5/conflict
classification, complete conflict union, capability-last publication, per-table
and pre/post-ALTER verification phases, fixed unavailable mapping and fresh
post-ALTER recovery.

```text
php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001

git diff --check
PASS (no output)
```

Both MariaDB verifiers remove their task-owned databases and worker processes.
This record does not claim setup Gate 5, command implementation, repository-wide
VERIFY_OK or launch readiness.
