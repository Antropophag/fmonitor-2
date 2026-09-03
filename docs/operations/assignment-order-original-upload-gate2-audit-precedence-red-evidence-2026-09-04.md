# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — audit precedence RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red_maintenance`

Verdict: **INTENDED RED; task 2.2 remains open**

This append-only Gate 2 record completes the implementation-independent audit
failure precedence matrix. A valid-shape authorization denial whose atomic
terminal-result/safe-audit transaction rolls back must return retryable
`FAILED/PERSISTENCE_FAILURE`, perform no stream read and leave no terminal
result. A retryable storage failure whose best-effort audit transaction also
rolls back must preserve the original `FAILED/STORAGE_FAILURE` and likewise
must not become a terminal request hit.

The CAS branch independently selects a different-winner
`CONFLICT/STALE_REVISION`, injects a lease-release Throwable, and then rolls back
the mandatory conflict terminal-result/audit transaction. The final outcome is
`FAILED/PERSISTENCE_FAILURE`; release is still attempted exactly once before
the audit transaction and its failure is safe-logged exactly once. This proves
that release failure does not skip conflict audit, while audit atomicity retains
its specified precedence.

The fixture change only makes the test-owned attempt-commit outcome injectable;
its default remains `COMMITTED`, so prior RED cases retain their behavior.
Production and OpenSpec task state are untouched. The separately recorded
MariaDB/five-FD constructibility blocker remains, task 2.2 stays open, and Gate
3 has not started.

```text
$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
$ php tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication
exit 255
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
$ git diff --check
PASS (no output)
```

Both PHP files lint and execution stops only at the explicit missing canonical
application seam before the support fixture or any external dependency loads.
