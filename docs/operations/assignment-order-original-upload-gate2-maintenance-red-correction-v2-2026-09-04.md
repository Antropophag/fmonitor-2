# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — maintenance RED correction v2

Date: 2026-09-04

RED author: `/root/original_upload_red`

Verdict: **CORRECTED INTENDED RED; fresh Gate 3 required**

## Correction

The previous maintenance oracle grouped a cutoff newer than `now - 3600s`
with scalar-shape rejection and expected zero dependency calls. The approved v4
precedence is instead: scalar shape, exact authorization, terminal request
lookup, then clock/cutoff validation.

The corrected test keeps invalid UUID, batch limits `0` and `1001`, and a
noncanonical cursor in the scalar-invalid group with zero dependency calls. It
moves cutoff `2026-09-02T08:15:31Z` into its own case against clock
`2026-09-02T09:15:30Z` and requires exactly:

```text
authorize:system:original-orphan-reconciler:assignment_order.original.storage.reconcile
request:00000000-0000-4000-8000-000000000101
clock
```

The result remains exact non-retryable `REJECTED/INVALID_COMMAND` with zero
counts and null cursor. The complete call-list assertion also proves no
candidate enumeration, digest lock, reference lookup, delete or result/audit
commit occurs.

Only the test oracle changed. Production, specs, tasks and prior append-only
evidence were not edited. The changed test requires fresh independent Gate 3.

## Corrected intended RED

Run in isolated worktree
`/home/antropophag/code/fmonitor-2-original-upload-maintenance-red` on exact
pre-maintenance revision `c46c4f382fc4c6ef84fb57c2b35cd343a9717af7`:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical maintenance public seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMaintenanceApplication
exit 255

$ git diff --check
PASS (no output)
```

This is intended RED, not setup failure: PHP parsing succeeds and execution
stops only at the explicit missing canonical maintenance application seam.
No GREEN or Gate 4 claim is made.
