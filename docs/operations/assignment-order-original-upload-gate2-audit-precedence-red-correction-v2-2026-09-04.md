# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — audit precedence RED correction v2

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **CORRECTED INTENDED RED; fresh Gate 3 required**

## Correction

The earlier oracle expected one cumulative lease release after an accepted
initial upload followed by a correction CAS conflict whose lease release
throws. The environment counter is intentionally cumulative: the accepted
initial operation releases once, and the conflict operation attempts its own
release once. The exact expected pair is therefore `[2, 1]`: two cumulative
release calls and one safe release-failure log.

Only this assertion/message changed. It still proves per-operation
exactly-once release while preserving the selected conflict result and safe-log
behavior. Production code, OpenSpec tasks and prior evidence were not changed.
Task 2.2 remains open and this test correction invalidates the prior Gate 3
review for the changed test.

## Corrected RED on pre-implementation revision

A detached task worktree under
`/home/antropophag/code/fmonitor-2-original-upload-audit-red` used exact revision
`921cbafdcf394d567be3e4aa6680baeec99e0427`. Applying only the corrected test
assertion produced:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication
exit 255

$ git diff --check
PASS (no output)
```

This is the intended pre-implementation failure, not a setup failure.

## Current diagnostic only

On exact current production revision
`da45b5eee01a6a120054d3d08238512cc3466e6b` with the corrected assertion:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUDIT_PRECEDENCE_OK
exit 0

$ git diff --check
PASS (no output)
```

This GREEN result is diagnostic evidence for the corrected oracle only. It is
not a Gate 4 claim, integration verdict or Gate 3 approval.
