# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker cleanup RED correction

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **CORRECTED INTENDED RED; fresh Gate 3 required**

The two-worker inner `finally` previously called `proc_get_status()` even after
`aoocFinish()` had already reaped the process with `proc_close()`. Cleanup now
closes any still-owned pipes, returns immediately when the process is no longer
a resource, and otherwise inspects it, terminates a live child, and calls
`proc_close()` to reap it. Thus successful already-reaped workers are harmless,
while exceptional live workers retain terminate-and-reap ownership.

A small pre-DB sensitivity fixture starts and closes a real child, then passes
the already-closed process value through the same cleanup helper. This executes
the guard even while the main concurrency RED stops later at a missing factory.
All database/resource ownership cleanup assertions remain unchanged.

Run from exact base `9284996` with the disposable test DB credential:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: predecessor seed complete; canonical production MariaDB/worker seam is missing: FMonitor2\AssignmentOrderOriginal\ProductionAssignmentOrderOriginalFactory
exit 255

$ git diff --check
PASS (no output)
```

The closed-process sensitivity and corrected seed complete before the intended
missing production seam. Production/spec/tasks/prior evidence are unchanged.
This is Gate 2 correction evidence, not GREEN or Gate 4; fresh Gate 3 is needed.
