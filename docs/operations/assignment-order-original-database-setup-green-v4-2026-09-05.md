# Assignment-order original database setup — binary fixture GREEN v4

Date: `2026-09-05`

Status: **GREEN / READY FOR FRESH INDEPENDENT GATE 5**.

Approved replacement RED: `73ef81e08ae77dd370625128552ae04e9fd7bb25`;
fresh Gate 3 v23 `APPROVED` review commit `8947ccd`.

Fixture validation now coerces every quoted expected/actual text comparison to
binary bytes. Capability alternatives are explicit binary equality branches.
Schema/database collation remains unchanged. Trailing-space, case/accent and
composed/decomposed Unicode drift are rejected before seed or cleanup mutation.

```text
php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK
php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK
make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
git diff --check
PASS (no output)
```

This is not Gate 5, command implementation, full verification or launch
readiness.
