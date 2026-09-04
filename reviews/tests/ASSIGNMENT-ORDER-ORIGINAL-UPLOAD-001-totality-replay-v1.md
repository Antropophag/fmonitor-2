# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrective totality/replay Gate 3 v1

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_totality_replay_gate3`
- Review timestamp: `2026-09-04T07:07:29+03:00`
- Reviewed commit: `ca213fb73bde1ee3eebfd2f789f3beb76c4294df`
- Trigger: Gate 5 `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and reviewed artifacts

The reviewer did not author the specification, test, RED evidence, production
implementation, or Gate 5 record. This append-only review is the only authored
artifact; tests and production were not edited.

```text
396dbb21d40b52e591017441e81a288dc17a62090a5535943f7b6d75cc97f867  tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
aa9803ebbc17f666882879f6b3edf0a6d4d681a45e9a29d1e4621c8ec45c27fc  docs/operations/assignment-order-original-upload-gate5-totality-replay-red-2026-09-04.md
```

The RED is genuine: the focused command exits `255` because malformed
`requestId=NOT-A-UUID` is accepted as revision 1 instead of returning
`REJECTED/INVALID_COMMAND`. PHP lint passes.

## What is correctly specified by the test

The five malformed command examples pin useful v4 boundaries for UUID,
calendar-date, INITIAL lineage, incomplete CORRECTION lineage, and blank
correction reason. The fresh-application `NO_CHANGES` scenario is sensitive to
process-local accepted state. The moved-leaf scenario correctly requires the
accepted fingerprint lookup to win over stale/current validation. The
authorization exception scenario requires a typed retryable result without
reading upload bytes.

## Blocking finding 1: malformed shape is not proven to precede authorization

V4 section 3 requires DTO shape and scalar bounds before exact authorization.
The malformed loop observes only stream reads, request lookup calls, and
storage events. It does not observe or assert zero authorizer calls. An
implementation may authorize every malformed command and still pass all five
assertions, provided it then returns `INVALID_COMMAND`. Add an authorization
trace/count (and, preferably, composition/clock/ID counters) and assert the
exact zero-dependency prefix required for invalid shape.

## Blocking finding 2: “every port call is total” has only one Throwable case

The approved contract explicitly requires totality at every application port
boundary. This RED injects `RuntimeException` only from `authorize()`. Existing
focused tests exercise several typed `FAILED`/`UNAVAILABLE` outcomes and lease
release exceptions, but typed outcomes are not sensitive to an uncaught
`Throwable` at the other port calls.

At minimum the corrective matrix must independently inject Throwables at each
reachable dependency operation and assert the contract-specific safe result,
ordering/cleanup, and absence of exception detail: terminal-request lookup;
composition lookup; clock; stage begin/write/completed-bytes/finalize;
stream read; inspector; fingerprint lookup; lineage lookup; root/revision ID
generation; accepted commit and attempt commit; lifecycle, storage and fault
observers; lease release; safe-log observer; and post-commit delivery observer.
Where a port has multiple calls with different semantics, cover the distinct
phases (especially fresh request lookup after unknown commit and fingerprint
plus lineage rereads after CAS conflict). Stream/stage close and abort
Throwables also need an explicit cleanup precedence expectation so cleanup
cannot replace an already selected result or escape diagnostics.

The expected mapping cannot be one blanket `PERSISTENCE_FAILURE` assertion:
v4 maps stream failure to `STREAM_FAILURE`, inspector/storage failures to
`STORAGE_FAILURE`, repository/composition/clock/ID failures to
`PERSISTENCE_FAILURE`, preserves durable accepted/replayed outcomes after
release/delivery failure, and preserves the original retryable storage/stream
failure when its best-effort audit/logging fails.

## No-leak assertion gap

The authorization case checks only the returned status tuple and unread
stream. It does not inspect safe logs or captured output and therefore does not
prove that `private port detail` is absent from logs/diagnostics. Add an exact
safe-log/output allowlist (including a distinctive secret per injected
Throwable) and assert the secret is absent. This is especially important for
cleanup, audit, safe-log, and delivery exceptions, where the selected domain
result may remain correct while diagnostics leak.

## Verification

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
PHP Fatal error: Uncaught TestFailure: Malformed shape/date/lineage/reason is typed INVALID_COMMAND.
Expected: REJECTED/INVALID_COMMAND with null evidence
Actual: ACCEPTED revision 1
Exit: 255
```

Gate 3 is **CHANGES_REQUESTED**. Gate 4 must not implement the totality/replay
correction from `ca213fb73bde1ee3eebfd2f789f3beb76c4294df`; first add sensitive
shape-before-authorization observation, the complete per-port Throwable matrix,
and explicit diagnostic non-leak assertions, capture a fresh RED, and request a
new independent Gate 3 review.
