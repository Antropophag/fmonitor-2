# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — storage close safe-log Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved FD base: `623f65a73cfc76310e70c8307612c954dd41e862`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

The fault matrix includes stream/stage close and abort failures. A close failure
after committed acceptance must preserve the stored Result and write an
operational safe log; abort failure likewise leaves a private stage for bounded
cleanup and safe log. Unlike content-lease release failure, the contract defines
no exact safe-log event name, phase value, correlation fields or serialized line
for these branches.

The evidence reader returns a closed exact `aoou-logs-v1` shape. Gate 2 can
assert no sensitive data, but cannot independently assert which log item must
exist, distinguish stream close from stage close/abort, or detect an
implementation that omits the required log. Inventing names would change the
safe operational contract.

Smallest amendment: publish exact safe-log events and safeFields for
pre/post-commit stream close, stage close and abort failure, including one
canonical evidence JSON example, correlation grammar and exact-once rules.

Task 4.1 remains unchecked. The retained worker transport RED now uses four
separate AF_UNIX/SOCK_STREAM socketpairs mapped to FDs 3–6 with parent/child
endpoint closure and bounded process cleanup; it still fails on the absent
worker bootstrap. No production, specification or OpenSpec artifact changed.

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed
$ independent SCHEMATA query
NO_AOOU_DATABASE_LEAKS
```

```text
90cd3472fbab143e9c3dc6d9ac7110468fae4cffad7a7e30889de35b9b4273b2  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d31611dddc1039a70083db1f86bb2faa160a5196fd3b18abb1977c7345163f6c  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
75cfbc7308db439dfeab89b056defad4d35532ffc0fc3ada5bda0baf5551f1bb  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
a707b2db6307e7376ecfab2f00b593383d7523aef54f919e4a92005059e5fba4  tests/Support/assignment_order_original_worker_entry.php
```
