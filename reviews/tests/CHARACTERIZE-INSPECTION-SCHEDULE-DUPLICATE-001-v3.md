# Test review: CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 (v3)

- Gate: 3 — fresh independent review of respecified v0.2
- Reviewer: separately tasked agent `/root/inspection_schedule_duplicate_test_review_v3`
- Independence: this reviewer did not author the specification, test, verifier, or prior reviews
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md`, version `0.2`
- Test: `tests/Verification/characterize_inspection_schedule_duplicate_001_test.php`
- Public seam: `php rapid-pilot/verify-inspection-schedule-duplicate.php`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Findings

### Blocking: the database audit observes only the final six-request state

The test-owned router is a substantial correction to the obsolete v0.1 design.
It independently records an unpredictable 48-hex nonce, the method, exact route,
form body, actual response status, `Location`, and `Cache-Control` for exactly six
real requests. The echo-only probe therefore fails independently of stdout. The
final SQL audit also proves exact schedule/event rows and raw payload bytes, all
fixture structures, authorization/case/order facts, and the unrelated decoy.

However, the only database snapshots are taken before the server starts and
after the verifier has completed all six requests. There is no independent
snapshot after creation, after the sequential replay, or before and after each
rejection. This does not satisfy the v0.2 requirements that creation history be
observed, that replay leave every original byte unchanged, and that each
rejection start from its own clean fixture and compare history/fingerprints
before and after that HTTP request.

A plausible defective verifier/handler sequence can still pass: the first POST
may create two rows and the replay may remove one; or a rejection may append a
row that a later request removes. More directly, a verifier has the supplied DB
credentials and can issue six genuine POSTs, then rewrite the final namespace to
the literal expected row before exit. The test-owned request log proves the HTTP
exchanges, but the single final snapshot cannot attribute the final history or
zero mutation to each exchange.

Add a test-owned per-request observation protocol. For example, have the router
pause after each response while the parent independently snapshots the database
and releases the next request. Prove the exact creation state, byte-identical
state after replay, and identical empty schedule/event history plus unchanged
fixture/schema/decoy state around each isolated rejection. The verifier must not
author those snapshots or their expected values. Add an adversarial six-POST
substitute that repairs/fabricates only the final database state and demonstrate
that the intermediate audit rejects it.

### Blocking: setup failures before the inner `try` can leak the decoy table

`isdExecute()` creates `{$prefix}unrelated_decoy`, calls `isdCreate()`, creates
the control directory/router, and starts the loopback server before entering the
`try/finally` at line 337. If fixture DDL/insertion, control-directory creation,
router writing, port reservation, `proc_open`, or readiness fails, its local
cleanup never runs. The outer `finally` drops only the eight names returned by
`isdTables()`; it never drops the exact decoy. Thus a setup failure can leave a
token-owned SQL table, contrary to the v0.2 cleanup-on-any-failure contract.

Put all mutations in a cleanup-owning `try/finally` (tracking which exact
members/process were created) or extend an exact-name outer cleanup to the
decoy. Exercise at least one controlled failure after decoy/fixture creation and
prove bounded server termination/reaping, exact SQL/artifact removal, and
ambient preservation. Cleanup must remain exact-name based.

### Blocking: required invalid-input surface is only partially exercised

The success path derives a 17-byte lowercase safe prefix (`isd_` + 12 hex +
underscore), and the test probes an occupied exact SQL name and occupied exact
artifact child without changing their bytes. It also checks a malformed token
and `/tmp` root once the verifier exists.

The v0.2 contract additionally requires pre-mutation rejection of a missing
token, missing root, malformed/symlink/non-directory root, exact home root, and
fallback locations. None of those cases is exercised. Because these are public
process inputs and their zero-mutation ordering is part of the executable
contract, source inspection at Gate 5 cannot replace Gate 2 coverage. Add exact
probes (including preservation of every probe path and SQL decoy) and verify exit
`2`, empty stdout, bounded termination, and no owned mutation. The current
17-byte prefix is safely below 28 bytes; v0.2 does not require manufacturing a
28-byte token-derived prefix, so no boundary change is requested here.

## Checks that are sound

- The v0.2 specification and exact four-line transcript hashes match the
  reviewed bytes. Expected fixture ids, dates, status codes, headers, rows and
  raw JSON are literals from Gate 1 rather than verifier output.
- One test-owned PHP loopback server dispatches the observed paths directly to
  `RapidPilotInspectionSchedule::handle(...)`. The router does not classify,
  normalize, retry, or persist scheduling behavior.
- Exactly six ordered POST records with the unpredictable parent-owned nonce,
  exact URL-encoded bodies, actual statuses, cache headers and redirects are
  asserted. The echo-only probe fails on absent request evidence.
- The final independent SQL audit is exact: one schedule, one event, referential
  schedule identity, exact creation columns, exact raw UTF-8 JSON key order,
  unchanged non-history fixtures, unchanged schemas and unchanged random-byte
  decoy.
- Two independently generated 12-hex tokens are required to yield byte-identical
  normalized stdout and empty stderr. Generated ids, tokens, ports, paths,
  prefixes, credentials and nonces are absent from the transcript.
- Verifier and server execution have bounded TERM/KILL paths, descriptor close,
  `proc_close()`, and `/proc/<pid>` absence checks. The explicit slow-process
  probe exercises the verifier watchdog. The pre-inner-`try` gap above is the
  remaining failure-path defect.
- The test does not claim concurrency, target scheduling policy, cadence,
  reschedule/cancel, reassignment, or target authorization semantics. Its six
  cases remain correctly classified as `PILOT_ONLY` observations.
- `php -l` reports no syntax error. The reproduced run emitted no PHP warning or
  success transcript.

## Reproduced focused RED evidence

The disposable MariaDB service was started from the repository with:

```text
make test-env-up
```

Compose reported the test database healthy. The following commands were then
run from `/home/antropophag/code/fmonitor-2`:

```text
php -l tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
```

Lint passed. The focused test exited `1`, stdout was empty, and stderr was:

```text
RED_ASSERTION: missing public inspection-schedule verifier must become a successful six-POST characterization; evidence={"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-inspection-schedule-duplicate.php\n","timed_out":false}
```

The public verifier is absent. MariaDB connection, namespace/collision probes,
fixture creation, echo-only sensitivity and timeout/reaping control all completed
before this branch, so this is a healthy missing-behavior RED, not a setup
failure. A repeated run produced the same intended classification. Read-only
post-run inspection found no `isd_*` tables or live scheduling verifier/server
process attributable to either run.

## Reviewed hashes

```text
55fb13233f3fcc6102512dc14157e1e29aa3b5bb4bb1db4474a9bdaeb143b566  specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md
738a7c47ed7f00756db1b6b074809c50f636de5fde8d00310920ea681af8f58f  tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
6a7d8676c3457eefcbcba772acc4dd853d0ccad557c479632454a8b06eb55da4  test-owned four-line normalized transcript
fcd474ff0cd5e7b5329bf2c38b37ec146aa37e51b2e294d4e1c72b8285aae08f  rapid-pilot/InspectionSchedule.php
```

No verifier hash is pinned at Gate 3 because
`rapid-pilot/verify-inspection-schedule-duplicate.php` remains absent.

## Required changes

1. Add parent-owned database/schema/history snapshots between the six observed
   HTTP exchanges, and an adversarial final-state-repair substitute that those
   intermediate observations reject.
2. Cover every mutation in `isdExecute()` with exact bounded cleanup; exercise a
   controlled failure after fixture/decoy creation and prove no residue.
3. Add the remaining mandatory invalid token/root probes with pre-mutation and
   preservation assertions.
4. Rerun the healthy focused RED and request a fresh independent Gate 3 review.

Gate 4 is not authorized for the current test hash.
