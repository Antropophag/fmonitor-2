# IDENTITY-ACCESS-SCHEMA-001 superseding RED evidence v12

- Date: `2026-09-01`
- Specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`, Gate 1 approved
- Supersedes: `identity-access-schema-red-evidence-v11.md` for the Gate 5
  preflight-boundary return path
- Production changes in this iteration: none
- Result: `QUALIFYING RED / READY FOR FRESH INDEPENDENT TEST REVIEW`

## Gate 5 finding converted into a sensitive test

The new append-only focused test exercises the public
`CanonicalMigrationApplication::run()` seam with a test-owned named
`databasePreflight` callback. The callback represents the initial
database/default-collation metadata preflight and is required to execute inside
the same application error boundary as canonical migrations, before any
migration is invoked.

Two literal scenarios are specified:

- an unexpected `Throwable` must return exit `70` with only
  `MIGRATION_FAILED`;
- `DatabaseUnavailable` must remain exit `69` with only
  `DATABASE_UNAVAILABLE`.

Both scenarios require zero migration invocations and an empty test-owned
mutation-marker list. The test does not inject into the CLI, change production,
or assert how the preflight obtains metadata. Previously approved canonical
runner and first-GREEN-helper bytes remain unchanged.

## Reproduced qualifying RED

With the disposable MariaDB 11.4 test service healthy:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/identity_access_schema_001_preflight_application_red.php
Expected unexpected: exit 70, MIGRATION_FAILED, empty stderr,
                     migrationInvocations=0, mutationMarkers=[]
Expected unavailable: exit 69, DATABASE_UNAVAILABLE, empty stderr,
                      migrationInvocations=0, mutationMarkers=[]
Actual both:          escapedThrowable=Error, migrationInvocations=0,
                     mutationMarkers=[]
```

The failure is the intended missing public application preflight API/boundary:
PHP rejects the named `databasePreflight` argument before entering the current
application. No migration ran and no mutation marker was recorded, so this is
not a database setup or migration failure.

Both callbacks were attempted independently before the combined assertion, so
the test pins the `DatabaseUnavailable` exit `69` branch as well as the
unexpected-failure branch. Production GREEN remains forbidden until a fresh
independent Gate 3 reviewer approves this test.

## Checks and gate state

- PHP syntax check: passed.
- Focused `git diff --check`: passed.
- Existing canonical test SHA-256 remains
  `1c8e21b0eedf84794349c14fb8bf706b95c616e225a32104ab62b7e21c94dafe`.
- Existing immutable first-GREEN helper SHA-256 remains
  `9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`.
- New preflight application RED SHA-256:
  `48c74ac4a18b9c8fd71618e79291822c6f7ea7dedcecb16472e12f23b8ce68ea`.

OpenSpec task `2.3` is complete for the now-authored preflight boundary RED.
Task `2.4` remains unchecked pending a fresh separately tasked independent test
review. No production edit is authorized by this evidence.
