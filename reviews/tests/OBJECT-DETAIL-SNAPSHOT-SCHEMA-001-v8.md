# Test rereview: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 observer RED v2

- Reviewer: `/root/object_detail_schema_gate3` (fresh independent Gate 3 reviewer; did not author this test)
- Exact reviewed commit: `cc02608f5b71323434ce4a2765d5a7b692a5cd92`
- Original observer RED: `474a4aa972fc931c15c93dd5f2af181e96c5c7ea`
- Prior review: `7a10449561617339bb60657815945a680078787b`
- Owner approval: `e8f17b63a3c93e8f4be5664c309b435fde3318e9`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Corrected test SHA-256: `e966f3b40fc10b043878fdeec7462eba81600913925bade8254f094b86c4e763`
- Original RED evidence SHA-256: `7bfd8493ff4b091c23db8edc31abef506672b43c91d80876974c9a9f39520fbe`
- v2 correction evidence SHA-256: `4c34d50aabc274fb6b618e102cd11b4628caa23d3505fb21cc5daf00eca956b2`
- Prior review record SHA-256: `c1f0211f7e2ae8fa67904ded4defb7543d4234036540988727b114c255f05cea`
- Verdict: **APPROVED** for the observer/interruption tranche only

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

The failure is the intended missing public verification API after a real public
migration successfully created the control family. It is not class loading of
the existing owner, MariaDB setup, or control migration failure. A fresh
read-only query returned `[]` for `fm2_ods_observer_%`; cleanup dropped only the
randomized database whose CREATE succeeded. `git diff cc02608^ cc02608 --check`
passed.

## Findings

The v2 reflection assertions close the prior review's only blocker. Expected
declarations are literal and independent of production:

- `ObjectDetailSnapshotSchemaPhase` must be string-backed and expose exactly
  the three ordered names and values `LOCK_ACQUIRED=lock_acquired`,
  `DETAILS_CREATED=details_created`, and
  `QUARANTINE_CREATED=quarantine_created`;
- `ObjectDetailSnapshotSchemaObserver` must expose only public non-static
  `observe(ObjectDetailSnapshotSchemaPhase $phase): void`, including exact
  parameter name, type, nullability, required/by-value/non-variadic flags, and
  return type;
- the verification class must be final and its `apply` method must be public
  static with exact `mysqli $connection`, required `string $tablePrefix`, and
  required `ObjectDetailSnapshotSchemaObserver $observer` parameters plus array
  return type, including names/nullability/reference/variadic flags.

The prospective behavioral body remains sensitive and properly isolated. Each
scenario uses a fresh worker connection and an independent reader. At every
emitted phase the reader proves the exact normative database/prefix lock is
owned by that worker and observes the exact real durable table inventory:
none at lock acquisition, details only after the first CREATE, and both tables
after the second CREATE.

The normal path requires the three-event transcript and exact success result.
The first-fault path throws from the observer after durable details creation;
it requires `DatabaseUnavailable`, lock release, details-only durability, an
ordinary fresh retry that creates only quarantine, and byte-preservation of a
fictional existing row. The closed path closes the actual worker connection
after durable quarantine creation and before final inspection; it requires the
same technical failure, both durable tables, eventual lock freedom, and a fresh
exact-repeat retry. Probe assertion failures are retained and rethrown before
product outcome checks, so Throwable translation cannot disguise a faulty
phase/table/lock observation as expected unavailability.

Connection closure, bounded lock-release polling, nested connection cleanup,
and database ownership are deterministic and scoped. The test invokes no
importer or source data.

## Authority boundary

This approval authorizes minimal GREEN only for the exact observer public API,
phase emission, observer-fault/closed-connection translation, durable schema,
lock release, and fresh retry behaviors in this corpus. It does not approve
DDL privilege-denial recovery, two-creator serialization or timeout, different
namespace concurrency, composed worker/IPC fixtures, importer behavior, full
migration Gate 5, integration, or Done.

## Required changes

None within the observer/interruption tranche.
