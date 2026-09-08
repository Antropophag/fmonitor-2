# Assignment-order original schema-v2 quote-aware GREEN — 2026-09-05

Append-only response to Gate 5 `CHANGES_REQUESTED` review
`0e9c96a25d2f6533c6051c5041ae40aa77108e7e`.

- Uppercase-literal RED: `595f9ed5374842f3708070d43c8176abda916235`.
- Fresh Gate 3: `c5c6c553fc1c9e942024f439bcb4a37a25388d98`.
- Production normalization now folds SQL syntax outside quoted strings only;
  quoted capability literal bytes remain case-sensitive and exact.

```text
$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK

$ php tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_V2_001_OK
```

Fresh independent Gate 5 remains required on the resulting exact SHA.
