# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — maintenance RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red_maintenance`

Verdict: **INTENDED RED; task 2.2 remains open**

## Covered non-MariaDB subset

The verifier calls only the approved public maintenance seam and loads its
in-memory support fixture only after proving that the canonical application
interface, command DTO and verification factory exist. The prepared matrix
covers scalar validation before any dependency call, exact string-principal
authorization, terminal request replay before storage, bounded page/canonical
cursor forwarding, and binary-order fixture candidates.

Per candidate it fixes the exact lock/reference/delete/release call sequence.
It distinguishes digest lock `OK`, `LOCKED` and `FAILED`; reference `FOUND`
true/false and `UNAVAILABLE`; delete `OK`, already absent and `FAILED`. It
expects completed referenced retention and deletion counts, retryable
`PARTIAL/LOCKED`, retryable `PARTIAL/STORAGE_FAILURE`, and request-repository
`FAILED/PERSISTENCE_FAILURE`. The count invariant is asserted in every exact
result tuple. A second request proves already-absent deletion is successful
while the physical delete count remains one; same-request replay proves zero
candidate, lock, reference and delete calls.

The atomic maintenance commit is checked against the complete safe public
property allowlist and exact system principal. No path, digest, candidate
identity, exception, SQL or document data is admitted to that DTO. Production
code and OpenSpec tasks are unchanged. The separately recorded MariaDB/five-FD
constructibility blocker remains active, so task 2.2 is not complete and Gate 3
has not started.

## Verification transcript

```text
$ php -l tests/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalMaintenanceEnvironment.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
$ php tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical maintenance public seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceApplication
exit 255
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
$ git diff --check
PASS (no output)
```

This is intended RED rather than fixture failure: both files lint, and runtime
stops at the explicit missing canonical maintenance seam before loading the
support fixture or touching storage/repository dependencies.
