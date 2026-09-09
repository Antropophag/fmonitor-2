# Independent Gate 5 review — PRODUCTION-RUNTIME-MIGRATION-LOCK-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Implementation author: orchestrating agent `/root`; this reviewer did not
  author production code, specification, or tests
- Verdict: **APPROVED (BOUNDED)**

This review covers only the canonical whole-catalogue advisory lock. It does not
approve the remaining production HTTP runtime.

## Reviewed identities

```text
e83375c0d61845708e50dbe2d0802394a6b62143a709f6b5fd767ceb1799dd12  specs/PRODUCTION-HTTP-RUNTIME-001.md
9ed5329d51da1845d97fddb9f4f82a58f88edf8055c907d3984ffd55cdc53830  openspec/changes/production-http-runtime/specs/operations/locked-schema-migrations/spec.md
78628a1a19bc48885debbba5978cdfdbbe4114634c9590b183bd741ea25800e3  tests/Runtime/migration_concurrency_lock_001_test.php
efdcae2006147ea1a7d5dfad968363726e61efabc070b5a11fa43e6f4cfb8618  app/InstallationProcess/CanonicalMigrationApplication.php
ab206c4226300b34bef52e1db4c5dd73e369cf152cda60b1db03ebbaf180560b  app/InstallationProcess/MariaDbMigrationLock.php
```

## Findings

No blocking finding in the bounded implementation.

`CanonicalMigrationApplication::run()` retains its public signature and acquires
the deployment lock before `runLocked()`, so catalogue validation, database
preflight, and migration callbacks are all excluded. A busy fixed-timeout
`GET_LOCK(..., 0)` returns the distinct stable exit 75 outcome without entering
the catalogue. The helper derives a bounded deterministic name from current
database, table prefix/catalogue identity, and a stable namespace.

The owning connection retains the lock across the complete ordered run. `finally`
attempts release after success and every mapped application failure. A release
anomaly converts success to `MIGRATION_FAILED` while preserving an already failed
database or migration outcome. `GET_LOCK` `NULL` and query/protocol failures map to
the existing secret-free database-unavailable outcome. Individual migration
preflights and their own locks remain unchanged.

The focused test uses a second real MariaDB connection to prove contention causes
zero preflight and migration calls, then proves normal success and reacquisition
after both success and synthetic migration failure. The specified rare transport
and release-anomaly branches are implemented but do not have fault-injection
evidence in this bounded cycle; this limitation must not be reported as separately
verified.

## Verification

```text
$ php -l app/InstallationProcess/CanonicalMigrationApplication.php
No syntax errors detected in app/InstallationProcess/CanonicalMigrationApplication.php

$ php -l app/InstallationProcess/MariaDbMigrationLock.php
No syntax errors detected in app/InstallationProcess/MariaDbMigrationLock.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/migration_concurrency_lock_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 canonical migration catalogue concurrency lock

$ git diff --check
# exit 0, no output
```

## Verdict

**APPROVED (BOUNDED).** The reviewed lock implementation conforms to the fixed
immediate-lock contract and reviewed Gate 3 expectations. Full slice completion
still depends on the remaining runtime tests, implementation, regression, and an
independent full code review.
