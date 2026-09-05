# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — isolated fingerprint barrier oracle correction

Date: `2026-09-05`

Correction author: `/root/fingerprint_barrier_correction`

This append-only record extends and supersedes the incomplete fixture scope in
`assignment-order-original-fingerprint-barrier-oracle-correction-superseding-2026-09-05.md`.
The already-approved correction covered the main worker races but missed the
included `assignment_order_original_isolated_races.php` fixture. Fresh
independent Gate 3 is required.

Production, approved specifications/OpenSpec artifacts, tasks and the main
worker test were not changed by this correction.

## Demonstrated former failure

At exact HEAD `c00f8ae18b09cd9e23f4790886a8bec0cfd06aab`, support-fixture hash
`2d75e683755f1c444b21d0e57f512ffee3265b044e1c00dd766f4b1d78c473fa`,
the compliant lifecycle reached both five-FD READY barriers and failed on the
remaining whole-snapshot premise:

```text
identical both READY preserve byte-exact complete baseline.
Expected stages: []
Actual stages:
  stage-0001, 327 bytes, 2026-09-02T09:16:00Z
  stage-0002, 327 bytes, 2026-09-02T09:16:00Z
```

## Corrected exact phase oracle

Both isolated-identical and isolated-different runs now independently require
at fingerprint-miss READY:

- byte-identical domain, request, fingerprint, event, audit, process and log
  evidence;
- byte-identical finalized inventory;
- exact `stage-0001` and `stage-0002`, each 327 bytes, with the run's configured
  UTC clock (`09:16:00Z` identical, `09:17:00Z` different).

After winner A is released, durable winner evidence remains exact while the
still-paused loser owns exact `stage-0002`; after loser release the existing
final inventories still require zero stages. No behavioral result oracle was
weakened.

## Failure ownership

The isolated fixture names both workers before start. Its `finally` reaps any
name still present in the main harness registry before closing the isolated
reader/database or removing artifacts. The shared reaper performs matching
RELEASE, closes parent endpoints, bounded SIGTERM polling for 200 ms, SIGKILL if
needed, and `proc_close`/reap.

A detached negative probe changed only the expected stage size from 327 to 326.
It failed at the intended READY assertion with exit 255. Afterward there were no
matching worker processes, no `t_aoou_race_%` database, and the detached
worktree was removable.

## Factual result after correction

The production tree used for the factual run was exact HEAD
`c00f8ae18b09cd9e23f4790886a8bec0cfd06aab` plus the separately owned current
runtime correction hash recorded below; that production file was not staged by
this author.

```text
$ php -l tests/Support/assignment_order_original_isolated_races.php
No syntax errors detected in tests/Support/assignment_order_original_isolated_races.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_WORKER_TRANSPORT_OK

$ git diff --check -- tests/Support/assignment_order_original_isolated_races.php
PASS (no output)
```

## Exact hashes

```text
617019a303efd714c9e6348d99e05aaffb57f6fee1212d176f29e7907b182693  tests/Support/assignment_order_original_isolated_races.php
7b6297b8bed813f682db82a496dd599f17a89cea6c85aae9554300483a036b4f  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
207f304502191cb522581900c6a086080932d9b77ba23e80afa7e19286e3cf3f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
```

This record intentionally omits its own circular hash.
