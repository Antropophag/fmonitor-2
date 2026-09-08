# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — fingerprint barrier fixture correction superseding evidence

Date: `2026-09-05`

Correction author: `/root/fingerprint_barrier_correction`

This append-only record supersedes the classification in
`assignment-order-original-fingerprint-barrier-oracle-correction-red-2026-09-05.md`.
Commit `1a2f8de44693da689d8144a7ca4bd7de5b70e606` remains immutable historical
evidence, but its correction is a post-implementation executable-fixture
correction, not a new pre-implementation RED.

Fresh independent Gate 3 is required for this corrected test. Production,
approved specification/OpenSpec artifacts, tasks and storage tests are not
changed by this correction.

## Exact baseline and retained oracle

The correction was rerun in a detached worktree at exact production baseline:

```text
87d3bd33063cd063b2202c92c3f5d54ad83db849
```

At `after_fingerprint_miss_before_cas`, both workers retain the approved exact
observable state:

- domain, requests, fingerprints, events, audits, process and safe logs are
  unchanged;
- finalized private inventory is unchanged;
- exactly two completed `327`-byte in-flight stages exist with configured UTC
  timestamp and next exact opaque stage IDs.

The five-FD `READY`/`RELEASE` protocol, after-private-finalize semantics and all
post-release cleanup/release/behavior assertions remain unchanged.

## Failure-path ownership correction

Every worker returned by the two-worker `start` fixture is now registered before
the READY oracle is evaluated. Normal `finish` removes a worker from that owned
registry only after result collection and reap. On any later assertion or
fixture failure, the outer `finally` drains the registry before reader, DB or
artifact cleanup:

1. send the matching bounded `RELEASE` when a READY token exists;
2. close all parent pipe/socket endpoints;
3. send SIGTERM and poll for at most 200 ms;
4. send SIGKILL if still running, then `proc_close`/reap;
5. remove the worker config artifact.

Only after all workers are reaped does cleanup close the evidence reader,
clean/drop the task-owned database and remove the task-owned filesystem root.

## Detached baseline transcript

```text
$ git worktree add --detach <temporary-root> 87d3bd33063cd063b2202c92c3f5d54ad83db849
Preparing worktree (detached HEAD 87d3bd3)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
Fatal error: Uncaught TestFailure: Identical B exact replay channels and loser request echo.
Expected status: replayed
Actual status: conflict; reasonCode: stale_revision
```

The corrected fingerprint-barrier assertion passed before this independent
behavioral failure. After the failure, process inventory contained no
`assignment_order_original_worker_entry` child, MariaDB contained no
`t_aoou_worker_%` database and the temporary detached worktree was removed.

```text
$ php -l tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php

$ git diff --check -- tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
PASS (no output)
```

## Exact hashes

```text
7b6297b8bed813f682db82a496dd599f17a89cea6c85aae9554300483a036b4f  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
```

Baseline production SHA is exact
`87d3bd33063cd063b2202c92c3f5d54ad83db849`. This record intentionally omits
its own circular hash.
