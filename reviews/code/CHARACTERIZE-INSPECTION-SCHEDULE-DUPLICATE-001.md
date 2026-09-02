# Code review: CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001

- Gate: 5 — fresh independent code review
- Reviewer: separately tasked agent `/root/inspection_schedule_duplicate_code_review_v1`
- Independence: this reviewer did not author the specification, Gate 2 test, verifier, wiring, or Gate 3 review
- Reviewed commit: `932662938837b28309fef2bf0fe3cadb2ce86e41` plus the exact uncommitted artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001.md`, version `0.2`
- Approved test review: `reviews/tests/CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001-v5.md`
- Production scope: `rapid-pilot/verify-inspection-schedule-duplicate.php` and its single characterization-suite registration in `tools/verification/run.sh`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

## Specification conformance

- The verifier validates the 12-character lowercase hexadecimal run token, the
  exact existing repository-owned artifact root, and the exact loopback HTTP
  origin before issuing any request. It rejects missing, relative, symlinked,
  non-directory, non-canonical, out-of-bound and NUL-containing roots. It does
  not choose a fallback location or create, repair, reuse or clean storage.
- The loopback URL is restricted to the exact `http` scheme, literal
  `127.0.0.1`, an integer port in `1..65535`, no path, and no user info, query
  or fragment. The verifier contains no database client, SQL, fixture DDL,
  direct persistence, fake audit, filesystem mutation or cleanup ownership.
- In the specified order, exactly six form-encoded HTTP `POST` requests cross
  the public route: accepted creation, exact sequential replay, wrong CSRF,
  missing capability, impossible date and ineligible case. The request ids,
  CSRF values and dates are exact Gate 1 literals; no handler behavior is
  reproduced in the verifier.
- Every response is checked for its exact status, exact singleton `Location`
  presence/value (or absence), and exact singleton `Cache-Control: no-store`.
  HTTP transport or malformed response framing is classified as setup failure;
  a contractual response mismatch is classified as regression failure.
- Success prints exactly the approved four-line deterministic transcript and
  nothing to stderr. Missing or unsafe setup exits `2`; behavioral regressions
  exit `1`. Tokens, paths, ports, ids and implementation messages cannot enter
  normalized stdout.
- The verifier does not assert concurrency, cadence, rescheduling,
  cancellation, reassignment, target authorization, translated messages or
  target persistence semantics. It remains a bounded rapid-pilot oracle rather
  than new domain logic.

The approved meta-test independently observes and audits the behavior. It
requires six real request-log records, exact per-request pre/post snapshots of
all nine fixture tables, exact persisted schedule/event history and raw JSON,
unchanged structures and ambient decoys, two deterministic runs, and bounded
process/SQL/artifact cleanup. An echo-only or direct-final-state verifier does
not satisfy those controls.

## Standards and boundary review

The implementation is a small explicit verifier with no new application
dependency, business persistence owner or public production seam. Names expose
setup versus assertion responsibilities; the request matrix avoids repeated
branching; and no speculative domain abstraction or broad production edit was
introduced. The one `run.sh` entry registers the focused meta-test in the
existing deterministic characterization order. No documented-standard breach
or material Fowler smell was found in the reviewed scope.

## Hashes pinned by this review

- Specification SHA-256:
  `55fb13233f3fcc6102512dc14157e1e29aa3b5bb4bb1db4474a9bdaeb143b566`
- Gate 2 test SHA-256:
  `ca5b21665cc75966d0b217200a92c759978ca374d3a0d1a007f2f034ff894c58`
- Gate 3 v5 review SHA-256:
  `fbc9bec5cde1eab0a6dc4037f376e352b7a33e2412f834e74f8041748fec875c`
- Verifier SHA-256 (pinned at Gate 5):
  `1ed0ff2cdac9ee334d6229b2708795e6af2937688176d866d48179717945e427`
- `tools/verification/run.sh` SHA-256:
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`
- Expected transcript SHA-256 inherited from approved Gate 3:
  `6a7d8676c3457eefcbcba772acc4dd853d0ccad557c479632454a8b06eb55da4`

## Verification evidence

Disposable MariaDB:

```text
make test-env-up
Container fmonitor2-test-test-db-1 Healthy
```

Syntax and focused GREEN:

```text
php -l rapid-pilot/verify-inspection-schedule-duplicate.php
No syntax errors detected in rapid-pilot/verify-inspection-schedule-duplicate.php

php -l tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
No syntax errors detected in tests/Verification/characterize_inspection_schedule_duplicate_001_test.php

php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact
INSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0
INSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001
```

The focused test reaches success only after its occupied-namespace, invalid
input, controlled setup/cleanup failure, echo-only, direct-final-state and
timeout/reap controls. Its success also proves both independent runs leave no
owned SQL table or artifact child.

Independent setup classification probe:

```text
php rapid-pilot/verify-inspection-schedule-duplicate.php
exit 2
stdout: empty
stderr: SETUP_FAILURE: inspection-schedule verifier run token is invalid
```

Full characterization suite, including calendar and all four photo
predecessors:

```text
tools/verification/run.sh characterization
exit 0
PASS calendar bounded projections, deterministic DOM and fail-closed overflow
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001
ok - CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 oracle is deterministic and isolated
ok - CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 oracle is deterministic, audited, isolated, and correctly classified
ok - CHARACTERIZE-INSPECTION-PHOTO-REJECTIONS-001 public oracle is deterministic, isolated, and correctly classified
ok - CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 public oracle is deterministic, isolated, and correctly classified
... all remaining characterization verifiers passed
```

Architecture, repository PHP lint and diff hygiene:

```text
make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

tools/verification/run.sh lint
exit 0

git diff --check -- rapid-pilot/verify-inspection-schedule-duplicate.php tools/verification/run.sh
exit 0
```

## Gate decision

Gate 5 passes for exactly the pinned verifier and suite wiring. Combined with
the unchanged approved v0.2 specification/test and Gate 3 v5 approval,
`CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001` satisfies its code-review
gate. Any change to the pinned verifier, specification, test or expected
transcript requires the corresponding gate to be re-evaluated.
