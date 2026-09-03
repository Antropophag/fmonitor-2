# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — schema migration GREEN evidence

Date: 2026-09-04 02:29:49 MSK (`2026-09-03T23:29:49Z`)
Gate: 4 minimal GREEN for task 3.1
Implementation commit: `17f03a5c0e997c0f15188c736b50a97ab95e4014`
Approved test review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v7.md` (`APPROVED`)

The production-only commit adds the canonical v12 seven-table original-evidence
migration, expands the exact capability constraint, teaches historical v3/v4
owners only the exact known v12 successor state, and registers v12 in the real
migration CLI. The migration preflights the capability table and every member of
the seven-table family before any DDL, rejects incompatible partial state, and
preserves exact compatible rows/counters on repeat. Historical registration
facts are not rewritten.

Production file hashes:

```text
c1cf978b175c98088e7c870aa06bfdae3b6699f4a06050fa95a7de4c6cb3f1e0  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
0d1675da7af05262d80a43d35158625d30473b652a73bd92a4dc9deebbc814c6  app/InstallationProcess/ProcessCapabilityChecksClassifier.php
90b9c3ac51a7dc5cd827a21479ed2adcbcf7f021b8517d12103f6d1d508b2346  app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php
1d10cf28fb7395b8908f5b1c76116e344c377955f061c3c9112fb7a062fedd89  bin/fmonitor2-migrate.php
```

Verification on the exact implementation commit:

```text
$ ... php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_CLEAN_OK
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_REPEAT_OK
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_POPULATED_OK
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_CONFLICT_OK
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_001_OK

$ ... php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

$ php -l app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
No syntax errors detected in app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
$ php -l app/InstallationProcess/ProcessCapabilityChecksClassifier.php
No syntax errors detected in app/InstallationProcess/ProcessCapabilityChecksClassifier.php
$ php -l app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php
No syntax errors detected in app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php
$ php -l bin/fmonitor2-migrate.php
No syntax errors detected in bin/fmonitor2-migrate.php

$ git diff --check
PASS (no output)

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
```

The database commands used the repository's local test-contour credential,
redacted here. This is Gate 4 evidence only. No Gate 5 verdict is implied;
task 3.1 requires fresh independent code review before integration.
