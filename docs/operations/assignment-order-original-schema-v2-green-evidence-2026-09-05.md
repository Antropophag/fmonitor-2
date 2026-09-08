# Assignment-order original schema-v2 GREEN evidence — 2026-09-05

Approved executable-spec frontier:

- v54 Gate 1: `390f5629cd1a39da7d39b414b0ca6ad0419d3234`; approval record `54e7cb2`.
- schema-v2 RED: `0393c3c89d89ee4f196d75e6938ec90b25398755`; hash correction `3d1f9684f7d55869becaf6e8a835f82392a442fb`.
- original Gate 3: `b65dd92e5de71e673bbb156185402216474dfb4d`.
- fixture corrections: `becc0ab19cc8271529820a2a720e91bc379d3ff9`, `a9b1e056a6ec7c44da17b845c1696fc5842ba101`.
- fresh amendment Gate 3: `d939546e54e86dcc559f80dd97c72e63c4a9992a`.

Minimal production change advances the original schema result to version 2,
creates the exact named non-unique `idx_aoou_revision_content`, recognizes the
exact historical v1 unique predecessor, upgrades it atomically without row
rewrites, and preserves capability-last publication and fail-closed drift.

Commands run from `/Users/antropophag/code/fmonitor-2` on 2026-09-05:

```text
$ php tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_V2_001_OK

$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK
```

`make architecture-check` was also run. It reported only 25 new violations in
untracked partial command-slice files (`AssignmentOrderOriginalRuntime.php`,
`MaintenanceService.php`, and `MariaDbRuntimeRepository.php`), not in this
schema-v2 implementation. Those real failures remain classified and must be
removed in command task 5; they are not skipped or accepted as GREEN.

Fresh independent schema-v2 Gate 5 remains required before command task 5 may
resume.
