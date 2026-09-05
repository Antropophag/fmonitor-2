# Assignment-order original schema-v2 Gate 3 amendment evidence — 2026-09-05

Append-only correction record for `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54.

- Approved RED test commit: `0393c3c89d89ee4f196d75e6938ec90b25398755`.
- Approved Gate 3 review commit: `b65dd92e5de71e673bbb156185402216474dfb4d`.
- First implementation run failed before exercising product behavior because mysqlnd returned `information_schema.STATISTICS.NON_UNIQUE` as an integer (`1`) while the test expected the string (`'1'`). The same fixture assertion expected string (`'0'`) for a unique index.
- The executable assertions are amended to preserve the exact semantic contract while matching the driver-native result type: integer `0` for unique and integer `1` for non-unique.
- No product behavior, workflow, schema shape, public contract, or production code requirement is changed by this amendment.
- A fresh independent Gate 3 review is required before this amended executable spec can authorize GREEN evidence.
