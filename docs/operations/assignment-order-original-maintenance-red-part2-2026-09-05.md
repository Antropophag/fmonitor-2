# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — maintenance RED part 2

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Outcome: **INTENDED RED — orphan fixture factory absent; task 4.1 remains open**

The real maintenance verifier now covers the V36 canonical two-orphan delete,
same-request replay, exact blob/request/audit evidence; V38 batch-limit-1 content
then stage pagination with exact cursor and deleted-position replay; and
one-shot DIGEST_LOCK, ORPHAN_REFERENCE_LOOKUP and ORPHAN_DELETE partial
accounting followed by normal convergent cleanup.

```text
$ php -l tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
No syntax errors detected
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalPrivateOrphanFixtureFactory seam is absent.
RED_ASSERTION: expected failing behavior observed
$ independent SCHEMATA query
NO_AOOU_DATABASE_LEAKS
$ git diff --check
PASS (no output)
```

This is additive evidence, not task completion. Concurrent maintenance/lease
exclusion and remaining worker fault/race cases remain.

```text
abf201b13c2cb1db0a3c66118fb04dd878614ba91b33765de3045980586cc8f7  tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
b12f3c88762ae59b298fca26b0942f223e95a7b2914398e7e0959d804a2eb845  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
9e96582344226df950df4f5f4c7937bc176ee2469ff7969a9790dfbcbe766e7f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```
