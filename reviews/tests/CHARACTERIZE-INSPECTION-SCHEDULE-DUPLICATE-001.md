# Test review: CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/inspection_schedule_duplicate_test_review_v1`
- Independence: this reviewer did not author the specification, test, verifier, or production oracle
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts identified below
- Specification: `specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md`, version `0.1`
- Test: `tests/Verification/characterize_inspection_schedule_duplicate_001_test.php`
- Public seam: `php rapid-pilot/verify-inspection-schedule-duplicate.php`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Findings

### Blocking: behavioral and concurrency evidence is verifier-authored and can be fabricated

The test independently supplies the complete fixed fixture and independently owns
the request, response, history, payload, rejection and transcript literals. Those
expected values are traceable to the specification and are not copied from the
future verifier. However, every assertion that those values were actually
observed at the HTTP seam is made only against the verifier's own JSON audit.

In particular, the test does not independently observe a listening loopback
endpoint, accepted sockets, form bodies, response bytes/headers, MariaDB history,
barrier arrivals, child liveness, or connection identities. A verifier can write
the expected `fixture`, `requests`, `responses`, `history_after_*`, fingerprints,
PIDs and connection IDs to the requested audit file and print the five expected
stdout lines without executing `RapidPilotInspectionSchedule::handle(...)` or
opening either contender connection. The PID checks do not repair this: arbitrary
distinct, non-existent positive integers satisfy the reported-child and reap
assertions. Likewise, arbitrary distinct integers satisfy the connection-id
assertions.

The outer test does independently observe namespace cleanup and preservation of
its decoys, but an echo-only verifier that never mutates either namespace also
satisfies those checks. The controlled failure modes add classification/cleanup
contracts, yet they can also be selected directly from the test-only environment
variable without exercising the behavior under characterization. Consequently
the current test can go GREEN while sequential creation/replay, all six rejection
zero-mutation cases, exact raw history and the real two-process race are entirely
absent. This violates Gate 2 sensitivity and the specification's explicit rule
that fabricated audit is insufficient.

The test must obtain non-forgeable evidence outside the verifier's success report.
For example, a test-owned observer/router or observer protocol can record the
actual request processes, barrier arrivals and raw HTTP exchanges, while a
test-owned privileged connection snapshots exact SQL state before verifier
cleanup. Whatever design is chosen, the verifier must not be the sole author of
both the action and all evidence that the action occurred. The test should include
an adversarial echo/fabricated-audit probe that demonstrates such a stub fails.

### Blocking: the mandatory artifact-root rejection surface is incomplete

The specification requires rejection, before mutation, of missing, malformed,
symlinked, non-directory and out-of-bound artifact roots, and explicitly forbids
`/tmp`, the user's home root and fallback locations. The test probes only missing
root and literal `/tmp`. A verifier that accepts a symlink, a regular file, an
arbitrary repository-external directory, or the user's home root can pass this
test. Add isolated probes for each normative root class and prove zero SQL/storage
mutation plus preservation of the occupied target where applicable.

### Blocking: the outer timeout path does not prove child reaping

`cisdRun()` sends signals to a process group only if the verifier PID happens to
equal its process-group ID; `proc_open()` does not establish that group here. On
the meta-test deadline path, the test knows only the verifier PID and checks only
that parent in its cleanup evidence. It cannot identify or prove termination of
already-forked request children. The controlled timeout probe trusts a
verifier-authored list of child PIDs and therefore has the same fabrication gap
described above. Establish a test-observed process group or another independently
tracked child lifecycle, then prove bounded termination/reaping on both the
verifier-classified timeout and the outer watchdog timeout after mutation.

### Non-blocking checks that passed

- The specification hash is pinned before execution and matches the reviewed
  bytes. The test hash is recorded below; no future verifier hash is pinned at
  Gate 3.
- The fixed fixture, form fields, route, handler name, exact response headers,
  sequential schedule/event rows, raw JSON byte order, six rejection cases and
  winner-neutral alternatives are expressed as test-owned literals.
- The four milestone lines have an independently pinned hash and their order plus
  final success line are exact.
- Token grammar, three 19-byte prefixes, nominal distinct-token runs, one SQL
  collision, one storage collision, ambient SQL/storage decoys, foreign-token
  decoys, setup/regression-after-mutation hooks and verifier-controlled timeout
  cleanup are asserted. These checks are useful but do not compensate for the
  behavioral self-attestation findings above.
- The test passes `php -l`.

## Reproduced focused RED evidence

The disposable MariaDB was confirmed healthy with `make test-env-up`. Command run
from `/home/antropophag/code/fmonitor-2`:

```text
php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
```

Result: exit `1`, stdout empty, and the intended missing-verifier failure:

```text
RED_ASSERTION: missing public inspection-schedule verifier must become a successful first run; evidence={"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-inspection-schedule-duplicate.php\n","timed_out":false,"pid":197603,"process_group_id":null}
Expected: 0
Actual: 1
```

This is a qualifying healthy-harness RED: database setup and decoy construction
completed, the verifier process was invoked, and failure is specifically the
absence of the public verifier rather than an environment/setup error.

## Reviewed hashes

```text
399fa963ef48b277138bdcec453caf3b2979b6861f5abe6f671744e9c60e21ef  specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md
d494e86e5bc1b4767a60af6a04795aa55c097756e8c23a3e2976bd222cde9d92  tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
53dc58074ea44e776132b50ba6e662477f5503683d20a94a01145f09477ade6f  test-owned four-line milestone transcript
```

## Required changes

1. Make HTTP execution, exact persisted history and the two-contender
   process/connection/barrier facts independently observable by the test; add a
   probe proving an echo-only/fabricated audit implementation cannot pass.
2. Add zero-mutation probes for symlinked, non-directory, out-of-bound and home-root
   artifact inputs (and any other normative malformed-root class not represented).
3. Make child tracking and reaping independently observable and bounded on the
   outer watchdog timeout as well as on the controlled timeout-after-mutation path.

Gate 3 must be repeated by a fresh reviewer after the test changes.
