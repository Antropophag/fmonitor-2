# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 parity/auth RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **INTENDED RED; task 2.2 remains open**

## Covered subset

The new public-seam verifier independently fixes the same document date,
upload time, PDF digest and byte size for direct and post-template initial
upload. Template provenance is deliberately absent from the command and cannot
change accepted evidence. It also denies role-name-only and the adjacent
capabilities `assignment_order.prepare`,
`assignment_order.confirm_registration`, and `installation.open`, proving each
denial precedes the first stream read.

This is only the first task 2.2 increment. Owned-parser adversaries,
stage/chunk/abort events, typed commit/audit and retry matrices, MariaDB CAS,
five-FD worker concurrency, maintenance, response-loss, and lease-release
faults remain pending. Therefore the OpenSpec checkbox is unchanged and no
Gate 3 verdict is claimed.

## Transcript

```text
$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication
exit 255

$ git diff --check
PASS (no output)
```

The failure is not setup-related: both verifier files lint, no DB/network/filesystem
dependency is reached, and execution stops at the explicit canonical production
application seam guard. Production code was not changed.
