# Test review: CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 (v2)

- Gate: 3 — fresh independent re-review after the v1 findings
- Reviewer: separately tasked agent `/root/inspection_schedule_duplicate_test_review_v2`
- Independence: this reviewer did not author the specification, test, verifier, or prior review
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts identified below
- Specification: `specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md`, version `0.1`
- Test: `tests/Verification/characterize_inspection_schedule_duplicate_001_test.php`
- Public seam: `php rapid-pilot/verify-inspection-schedule-duplicate.php`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Findings

### Blocking: the new pause protocol still does not independently observe the HTTP behavior

The correction materially improves the v1 test. The meta-test now chooses a
48-hex nonce, starts the verifier under an observed `setsid` process group,
requires two distinct live `/proc` workers in that group, and independently sees
two distinct live connection IDs in MariaDB `PROCESSLIST` before releasing the
race. It then pauses verifier cleanup and independently audits exactly 24 tables,
all fixture rows, the final sequential/rejection/concurrent histories, exact raw
payload bytes, winner neutrality, and the pre/post schema fingerprint. A stale
audit file or a stdout-only echo can no longer satisfy those checks.

That evidence still does not prove that any HTTP request, much less all ten
specified requests, traversed `RapidPilotInspectionSchedule::handle(...)`.
Nothing outside the verifier records an accepted socket, request body, route,
handler dispatch, raw response, first sequential intermediate state, or any of
the six rejection exchanges. The verifier receives the nonce and every control
path in its own environment. A fabricated implementation can therefore:

1. start two idle child processes and two idle MariaDB connections;
2. report those real PIDs and connection IDs in the nonce-bearing barrier;
3. wait for the release token;
4. create the 24 expected tables and insert the final literal schedule/event
   rows directly;
5. emit a fabricated JSON audit and normalized transcript.

That implementation would satisfy the test-owned `/proc`, process-group,
`PROCESSLIST`, exact final-database and nonce checks without opening a loopback
listener or executing the public HTTP seam. Likewise, the final empty rejection
history cannot distinguish six genuine rejected requests from no requests, and
the final sequential row cannot distinguish create-plus-replay from a single
direct insert. The requested adversarial echo/fabricated-audit sensitivity probe
is not present in this revision.

Gate 2 must make the behavioral exchange independently observable. A test-owned
proxy/observer can record raw requests and responses while forwarding to the
verifier-owned loopback servers, or an equivalent protocol can give the test
non-verifier-authored evidence for every request and for the state between the
first and second sequential calls. Add an adversarial substitute that writes a
plausible audit/final database and starts dummy live children/connections, and
prove that the test rejects it.

### Blocking: the outer-watchdog failure path is still neither exercised nor proven reaped

`cisdRunControlledTimeoutObserved()` proves the cooperative
`timeout_after_mutation` path only: it consumes a verifier-authored marker,
checks two children live in the isolated group, and checks the reported processes
are gone after the verifier exits. There is no probe that forces the verifier and
children to outlive the meta-test deadline and thereby executes the outer
watchdog path.

On an exception or deadline inside `cisdRunObserved()` and
`cisdRunControlledTimeoutObserved()`, the catch block sends TERM/KILL and calls
`proc_close()`, but it does not assert that every independently observed child is
dead, prove that the owned SQL/storage namespace was cleaned, or prove decoy
preservation before propagating the error. The global `finally` sends further
signals but does not reap or verify absence. Thus the test cannot establish the
specification's bounded cleanup/reaping guarantee on every failure, including an
uncooperative outer-watchdog timeout after mutation.

Add a controlled non-cooperative hang after children and mutation are externally
observable, let the outer watchdog terminate the isolated group, and then assert
all observed PIDs are absent, all exact owned members are gone, and all decoys
remain unchanged.

### Blocking: test cleanup uses forbidden wildcard discovery and can delete an unexpected table

The specification permits exactly eight basenames under each of three prefixes
and requires exact-name enumeration for cleanup; wildcard discovery or cleanup
is expressly forbidden. `cisdOwnedTables()` queries `LIKE <prefix>%`, records
every matching name in `$discovered`, and the global `finally` drops every such
name. Consequently a verifier-created unexpected table, or any other table that
appears under the token prefix, is deleted by the meta-test even though it is not
one of the exact 24 authorized fixture tables.

