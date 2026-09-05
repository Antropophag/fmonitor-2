# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker barrier V52 RED

Date: `2026-09-05`

RED author: `/root/assignment_original_red2`

Retained worker config now includes final exact `barrierEvent` key with
`after_fingerprint_miss_before_cas`; invalid value follows exact pre-secret exit
70 channels. All prior transport/fault cases remain source-valid and fail only
on the absent worker bootstrap.

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed
$ git diff --check
PASS (no output)
```

Task 4.1 remains open pending the fresh isolated identical/different and
after-private-finalize maintenance/lease race harness.

```text
c9e5c0704cff92f0e5440b4b2bcb25cdd6088188efeaaa6a04c2d1ef50e99155  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
```
