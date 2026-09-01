# IDENTITY-ACCESS-SCHEMA-001 Gate 2 RED evidence

- Date: `2026-09-01`
- Test author: fresh separately tasked agent
  `identity_access_red_author_20260901a`
- Executable specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`, owner-approved
- Public seam: `php bin/fmonitor2-migrate.php`
- Production code changed: no
- Gate 2 result: `QUALIFYING RED`

## Test artifact

`tests/InstallationProcess/identity_access_schema_001_test.php` exercises the
real canonical CLI process against a task-owned random MariaDB database. The
test owns literal expectations from the approved specification rather than
reading migration maps, production DDL or production schema constants.

The authored scenarios cover:

- clean creation, exact literal `[1,2,3,4,5,6]`, nine empty identity tables and
  no seed;
- complete repeat and populated semantic-compatible preservation;
- restartable 8/9 exact-compatible recovery and its no-op repeat;
- representative column/extra-structure and FK delete-rule conflicts with
  zero mutation;
- non-empty prefix coexistence, decoy preservation and composed 25/26 plus
  invalid-character pre-DB-access validation;
- runtime-owner ratchet rejecting `CREATE`, `ALTER` or `DROP` in the current
  access/auth consumers.

This is a focused tracer suite, not an independent Gate 3 review. The broader
category-complete mutation matrix and request-level DDL observation remain
visible review questions before OpenSpec tasks 2.1–2.3 may be marked complete.

## Commands and results

Initial environment probe:

```text
$ php -l tests/InstallationProcess/identity_access_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/identity_access_schema_001_test.php

$ php tests/InstallationProcess/identity_access_schema_001_test.php
mysqli_sql_exception: Connection refused
```

That first attempt is classified as setup failure and is not RED evidence.
The repository-owned isolated MariaDB contour was then started:

```text
$ make test-env-up
Container fmonitor2-test-test-db-1 Healthy
```

Qualifying run:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/identity_access_schema_001_test.php
PHP Fatal error: Uncaught TestFailure: Clean literal canonical result.
Expected: ['ok'=>true,'schemaVersion'=>6,
           'appliedVersions'=>[1,2,3,4,5,6]]
Actual:   ['ok'=>true,'schemaVersion'=>5,
           'appliedVersions'=>[1,2,3,4,5]]
```

Exit code was `255`. The CLI itself started, connected to healthy MariaDB,
successfully applied existing canonical migrations v1–v5 and returned a valid
JSON result. The assertion therefore failed only because the approved literal
identity/access v6 migration and final runner version are absent. This is the
intended missing behavior, not a fixture, connection, parser or process setup
failure.

## Gate boundary

No production implementation is authorized by this record. A different fresh
agent must independently review the approved specification, test sensitivity
and captured RED under Gate 3. OpenSpec tasks 2.1–2.3 stay unchecked until that
review determines that their full stated verification scope is actually met;
task 2.4 stays unchecked until an `APPROVED` review exists.
