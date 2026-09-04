# Inspection-schedule characterization process-group portability

Date: `2026-09-04`

Initial independent review: `9da2276` (`NEEDS_CHANGES`).

## RED

The characterization harness launched both server and verifier through GNU
`setsid --wait` and checked reaping through Linux `/proc`. Native macOS has no
such executable or procfs, so the test failed before the public HTTP behavior.
The unchanged Linux run ended with the exact `CHARACTERIZATION_OK` transcript.

## Corrected Gate 2 candidate

Commit: `c5292433fafdffe4741e0ea9de72c07fbe62c655`

```text
7483f6e72df9f24ee117591c49c5cbe9b901b22634a6a2caec6f72a32112e4c0  tests/Support/process_group_exec.php
cd19902e847c1a04a2dd4e3e4364779ff186ab6d1c70fba176303e54b5b5ebf3  tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
```

The task-owned PHP wrapper calls `posix_setsid()`, publishes `READY <pid>` on a
dedicated FD, waits for exact parent `RELEASE <pid>`, then replaces itself with
the exact command through `pcntl_exec()`. The parent releases only after
boundedly proving `PGID == PID`. This closes the fast-child race and ensures
negative-PGID TERM/KILL can target only the owned group.

Reaping is checked portably with `posix_kill(pid, 0) == false` after confirmed
non-running state and `proc_close`. Controls prove normal TERM timeout,
TERM-resistant KILL, fixed missing-argv and invalid-exec failures, and rejection
plus positive-PID cleanup of a launcher that did not establish its own group.

Both complete matrices produced:

```text
INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact
INSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0
INSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001
```

This was repeated on native macOS arm64 and Linux arm64 in a disposable owned
workspace under a non-root user. PHP lint and diff-check pass. Production,
specification and expected behavior bytes are unchanged. Fresh Gate 3 remains
required.
