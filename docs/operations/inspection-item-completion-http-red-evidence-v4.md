# INSPECTION-ITEM-COMPLETE-001 Gate 3 v4 regression evidence

Date: 2026-09-01

Test-only corrections requested by Gate 3 v4 are GREEN against the integrated
implementation; production and approved artifacts were not edited.

- The HTTP adapter test sends a malformed `item_completed`, observes exactly
  one resolution and recording call, asserts trusted actor `7301`, canonical
  resolved case `9512`, and uses the approved `InspectionItemCommandPolicy` to
  establish invalidity. It asserts only the approved `rejected/revision 0`
  mapping and does not freeze internal malformed-field sentinels.
- The focused MariaDB test applies canonical v1-v8 under a private prefix,
  adds an unexpected index to a required v8 table, and calls the public
  production factory. It observes `INSPECTION_SCHEMA_UNAVAILABLE/revision 0`.
  Exact table properties, ordered columns, ordered indexes, and all rows for
  revisions, operations, installer evidence, and photos are scalar-snapshotted
  before/after and remain identical, proving zero DDL repair and zero
  slice-owned business mutation. The receipt clock remains unused.

```text
php tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring

make test-env-up
php tests/InstallationProcess/inspection_item_complete_001_schema_drift_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 partial v8 schema drift
make test-env-down
```

```text
029936a0e06ee8b2dd4351e47b654be1fc771b13adde39fb3a409d99a01ee7df  tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
924358aa7468fc76d41de9f7217d7283704e2bd6b03390b1701576a11e02be69  tests/InstallationProcess/inspection_item_complete_001_schema_drift_test.php
```

The separate raw-HTTP endpoint-admission coverage remains explicitly
unresolved; no static, reflection, or duplicate-policy fake is claimed as an
endpoint test.
