# Test review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 observer/interruption RED

- Reviewer: `/root/seed_original_planning_review` (independent of this test)
- Test author: `/root`
- Reviewed commit: `474a4aa972fc931c15c93dd5f2af181e96c5c7ea`
- Owner approval commit: `e8f17b63a3c93e8f4be5664c309b435fde3318e9`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Test: `tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php`, SHA-256 `cb4d195716b235f2c7488f187daa2a756260a04b1d32dcbf1b053bcf99a00fcb`
- RED evidence: `docs/operations/object-detail-schema-observer-red-2026-09-05.md`, SHA-256 `7bfd8493ff4b091c23db8edc31abef506672b43c91d80876974c9a9f39520fbe`
- Verdict: **CHANGES_REQUESTED**

## Independent execution

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php
No syntax errors detected in tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php
PREREQUISITE PASS: real canonical family and independent observation connection
TestFailure: INTENDED_RED: approved observer verification API is missing
Expected: true
Actual: false
exit 255
```

The RED is the intended missing public verification API, not database setup or
control-family failure. A fresh read-only residue query returned `[]` for
`fm2_ods_observer_%`. `git diff 474a4aa^ 474a4aa --check` passed.

## Findings

The behavioral body is otherwise well constructed:

- a real public migration creates a control family before the missing-API guard;
- every scenario uses a distinct worker connection and an independent reader;
- the reader checks the exact normative named-lock identity and real table
  inventory at each ordered phase;
- the first interruption deliberately throws after durable details creation,
  while the second closes the actual worker connection after durable quarantine
  creation and before final verification;
- probe assertion failures are retained separately and rethrown before outcome
  assertions, so production Throwable mapping cannot disguise a bad probe as
  expected `DatabaseUnavailable`;
- fresh-connection retry checks the partial and complete durable outcomes and
  preserves the fictional details row exactly;
- nested cleanup closes connections and drops only the randomized database
  whose CREATE succeeded.

One blocking sensitivity gap remains. The approved specification gives exact
constructible declarations for the backed string enum, observer interface, and
static verification entry point. The test only combines `class_exists`,
`interface_exists`, and `enum_exists`, then exercises one compatible usage. It
does not independently assert:

- that the enum is string-backed and has exactly the three named cases with
  exact values, with no extra cases;
- that `ObjectDetailSnapshotSchemaObserver` declares exactly public
  `observe(ObjectDetailSnapshotSchemaPhase $phase): void`;
- that `ObjectDetailSnapshotSchemaMigrationVerification` exposes the exact
  public static `apply(mysqli $connection, string $tablePrefix,
  ObjectDetailSnapshotSchemaObserver $observer): array` contract.

Broader parameter types, altered return declarations, extra enum cases, or
additional public interface requirements are public API drift. Some could
satisfy all current behavioral calls, so the test is not yet independently
sensitive to the exact Gate 1 declaration contract.

Add reflection-based, production-independent assertions for those exact three
declaration shapes immediately after the existence guard. Capture a fresh
missing-API RED and request another independent Gate 3 review. Do not derive
these expectations from implementation.

## Authority boundary

This review is limited to observer phase and interruption-fault behavior.
Actual privilege-denial/retry, two-creator IPC and other concurrency cases,
importer behavior, full migration completion, Gate 5, integration, and Done
remain separate and unapproved.
