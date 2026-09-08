# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — executable RED после predecessor correction

- Date: `2026-09-04`
- Gate: `2`
- Corrected test head: `d76c16d866f442e804119484d0b0e87f6dc78258`
- Fresh independent Gate 3: `561c725eb1c65e94d04f3fa0ee1f86e2ef399dd1`, `APPROVED`
- Production changes at reproduction: none

После установки Docker focused tests исполнены в disposable PHP 8.5 container
с `mysqli`/`pcntl`, mounted current checkout и read-only public `../shlz-ui`,
против disposable MariaDB из `compose.test.yaml`.

```text
$ php -l tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
No syntax errors detected in tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php

$ php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ php tests/InstallationProcess/pilot_object_list_001_test.php
Fatal error: Uncaught TestFailure: CSS unavailable after healthy local authorization and before list read status
Expected: 503
Actual: 200
... tests/InstallationProcess/pilot_object_list_001_test.php(298): polError(...)
exit 255
```

Это qualifying current RED, не setup failure: stale predecessor теперь GREEN,
а unchanged approved integration verifier падает на первой ожидаемой production
границе. Downstream query-ignorance и exact 500/501 ceiling остаются следующими
утверждениями того же approved test.
