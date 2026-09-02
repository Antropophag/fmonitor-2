# INSPECTION-ITEM-COMPLETE-001 Gate 3 v6 regression evidence

Date: 2026-09-01

Test-only v6 corrections are GREEN; production and approved artifacts were not
edited.

The malformed HTTP contour starts from the otherwise-valid approved literal
envelope and changes only `deviceInstallationId` to `not-a-uuid`. The recording
spy observes that exact raw value, trusted actor `7301`, canonical resolved case
`9512`, one resolver call and one recording call. Its `INVALID_COMMAND` result
maps exactly to `{status: rejected, revision: 0}`. No production policy helper
is used as a test oracle.

The MariaDB drift contour snapshots exact `SHOW CREATE TABLE` output and all
ordered rows for each of the four v8 tables before and after the public factory
call. This includes the complete engine/charset/collation, columns/defaults,
generated expressions, indexes, foreign keys/checks and table options such as
`AUTO_INCREMENT`. The snapshot remains byte/value-identical while the result is
`INSPECTION_SCHEMA_UNAVAILABLE/revision 0`, proving zero DDL repair and zero
slice-owned mutation.

```text
php tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring

make test-env-up
php tests/InstallationProcess/inspection_item_complete_001_schema_drift_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 partial v8 schema drift
make test-env-down
```

```text
9f9351c5b90210f43f2f4eda87dc0f669fd421123cd5c9b9b8bb877d9bc63abc  tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
535dedbcbc0b67d016cd8b2e7fbc3f69b0f75b3e456ff8c8364bee8b077cbd17  tests/InstallationProcess/inspection_item_complete_001_schema_drift_test.php
```

Test Compose was stopped with volumes and orphans removed after verification.
