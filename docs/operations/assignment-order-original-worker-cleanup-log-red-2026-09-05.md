# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker cleanup-log RED

Date: `2026-09-05`

RED author: `/root/assignment_original_red2`

The worker transport source now executes requests 301–303 with real malformed
PDF inspection and STAGE_ABORT, STAGE_CLOSE and STREAM_CLOSE faults. Each keeps
exact `REJECTED/INVALID_PDF`, records the independently SHA-derived correlation,
exact event/phase/sequence through the fresh evidence reader, then proves same-
request replay adds no cleanup log.

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed
$ git diff --check
PASS (no output)
```

Task 4.1 remains open pending concurrent worker races and remaining committing
unknown/release branches.

```text
1f8f84aa155549b091c8a94d2867fb72d7d9009e2a0d512144ac2efe60fb3973  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
```
