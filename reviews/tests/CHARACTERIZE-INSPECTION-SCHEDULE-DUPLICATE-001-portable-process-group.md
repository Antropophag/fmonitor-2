# CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 portable process group — Gate 3 review

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_evidence_gate1`
- Reviewed repository commit: `89244b57c6ea01592d60523136d4f85addc0383a`
- Current test SHA-256: `7ecf1cd18fcae798d7ef163f3bb9fa89b6d596ac2fb1448776f28fd5dc6a5e24`
- Specification SHA-256: `55fb13233f3fcc6102512dc14157e1e29aa3b5bb4bb1db4474a9bdaeb143b566`
- Public seam: unchanged six-request loopback HTTP characterization through
  `rapid-pilot/verify-inspection-schedule-duplicate.php`
- Verdict: **NEEDS_CHANGES**

## Finding

The current test launches both its loopback server and verifier with external
GNU-specific `setsid --wait`. It later proves reaping by polling Linux-only
`/proc/<pid>`. These are harness dependencies, not requirements of the public
inspection-schedule behavior.

On Linux the exact current test reaches all controls and ends with
`CHARACTERIZATION_OK`. On native macOS it fails immediately at the first
`proc_open(["setsid", "--wait", ...])` because that external executable is not
present. It therefore never starts the server, makes the six HTTP requests or
tests creation, sequential duplicate and rejection behavior. This is a setup
failure, not a product regression. The `/proc` absence assertions are likewise
vacuous on macOS because `/proc/<pid>` is absent even for a live process.

The existing Linux-only result remains useful behavioral evidence, but a test
that is part of the native characterization target cannot claim a portable
GREEN while one supported host fails before the public seam.

## Exact minimal Gate 2 correction

Add one task-owned PHP process-group exec wrapper under `tests/Support/` and use
it for both current launch sites. The wrapper must:

1. accept only a non-empty exact command vector supplied as positional argv;
2. fail with a fixed setup exit and redacted stderr when argv is invalid or
   `posix_setsid` / `pcntl_exec` is unavailable;
3. call `posix_setsid()` and require a positive session result;
4. replace itself with the exact executable/argv via `pcntl_exec`, preserving
   the explicitly supplied child environment and working directory from
   `proc_open`;
5. if exec returns/fails, emit only the fixed setup diagnostic and fixed exit.

Launching `[PHP_BINARY, wrapper, ...exactCommand]` preserves the important GNU
`setsid --wait` property without an intermediary waiter: the wrapper PID creates
its own session/process group and `pcntl_exec` replaces that same PID with the
server or verifier. The `proc_open` handle therefore continues to own the exact
group leader it waits for.

Immediately after each launch, before server readiness or verifier execution is
accepted, the parent must boundedly require:

```text
pid > 0
posix_getpgid(pid) === pid
```

Early child exit, unavailable `posix_getpgid`, timeout or any other group
identity must be a setup failure with descriptors/process cleaned. This proof is
what makes later `posix_kill(-pid, SIGTERM|SIGKILL)` safe: the negative target
can only address the newly owned group, never the parent's or a foreign group.

Keep the current negative-process-group TERM grace and KILL escalation in both
server cleanup and verifier timeout paths. Do not replace them with positive-PID
termination after group ownership has been established. Close descriptors and
call `proc_close()` only after the leader is confirmed non-running. Then replace
each `/proc/<pid>` poll/assertion with a bounded portable assertion that
`posix_kill(pid, 0)` is false after confirmed `proc_close()`. Failure to prove
absence is a setup/cleanup failure. Cleanup must remain attempt-all and preserve
the primary failure.

No production file, product specification, expected transcript, SQL fixture,
HTTP request or behavior assertion should change.

## Required wrapper and cleanup sensitivity

The corrected Gate 2 evidence must include bounded controls which prove:

- the wrapper establishes `pgid === pid` before an exact slow child publishes
  readiness;
- negative-PG TERM stops and reaps that leader; a TERM-resistant exact child
  reaches negative-PG KILL and is then absent by `posix_kill(pid, 0)`;
- invalid/missing argv and an invalid executable take the fixed wrapper failure
  path without publishing child readiness or leaving a live PID/group;
- the parent rejects a launcher that has not established its own group. This
  can be a task-owned negative wrapper/control; it must not signal the negative
  PGID unless equality was first proven;
- ordinary server and verifier success both close and reap their wrapper/exec
  leader, and all existing controlled setup/cleanup/slow-process adversaries
  remain effective.

Directly forcing `posix_setsid()` failure is optional if it would require an
unsafe race or test-only branch in the wrapper; source inspection plus the
group-identity negative control and fixed early-exit handling are sufficient.
The wrapper itself must contain no production selector or application behavior.

## Preserved test quality

The prior Gate 3 review's substantive coverage remains sound and must remain
byte-for-byte in expectations: exact specification/transcript hashes, six real
POSTs, nonce/method/route/body/status/header evidence, per-request nine-table
pre/post snapshots, exact schedule/event creation, duplicate and four rejection
zero-mutation proofs, echo/direct-final-state adversaries, namespace collision
checks and attempt-all SQL/filesystem cleanup. Only process construction and
portable reap observation return to Gate 2.

## Verdict

**NEEDS_CHANGES.** The existing test is behaviorally sensitive on Linux but its
external GNU `setsid --wait` and `/proc` dependencies make native macOS failure
pure setup and make reap observation non-portable. Apply the bounded test-only
PHP `posix_setsid` + `pcntl_exec` wrapper correction above at both launch sites,
retain negative-group TERM/KILL, add the group/wrapper controls, demonstrate the
same intended RED or GREEN on macOS and Linux, then obtain a fresh independent
Gate 3 review. Production/spec changes are neither needed nor authorized.

The reviewer changed no test, support, production or specification file. Only
this immutable review record was added.
