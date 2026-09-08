# Test review: INTEGRATION-SHARDING-001

- Дата: `2026-09-08`
- Reviewer: независимый агент `/root/shard_test_review`
- Автор test delta: отдельный агент
- Reviewed base: `2009c9bed8003cff492b70befeb67d4f582229e5`
- Owner contract: `docs/operations/integration-sharding-owner-decision-2026-09-08.md`
- Test: `tests/Verification/verification_ci_001_test.py`
- Exact test SHA-256:
  `b48e23d6a96fa87f9786acbdf4cce7de4554c4c4b7c34aa4e1e2628b5d5f0e72`
- Verdict: `CHANGES_REQUESTED`

## Blocking findings

1. **The Make wrapper test does not prove shard selection.**
   `test_make_forwards_integration_shard_without_leaking_make_controls` uses the
   default synthetic inventory, which contains only one integration entry. Its
   expected trace is therefore identical if the Make recipe ignores `SHARD=1/2`
   and runs the complete integration category. Use a multi-entry integration
   fixture whose `1/2` subset differs from the unsharded list, then assert the
   exact selected subset while retaining the environment-leak assertion.

2. **The pre-runtime assertions cannot observe the integration DB probe.**
   The fake `php` executable exits immediately for `-r` without writing to
   `TRACE`. Consequently `assertFalse(self.trace.exists())` passes even if an
   invalid sharded `run integration` reaches the MariaDB availability probe.
   Listing tests have the same blind spot if the implementation wrongly probes
   before listing. Make the stub record or fail on `php -r`, or provide a
   separate probe marker, and assert that marker remains absent for listing and
   every invalid selector. Runtime-file traces should remain independently
   assertable.

3. **The repository test hardcodes the current inventory size and conflicts
   with automatic admission of new files.**
   `test_real_integration_shards_partition_all_177_entries_once` requires both
   `len(expected) == 177` and exactly 177 unique combined entries. The owner
   contract says validated new files enter one shard automatically; the test
   would fail on the next legitimate catalogue addition even if the sharding is
   correct. Derive the expected count from the validated unsharded list and
   prove ordered alternating slices, disjointness, complete union, and no
   duplicates dynamically. The present 177-file baseline belongs in external
   review/CI evidence, not as a permanent implementation constraint.

These gaps permit materially incomplete implementations to pass and make a
correct future inventory change fail. They block Gate 3. No custom receipt,
platform simulation, timing manifest, balancing framework, or broader sharding
abstraction is needed to correct them.

## Coverage that is otherwise aligned

The remaining assertions are bounded to the authorized two-job design and
appropriately cover stable path sorting, alternating synthetic partitions,
selected runtime/path execution, continuation after child failure, malformed and
non-integration selector rejection, Make-control environment cleanup, and the
standard GitHub matrix shape. The workflow test requires `fail-fast: false`,
`shard: [1, 2]`, `ubuntu-latest`, per-job reset/migration, shard invocation,
`always()` teardown, and aggregation through `needs.integration.result`.
Existing aggregate cases reject failed, cancelled, skipped, or missing required
full-run results and retain docs-only behavior.

## Independently reproduced RED

Command:

```text
$ python3 tests/Verification/verification_ci_001_test.py
```

Result on the reviewed worktree: exit `1`; `Ran 15 tests in 13.658s`, with
`FAILED (failures=5)`. Ten pre-existing tests passed. The five new tests failed
against the missing implementation as follows:

```text
test_integration_shards_are_stable_disjoint_complete_sorted_partitions
  ci.py: error: unrecognized arguments: --shard 1/2

test_real_integration_shards_partition_all_177_entries_once
  ci.py: error: unrecognized arguments: --shard 1/2

test_sharded_run_attempts_every_assigned_file_and_reports_failure
  ci.py: error: unrecognized arguments: --shard 1/2

test_make_forwards_integration_shard_without_leaking_make_controls
  make-control environment leaked

test_workflow_runs_two_isolated_integration_shards_and_aggregates_them
  missing name: Integration (${{ matrix.shard }}/2)
```

The observed failures are caused by the unchanged runner, Make wrapper, and
single integration workflow job; no database was required. This is an intended
RED baseline, but a qualifying RED alone cannot compensate for the three test
sensitivity gaps above.

## Verdict

`CHANGES_REQUESTED`

Revise the exact test artifact to use a multi-entry Make fixture, make DB-probe
attempts observable in all pre-runtime cases, and derive real-inventory union and
uniqueness from the validated full list rather than a permanent 177 count. Then
capture a fresh independent RED and obtain a new Gate 3 review before runner,
Makefile, or workflow implementation begins. Any byte change invalidates the
SHA-256 reviewed here.
