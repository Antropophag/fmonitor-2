# Object-detail schema — corrected bounded RED v2

Date: 2026-09-05. Test correction author: `/root`.
Gate 3 finding: `11b911d795ab2617f04cfe37c8c18937853f3713`.
Original RED: `77186fe02b5ac0fc37c1968a96bb17b427ea89c7`.
Approved spec remains SHA-256
`be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`.

## Corrections

- Snapshot helper takes an explicit whitelisted row key; decoys use `id`.
- Exact column type/unsigned/length and per-column charset/collation assertions
  are independent of production. Null and empty absent generation-expression
  metadata normalize to the same absence, not arbitrary expressions.
- Before the production guard, fixture calibration exercises both exact
  collations, decoy snapshot and seven independent drift detectors (type,
  unsigned, column collation, default, extra index, nullable, engine).
- The same drift fixtures must be rejected without creating an absent sibling;
  an incompatible quarantine plus missing details separately tests full-family
  preflight in the opposite order.
- Added absent/partial compatibility-false, exact 25-byte tables, a second
  database default and real v12 table/emptiness checks after CLI success.
- CLI invocation passes credentials through its environment, uses nonblocking
  bounded output, a monotonic 30-second deadline and finally termination,
  pipe closure and reap. The existing invalid-prefix CLI calibrates the helper
  before the missing production seam guard.
- Main database cleanup now requires successful owned CREATE; a failed CREATE
  cannot cause deletion of a pre-existing colliding database name.

## Fresh execution

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

All new pre-guard calibrations completed successfully. Post-guard assertions
remain prospective behavior tests; this record does not claim they executed
against nonexistent production or that full v12 has reached GREEN.

Read-only schema-name query matching the exact test naming grammar returned
`[]` after the run. Diff-check exited 0. Corrected test SHA-256:
`aa0a43e13818948de81ca877139f1b7c1cd07d539f73497169867169bfa8c3d3`.

No production/spec/planning/config/importer changes. Fresh independent Gate 3
required. Interruption/concurrency and composed-fixture amendments remain
separate unfinished required work; no full migration completion is asserted.
