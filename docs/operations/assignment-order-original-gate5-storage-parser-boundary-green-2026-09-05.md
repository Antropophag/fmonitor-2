# Assignment-order original Gate 4 storage/parser/boundary GREEN

- Date: `2026-09-05`
- Scope: Gate 5 corrective findings 1, 2 and 7 only
- Independent Gate 3 approval: `8231b8abbe2306dde00ba27eaa6a9b095f71edaa`
- Corrective RED frontier: `3590010cb253cf4b0e09a5c0f2382bf1e8d881cb`
- RED evidence frontier: `62e07f9`
- Integrated production baseline: `87d3bd33063cd063b2202c92c3f5d54ad83db849`
- Gate 4 author: Codex agent `/root/command_gate5_storage_green`
- Outcome: `GREEN`; fresh independent Gate 5 review remains required.

## Exact production artifacts

```text
7a6f693c0fda3cfa19c5d693373f07460549f27ab834ce8b411c677bb60e13bf  app/AssignmentOrderOriginal/AssignmentOrderOriginalFileStorage.php
39d3c88b82c9f022e3d1a6b1946ee98e5914af8e1c4e6d572ac372352b66a57a  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
c4ad03071c4b65d5796d72dac6b6d55e4f97e1ce05e9484976ef891547aabdcf  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
8a7e9a58199afbebaf2d2e82ca758bbeb4f9d44909ff5fbc953a6c6432b6e634  app/AssignmentOrderOriginal/MariaDbMaintenanceService.php
```

`Runtime.php` changes are limited to existing-owned `0600`, owner and symlink
safe-log construction checks. `MariaDbAssignmentOrderOriginalEvidence.php`
only removes the superseded inline parser so the owned class is the single
production implementation. The integrated domain correction at `87d3bd3` is
otherwise preserved.

## Exact executable artifacts exercised

```text
8b162fe38a8df31637eebf317392419ebb94e9ec54205898242bf418d4a26b25  tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
10f29221e3576e159b4fed2342591eb45138f9e22352ad4b91851df5f8f5c2dd  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
f47a57ea01f37f6bb30b6a09770f2b22629156f67edca9b68ecb6bfc68131c61  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

## Literal verification

```text
ASSIGNMENT_ORDER_ORIGINAL_PRIVATE_BYTES_RESTART_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
ASSIGNMENT_ORDER_ORIGINAL_PRODUCTION_BOUNDARY_OK
ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_OK
ASSIGNMENT_ORDER_ORIGINAL_LEASE_RACE_OK
ARCHITECTURE CHECK PASSED (7 rules)
```

All five changed PHP files pass `php -l`; `git diff --check` exits `0`.

The broader assignment-order-original run also passed every suite through
`ASSIGNMENT_ORDER_ORIGINAL_WORKER_PROTOCOL_OK`. Its final worker-transport test
failed on the separately changing fingerprint-barrier expectation (`REPLAYED`
expected, `CONFLICT/stale_revision` actual). That test is outside this approved
correction scope and was already under a separate Gate 3 correction; it was not
changed or reclassified here.
