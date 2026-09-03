# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — v12 conflict-routing RED v4

Date: 2026-09-04 02:02:04 MSK (`2026-09-03T23:02:04Z`)
Gate: 2 correction after the first Gate 4 implementation attempt
RED author: separately tasked agent `/root/original_upload_migration_red`
Base: `03cf697e252431e91ef55b08eaa246300690fcf9`

## Gate 4 finding and disposition

The approved v3 test routed the hostile successor capability fixture through the
entire v1-v12 catalogue. The historical v3 migration correctly owns and rejects
an unknown extra literal before v12 runs, returning a v3 conflict. Requiring that
same full-catalogue call to report v12 was unreachable without weakening the
existing v3/v4 hostile-capability gates.

The corrected fixture still creates the exact canonical v1-v11 predecessor, then
calls the public `AssignmentOrderOriginalSchemaMigration::apply()` v12 owner
directly. It requires the exact v12 conflict result, named capability table and
complete zero-mutation snapshot. Existing v3/v4 tests remain unchanged and
continue to reject unknown extra capability semantics at their own frontier.

Runner expectations are now separated by lifecycle rather than contradicted:

- before v12 registration, the unchanged
  `PRODUCTION-MIGRATION-RUNNER-001` suite characterizes the exact historical v11
  runner and 31-table catalogue;
- the successor schema verifier requires the deployment CLI to return exact
  `schemaVersion=12`, applied versions `1..12` on clean apply and none on repeat,
  and validates the seven new tables plus expanded capability contract.

Thus Gate 2 does not rewrite the v11 characterization before implementation,
while a future v12 implementation cannot pass without canonical runner
registration. Production was fully restored to clean base `03cf697e` before this
test correction.

## Reproduced evidence

Corrected test SHA-256:

```text
5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
```

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

$ git diff --check
PASS (no output)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical additive
assignment-order-original schema migration v12 is missing.
```

The corrected serializer sensitivity assertions still pass before the unchanged
missing-class sentinel. The current runner is independently GREEN at v11; the
successor remains RED solely because its public v12 owner is absent.

## Gate status

This correction is Gate 2 only. Production is untouched, task 3.1 remains open,
and fresh independent Gate 3 approval is required before another Gate 4 attempt.
