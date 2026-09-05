# Assignment-order original schema-v2 Gate 5 correction GREEN — 2026-09-05

Append-only follow-up to Gate 5 `CHANGES_REQUESTED` review
`9a8e288569edd042887a1aed81bde3864aa9300b`.

- Corrective RED commit: `781f543687eaa467f0a0e89d1c17e67f30bbd6f7`.
- Fresh corrective Gate 3: `7b0894bfcc6ff1479e4e56538971f598f6ce1256`.
- The clean revisions DDL now declares `idx_aoou_revision_content` directly;
  the separate index rename path was removed.
- Capability classification now requires the normalized whole CHECK expression
  to be exactly a `capability IN (...)` expression. A trailing `AND 1=1` is
  classified as conflict before any original-schema DDL.

Corrective GREEN transcript:

```text
$ php tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_V2_001_OK

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK

$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK
```

Fresh independent Gate 5 is required on the resulting exact implementation SHA.
