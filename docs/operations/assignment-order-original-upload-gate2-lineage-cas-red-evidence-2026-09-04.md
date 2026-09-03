# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — lineage/CAS RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **INTENDED RED; task 2.2 remains open**

The test-owned repository now retains an append-only commit lineage and exposes
current/root/contains-revision reads. The public-seam verifier establishes
revision 1 and revision 2, then independently expects `STALE_REVISION`,
`TARGET_NOT_FOUND`, and `TARGET_NOT_CURRENT` before stream read, with exact
non-retryable `CONFLICT` results and byte-for-byte unchanged prior commit
evidence.

CAS-loser simulations exercise the required post-conflict rereads: an identical
winner is discoverable by accepted fingerprint and maps to `REPLAYED`; a
different winner advances current lineage and maps to
`CONFLICT/STALE_REVISION`. Both preserve prior revisions. The deterministic
five-FD/two-process MariaDB overlap proof remains a separate pending subset, so
task 2.2 stays open. Production code is unchanged and Gate 3 has not started.

```text
$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
$ php tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication
exit 255
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
$ git diff --check
PASS (no output)
```

This is intended RED rather than setup failure: both PHP files lint and the run
stops only at the missing canonical production application seam before any
external dependency.
