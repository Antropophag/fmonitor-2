# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 fingerprint-barrier fixture review v2

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/fingerprint_barrier_gate3_v2`
- Correction author: separately tasked agent `/root/fingerprint_barrier_correction`
- Reviewed commit: `bc05af1b7822ba0fef05678888554b0d11657c71`
- Committed production baseline: `87d3bd33063cd063b2202c92c3f5d54ad83db849`
- Historical superseded correction: `1a2f8de44693da689d8144a7ca4bd7de5b70e606`
- Contract: approved `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v42/v49/v52 worker lifecycle
- Verdict: **APPROVED**

The reviewer did not author the executable fixture, correction, production
implementation, specification, or correction evidence. This append-only
review record is the only artifact authored by this reviewer.

## Scope and prior rejection

Commit `bc05af1b7822ba0fef05678888554b0d11657c71` is the direct child of exact
committed production baseline `87d3bd33063cd063b2202c92c3f5d54ad83db849`.
Its diff contains only the worker-transport executable fixture and its
append-only superseding correction record; it contains no production or
approved-contract edit.

The prior reviewer issued no approval and no review-artifact commit for
`1a2f8de44693da689d8144a7ca4bd7de5b70e606`. The reported findings were that
the older reproduction depended on uncommitted production whose bootstrap
fabricated READY outside the application lifecycle, and that a failure after
workers started could bypass RELEASE, endpoint closure and reap. The present
review therefore treats the old record only as immutable history, not as valid
RED evidence or an earlier Gate 3 approval.

## Oracle review

The corrected fixture snapshots state only after both real workers publish
their individual `READY after_fingerprint_miss_before_cas` tokens. At that
point it requires byte-identical domain, request, fingerprint, event, audit,
process and safe-log evidence; byte-identical finalized inventory; and exactly
two newly completed in-flight stages. Each stage is asserted as exactly 327
bytes, at configured UTC `2026-09-02T09:17:00Z`, with the next deterministic
opaque identities. This matches the approved phase: received bytes and their
fingerprint lookup are complete, but private finalize and CAS have not begun.

All post-RELEASE channel and persistence assertions remain in place. On the
committed baseline, the barrier oracle passes and execution advances to the
intended behavioral difference: the identical losing request returns exact
`conflict/stale_revision` channels where the contract requires an exact
`replayed` result echoing the losing request ID. The correction therefore is a
post-implementation fixture correction and does not claim a new pre-GREEN RED.

## Failure-safe fixture ownership

Each worker returned by `start` is registered in `liveWorkers` before its READY
assertion. Successful `finish` removes ownership only after reading result and
stderr, closing endpoints, `proc_close`/reap and removing its config artifact.
Any exception before that point leaves the worker registered for the outer
`finally`.

The outer cleanup drains registered workers before reader, database and
task-owned artifact cleanup. It attempts the matching RELEASE for the captured
READY token, closes all pipe/socket endpoints, sends SIGTERM, polls for at most
20 x 10 ms, escalates to SIGKILL if still running, calls `proc_close` to reap,
and removes the per-worker config artifact. Only then does it close the reader,
clean/drop the database and recursively remove the bounded random control root.

## Independent reproduction

The reviewer created a detached worktree at exact reviewed commit `bc05af1`,
whose parent is the exact committed production baseline `87d3bd3`. The normal
run produced:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
Fatal error: Uncaught TestFailure: Identical B exact replay channels and loser request echo.
Expected status: replayed
Actual status: conflict; reasonCode: stale_revision
```

The stack location was the identical-loser assertion after the corrected
barrier assertion. Before and after the run, inventories were compared for
`assignment_order_original_worker_entry.php` processes,
`t_aoou_worker_%` MariaDB databases and immediate `aoou-worker-*` temporary
roots; all post-run inventories were empty.

For direct failure-path sensitivity, the reviewer made a temporary, uncommitted
detached-worktree injection that throws immediately after the first two-worker
barrier oracle. That run failed with `GATE3_FORCED_POST_READY_FAILURE`; the same
three post-run inventories were again empty. The temporary injection is not
part of the reviewed hash and was not applied to the shared branch.

Additional checks:

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
$ git diff --check 87d3bd33063cd063b2202c92c3f5d54ad83db849..bc05af1b7822ba0fef05678888554b0d11657c71
PASS (no output)
```

## Gate decision

The superseding executable is phase-accurate, remains sensitive to the
approved identical-race behavior, and now owns/reaps live workers before every
dependent cleanup boundary. Gate 3 is **APPROVED** for the exact reviewed
commit and hashes below. This verdict approves only the corrected executable
fixture; it is not a Gate 5 production review.

## Exact reviewed hashes

```text
7b6297b8bed813f682db82a496dd599f17a89cea6c85aae9554300483a036b4f  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
b2e439435b0aacf71ab552b64d70ff1a725f05f9b2db646925046bbd4c6d640f  docs/operations/assignment-order-original-fingerprint-barrier-oracle-correction-superseding-2026-09-05.md
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

This review intentionally omits its own circular hash.
