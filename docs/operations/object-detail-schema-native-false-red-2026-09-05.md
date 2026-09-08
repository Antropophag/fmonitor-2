# Object-detail schema — native-false CREATE event RED

Date: 2026-09-05. Author: `/root`.
Base: `62963e95e489da04ce615f774dd148716d381ea9`.
Contract: approved v0.4 events emitted only after real successful CREATE.

With MYSQLI_REPORT_OFF, a real CREATE denial returns false. The isolated
principal can create only details; an independent denied-probe statement
confirms native false before calling the public verification migration.
The migration correctly fails unavailable, leaves only details and releases
its lock, but incorrectly emits QUARANTINE_CREATED for the failed statement.

Focused command:
`FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_native_false_001_test.php`

```text
INTENDED_RED: failed CREATE must not emit quarantine_created
Expected: [lock_acquired, details_created]
Actual: [lock_acquired, details_created, quarantine_created]
exit 255
```

All prerequisite and durable-state assertions preceding the event comparison
passed. The test restores mysqli reporting and closes the owned connection,
then deletes only its successfully created disposable user/database. No
production/spec/config was changed; Gate 3 is required before correction.
Syntax/diff checks passed. Exact test bytes are preserved in the commit.
