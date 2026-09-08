# Assignment-order original database setup — replacement GREEN v3

Date: `2026-09-05`

Status: **GREEN / READY FOR FRESH INDEPENDENT GATE 5**.

Approved replacement RED: `5c97abcd270b34aa4ab9d08f0583264ece653b71` with
fresh Gate 3 v22 `APPROVED` at
`a3df95a71d509fa8dc15e002d1d8ef54504eb8ea`.

The correction makes a missing capability prerequisite a zero-DDL conflict,
keeps exact V4→V5 capability publication last in `affectedTables`, and preserves
V5 repeat as empty `UNCHANGED`. Complete fixture byte validation, observer
recovery and seed/cleanup contention remain GREEN.

```text
php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK
php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK
make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
git diff --check
PASS (no output)
```

The first regression invocation without the explicit current Compose test-root
password failed admission only; it was rerun with the documented test credential
and passed. No failure was reclassified or skipped. This is not Gate 5 or launch
readiness.
