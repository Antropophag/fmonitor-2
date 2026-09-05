# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — isolated fingerprint barrier Gate 3 review

Date: `2026-09-05`

Reviewer: `/root/isolated_barrier_gate3`

Decision: `APPROVED`

## Reviewed scope and provenance

- correction commit: `0b087a3438353efc8d6e5dff0c12bef71771fe5c`
- parent approved fixture correction: `bc05af188816989dddf2df8fc87ce8e44daff50c`
- parent independent review: `c00f8ae18b09cd9e23f4790886a8bec0cfd06aab`
- committed support fixture SHA-256: `617019a303efd714c9e6348d99e05aaffb57f6fee1212d176f29e7907b182693`
- committed worker suite SHA-256: `7b6297b8bed813f682db82a496dd599f17a89cea6c85aae9554300483a036b4f`
- separately owned, unstaged runtime dependency SHA-256: `207f304502191cb522581900c6a086080932d9b77ba23e80afa7e19286e3cf3f`

`git show` confirms that the correction commit contains only the isolated-races
support fixture and its append-only correction evidence. It does not contain
production, OpenSpec, task-list, or main worker-suite changes. The factual
reproduction deliberately used the named uncommitted runtime dependency; this
is a post-implementation fixture correction and is not represented as prior RED.

## Oracle review

For both isolated `identical` and `different` races, the corrected fixture:

- requires both workers to have reached their five-FD fingerprint-miss READY
  barriers before observing state;
- preserves domain, requests, fingerprints, events, audits, process projection,
  safe log and finalized inventory byte-exact at that phase;
- requires the exact two completed stages, `stage-0001` and `stage-0002`, with
  byte size `327` and the scenario clock (`09:16:00Z` or `09:17:00Z`);
- after winner A completes, requires the complete exact durable winner state
  plus the still-paused loser's exact `stage-0002`;
- retains the exact accepted/replayed result for identical content and the exact
  accepted/stale-revision result for different content;
- retains the literal final inventories, including zero stages, the stale audit,
  and the safe release-failure log where applicable.

The outer fixture now pre-registers both deterministic worker names. Its
`finally` invokes the shared reaper for every still-registered worker before
closing readers/databases and removing artifacts. The reaper sends matching
`RELEASE`, closes endpoints, polls SIGTERM for at most 200 ms, escalates to
SIGKILL if required, and calls `proc_close`; normally finished workers are
removed from the registry by `finish`.

## Independent reproduction

On correction commit `0b087a3` plus only the separately owned runtime dependency:

```text
$ php -l tests/Support/assignment_order_original_isolated_races.php
No syntax errors detected in tests/Support/assignment_order_original_isolated_races.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_WORKER_TRANSPORT_OK

$ git diff --check 0b087a3^ 0b087a3
PASS (no output)
```

In a detached worktree at exact correction commit, the runtime dependency was
copied byte-for-byte and only the isolated READY oracle's expected stage size
was mutated from `327` to `326`. The suite exited `255` at the intended
`identical workers READY` assertion, showing actual sizes `327`. After exception
cleanup, process inspection found no assignment-order worker entry process,
MariaDB inspection found no `t_aoou_race_%` or `t_aoou_worker_%` database, and
filesystem inspection found no isolated worker artifact directory.

## Gate 3 conclusion

The correction is exact, behavior-sensitive, deterministic for both isolated
race branches, and owns bounded cleanup of every registered worker. No finding
blocks implementation or the subsequent fresh Gate 5 review.

This append-only review intentionally omits its own circular hash.
