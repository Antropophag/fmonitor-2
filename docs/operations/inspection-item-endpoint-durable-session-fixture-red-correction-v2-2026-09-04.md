# Inspection item endpoint — durable session fixture RED correction v2

Date: 2026-09-04

RED author: `/root/original_upload_red`

Lineage: `2ae633c` plus independent review `e9fbc32`

Verdict: **OWNERSHIP ORACLE CORRECTED; next admission RED preserved; fresh Gate 3 required**

## Correction

V1 passed the `sessions` directory itself as `FMONITOR_SESSION_STATE_ROOT`,
which would make the canonical adapter manage `sessions/sessions/<instance>`.
V2 passes the validated task root and observes only the exact managed path
`<state-root>/sessions/<unique-instance>`.

The lstat-based observer requires state-root descendants `sessions` and the
unique instance to be current-euid real directories, never symlinks, mode 0700,
with no sibling under `sessions`. Every instance entry must be current-euid,
non-symlink, regular mode 0600 and match exactly one approved committed, lock,
stage or revoked filename grammar. It rejects unexpected directories,
dangling/file/directory symlinks, FIFO, Unix socket, unexpected filename and a
symlink-based path escape. It enumerates every directory entry with `scandir`
and `lstat`; non-files cannot disappear from observation.

Each adversarial fixture must fail before cleanup. The existing ownership-safe
recursive cleanup now unlinks symlinks before testing directory type, then each
probe asserts its task-owned root is absent. The main final cleanup and root
absence proof remain unchanged.

Allowed mutation is narrowly separated: checklist tables and non-session
artifacts remain byte-identical; the exact managed session instance starts
empty and may gain only grammar-valid files through CSRF/session operations.

## Verification

```text
$ php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
exit 255

$ git diff --check
PASS (no output)
```

All ownership sensitivities, GET/CSRF and durable session assertions pass before
the preserved endpoint admission RED. Production and product assertions were
not changed. Fresh independent Gate 3 is required.
