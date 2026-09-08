# Test review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 native-false CREATE event RED

- Reviewer: `/root/seed_original_planning_review` (independent of this test)
- Test author: `/root`
- Reviewed commit: `dc4b701cee160166edad31ad27c592e4bf20a980`
- Owner-approved specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Test: `tests/InstallationProcess/object_detail_snapshot_schema_native_false_001_test.php`, SHA-256 `706756f7f55fea544952fe0aa7c0ad29b9c601d71ad5667a5ea70392b0cd6d7a`
- RED evidence: `docs/operations/object-detail-schema-native-false-red-2026-09-05.md`, SHA-256 `66ad66292f9e7872b288387502062fd77383ac89244f7c971de78b502a4ebf31`
- Verdict: **APPROVED** for the native-false event correction only

## Findings

The test is independently sensitive to the approved rule that a CREATE phase
is emitted only after a real successful CREATE:

- It creates a randomized disposable database and principal, proves the exact
  connected `CURRENT_USER()`, and queries `TABLE_PRIVILEGES` to show that the
  principal has database SELECT plus CREATE only for the details table.
- With `MYSQLI_REPORT_OFF`, an independent real `denied_probe` CREATE is required
  to return native boolean `false` before the public verification migration is
  invoked. A setup, privilege, or host-behavior mismatch therefore cannot be
  mistaken for the target RED.
- The public call must still fail as typed `DatabaseUnavailable`. Independent
  admin observations then require only the durable details table and a free
  exact normative lock, separating correct failure/durable-state behavior from
  the event transcript defect.
- The literal event oracle expects exactly `lock_acquired` and
  `details_created`. Current production adds `quarantine_created` after the
  denied statement returned false, so the failure directly exposes the missing
  success-result check rather than deriving an expectation from implementation.

The test restores strict mysqli reporting in the inner `finally` before any
postcondition query and again in the outer `finally` for abnormal paths. It
closes the restricted connection, drops only the randomized user whose CREATE
succeeded, drops only the randomized database whose CREATE succeeded, and
closes admin through nested cleanup blocks. The generated password is neither
logged nor used in expected output.

## Independent execution

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_native_false_001_test.php
No syntax errors detected in tests/InstallationProcess/object_detail_snapshot_schema_native_false_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_native_false_001_test.php
TestFailure: INTENDED_RED: failed CREATE must not emit quarantine_created
Expected: [lock_acquired, details_created]
Actual: [lock_acquired, details_created, quarantine_created]
exit 255
```

A fresh read-only residue inventory returned no `fm2_ods_false_%` database and
no `ods_false_%` database user. `git diff dc4b701^ dc4b701 --check` passed.

## Authority boundary

This approval authorizes minimal correction only for suppressing a CREATE phase
when mysqli returns native false. It does not approve broader privilege-denial
recovery, observer/concurrency behavior beyond existing reviews, importer
behavior, full migration integration, Gate 5, or Done.

Required changes: none within this native-false event tranche.
