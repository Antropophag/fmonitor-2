# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker command RED part 3

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Outcome: **INTENDED RED — worker bootstrap absent; task 4.1 remains open**

The real-worker source now runs every non-committing initial fault before normal
acceptance: stream read, stage begin/write, private finalize, accepted-candidate
stage/stream close, definite rollback, rollback+release failure, unknown
NOT_FOUND and unknown-NOT_FOUND+release failure. Each requires exact retryable
Result and leaves the same request available. Correction acceptance is exercised
through the V29 `COMMIT_UNKNOWN_FOUND` script and a normal retry must be exact
REPLAYED. Existing DSN/config, Result publisher, replay/no-change/target matrices
remain.

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed
$ git diff --check
PASS (no output)
```

This is additive evidence, not completion: concurrent workers, remaining
committing unknown/release cases, abort/log and maintenance fault/race variants
remain before task 4.1 can be checked.

```text
73e71d94e3ce82486212f37527c7edc14e6b239a62bfaf0af94aebd02141ac25  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
b12f3c88762ae59b298fca26b0942f223e95a7b2914398e7e0959d804a2eb845  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
9e96582344226df950df4f5f4c7937bc176ee2469ff7969a9790dfbcbe766e7f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```
