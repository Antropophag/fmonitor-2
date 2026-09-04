# CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 portable process group — Gate 3 v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_evidence_gate1`
- Reviewed test commit: `c5292433fafdffe4741e0ea9de72c07fbe62c655`
- Prior portability review: `9da2276c97498dbc6c17b9eade3c3ed4cff4878c`
- Wrapper SHA-256: `7483f6e72df9f24ee117591c49c5cbe9b901b22634a6a2caec6f72a32112e4c0`
- Test SHA-256: `cd19902e847c1a04a2dd4e3e4364779ff186ab6d1c70fba176303e54b5b5ebf3`
- Specification SHA-256: `55fb13233f3fcc6102512dc14157e1e29aa3b5bb4bb1db4474a9bdaeb143b566`
- Verdict: **NEEDS_CHANGES**

## Corrected findings

The task-owned `process_group_exec.php` wrapper removes the GNU `setsid --wait`
dependency without changing the product seam. It rejects missing argv or missing
POSIX/pcntl primitives with fixed redacted exit `70`, validates the executable
scalar, calls `posix_setsid()`, opens only inherited FD 3/4, publishes exact
`READY <pid>`, waits for exact `RELEASE <pid>`, closes those descriptors, and
then calls `pcntl_exec()` with the exact executable and argv. Any returned/failed
exec produces the same fixed failure. Successful exec replaces the wrapper in
the same PID.

Both server and verifier launch sites now pass the exact command through this
wrapper. The parent boundedly reads FD 3 and releases FD 4 only when both the
exact PID-tagged READY line and `posix_getpgid(pid) === pid` are true. Because
the wrapper cannot exec before RELEASE, this closes the Linux quick-child race:
even an immediately exiting command cannot disappear before group identity is
proven. Negative-PG TERM/KILL is therefore used only after ownership is known.

The corrected test includes effective controls for:

- a normal slow process reaching the timeout/TERM path;
- a TERM-ignoring process reaching bounded KILL behavior;
- missing wrapper argv and invalid exec returning exact exit `70`, empty stdout
  and fixed stderr;
- an ungrouped process proving `pgid !== pid` and receiving only positive-PID
  cleanup, never an unsafe negative-PG signal.

The full exact characterization is GREEN on native macOS in this independent
run and is recorded GREEN under a non-root Linux arm64 run. Both produce the
unchanged four-line `CHARACTERIZATION_OK` transcript. PHP lint passes for the
wrapper and test. Production, specification, SQL fixture and expected behavior
bytes did not change.

## Remaining blocking finding

### KILL is not followed by bounded non-running confirmation before `proc_close`

The prior review required both runners to call `proc_close()` only after the
owned leader was confirmed non-running. The candidate does this on ordinary
completion, but not on escalation:

- `isdStop()` sends negative-PG SIGKILL when the TERM grace expires, then
  immediately closes pipes and calls `proc_close()` without a post-KILL status
  loop.
- `isdRun()` sends negative-PG SIGKILL on timeout, breaks the polling loop, then
  switches stdout/stderr to blocking mode and drains them before `proc_close()`;
  it performs no post-KILL non-running check.

The successful TERM-resistant control shows the usual OS path exits quickly,
but it does not make these operations bounded if SIGKILL delivery fails, status
does not transition, or a descendant retains a pipe. Blocking
`stream_get_contents()` or `proc_close()` can then hang indefinitely. The later
one-second `posix_kill(pid, 0)` check begins only after those potentially
blocking calls and cannot protect them.

This is a harness-totality issue, not product behavior. Add a monotonic bounded
post-KILL `proc_get_status()` poll in both paths. Close/drain descriptors and
invoke `proc_close()` only after status is confirmed non-running. If the final
deadline expires, avoid a blocking close/drain, attempt every independently
safe descriptor cleanup, and surface a fixed cleanup/setup failure while
preserving the primary failure. After confirmed non-running and `proc_close()`,
retain the bounded portable `posix_kill(pid, 0) === false` assertion.

Also apply the same attempt-all rule to the handshake-failure branch: its
positive-PID TERM/KILL cleanup currently throws without `proc_close()` when the
final status remains running. It must report that inability explicitly without
silently treating the process as reaped or entering an unbounded close.

Add a test-owned cleanup fault/control that makes the pre-fix ordering observable
(for example, a descendant holding an output FD until group KILL) and proves the
post-KILL confirmation/drain/close path stays deadline-bounded. No production or
specification edit is needed.

## Independent evidence

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

$ git diff c529243^ c529243 -- specs rapid-pilot app
PASS (no output)

$ git diff --check
PASS (before adding this immutable review; no output)
```

## Verdict

**NEEDS_CHANGES.** Portability, same-PID group establishment, quick-child
ordering, wrapper fixed failures and behavioral sensitivity are corrected. The
remaining unbounded post-KILL drain/`proc_close()` paths prevent Gate 3 approval
of cleanup totality. Return only those runner/cleanup mechanics to Gate 2, keep
all product expectations unchanged, demonstrate bounded failure sensitivity,
and obtain another fresh independent Gate 3 review.

The reviewer changed no wrapper, test, production or specification file. Only
this immutable review record was added.
