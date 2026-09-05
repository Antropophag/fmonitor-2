# Test rereview: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 bounded RED correction v2

- Reviewer: `/root/seed_original_planning_review` (fresh independent Gate 3 role)
- Correction author: `/root`
- Reviewed commit: `d0fba1c07077edcf0303632b5c55c06c918b9de1`
- Authorized base / owner approval commit: `e8f17b63a3c93e8f4be5664c309b435fde3318e9`
- Prior Gate 3 review commit: `11b911d795ab2617f04cfe37c8c18937853f3713`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Corrected test: `tests/InstallationProcess/object_detail_snapshot_schema_001_test.php`, SHA-256 `aa0a43e13818948de81ca877139f1b7c1cd07d539f73497169867169bfa8c3d3`
- Corrected RED evidence: `docs/operations/object-detail-snapshot-schema-red-correction-v2-2026-09-05.md`, SHA-256 `522c9475156db11b598d5fe628c8c5e789b6445203429eb27539888acd969f1e`
- Verdict: **CHANGES_REQUESTED**

## Verification

The corrected test was rerun independently:

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/object_detail_snapshot_schema_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
PREREQUISITE PASS: isolated MariaDB fixture is writable and observable
TestFailure: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 requires the missing public v12 migration seam.
Expected: true
Actual: false
exit 255
```

A fresh read-only schema query afterward returned `[]` for
`fm2_ods_red_%`, `fm2_ods_runner_%`, and `fm2_ods_collation_%`. The RED is the
intended missing-public-seam failure, not setup failure, and randomized owned
database cleanup completed.

## Findings

The correction fixes the decoy row key; exact type/unsigned/width and
per-column charset/collation sensitivity; two-collation calibration; absent and
both partial compatibility checks; the opposite-order family preflight case;
actual CLI-created table inspection; and bounded subprocess termination, pipe
closure, reap, and output limits. The pre-guard calibration genuinely executes
the exact two-table oracle for both collations, the binary decoy snapshot, seven
metadata drift detectors, and the bounded invalid-prefix CLI path before the
missing-class guard.

One prior required change is not fully fixed:

1. The 25-byte direct-prefix case checks the result and calls the exact metadata
   oracle for both tables, but it does not assert that either table is empty or
   that `isCompleteCompatible()` returns true. `odsAssertExact()` does not
   inspect its captured `rows` field. This does not satisfy the prior review's
   explicit empty-and-compatible requirement.
2. After an invalid direct prefix throws `InvalidArgumentException`, the test
   checks only that `isCompleteCompatible()` returns false. That result is also
   valid for a partial family, so an implementation that mutates one composed
   family table and then throws would pass. The test must assert both exact
   composed family tables remain absent for every invalid direct prefix.

Add explicit empty-row and complete-compatible assertions for the 25-byte
family, plus direct absence assertions for both composed family members after
each invalid prefix. Then capture a fresh intended RED and request another
independent Gate 3 review.

This review is only for the bounded first tranche. Deterministic interruption,
connection-loss/final-verification behavior, DDL denial/retry, named-lock
timeout, concurrent creators, and composed fixtures remain unapproved. It does
not authorize production changes, importer behavior, full v12 completion,
Gate 5, or Done.
