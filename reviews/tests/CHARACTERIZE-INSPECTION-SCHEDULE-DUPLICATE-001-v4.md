# Test review: CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 (v4)

- Gate: 3 — fresh independent re-review of v0.2 after the v3 findings
- Reviewer: separately tasked agent `/root/inspection_schedule_duplicate_test_review_v4`
- Independence: this reviewer did not author the specification, test, verifier, source handler, or any prior review
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md`, version `0.2`
- Test: `tests/Verification/characterize_inspection_schedule_duplicate_001_test.php`, 526 lines
- Source oracle inspected: `rapid-pilot/InspectionSchedule.php`
- Discovery evidence inspected: `docs/operations/inspection-schedule-behavior-evidence.md`
- Prior review inspected: `reviews/tests/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001-v3.md`
- Public seam: `php rapid-pilot/verify-inspection-schedule-duplicate.php`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Findings

### Blocking: per-request evidence still omits the non-history fixture and decoy state

The router now records independently owned `pre` and shutdown-time `post`
snapshots for both history tables. Those snapshots include normalized schema
hashes and raw ordered rows. They correctly prove that request 1 transitions
from empty history to one exact schedule plus one exact event and that requests
2–6 leave those history bytes unchanged. The direct-final-state adversary is
also rejected because it fabricates history before request 1 and therefore
violates the independently observed empty `pre` state.

However, `isdRouterHistory()` snapshots only
`fm2_pilot_inspection_schedules` and
`fm2_pilot_inspection_schedule_events`. The authorization, case and assignment
fixtures and the random-byte `unrelated_decoy` are observed only once before
the six requests and once after all six requests. Consequently, the test does
not prove the v0.2 requirement that every rejection compare all relevant
fixture fingerprints and the decoy before and after that HTTP request. A
defective request can modify a case, assignment, role or decoy and a later
request (or the verifier, which has the fixture credentials) can restore the
final state while all current assertions pass.

Extend the test-owned per-request observation to include stable fingerprints
of the six non-history fixture tables and the exact decoy, or add an equivalent
parent-controlled request barrier and snapshots. Assert those values are
byte-identical before and after each request. The four rejection objects are
separate clean case fixtures, while the accepted schedule/event history may
remain present as the unchanged ambient history for requests 3–6.

### Blocking: one cleanup exception can still skip the remaining owned cleanup

All `isdExecute()` mutations are now inside a `try/finally`, and the controlled
failure immediately after fixture/control-directory creation demonstrates the
ordinary cleanup path. This fixes the concrete pre-`try` leak reported in v3.

The cleanup operations in that `finally` are nevertheless sequential and
unguarded:

1. `isdStop(...)`;
2. `isdDrop(...)`;
3. `isdTree($control)`;
4. `DROP TABLE ... unrelated_decoy`.

If process stopping/reaping, any exact table drop, or control-tree deletion
throws, PHP exits the `finally` at that point and skips all later cleanup. The
outer `finally` retries only `isdDrop()` for the eight fixture names; it neither
drops the exact decoy nor reliably removes a control directory outside the
top-level artifact-root cleanup when the inner exception changes control flow.
This is weaker than the v0.2 cleanup-on-success-or-any-failure contract and can
still leak an owned process, SQL decoy or artifact after a cleanup-path error.

Make each exact cleanup step best-effort and independent, retaining the first
failure until all owned resources have been attempted and absence has been
checked. The outer emergency cleanup should also know the exact decoy and
control-child names. Add a controlled cleanup-path fault after all resource
classes exist and prove that the other classes are still removed and the
process is reaped.

## Corrected v3 findings and sound checks

- The specification and exact four-line transcript hashes match the reviewed
  bytes. Expected statuses, headers, dates, identities, rows and JSON bytes are
  literals from Gate 1, not verifier output.
- The router dispatches six real form-encoded `POST` requests to
  `RapidPilotInspectionSchedule::handle(...)`. Its unpredictable nonce, exact
  method/route/body, response status, `Location`, `Cache-Control`, pre-history
  and post-history make an echo-only substitute fail.
- Request 1's observed post-state proves one exact schedule and one exact event,
  including referential identity, actor/time and raw UTF-8 JSON key order.
  Requests 2–6 prove byte-identical schedule/event rows and schemas, so replay
  and all four rejections cause no history mutation.
- The direct-final-state substitute performs six genuine POSTs and prints the
  expected transcript but is rejected on request 1's non-empty pre-history.
- `isdExecute()` now begins its cleanup-owning `try` before its first mutation.
  A controlled post-mutation setup failure proves normal exact table, decoy and
  control-directory cleanup while preserving ambient storage. The remaining
  exceptional-cleanup gap is isolated above.
- The invalid-input table covers malformed and missing token; missing/unset
  root; symlink, non-directory, relative, home and `/tmp` roots. Each probe is
  bounded, must exit `2`, must produce empty stdout, and is checked for no owned
  SQL/artifact child. Symlink and file bytes are preserved. These probes are
  source-reviewed here; the intended missing-verifier RED occurs before their
  runtime branch, as expected for a missing public seam.
- Occupied exact SQL and artifact-child probes preserve the occupied member.
  Random ambient bytes and the unrelated exact-name decoy are checked, and no
  wildcard cleanup is used.
- The verifier and server runners have TERM/KILL bounds, close descriptors,
  call `proc_close()`, and check `/proc/<pid>` disappearance. The slow-process
  probe exercises the verifier timeout/reap path.
- Two distinct tokens must yield byte-identical exact stdout and empty stderr;
  generated tokens, prefixes, paths, credentials, nonces, ports and process ids
  cannot enter the transcript.
- The 526-line test is dense but remains navigable through narrowly named
  helpers and one orchestration block. No refactor is required for Gate 3 once
  the two behavioral gaps above are corrected.
- The test does not promote deferred concurrency, cadence, reassignment,
  reschedule/cancel, target authorization or target persistence semantics.

## Hashes pinned by this review

- Specification SHA-256:
  `55fb13233f3fcc6102512dc14157e1e29aa3b5bb4bb1db4474a9bdaeb143b566`
- Test SHA-256:
  `a21ab387111dbe25c12e6220e4cd4666adf8946e4a9b3db16d706b2b39720aa9`
- Expected transcript SHA-256:
  `6a7d8676c3457eefcbcba772acc4dd853d0ccad557c479632454a8b06eb55da4`
- Verifier hash: deliberately not pinned at Gate 3; the verifier is absent and
  is a Gate 4 artifact.

## Verification evidence

Environment:

```text
make test-env-up
... Container fmonitor2-test-test-db-1 Healthy
```

Syntax:

```text
php -l tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
No syntax errors detected in tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
```

Healthy focused RED:

```text
php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
exit 1
stdout: empty
stderr:
RED_ASSERTION: missing public inspection-schedule verifier must become a successful six-POST characterization; evidence={"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-inspection-schedule-duplicate.php\n","timed_out":false}
```

The test reached that intended RED only after its collision, controlled setup
failure, echo-only, direct-final-state and timeout adversaries completed. A
post-run information-schema query found no `isd_*` SQL member belonging to the
run. Pre-existing ambient artifact roots from earlier task runs were not
modified or claimed as this review's resources.

## Gate decision

Gate 3 does not pass. Return to Gate 2. Preserve the pinned Gate 1 literals and
HTTP seam; add per-request non-history/decoy evidence and make cleanup resilient
to failures within cleanup itself. A fresh independently tasked reviewer must
approve the corrected test before Gate 4 begins.
