# INSPECTION-ITEM-COMPLETE-001 — independent MariaDB Gate 3 review

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
test, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed baseline

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved composition-amended executable spec: SHA-256
  `cdd85ba009e3bbb6993fd50b26ab199caf5017086d43d43bc474586ff0982e7b`.
- Composition amendment owner approval: SHA-256
  `638658b9ed5300d0ae3f6dfb32d10cf4600ea1587415c3d50228ed0185de67c7`.
- Amendment independent rereview: SHA-256
  `8c02aa99713b7307406f787a4ee3a7d9b197830d700337f1f041fafb539333f5`.
- Canonical v8 executable schema contract: SHA-256
  `82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7`.
- Canonical v8 main OpenSpec: SHA-256
  `5708fbaf7f2b98bea23c80c81c193b76a9db66b06061d2a124646a838671d3e9`.
- MariaDB RED test: SHA-256
  `8d36dafb9ac35940b00178a716bd6b0b8ae865132c942fb3143d7beded913b01`.
- MariaDB RED evidence: SHA-256
  `50a22e991a46eafe19ca3b8ff5b17ded6ef77da8c2949dd803fe19471b58c824`.
- Canonical migration runner regression: SHA-256
  `81df9e82583c489380d917d615e00d89021c8e35e199a3639364545a5deb1d03`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Test Compose definition: SHA-256
  `ee3b6fd4e82441b6066f1adfc3020ebde4f8ba576f95db81796f4ec50d5ad16d`.

## Independently reproduced lifecycle

