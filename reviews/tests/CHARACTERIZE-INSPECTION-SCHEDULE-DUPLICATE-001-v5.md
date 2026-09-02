# Test review: CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 (v5)

- Gate: 3 — fresh independent final re-review of v0.2 after the v4 findings
- Reviewer: separately tasked agent `/root/inspection_schedule_duplicate_test_review_v5`
- Independence: this reviewer did not author the specification, test, verifier, source handler, or any prior review
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the uncommitted artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md`, version `0.2`
- Test: `tests/Verification/characterize_inspection_schedule_duplicate_001_test.php`, 540 lines
- Source oracle inspected: `rapid-pilot/InspectionSchedule.php`
- Discovery evidence inspected: `docs/operations/inspection-schedule-behavior-evidence.md`
- Prior review inspected: `reviews/tests/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001-v4.md`
- Public seam: `php rapid-pilot/verify-inspection-schedule-duplicate.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

### v4 per-request observation gap is corrected

The test-owned router now snapshots all nine relevant tables immediately before
and at shutdown after every request:

1. `fm2_pilot_users`;
2. `fm2_pilot_roles`;
3. `fm2_pilot_role_permissions`;
4. `fm2_pilot_user_roles`;
5. `fm2_installation_cases`;
6. `fm2_assignment_orders`;
7. `fm2_pilot_inspection_schedules`;
8. `fm2_pilot_inspection_schedule_events`;
9. the exact `unrelated_decoy`.

Each table snapshot contains its `SHOW CREATE TABLE` value normalized only for
the non-domain `AUTO_INCREMENT` presentation counter and its raw rows sorted by
an independently deterministic JSON-byte ordering. Request 1 must begin with
the complete exact fixture snapshot and end with that same nine-table snapshot
except for the one literal schedule and one literal event. Requests 2–6 must
each begin and end byte-identically at that exact accepted state. Thus the
duplicate and each of the four isolated rejection requests independently prove
no change to schedule history, authorization facts, case/order facts, schemas,
or the unpredictable decoy. A later restoration cannot hide an intermediate
mutation because both sides of every request are recorded by the test-owned
router.

### v4 exceptional-cleanup gap is corrected

`isdExecute()` keeps cleanup ownership across setup, server execution and
audit. Its four exact cleanup classes — server/reap, fixture tables, control
directory, and decoy table — are each attempted inside their own `try/catch`.
Failures are accumulated only after every step has run, while the primary
failure remains first and cleanup diagnostics are appended.

The controlled cleanup-path probe starts the real loopback server and creates
all SQL/storage resources, then injects an error after the exact process has
been stopped and reaped. It proves that fixture cleanup, control-directory
cleanup and decoy cleanup still run, that no exact SQL/artifact member remains,
and that ambient bytes remain unchanged. The ordinary controlled setup fault
continues to prove the same bounded cleanup when failure happens after SQL and
control-directory creation but before server startup.

## Traceability and sensitivity checks

- The exact v0.2 specification hash and exact four-line transcript hash are
  asserted before behavioral setup. All statuses, routes, forms, headers,
  identities, dates, rows and raw event JSON bytes are Gate 1 literals rather
  than values copied from verifier output.
- Six real form-encoded `POST` requests cross one loopback HTTP seam and execute
  `RapidPilotInspectionSchedule::handle(...)`. The independently generated
  request nonce, exact route/body/status/header evidence and full pre/post state
  make an echo-only substitute fail.
- Request 1 proves the exact allowed transition. Request 2 proves the
  sequential exact duplicate is a successful no-op. Requests 3–6 prove the
  specified CSRF, capability, invalid-date and ineligible-case rejection
  boundaries with full zero mutation.
- The direct-final-state adversary makes six genuine requests and prints the
  expected transcript, yet fails because request 1 did not begin with the exact
  empty-history fixture state. This demonstrates sensitivity to fabricated
  final state and transcript-only implementations.
- Fixture DDL is test-owned, both history tables are pre-created, and behavioral
  actions do not substitute direct SQL or `ensureSchema(...)` for the public
  HTTP seam.
- Exact SQL-name and artifact-child collisions are rejected without altering
  the occupied member. Invalid token/root probes cover malformed and missing
  token, missing/unset root, symlink, non-directory, relative, home and `/tmp`
  roots, with bounded execution and no owned mutation.
- Verifier and loopback processes have TERM/KILL bounds, close descriptors,
  call `proc_close()`, and check `/proc/<pid>` disappearance. The slow-process
  control exercises timeout and reap behavior.
- Two distinct unpredictable tokens must produce byte-identical stdout and
  empty stderr. Tokens, prefixes, paths, credentials, nonces, ports and process
  ids cannot enter the normalized transcript.
- The test remains scoped to the explicitly `PILOT_ONLY` creation/duplicate and
  four rejection observations. It does not promote concurrency winner policy,
  cadence, reassignment, reschedule/cancel, target authorization, or target
  persistence semantics.

## Hashes pinned by this review

- Specification SHA-256:
  `55fb13233f3fcc6102512dc14157e1e29aa3b5bb4bb1db4474a9bdaeb143b566`
- Test SHA-256:
  `ca5b21665cc75966d0b217200a92c759978ca374d3a0d1a007f2f034ff894c58`
- Expected transcript SHA-256:
  `6a7d8676c3457eefcbcba772acc4dd853d0ccad557c479632454a8b06eb55da4`
- Verifier hash: deliberately not pinned at Gate 3; the verifier is absent and
  is a Gate 4 artifact.

## Verification evidence

Healthy disposable database:

```text
make test-env-up
Container fmonitor2-test-test-db-1 Healthy
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

Reaching this RED proves that the occupied-name controls, controlled setup
failure, controlled cleanup failure, echo-only adversary, direct-final-state
adversary and timeout/reap control all completed first. A post-run
`information_schema.TABLES` query found no `isd_*` table. The run added no
surviving `inspection-schedule-duplicate-*` artifact root; three roots already
present from older task runs were observed but were not claimed or modified by
this review.

Diff hygiene for the reviewed specification and test:

```text
git diff --check -- specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
exit 0
```

## Gate decision

Gate 3 passes for exactly the pinned v0.2 specification and test bytes. Gate 4
may implement the minimal missing public verifier. Any change to the
specification, test, expected transcript, public seam, request/state evidence,
or cleanup contract invalidates this approval and requires a fresh Gate 2 RED
and independent Gate 3 review.
