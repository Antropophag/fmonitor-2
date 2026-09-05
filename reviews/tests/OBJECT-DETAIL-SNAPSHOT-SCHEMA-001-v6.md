# Test review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 held-lock and retry RED

- Reviewer: `/root/seed_original_planning_review` (independent of this test)
- Test author: `/root`
- Reviewed commit: `30657cd34b40041e78bcd0ce42622e9aff52a144`
- Owner approval commit: `e8f17b63a3c93e8f4be5664c309b435fde3318e9`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Test: `tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php`, SHA-256 `b0f0bbc6b830bdcf37dddef8c07d72ce1e1016991586f29dbc86415c78b8c1f2`
- RED evidence: `docs/operations/object-detail-schema-held-lock-red-2026-09-05.md`, SHA-256 `628ec08d7e140823c2805762fe5fbd5374371b441744ec0f61b03fd6ca814734`
- Verdict: **APPROVED** for the held-lock rejection and ordinary retry tranche only

## Findings

The test opens two real mysqli connections to one uniquely named, successfully
created test database and proves their connection/thread identities differ.
The fixture connection acquires the exact normative lowercase SHA-256 lock for
the byte sequence `object-detail-schema-v1`, NUL, current database name, NUL,
and `held_` prefix. The worker connection independently observes the fixture's
connection ID as the lock owner before the public migration call.

While that independent lock remains held, the test requires
`ObjectDetailSnapshotSchemaMigration::apply()` to throw `DatabaseUnavailable`,
compares the complete table inventory with its one-decoy baseline, verifies the
failed caller did not release the fixture connection's lock, and compares the
opaque binary decoy row byte-for-byte. It accumulates the two product outcomes
before releasing the fixture-owned lock, so the RED reports both missing
behaviors without leaking the lock into cleanup.

The prospective post-release assertions are correctly scoped: an ordinary
retry must return the exact sorted two-table v12 creation result, after which
immediate acquisition by the other connection proves the migration released
its own named lock. Exact table shape and broader migration outcomes remain
covered by the separately reviewed bounded corpus; this focused test does not
duplicate them.

Cleanup is ownership-bounded. Database deletion occurs only after this test's
randomized `CREATE DATABASE` succeeded; the fixture releases only its named
lock, closes both worker/holder connections, drops only that owned database,
and closes admin through nested `finally` blocks.

## Independent execution

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php
No syntax errors detected in tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php
PREREQUISITE PASS: real distinct connection holds the exact migration lock
TestFailure: INTENDED_RED: held migration lock must return DatabaseUnavailable; held migration lock must prevent all family creation
exit 255
```

This is the intended missing lock behavior, not class loading, database setup,
connection identity, fixture lock acquisition, or observation failure. A fresh
read-only residue query returned `[]` for `fm2_ods_lock_%` after execution.
`git diff 30657cd^ 30657cd --check` passed.

## Authority boundary

This approval authorizes minimal GREEN only for an independently held lock's
timeout/no-mutation result, lock preservation/release, and ordinary retry.
Two-creator serialization, observer-driven interruption, DDL-denial and final-
verification faults, connection-loss behavior, composed concurrency fixtures,
importer behavior, full migration approval, Gate 5, integration, and Done
remain separate and unapproved.

Required changes: none within this held-lock/retry tranche.
