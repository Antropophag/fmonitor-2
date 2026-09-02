# INSPECTION-ITEM-COMPLETE-001 — independent endpoint/prefix Gate 3 review

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, evidence, specification or production)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Raw endpoint admission test: SHA-256
  `2e2717a1635650966f17a472c0d6d2a6ca0f03dc6112f8f7273d1a6f6a65b64b`.
- Endpoint RED evidence: SHA-256
  `ab9fee6d396f030b1ec1ef25577283b9c88367fd7bd719721965ef8cc10a824a`.
- Prefix validation test: SHA-256
  `e80907a1a8b365ee500c2e0e6f2c204d1e09f006e755f70b4d37c707d9d528c6`.
- Prefix RED evidence: SHA-256
  `2490918f146b700a8dd14c2b4f2953f7b537cd5115b4bc6572b324586b9e38ec`.
- Current endpoint coordinator: SHA-256
  `050d4dc24d7fc95f354275df4ba9b946d5ea6dfdebe2e0d198709df16b806143`.
- Current `ChecklistSync`: SHA-256
  `1e68fad44e18b6eb569830d7ae7c6d394dcf7826dd7f3692009eeaa7c0eddeeb`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

No production or test file was edited by this review.

## Independent reproduction and cleanup

With a healthy isolated Compose database I ran:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
php -l tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
```

Both syntax checks passed. Endpoint admission produced the intended behavioral
RED after a healthy canonical migration and router start:

```text
Unassigned active exact-capability engineer obtains offline sync context.
Expected: 200
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

Prefix validation produced the intended first missing guard:

```text
Invalid production prefix must fail configuration before DB access:
aaaaaaaaaaaaaaaaaaaaaaaaaa
RED_ASSERTION: expected failing behavior observed
```

Both runner commands exited `0`. After the endpoint run I independently found
no `t_iea_%` database, no `.test-artifacts/iea-*` directory and no owned PHP
router process. I then ran
`docker compose -f compose.test.yaml down -v --remove-orphans`; final Compose
`ps --all` was empty.

## Checks that pass

- The endpoint test starts the real production router and sends bytes over a
  loopback HTTP socket. HTTP status/body are the public oracle; no reflection,
  static-source matching or injected policy fake decides admission.
- The MariaDB fixture is private and independently establishes the important
  distinction: actor `7301` is active and has exactly
  `inspection.item.complete`, assigned engineer is distinct `7302`, and no
  `checklist.edit` grant is inserted. Canonical v1-v8 migration and a coherent
  working object/order avoid the legacy 403 being a broken-card/setup result.
- The observed endpoint 403 is therefore a genuine missing authorization
  behavior, not migration/router failure.
- The prefix test uses an unconnected `mysqli` and catches only
  `InvalidArgumentException`. Once green, a constructor that touches the DB
  before rejecting will expose a mysqli/error failure instead of satisfying the
  expectation. The 26-byte and non-ASCII inputs are independently literal.
- Normal-path endpoint cleanup succeeded in this reproduction, including
  private database, server and artifact root removal.

## Blocking findings

### EP-01 — sync-context admission does not prove operation-endpoint admission

The test sends only
`GET /pilot/construction-control/objects/4512/sync-context`. The actual item
command is handled by a separate POST checklist route with a separate copy of
the legacy `$allowed` condition. A minimal implementation can relax only
`syncContext`, return the expected CSRF/revision response, and leave the actual
`item_completed` POST at HTTP 403; this test will pass although actor `7301`
still cannot complete anything.

Required change: retain the raw sync-context request, capture its session cookie
and CSRF token, then make a raw POST to the real checklist operations endpoint
as actor `7301`. Assert an outcome proving it passed endpoint admission and
reached the item application path. A deliberately single-field malformed item
may assert the already approved 422/rejected syntax mapping without requiring a
complete acceptance fixture; alternatively build the full approved fixture and
assert accepted revision/evidence. A 403 must fail the test.

### EP-02 — read-only/mutation behavior is unobserved

The fixture leaves the checklist revision table empty. On a future 200,
sync-context calls projection; a wrong projection may create revision zero (or
perform other DML/runtime DDL) and still return the asserted `revision=0`. The
test has no before/after projection, so this forbidden mutation is invisible.
This is especially relevant because the approved spec explicitly makes
projection reads read-only.

Required change: before starting HTTP, snapshot exact `SHOW CREATE TABLE` plus
all ordered rows for the four v8 evidence tables (or an equally complete
independent scalar projection). Assert exact equality after the GET admission
probe. If EP-01 uses a deliberately rejected POST, assert the same equality
after POST. If it uses an accepted POST, assert only the exact approved append
and revision delta while preserving schema and unrelated rows.

### EP-03 — endpoint failure cleanup is not bounded or self-verifying

`ieaStart` leaks its newly opened process/pipes if the listen deadline expires,
because it throws before returning ownership to `$server`. `ieaStop` sends TERM
once and then performs blocking pipe drains/`proc_close` with no deadline or
KILL/reap fallback. The HTTP socket read likewise has no explicit bounded read
timeout. Thus an assertion/server failure can hang or leave a router even
though normal cleanup happened to pass. The evidence's unconditional cleanup
claim is stronger than the test guarantees.

Required change: keep process ownership immediately after `proc_open`; on every
start/request/assertion failure, close input, TERM, bounded nonblocking drain,
KILL if needed, and reap. Bound connect/write/read. Aggregate cleanup failures
without masking the primary test failure and self-verify the owned database,
root and process are absent.

### PX-01 — canonical prefix character contract is under-covered

The approved prefix is ASCII `[A-Za-z0-9_]*` at 0..25 bytes. The test covers the
26-byte ceiling and non-ASCII, but not a disallowed ASCII character. An
implementation that checks only length and ASCII bytes while accepting
`bad-prefix` passes both cases and violates the canonical grammar.

Required change: add an independently literal invalid-character prefix such as
`bad-prefix` (and preferably a whitespace case) and require the same
`InvalidArgumentException` before DB access. Reuse the same unconnected handle
so an early DB touch remains observable. Boundary-valid empty/25-byte cases are
already covered at the approved factory seam and need not be duplicated unless
this constructor introduces a different validation implementation.

## Gate decision

Both RED failures are genuine for the first missing behaviors and their normal
fixtures are isolated. However a wrong GREEN can still leave the command POST
forbidden, mutate state during GET, leak/hang its HTTP process on failure, or
accept invalid ASCII punctuation. These sensitivity and cleanup gaps require
Gate 2 correction. The endpoint/prefix increment is `CHANGES_REQUESTED`; Gate 4
must not implement against these exact tests as an approved complete contour.
