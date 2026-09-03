# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — schema migration RED evidence

Date: 2026-09-04 01:38:48 MSK (`2026-09-03T22:38:48Z`)
Gate: 2 — intended RED for OpenSpec task 3.1
RED author: separately tasked agent `/root/original_upload_migration_red`
Base commit: `157051b5105adcfebe7caf201d0914e67bcb3fe7`

## Approved contract and scope

The verifier is pinned to the owner-approved v4 executable-spec hash
`97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
and delta-spec hash
`127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`.
Strict OpenSpec validation passed before RED authoring.

This bounded Gate 2 increment covers only the additive canonical migration at
the actual v11 frontier. It calls the public `CanonicalMigrationApplication`
and deployment CLI and expects v12 to:

- accept a clean predecessor and be a byte-preserving no-op on repeat;
- preserve populated capability rows and all historical manual-registration
  facts exactly;
- extend the named capability CHECK to the exact seven-value set containing
  `assignment_order.original.upload`, `assignment_order.original.correct`, and
  `assignment_order.original.storage.reconcile`, while retaining all four
  historical literals including `assignment_order.confirm_registration`;
- fail closed on an unknown capability semantic with
  `SCHEMA_MIGRATION_CONFLICT` at v12 and zero schema/row/counter/decoy mutation.

The verifier deliberately does not name, seed, query, or infer private
original-evidence tables. Their representation remains owned by the production
repository/migration implementation; complete schema state is used only as an
opaque before/after preservation snapshot. No production file or OpenSpec task
was changed, and task 3.1 remains open pending Gate 3 and implementation.

## Setup/predecessor proof

```text
$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
```

The existing canonical migration runner therefore reaches and repeats the
current v11 catalogue against healthy MariaDB. The new failure is not a DB,
credential, autoload, PHP syntax, or predecessor-migration setup failure.

## Intended RED

Test SHA-256 at execution:

```text
15b82a7d395a3c79769d522e2421c8069d141553c8d29fa1ff4c5667a9715279  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
```

Commands and exact relevant output:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

$ git diff --check
PASS (no output)

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical additive
assignment-order-original schema migration v12 is missing.
```

The verifier stops after approved-hash checks at the explicit missing public
migration class `FMonitor2\InstallationProcess\AssignmentOrderOriginalSchemaMigration`.
That class and v12 runner registration are production behavior intentionally
absent at the base commit. Once implemented, the same verifier continues
through clean, repeat, populated and conflict fixtures.

## Gate status

Gate 2 for this task-3.1 migration subset is demonstrably **RED for the intended
reason**. This record is not Gate 3 approval, does not authorize production
implementation by its author, and does not mark OpenSpec task 3.1 complete.
