# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — repository/replay RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **INTENDED RED; task 2.2 remains open**

## Covered subset

The repository double now creates stored accepted results only from the typed
`AssignmentOrderOriginalAcceptedCommit`; adapters cannot self-attest success.
The public-seam verifier covers exact initial commit event/revision invariants,
same-request terminal replay with zero replacement-stream reads and identical
evidence, cross-request semantic-fingerprint replay, reason-only `NO_CHANGES`,
same-bytes/new-date revision 2, composition-drift `SEMANTIC_COLLISION` before
stream, and exact non-retryable authorization denial.

The safe attempt commit is checked by its complete public property set. It has
no filename, path, bytes, composition members/names, correction reason, SQL,
exception or parser detail.

MariaDB CAS/five-FD concurrency, maintenance, commit ambiguity/response loss,
and lease-release faults remain pending. Task 2.2 stays open; Gate 3 has not
started and production code is unchanged.

## Verification transcript

```text
$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
$ php tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication
exit 255
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
$ git diff --check
PASS (no output)
```

This is intended RED, not setup failure: both PHP files lint and execution stops
at the explicit missing production application seam before external resources.
