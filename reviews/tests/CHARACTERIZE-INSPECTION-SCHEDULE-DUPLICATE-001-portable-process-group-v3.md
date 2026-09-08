# CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 portable process group — Gate 3 v3

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_evidence_gate1`
- Reviewed test commit: `3b37158990f5ccb672e22ec05e95c973b1924d0a`
- Prior review: `323b7113edf9c34bcab0c94d49bed580eedca5ac`
- Wrapper SHA-256: `7483f6e72df9f24ee117591c49c5cbe9b901b22634a6a2caec6f72a32112e4c0`
- Test SHA-256: `3dac0b1f98911284aa276cc38014fb5c328304ab9e6e4731ef221a1a324b9d15`
- Specification SHA-256: `55fb13233f3fcc6102512dc14157e1e29aa3b5bb4bb1db4474a9bdaeb143b566`
- Verdict: **APPROVED**

## Prior finding disposition

**Resolved.** Both runner paths now perform a monotonic one-second
`proc_get_status()` loop after SIGKILL:

- `isdStop()` sends negative-PG KILL only after its TERM grace, polls until the
  leader is non-running or the deadline expires, and calls `proc_close()` only
  after non-running is proven.
- `isdRun()` sends negative-PG KILL on timeout, keeps stdout/stderr nonblocking
  while polling and collecting output, and does not enter final drain/close
  until non-running is proven.

If either final deadline expires, the runner closes each independently safe
pipe and throws a fixed `CLEANUP_FAILURE` instead of entering blocking
`stream_get_contents()` or `proc_close()`. After proven non-running, descriptors
are drained/closed, `proc_close()` reaps the leader, and the existing bounded
portable `posix_kill(pid, 0) === false` assertion proves it is absent.

The handshake-failure path retains its bounded positive-PID TERM/KILL/status
cleanup because it has not proved ownership of a negative process group. It
calls `proc_close()` only when non-running is known and otherwise closes safe
descriptors and raises setup failure. It therefore has the same essential
no-blocking-while-live property without risking a signal to a foreign group.

The TERM-resistant control makes the corrected escalation observable: its child
installs `SIG_IGN`, exceeds the 150 ms operation deadline, receives KILL, reaches
status `124`, is reaped, and completes within the independent two-second bound.
The ordinary slow-process control continues to exercise TERM.

## Preserved wrapper and test quality

- The wrapper still has fixed redacted failure behavior, establishes a new
  session through `posix_setsid()`, publishes exact PID-tagged READY on FD 3,
  requires exact RELEASE on FD 4, and only then replaces itself through
  `pcntl_exec()` in the same PID.
- Both server and verifier parents boundedly require positive PID, exact READY
  and `posix_getpgid(pid) === pid` before release. This keeps the Linux
  quick-child race closed and makes negative-PG TERM/KILL ownership explicit.
- Missing argv and invalid exec retain exact exit `70`/empty stdout/fixed stderr.
  The ungrouped control proves group-identity rejection and positive-PID-only
  cleanup.
- Exact specification/transcript hashes, six real POST requests, per-request
  nine-table evidence, creation/duplicate/rejection matrices, adversaries,
  namespace isolation and attempt-all SQL/filesystem cleanup are unchanged.
- Production, specification, verifier behavior and expected result bytes did
  not change in the correction.

## Verification evidence

The complete corrected test is recorded GREEN on native macOS arm64 and Linux
arm64 as a non-root user. The reviewer independently repeated the native run:

```text
$ php -l tests/Support/process_group_exec.php
No syntax errors detected in tests/Support/process_group_exec.php

$ php -l tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
No syntax errors detected in tests/Verification/characterize_inspection_schedule_duplicate_001_test.php

$ php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact
INSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0
INSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001

$ git diff c529243..3b37158 -- specs rapid-pilot app
PASS (no output)

$ git diff --check
PASS (before adding this immutable review; no output)
```

## Verdict

**APPROVED.** The sole v2 cleanup blocker is closed. Process-group construction,
quick-child ordering, negative-signal ownership, TERM/KILL escalation,
nonblocking bounded observation, reap verification and behavior sensitivity are
portable and deterministic at the reviewed hashes. The unchanged exact
characterization passes on both required host families. This corrected test may
advance beyond Gate 3; any subsequent test/wrapper or expectation change
requires a new independent review.

The reviewer changed no wrapper, test, production, verifier or specification
file. Only this immutable review record was added.
