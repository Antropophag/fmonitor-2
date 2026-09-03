# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — cleanup oracle correction RED v3

Date: 2026-09-04

```text
62c3ad3cf0ed8ebe18fc07009b41e799e61ecc3c17921e27adaaf746898aa2cf  tests/InstallationProcess/pilot_object_list_001_test.php
```

The test now performs one owned `scandir` of the foreign directory before its
baseline `lstat`. This primes Linux atime before the exact metadata snapshot;
the later verifier traversal can no longer make the preservation oracle fail on
its own first read. No metadata field or equality assertion was removed.

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_list_001_test.php
approved removal predecessor: no work item or root navigation destination
Expected: 0
Actual: 2
exit=255
```

The canonical object-list fixture/revoke tracer passes. Cleanup completes
without a secondary exception, leaving only the separately owned navigation
RED. Production and fixture implementation are unchanged.
