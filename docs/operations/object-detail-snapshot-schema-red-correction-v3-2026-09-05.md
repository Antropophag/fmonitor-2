# Object-detail schema bounded RED v3

Date: 2026-09-05. Test author: `/root`.
Corrects independent Gate 3 findings at
`216a9b7ca915d05fa8ab9d9719547e2cdfb17880`.

Added exact empty-details, empty-quarantine and compatibility=true assertions
for the 25-byte prefix. Each invalid-prefix run compares complete table-name
inventory before/after, so neither family member nor a sanitized alternative
can be created silently. A separate mysqli sentry proves zero query/prepare/
escaping calls for invalid direct apply and compatibility checks.

Commands:

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
No syntax errors detected
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
PREREQUISITE PASS: isolated MariaDB fixture is writable and observable
TestFailure: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 requires the missing public v12 migration seam.
Expected: true
Actual: false
exit 255
```

Pre-guard calibration remains GREEN; no setup failure replaced the intended
RED. Post-guard future production assertions remain unexecuted at this absent-
seam baseline. Read-only test database residue query returned `[]`; diff-check
exited 0. Test bytes are pinned by the containing commit. No production,
specification, OpenSpec, config or importer changes. Fresh Gate 3 required.
