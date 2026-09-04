# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrective totality/replay Gate 3 v4

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_totality_replay_gate3_v4`
- Review timestamp: `2026-09-04T07:22:37+03:00`
- Reviewed commit: `84e4606182cfadca6510c3e0e0f9f6395f22cb42`
- Prior reviews: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-totality-replay-v1.md`, `-v2.md`, `-v3.md` (`CHANGES_REQUESTED`)
- Trigger: Gate 5 `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Verdict: **CHANGES_REQUESTED**

## Independence and reviewed artifacts

The reviewer did not author or edit the v4 specification, RED tests/support,
production implementation, evidence, Gate 5 record, or prior Gate 3 reviews.
This append-only review is the only authored artifact.

```text
891c799f21f7d2793d29aa1fe14ba7b2b1185c795ca80f98b21c699d660d11a1  tests/InstallationProcess/assignment_order_original_upload_001_totality_replay_test.php
51f9c3c21746d707508d93e5250bacfa29c3524f3dcd5292a20ff85b8d3028ae  tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
6b31748ebb02e2595527cddda186daf73e123093d22d49157222e9ae5ce9188b  docs/operations/assignment-order-original-upload-gate5-totality-replay-red-correction-v4-2026-09-04.md
```

The focused RED is genuine: malformed `requestId=NOT-A-UUID` is accepted as
revision 1. The correction does make accepted-fingerprint replay require zero
lineage lookup, adds exact filtered held-lease repository and storage traces for
the two post-CAS reread faults, and crosses a serialize/unserialize boundary
before reconstructing repository/application adapters for `NO_CHANGES`.

## Blocking finding 1: the safe-log oracle is neither exact nor complete

`$assertSafeLogs` iterates whatever logs happen to exist, so zero logs pass
vacuously. It does not assert an expected call count, the event value, the exact
phase for the scenario, or the correlation value/relationship; it accepts any
number of logs, any event string and any of six broadly allowed phases. This is
not the exact per-outcome allowlist required by the prior reviews. For example,
an extra safe log with event `private_filename_dump`, a fresh random
12-hex correlation and phase `committed` passes.

The helper is called only for CAS release failure and the three post-commit
cases. Generic dependency faults, attempt-audit failure, unknown-outcome lookup,
post-CAS reread failures, cleanup, stream-read and stream-close failures retain
only substring non-leak checks (or no log check at all). They can emit unrelated
exception/SQL/path/filename detail not equal to the currently injected secret
without failing. Assert the exact complete log list per scenario: count, ordered
event names, exact safe-field keys and exact allowed scalar values/grammar, plus
absence of all forbidden data.

## Blocking finding 2: the Throwable matrix still omits distinct reachable calls

The v4 diff does not expand the generic matrix. `lifecycle`, `fault_injector`,
`storage_observer`, and `safe_log` still inject only their first reachable call.
The fixture's `trip()` counters show these are multi-call ports, and their later
phases have different cleanup/outcome precedence. `commit_attempt` is tested
only on authorization denial, not independently for the other retryable failure
paths that require best-effort audit without replacing the selected result.
The unknown second request lookup is covered as a Throwable, but still lacks
exact repository/storage/release trace and exact log oracle. Thus the suite does
not yet establish v4's “all port calls are total” requirement or the full
shape/Throwable matrix requested by Gate 3.

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

$ git diff --check 84e4606^..84e4606
Exit: 0
```

Gate 3 remains **CHANGES_REQUESTED**. Gate 4 must not implement this correction
until every log-producing scenario has a non-vacuous exact ordered allowlist and
the distinct reachable calls of multi-call observers/fault hooks/audit paths are
covered with their outcome, cleanup, trace and non-leak precedence.