The live success audit correctly requires the exact 24-name set, and the
foreign-token SQL/storage decoys are useful, but those assertions do not make
the cleanup safe. Replace cleanup discovery with the independently constructed
24-name allowlist. An unexpected same-token decoy should be detected and
preserved, not adopted and dropped.

### Blocking: pre-mutation root probes do not cover their own filesystem mutation surface

The symlink, regular-file, repository-sibling, exact-home, `/tmp`, nonexistent
and missing-root probes substantially close the v1 root-coverage finding.
However, `cisdAssertRootRejected()` supplies a random audit path under
`.local/test-artifacts/` and never asserts that this file remains absent. A
verifier may write that audit file before rejecting the unsafe root and still
pass the probe; the path is outside `$artifactRoot`, so the test's final cleanup
does not remove it either. This contradicts the required rejection before any
filesystem mutation.

Assert nonexistence of each probe audit path before and after invocation and
include it in exact cleanup-on-test-failure handling. Also add an explicitly
malformed root representation (for example a relative path); the current
nonexistent absolute path tests existence, not malformed-path validation.

## Checks that remain sound

- The specification hash is pinned and matches the reviewed bytes. The fixture,
  route, form fields, handler name, response statuses/headers, schedule/event
  columns, raw JSON key order, six rejection cases, and actor-neutral winner set
  remain independent literals traceable to Gate 1.
- The test independently expects the exact four milestones and pins their
  transcript hash. Two distinct-token successful runs are required to produce
  byte-identical stdout and empty stderr.
- The final SQL audit checks exactly 24 expected tables (eight per family), all
  case/order/authorization fixtures, zero rejection history, one sequential
  schedule/event and one concurrent winner-neutral schedule/event. It also
  checks all observed schemas against the barrier-time fingerprint.
- SQL collision, storage collision, setup-after-mutation,
  regression-after-mutation, unavailable-database and the expanded root probes
  retain explicit setup/regression classifications.
- The current file passes `php -l`. After the reproduced RED, read-only checks
  found no `isd_*`/test-decoy tables, generated artifact root, or verifier
  process left behind.

These strengths do not compensate for the missing independent HTTP observation
and failure-path safety proofs above.

## Reproduced focused RED evidence

The disposable MariaDB service was started with:

```text
make test-env-up
```

It reached the Compose `Healthy` state. Commands then run from
`/home/antropophag/code/fmonitor-2`:

```text
php -l tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
```

Lint passed. The focused command exited `1`, emitted no success stdout, and
failed for the intended missing public verifier/protocol reason:

```text
RED_ASSERTION: missing public inspection-schedule verifier must implement the test-owned live race barrier protocol
```

The public verifier file is absent. Database setup and fixture construction had
already succeeded, so this is a qualifying healthy-harness RED rather than an
environment/setup failure. Post-run inspection found no owned database tables,
artifact root, or verifier process.

## Reviewed hashes

```text
399fa963ef48b277138bdcec453caf3b2979b6861f5abe6f671744e9c60e21ef  specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md
f9644fcca2436b84f22552e15cbed6f39062055466016327bcf545e4e6523c4c  tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
53dc58074ea44e776132b50ba6e662477f5503683d20a94a01145f09477ade6f  test-owned four-line milestone transcript
```

No verifier hash is pinned at Gate 3 because the verifier remains absent.

## Required changes

1. Add test-owned observation of every actual HTTP request/response and the
   first sequential intermediate state; include an adversarial fabricated-audit
   substitute that the test demonstrably rejects.
2. Exercise the real outer-watchdog path after observable mutation/child start
   and prove bounded process-group termination, reaping, exact namespace cleanup
   and decoy preservation.
3. Enumerate only the exact 24 fixture table names for cleanup and preserve any
   unexpected same-token table.
4. Assert that every unsafe-root probe's audit path remains absent, include those
   paths in failure cleanup, and cover an explicitly malformed root string.

Gate 3 must be repeated by another fresh reviewer after these corrections.
