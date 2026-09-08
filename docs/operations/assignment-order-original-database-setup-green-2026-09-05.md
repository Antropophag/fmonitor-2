# Assignment-order original database setup — minimal GREEN

Date: `2026-09-05`

Status: **GREEN / READY FOR FRESH INDEPENDENT GATE 5**.

Approved RED authority is exact test commit
`5ebf9be5700f7addd83cf7a2705f851c477f2ce5`; fresh independent Gate 3 v16 is
the explicit `APPROVED` review committed at
`47194340cad8a171d2594bdf09294b314494e457`.

Production additions are limited to the version-1 additive schema/capability
migration, MariaDB verification fixture, pure CHECK canonicalizer and public
autoload shims. DDL remains under `app/InstallationProcess/*SchemaMigration.php`;
fixture SQL remains under `app/InstallationProcess/MariaDb*`. No application,
HTTP, cron or rapid-pilot runtime path invokes migration or fixture setup.

Verification:

```text
php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001

git diff --check
PASS (no output)
```

The setup verifier covers clean/repeat/leading-partial/populated/conflict
migration, exact schema semantics, capability grants, deterministic fictional
seed/repeat/drift/cleanup, real contention and exceptional worker cleanup. It
removed every owned database and reported no process/schema leak. This record
does not approve command implementation, Gate 5, full verification or launch
readiness.
