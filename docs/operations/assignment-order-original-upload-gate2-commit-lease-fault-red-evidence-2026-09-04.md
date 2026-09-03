# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — commit/lease fault RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red_maintenance`

Verdict: **INTENDED RED; task 2.2 remains open**

## Covered implementation-independent subset

The existing public-seam in-memory environment now exposes observations for
accepted-commit calls, terminal/fingerprint/lineage reads, content-lease state,
release attempts, delivery, safe logs and storage recovery ownership. Defaults
remain unchanged for every earlier RED verifier. No production type or behavior
was added.

The new verifier covers definite rollback as `FAILED/PERSISTENCE_FAILURE`, an
`OUTCOME_UNKNOWN` commit resolved by a fresh same-request lookup as found,
reliably absent or unavailable, and the exact distinction between
`PERSISTENCE_FAILURE` and `PERSISTENCE_OUTCOME_UNKNOWN`. It proves that the
lease remains held through commit and the resolving lookup, then is released
exactly once. A reliably absent outcome retries the same request and permits
one new commit attempt with at most one accepted fact and one terminal result.

A verifier delivery fault simulates response loss after durable commit. The
same request then returns `REPLAYED` before reading a deliberately invalid
replacement stream and without another commit or revision.

Typed `FAILED` and thrown lease-release failures are exercised after committed,
rolled-back, unknown-found, unknown-not-found, unknown-unavailable and both
identical/different CAS-conflict resolutions. They preserve the already selected
accepted, replayed, stale-conflict or persistence result. The storage recovery
token remains owned, each failure produces exactly one stable log with only
`correlation_id` and `phase`, and the test rejects content identity, filename,
path and exception detail. CAS tests require both fingerprint and current-lineage
rereads while the lease is held; a non-replay conflict attempts release before
its required terminal result/audit commit.

Rollback and reliably absent failures expose no accepted evidence or terminal
result and leave the process snapshot unchanged; finalized bytes remain private
storage recovery material rather than a public/domain original. Production code
and OpenSpec tasks are unchanged. The MariaDB/five-FD acceptance subset remains
separately blocked, so task 2.2 is not complete and Gate 3 has not started.

## Verification transcript

```text
$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
$ php tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication
exit 255
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
$ git diff --check
PASS (no output)
```

This is intended RED rather than broken setup: both PHP files lint and execution
stops at the explicit canonical application guard before loading the support
fixture or invoking storage/repository dependencies.