I ran the exact lifecycle:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-up
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-down
docker compose -f compose.test.yaml ps --all
```

Syntax passed. Compose became healthy. The RED runner exited `0` after the PHP
test failed at the intended missing
`ProductionInspectionEvidenceFactory` seam. That check happened only after the
canonical runner returned exact v1-v8 application and the fixture setup
completed, so this is a qualifying composition RED rather than database/setup
failure.

I independently queried for `t_iic_%` databases and inspected
`.test-artifacts/iic-*` after RED; neither leaked. `make test-env-down` removed
the service, volume and network, and final `ps --all` was empty.

## Findings

### MDB-01 — BLOCKER: global lock-wait count does not identify either worker or the intended row

The overlap proof reads only:

```sql
SELECT COUNT(*) FROM information_schema.INNODB_LOCK_WAITS
```

and accepts any global count `>= 2`. It does not identify the two worker
connection ids, the coordinator's blocking transaction, the private database,
the prefixed `fm2_checklist_revisions` table, its primary key, or case `4512`.
Two unrelated waits elsewhere in the shared test server would satisfy the
assertion even if one or both workers had not reached the revision lock. A
single worker transaction with unrelated nested waits can also make a raw wait
row count differ from a count of distinct intended workers.

Action required: each worker must publish its caller-owned MariaDB connection
thread id with its ready signal. Capture the coordinator connection/thread id.
Join `INNODB_LOCK_WAITS` to transaction/lock metadata and require exactly the
two distinct worker thread ids to be waiting behind the coordinator on the
private `${prefix}fm2_checklist_revisions` record for installation case `4512`
(including exact table/index/lock-data evidence supported by this MariaDB
version). Reject unrelated waits rather than counting them.

### MDB-02 — BLOCKER: assertion and child failures can hang or leak indefinitely

The normal path has no bounded worker completion/reaping deadline:
`stream_get_contents()` on child stdout/stderr and `proc_close()` may block
forever. More importantly, failure paths are not owned by `finally`:

- if the second worker cannot start or readiness times out, a started worker
  remains in the unbounded `while (!is_file($go))` loop;
- if the overlap assertion fails, the coordinator still holds the revision-row
  transaction while both workers may be blocked behind it;
- the `finally` block attempts `DROP DATABASE` before rolling back/closing that
  coordinator or terminating/reaping workers, which can itself block on their
  active transaction/metadata use;
- an assertion while processing the first child can leave the other child
  unclosed and unreaped;
- artifact unlink/rmdir races with surviving workers that can still write.

Action required: track coordinator, query connection, all pipes and all child
processes from creation. A top-level bounded cleanup must, on every throwable,
rollback/close the coordinator first, signal or terminate every surviving
worker, poll with a deadline, escalate termination if needed, close every pipe,
and reap every process before dropping the exact database and deleting worker
artifacts. Normal result collection also needs a deterministic deadline and
must collect/reap both children before asserting either result. The worker's go
wait and command execution need bounded failure reporting.

### MDB-03 — BLOCKER: the asserted “all 26 canonical tables” is only a count

The setup query counts names matching a `LIKE` pattern and asserts `26`; it does
not compare the exact 26 canonical table names. The prefix includes an
unescaped SQL `LIKE` underscore, and the assertion can pass with a missing
canonical table plus a similarly matching impostor. This does not support the
evidence statement that the test “independently requires all 26 canonical
tables.”

Action required: fix a literal expected list of the 26 approved v1-v8
`${prefix}` table names from the canonical contract, query exact private-schema
catalogue names using binary equality/prefix handling without `LIKE` wildcard
ambiguity, sort both lists, and compare them exactly before fixture inserts.
Keep the runner's exact `[1..8]` result assertion as a separate check.

### MDB-04 — BLOCKER: unchanged final catalogue does not prove runtime DDL absence

Workers and the query application use the root/admin connection. Comparing the
catalogue before and after catches persistent schema changes, but an
implementation can create/alter and then restore/drop runtime objects and still
pass. The test therefore overclaims that it proves factory/commands perform no
DDL or repair. The canonical v8 boundary specifically requires DML-only runtime
consumption.

Action required: after admin-owned migration/fixture setup, create a private
runtime principal with only the exact connection/DML privileges needed by the
application and use that principal for every factory-created worker/query
connection. Keep admin access only for setup, overlap observation/coordination
and cleanup. Successful behavior under a principal that cannot execute DDL,
together with exact catalogue and decoy preservation, makes the no-runtime-DDL
claim sensitive. Clean up the unique runtime user on every path.

## Checks that otherwise pass

- The RED occurs after a healthy fresh canonical runner result, not before
  migration or fixture setup. Fixture facts match the approved actor,
  capability, working case, registered order, two installer snapshots,
  immutable template and revision-zero preconditions.
- The intended production behavioral observations use only
  `completeItem`/`getItemCompletion`; direct SQL is limited to deployment,
  fixture, overlap coordination and schema/decoy checks.
- Worker design uses separate processes, caller-created connections,
  application instances and fixed clocks. It does not share one application or
  connection concurrently.
- Sorting results lexicographically by status correctly canonicalizes the
  unordered pair to `ACCEPTED(1), STALE_REVISION(1)`.
- Winner/loser selection is made through the public query. Replay reconstructs
  the winner's exact original command; the conflict changes only installer
  attribution to another currently assigned installer.
- The unique database/prefix/artifact names and decoy are appropriately private
  and bounded on the successful/missing-factory path. Final catalogue equality
  and decoy value checks are useful residual-mutation checks once MDB-04 is
  fixed.
- No new migration/version or runtime schema repair is approved by this test.
  The slice continues to consume canonical v8; it does not introduce v9.

## Gate decision

MariaDB Gate 3 is `CHANGES_REQUESTED`. The observed missing-factory RED is
valid, but the currently unreachable overlap harness can accept unrelated lock
waits and can hang/leak on common failure paths; its canonical-table and
no-runtime-DDL claims are not sufficiently sensitive. Return the test and
append-only RED evidence to Gate 2, close MDB-01 through MDB-04, reproduce the
healthy missing-factory RED with clean lifecycle, and request a fresh
independent rereview before production composition work.
