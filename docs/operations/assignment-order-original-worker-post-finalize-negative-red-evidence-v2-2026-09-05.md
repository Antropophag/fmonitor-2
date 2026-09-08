# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — worker post-finalize negative RED v2

- Date: `2026-09-05`
- RED author: `Codex agent /root/command_gate5_red_domain`
- Supersedes only the worker-harness evidence from the prior append-only record;
  neither earlier record is rewritten.
- Prior test commit: `1e56249ea3c16b9e3f50b787279f6a679a6ebed4`
- Fresh Gate 3 harness findings: bounded channel collection, bounded teardown,
  and exact successful child-exit proof were required.

## Corrected executable identity

```text
84cd4b2a8a8cf751eb461de1fc1a48f042194489e8bd0dde045ba25c1043dea0  tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
```

Every observed child channel (`READY`, result, stdout and stderr) is now
nonblocking. One three-second loop drains all four channels, releases only an
observed broken READY, and captures the child exit status at the transition to
not-running. A corrected worker must produce the exact authorization-denied
result, empty READY/stdout/stderr and exit `0`.

The `finally` path is independently bounded: it closes inherited endpoints,
sends SIGTERM, waits at most 500 ms, then sends SIGKILL if still running, waits
at most another 500 ms and always calls `proc_close()` to reap the child. Thus a
corrected worker which emits neither READY nor immediate EOF cannot hang the
test, while the production fabrication remains the first behavioral RED.

## Reproduced intended RED

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
```

Exact first mismatch is unchanged:

```text
Unauthorized command emits no post-finalize READY because no actual finalize lifecycle event occurred.
Expected: ''
Actual:   'READY 00000000-0000-4000-8000-000000000560\n'
```

The command terminates in under one second on the reviewed broken worker. PHP
lint and `git diff --check` pass. This remains Gate 2 evidence only; the exact
corrected bytes require a fresh independent Gate 3 review.
