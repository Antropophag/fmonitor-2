# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — committing fault RED

Date: `2026-09-05`

RED author: `/root/assignment_original_red2`

The worker source now advances one correction lineage through exact
COMMIT_UNKNOWN_UNAVAILABLE, its release-failure composite,
COMMIT_UNKNOWN_FOUND_RELEASE_FAILURE and committed CONTENT_LEASE_RELEASE.
Unknown-unavailable first returns retryable outcome unknown; found/committed
release failures preserve accepted. Every normal same-request retry must return
REPLAYED with exact durable revision ID/number.

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed
$ git diff --check
PASS (no output)
```

Task 4.1 remains open for concurrent identical/different worker races, their
lease-conflict/evidence assertions and maintenance/lease exclusion concurrency.

```text
e0dc5983b015cb5db6fa635ffd5de965fa292f64553a707bccb2c8178deb4d4e  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
```
