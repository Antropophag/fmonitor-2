# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrective totality/replay Gate 3 v3

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_totality_replay_gate3_v3`
- Review timestamp: `2026-09-04T07:18:59+03:00`
- Reviewed commit: `995fa54dc3c98c03bf74e092be5d9a5c3f278e40`
- Prior reviews: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-totality-replay-v1.md`, `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-totality-replay-v2.md` (`CHANGES_REQUESTED`)
- Trigger: Gate 5 `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and reviewed artifacts

The reviewer did not author or edit the v4 specification, RED tests/support,
production implementation, evidence, Gate 5 record, or prior Gate 3 reviews.
This append-only review is the only authored artifact.

```text
99b7949e418c94cece2b38112d43966715f54d61b5b1602ff3dda4b1b022a5a9  tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
51f9c3c21746d707508d93e5250bacfa29c3524f3dcd5292a20ff85b8d3028ae  tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
8f3832fe705eeaf570cf0f57aabd43224362430392ab5b109a3552ac05a77955  docs/operations/assignment-order-original-upload-gate5-totality-replay-red-correction-v3-2026-09-04.md
```

The correction now serializes all eleven public result fields. It also adds a
stream-close Throwable, second-call CAS reread injections under a held lease,
release-failure outcome preservation, a newly constructed repository adapter,
and a replay repository trace. These are useful and the focused RED remains
genuine: malformed `requestId=NOT-A-UUID` is still accepted as revision 1.

## Blocking finding 1: exact safe-log allowlists remain unproved

The matrix still checks only that the currently injected `throwSecret` substring
is absent. It does not compare captured log count, event name, field keys and
field values with an exact per-outcome allowlist. Thus arbitrary filename,
path, composition, correction reason, SQL text, a different exception detail,
or extra log event can be emitted without failing the test.

The new release-failure assertion checks only the keys of the first log's
`safeFields`; it does not assert the event, exact scalar values, exactly-one
call, or absence of additional logs. The unknown-outcome second request lookup,
post-CAS reread failures, cleanup, stream-close, audit and post-commit observer
cases likewise have no exact-log oracle. This does not close prior v2 blocking
finding 1.

## Blocking finding 2: replay hit still permits lineage access

The replay assertion accepts either no lineage lookup or a lineage lookup after
the fingerprint lookup:

```php
$lineage === false || $fingerprint < $lineage
```

V4 requires an accepted fingerprint hit to return before stale/current lineage
validation, and prior v2 explicitly required zero lineage calls on the hit.
An implementation that performs fingerprint lookup and then unnecessarily
consults lineage still passes. Assert the exact replay trace and no lineage
lookup after the accepted fingerprint hit.

## Blocking finding 3: phase and restart oracles remain incomplete

The post-CAS cases assert only membership of `fingerprint_lookup:held` or
`lineage_lookup:held` and a release-call delta. They do not assert the exact
ordered trace through commit conflict, required rereads and exactly-once release,
so extra rereads, premature/intermediate release attempts, or wrong ordering can
pass. The generic lifecycle, fault-injector, storage-observer and safe-log
injections still select only their first reachable invocation rather than
enumerating their distinct phase-dependent calls.

The `NO_CHANGES` scenario constructs a new repository wrapper, but both wrappers
share the same live environment arrays. No reconstruction/serialization boundary
proves that committed evidence, rather than process-local fixture state, survives
a restart. This is weaker than the durable-adapter evidence required by prior v2.

## Verification

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php

$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
PHP Fatal error: Uncaught TestFailure: Malformed shape/date/lineage/reason is typed INVALID_COMMAND.
Expected: REJECTED/INVALID_COMMAND with null evidence
Actual: ACCEPTED revision 1
Exit: 255

$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUDIT_PRECEDENCE_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_FAILURE_MATRIX_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_CONCURRENCY_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_REPLAY_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_STREAM_STORAGE_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK

$ git diff --check 995fa54^..995fa54
Exit: 0
```

Gate 3 remains **CHANGES_REQUESTED**. Gate 4 must not implement this correction
until the test has exact per-outcome safe-log allowlists, zero-lineage replay-hit
proof, exact post-CAS lease/reread/release traces, exhaustive distinct observer
phases, and restart-durable repository reconstruction evidence.
