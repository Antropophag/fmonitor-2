# Object-detail schema — held-lock RED

Date: 2026-09-05. Test author: `/root`.
Base: `79588268004477c173289a9801b1afdf95c348ce`.
Approved contract: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4, owner approval e8f17b6.

Two real connections use one uniquely owned fictional database. The fixture
connection acquires the exact normative SHA-256 database/prefix named lock;
the migration connection independently observes the holder connection ID.
The public production migration is then called while the fixture holds the
lock. It must return DatabaseUnavailable and leave the complete table inventory
unchanged. The fixture lock identity and opaque decoy are checked independently.
After release the intended success path must create both tables and release
its own lock, proven by immediate acquisition from the other connection.

Commands:

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php
No syntax errors detected
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php
PREREQUISITE PASS: real distinct connection holds the exact migration lock
INTENDED_RED: held migration lock must return DatabaseUnavailable; held migration lock must prevent all family creation
exit 255
```

Finally releases only the fixture-owned lock, closes both connections and drops
only the database whose CREATE succeeded. A read-only residue query returned
`[]`. Diff-check passed. No production/spec/config changes. This is a focused
held-lock RED, not yet two-creator serialization or observer interruption proof.
Fresh independent Gate 3 is required before implementing this behavior.
