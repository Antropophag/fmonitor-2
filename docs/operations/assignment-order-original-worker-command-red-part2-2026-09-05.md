# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker command RED part 2

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Outcome: **INTENDED RED — worker bootstrap absent; task 4.1 remains open**

The four-SOCK_STREAM worker verifier now additionally asserts exact same-request
replay, every V31 Result publisher failure including seven-byte short prefix and
normal replay recovery, accepted correction revision 2, correction same-request
replay, reason-only `NO_CHANGES`, and independently distinct stale, target-not-
found and target-not-current corrections. Canonical Result lines use V24/V27
ordering and V42 composition-derived setup.

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed
$ independent SCHEMATA query
NO_AOOU_DATABASE_LEAKS
$ git diff --check
PASS (no output)
```

This is additive evidence, not task completion. Identical/different concurrent
workers and remaining repository/storage/lease fault scenarios remain required.

```text
4c0fddb67b3aea8216b6adb923f3f442ec6e5f12a1fcad79ed5b0076d70bac35  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
b12f3c88762ae59b298fca26b0942f223e95a7b2914398e7e0959d804a2eb845  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
9e96582344226df950df4f5f4c7937bc176ee2469ff7969a9790dfbcbe766e7f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```
