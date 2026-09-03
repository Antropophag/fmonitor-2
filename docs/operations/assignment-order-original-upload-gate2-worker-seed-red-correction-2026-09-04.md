# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker seed RED correction

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **CORRECTED INTENDED RED; fresh Gate 3 required**

## Correction

The two-worker MariaDB fixture omitted required
`fm2_order_installers.employed_from_snapshot`. Both independently specified
installer rows now store literal employment start date `2024-01-01`; their
`employed_to_snapshot` values are explicitly `NULL`, exercising the approved
nullable end-date contract. All other predecessor facts, commands, barrier
protocol and expected concurrency outcomes are unchanged.

The missing production-seam guard was moved after the canonical migration and
complete predecessor seed. This prevents a missing factory from hiding fixture
schema failures while retaining the same intended RED classification.

## Verification

Run in isolated worktree from exact production revision
`52295f2edf25b0dda63ca6a5bf3fe00be8747e04`:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_demo_local php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
mysqli_sql_exception: Access denied for user 'root'
exit 255
```

That first attempt is classified **SETUP FAILURE** and is not RED evidence.
The disposable test contour declares root password
`fmonitor2_test_root_local`; with that credential:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: predecessor seed complete; canonical production MariaDB/worker seam is missing: FMonitor2\AssignmentOrderOriginal\ProductionAssignmentOrderOriginalFactory
exit 255

$ git diff --check
PASS (no output)
```

Reaching the explicit `predecessor seed complete` guard proves the canonical
v12 migration, both corrected installer inserts (including nullable end dates),
and the remaining root/revision/request/fingerprint/event seed all completed.
The verifier's existing `finally` removed only its randomly named owned test
database/resource roots.

No production, specification, OpenSpec task or prior evidence was changed.
This is corrected Gate 2 evidence only, not GREEN or Gate 4. The changed test
requires a fresh independent Gate 3 review.
