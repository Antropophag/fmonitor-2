# IDENTITY-ACCESS-SCHEMA-001 Gate 2 superseding evidence v9

- Date: `2026-09-01`
- Role: fresh Gate 2 test author `identity_access_green_fixture_fix_20260901m`
- Supersedes: `identity-access-schema-red-evidence-v8.md` for current test setup,
  first-GREEN execution and task accounting
- Outcome: `FOCUSED GREEN`; fresh independent Gate 3 rereview required because
  reviewed test code changed
- Production code changed by this test-author iteration: no

## Test-only setup corrections

1. The engine conflict fixture now converts
   `fm2_pilot_user_status_events` to MyISAM. That table has no long indexed
   character key, so MariaDB can create the deterministic legal defect and the
   assertion remains sensitive only to the required `InnoDB` engine category.
   The previous `auth_attempts` mutation failed during setup because its
   utf8mb4 `varchar(254)` index exceeds MyISAM's 1000-byte key limit.
2. The isolated runtime observer no longer unconditionally creates
   `fm2_pilot_user_status_events` after the explicit bootstrap has established
   canonical v6 readiness. Migrated block/unblock still runs with the complete
   table, while the observer explicitly drops it for the missing path and then
   creates the test-owned incompatible one-column variant for the incompatible
   path. All three paths retain exact HTTP/state/DDL expectations.

Neither correction weakens an expectation or changes production. During the
first corrected schema run, the suite exposed a production ordering defect:
the diagnostic result returned `roles, users` instead of approved family order
`users, roles`. The Gate 4 implementation author removed lexical sorting; the
test expectation was unchanged and the subsequent full run passed.

## Immutable first-GREEN contract

`identity_access_schema_001_test.php` now invokes the previously authored
`iaAssertGreenApplicationFailureContract()` through the public
`CanonicalMigrationApplication`. Test-owned v6 callbacks throw an unexpected
failure; a test-owned v7 callback records any forbidden later invocation. The
immutable helper proves:

- exact exit `70`, stdout
  `{"ok":false,"reason":"MIGRATION_FAILED"}\n`, and empty stderr;
- the post-v6 callback is invoked exactly zero times.

The helper remained byte-identical with SHA-256
`9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`.

## Focused verification

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/identity_access_schema_001_test.php
PASS: IDENTITY-ACCESS-SCHEMA-001 canonical runner and runtime ownership

$ tools/verification/run-identity-access-isolated-red.sh
PASS: IDENTITY-ACCESS-SCHEMA-001 isolated runtime observer
```

Additional checks:

- `php -l` passed for both focused PHP tests;
- `bash -n tools/verification/run-identity-access-isolated-red.sh` passed;
- focused `git diff --check` passed;
- no `fm2-ia-red-*` container remained after the isolated runner trap.

Current test SHA-256 values:

- canonical/application suite:
  `84aecfdf7898dbc5b6a825178b9a7f3edcf1daec43902782d6c9dc51b6c8302b`;
- runtime observer:
  `1869c88980c3d9330eba2293844810f2c932496bb449fc4640ba205b43ce10d8`.

OpenSpec tasks 2.2 and 2.3 are checked because their approved test scope now
executes against minimal GREEN without setup failure. Task 2.4 remains unchecked
until a fresh separately tasked independent reviewer records `APPROVED` in a
new append-only review.
