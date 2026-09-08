# Object-detail schema — observer/interruption RED

Date: 2026-09-05. Test author: `/root`.
Base: `8da502f1f21320ec0d1c34eaa7c49dc6eb4277c5`.
Contract: owner-approved OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4.

The test first creates a control family through the real public migration.
It then requires the specified verification class, phase enum and observer
interface. Prospective cases observe real table inventories and lock-holder
identity from a distinct connection at every phase: normal CREATE sequence,
observer interruption after details CREATE, and actual connection close after
quarantine CREATE before final verification. Ordinary fresh-connection retry
must preserve existing rows and complete only the missing member.

Probe assertion failures are retained separately from intentional observer
faults, so the production adapter Throwable mapping cannot disguise a failed
independent observation as the desired DatabaseUnavailable result.

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php
No syntax errors detected
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php
PREREQUISITE PASS: real canonical family and independent observation connection
INTENDED_RED: approved observer verification API is missing
Expected true / Actual false
exit 255
```

Owned database residue query returned `[]`; diff-check passed. No production,
specification or configuration changes. Missing-API RED does not claim the
post-guard cases executed already. Real DDL privilege-denial and two-creator
IPC tests remain separate required work. Fresh independent Gate 3 is required.
