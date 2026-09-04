# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrective totality/replay Gate 3 v2

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_totality_replay_gate3_fresh`
- Review timestamp: `2026-09-04T07:14:28+03:00`
- Reviewed commit: `c67c9fc12692d4d409bbf207e7f4d6b1ab33f86d`
- Prior review: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-totality-replay-v1.md` (`CHANGES_REQUESTED`)
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and reviewed artifacts

The reviewer did not author the v4 specification, corrected test, test support,
RED evidence, production implementation, or prior Gate 3 review. This new
append-only review is the only authored artifact; tests and production were not
edited.

```text
ad874778204bbd19b2aece7ce2ab409bc8588369ab4bc4e5b4922399480084fb  tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
51f9c3c21746d707508d93e5250bacfa29c3524f3dcd5292a20ff85b8d3028ae  tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
b9f6f9e6e040d4d2e8b6aa51631aedce9f771950e4df31b8ecbb01e9f2ce38d1  docs/operations/assignment-order-original-upload-gate5-totality-replay-red-correction-v2-2026-09-04.md
90794c86fe6c4e0733597fd2b6d5cfd019af5daf6cb87fecda541a02988db7d4  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-totality-replay-v1.md
```

The RED remains genuine. The focused command exits `255` because malformed
`requestId=NOT-A-UUID` is accepted as revision 1 instead of returning
`REJECTED/INVALID_COMMAND`. PHP lint and the reviewed-commit diff check pass.
The new authorization counter correctly makes shape-before-authorization
observable.

## Blocking finding 1: public-result and safe-log non-leak oracle is incomplete

The test defines `$publicResult` with all eleven public Result fields but never
uses it. Every matrix assertion serializes `$rejected($result)`, which observes
only status, reason, retryable, root ID and revision ID. A Throwable secret can
therefore leak through `requestId`, `revisionNumber`, `documentDate`, `sha256`,
`byteSize`, or `uploadedAt` without failing the RED.

The safe-log check is only `str_contains(..., $throwSecret) === false`; it does
not assert the exact allowed event names, field keys, scalar values or call
count. Consequently an implementation may log arbitrary forbidden filename,
path, composition, correction-reason, SQL or unrelated exception detail and
still pass. Use the complete `$publicResult`, give every injected Throwable a
phase-unique secret, and compare captured logs to an exact per-outcome allowlist
in addition to checking that the unique secret is absent. Apply the same full
oracle to unknown-outcome, cleanup, stream, audit, lease/log and delivery cases.

## Blocking finding 2: the matrix does not cover distinct multi-call phases

The generic `fingerprint_lookup` injection trips the ordinary pre-lineage lookup
before finalize. The generic `lineage_lookup` injection trips ordinary
correction validation before finalize. Neither reaches `commitAccepted()`
returning `CONFLICT`, so the RED does not test v4's required fingerprint reread
and current-lineage reread while the finalized-content lease remains held. It
therefore cannot distinguish premature release, skipped rereads, wrong selected
`REPLAYED`/`STALE_REVISION`/`PERSISTENCE_FAILURE`, or release failure replacing
the selected outcome. Add separate identical-winner and different-winner CAS
conflict scenarios, inject Throwable independently into each post-conflict
reread, and assert exact trace, selected result, release exactly once and safe
diagnostics.

Likewise `fault_injector`, `storage_observer`, `lifecycle`, `request_lookup` and
safe logging are multi-call ports with phase-dependent semantics. The current
matrix generally throws only on their first call. The unknown-commit second
request lookup is present, but lacks the full-result/log non-leak oracle and
exact call/trace assertion. Stream `close()` Throwable is not covered (the
throwing stream only throws from `read()`), and cleanup assertions check event
membership rather than exact once/order and precedence. The corrected matrix
must enumerate the reachable distinct calls, not only each port name.

## Blocking finding 3: replay precedence and restart durability are not proven

The moved-leaf case asserts the eventual `REPLAYED` result but never asserts the
repository trace or zero lineage calls. An implementation that consults lineage
first and only then returns the fingerprint hit can pass. Assert the exact
fingerprint-before-lineage trace and that lineage/current-target validation is
not invoked on the hit.

The `NO_CHANGES` case creates a fresh application object but reuses the same
in-memory environment and repository object state. This proves absence of state
inside the application instance, but not the stated restart-durable repository
property. The RED needs either a reconstruction/serialization boundary for the
repository fixture or explicit reliance on an already approved durable-adapter
test, with that exact test and assertion identified in the evidence.

## Verification

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
PHP Fatal error: Uncaught TestFailure: Malformed shape/date/lineage/reason is typed INVALID_COMMAND.
Expected: REJECTED/INVALID_COMMAND with null evidence
Actual: ACCEPTED revision 1
Exit: 255

$ git diff --check c67c9fc^..c67c9fc
Exit: 0
```

Gate 3 remains **CHANGES_REQUESTED**. Gate 4 must not implement this correction
until the exact public-result/log oracle, distinct phase matrix, post-conflict
lease/reread cases, replay ordering and durable restart evidence receive a new
fresh independent Gate 3 review.
