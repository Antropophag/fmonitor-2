# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — Gate 4 GREEN attempt

Date: 2026-09-03

После независимого Gate 3 `APPROVED` минимальный test-owned fixture заменил
generic role `900018` для actor 18 на canonical active role `5101`, assignment
и byte-exact permission `objects.read`. Production authorization не менялась.

```text
php -l tests/Support/PilotObjectReadRbacFixture.php
No syntax errors detected in tests/Support/PilotObjectReadRbacFixture.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_list_001_test.php
TestFailure: approved removal predecessor: no work item or root navigation destination
Expected: 0
Actual: 2
```

Собственный canonical-role revoke tracer и positive actor admission прошли:
исполнение достигло следующего независимо owned navigation predecessor. Это не
полный GREEN среза; tasks 3.1–4.3 остаются открыты до navigation GREEN и полного
выполнения RBAC matrix.
