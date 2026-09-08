# Test rereview: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 bounded RED v3

- Reviewer: `/root/object_detail_schema_gate3` (fresh independent rereviewer; did not author the corrected test or production)
- Reviewed commit: `dba69ad414da65824399123b5499093afcf2a690`
- Authorized base / owner approval commit: `e8f17b63a3c93e8f4be5664c309b435fde3318e9`
- Independent Gate 1 commit: `eaef9ebbd14cd34e1fe7faf85bde0a6968c0d0db`
- Prior reviews: `11b911d795ab2617f04cfe37c8c18937853f3713` and `216a9b7ca915d05fa8ab9d9719547e2cdfb17880`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Corrected test: `tests/InstallationProcess/object_detail_snapshot_schema_001_test.php`, SHA-256 `f1386aa9b36b6eb36aa4b2eab13ca67061687b28af3a7ffebaf7c8acaa86d5bb`
- v2 correction record SHA-256: `522c9475156db11b598d5fe628c8c5e789b6445203429eb27539888acd969f1e`
- v3 correction record SHA-256: `212c319f264c489e0c74541cc9251bc6755d322480353b8ccc754534eddec313`
- Verdict: **APPROVED** for the bounded first tranche only

## Independent execution

The exact reviewed commit was confirmed as `HEAD`. Commands and results:

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

All pre-guard fixture/oracle and bounded-CLI calibrations therefore ran before
the intended assertion failure. A fresh administrative inventory query for
`fm2_ods_red_%`, `fm2_ods_runner_%`, and `fm2_ods_collation_%` returned `[]`.
`git diff --check` exited 0. The RED is the missing approved public seam, not a
syntax, database, fixture, subprocess, or cleanup failure.

## Findings

All blockers from both prior reviews are closed in the exact test bytes:

- decoy snapshots use their real `id` key and compare binary row bytes before
  and after migration;
- the independently declared manifest asserts ordered names, exact accepted
  column types including unsigned object identity, per-column charset and
  collation, nullability/default/generated/extra metadata, engine, table
  collation, PK-only index shape, and absence of FK/CHECK constraints;
- literal fixture calibration exercises the oracle before the RED guard and
  proves sensitivity to type, signedness, column collation, default, extra
  index, nullability, and engine drift;
- both `utf8mb4_unicode_ci` and `utf8mb4_general_ci` database defaults are
  exercised, preventing a fixed-collation implementation from satisfying the
  corpus;
- absent and both exact-partial compatibility results are false before repair;
  both partial directions, both family-member conflict orders, family-wide
  preflight, preservation, and sorted exact results are independently checked;
- the 25-byte prefix family is asserted exact, empty, and complete-compatible;
- each invalid direct prefix preserves the complete table inventory, while an
  independent mysqli sentry proves `apply` and compatibility validation perform
  zero query, prepare, or escaping calls;
- invalid CLI prefixes are checked against an unreachable endpoint and the
  runner helper itself is calibrated before the RED guard;
- runner execution has monotonic and output bounds plus pipe closure,
  termination and reap handling; the future successful runner path checks exact
  v12 JSON, both exact empty physical tables, and repeat output before dropping
  its owned randomized database;
- database names are random, CREATE ownership is established before cleanup,
  connections are closed, and cleanup targets only the exact owned databases.

The expected values are literal and independent of production implementation.
The corpus is sensitive enough to authorize minimal GREEN for this bounded
clean/repeat/partial/conflict/manifest/collation/prefix/namespace/runner tranche.

## Authority boundary

This approval is not approval or completion of full canonical v12. The
deterministic interruption, connection loss/final-verification failure,
table-scoped DDL denial and retry, named-lock timeout, concurrent creator
serialization, different-namespace concurrency, and composed support/IPC
fixtures remain required by the approved specification but outside this
tranche and unapproved. Importer DML/source behavior, full regression,
architecture verification, Gate 5, integration, and Done are also not claimed.

## Required changes

None for the bounded first tranche.
