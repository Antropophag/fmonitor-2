# PRODUCTION-RUNTIME-READINESS-SCHEMA-001 — Gate 3 review

- Reviewer: `/root/runtime_tests`
- Test author: `/root/runtime_review`
- Reviewed artifact: `tests/Runtime/production_readiness_schema_001_test.php`
- SHA-256: `d87a4ca664530497a84860ad1452c75e3c367bd3d118852c638d891556c3073e`
- Verdict: **APPROVED**

The test exercises the public `bin/fmonitor2-runtime-check.php` seam with valid
prepared storage and two isolated canonical-v22 databases. Removing either the
object-detail table or original-audit table models a route-critical incomplete
schema. Exact exit/status JSON and empty stderr prevent an ambiguous readiness
result. The before/after information-schema table and column inventories plus an
ambient row prove that readiness remains read-only for each missing-table case.

Focused RED command:

```text
php tests/Runtime/production_readiness_schema_001_test.php
```

Observed exit: `255`. The first case expected exit `70` with
`{"ok":false,"reason":"SCHEMA_NOT_READY"}` but received exit `0` with
`{"ok":true}`. Fixture migration, storage preparation, and cleanup succeeded.

This is a focused family-inventory contract. It does not claim exhaustive drift
coverage inside every table; the canonical migration tests retain that ownership.

## Independent rereview of strengthened test

Reviewer: `/root`; test author remains `/root/runtime_review`. Verdict: **APPROVED**.
Reviewed SHA256: `75ca760da80c5960c93df418cf27b208d4a3bbf22c13cf106d3ce5ea13bfd6be`.
The revised test requires healthy public CLI success before deleting each route-critical table; an always-not-ready implementation cannot pass. It snapshots every synthetic table's rows plus schema metadata, so the failure path cannot repair schema or alter history silently. Setup uses complete canonical v22 and valid prepared private storage; each database/path is task-owned and cleaned.
Root reran `php tests/Runtime/production_readiness_schema_001_test.php` before implementation: exit255, missing object detail expected70/SCHEMA_NOT_READY versus actual0/ok. Healthy control had already passed. This proves the intended missing readiness coverage, not broken setup. The second original-audit case is retained as an independent barrier. Root did not author the test; production changes may now implement the reviewed missing-family checks. Full runtime/browser acceptance remains separate.
