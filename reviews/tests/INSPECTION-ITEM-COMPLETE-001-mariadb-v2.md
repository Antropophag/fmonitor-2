# INSPECTION-ITEM-COMPLETE-001 — independent MariaDB Gate 3 rereview v2

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
test, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved composition-amended spec: SHA-256
  `cdd85ba009e3bbb6993fd50b26ab199caf5017086d43d43bc474586ff0982e7b`.
- MariaDB test: SHA-256
  `d319bce936ce6638433ed875fd97526dab4a363fbc16ecd69185d30ae5885b7d`.
- MariaDB RED evidence v2: SHA-256
  `f1af85ca6244cc8077ae854c0bc5807d3200b9d8aa1a1486316de025ba2073be`.
- Prior MariaDB Gate 3 review: SHA-256
  `6cb0958e2be8a525a5c55b72e0281bbd55b96db8608e27b485a218deea86680b`.
- Original MariaDB RED evidence: SHA-256
  `50a22e991a46eafe19ca3b8ff5b17ded6ef77da8c2949dd803fe19471b58c824`.
- Canonical runner regression: SHA-256
  `81df9e82583c489380d917d615e00d89021c8e35e199a3639364545a5deb1d03`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Compose test definition: SHA-256
  `ee3b6fd4e82441b6066f1adfc3020ebde4f8ba576f95db81796f4ec50d5ad16d`.

## Independently reproduced lifecycle

I ran:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-up
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-down
docker compose -f compose.test.yaml ps --all
```

Syntax passed. Compose became healthy. The RED runner exited `0` after the test
failed at the missing `ProductionInspectionEvidenceFactory` seam, only after the
exact v1-v8 runner result, literal 26-table catalogue, fixtures, decoy and unique
DML-only principal had succeeded. This remains a qualifying composition RED,
not setup failure.

Before Compose teardown I independently probed the server and obtained
`POST_CLEANUP databases=0 users=0`. No `.test-artifacts/iic-*` directory or
actual worker PHP process remained. Compose down removed container, volume and
network; final `ps --all` was empty.

## Closure of prior blockers

- **MDB-01 closed:** workers publish distinct caller-owned MariaDB thread ids.
  The overlap query restricts requesters to those ids and joins
  `INNODB_LOCK_WAITS`, `INNODB_TRX` and requested `INNODB_LOCKS` metadata. It
  requires both exact requester ids, the exact coordinator blocker id, private
  revision table, `PRIMARY` index, `RECORD` type and lock data beginning with
  the only seeded key `4512`. Unrelated global waits cannot satisfy it. These
  columns and joins match MariaDB's InnoDB metadata contract.
- **MDB-03 closed:** the test fixes and sorts the literal 26 canonical v1-v8
  names and strictly compares every private-prefix catalogue entry selected by
  binary `LEFT` equality. It no longer substitutes a wildcard count for table
  identity.
- **MDB-04 closed:** migration, fixture, lock coordination and cleanup remain
  admin-owned. A newly created unique user receives exactly
  `SELECT, INSERT, UPDATE` on only the private database; no DDL privilege is
  granted. Worker and query/replay factory connections authenticate explicitly
  as that user, so they cannot inherit the admin connection. Catalogue and
  decoy comparisons remain useful additional residual checks. The unique user
  is dropped in cleanup.
- **MDB-02 substantially improved:** go/readiness/lock/result waits now have
  deadlines; coordinator/query handles, every worker resource/pipe, runtime
  user, database and five exact artifact members are centrally tracked. Cleanup
  orders rollback/close before worker termination/reaping and database removal.

## Remaining findings

### MDB2-01 — BLOCKER: owned subprocess termination/reaping is still not fully bounded

The lifecycle still has unbounded waits despite claiming every owned process is
bounded:

1. `iicMigrate()` performs blocking `stream_get_contents()` on both runner
   pipes and then `proc_close()` with no deadline, no nonblocking polling and no
   top-level process ownership. A stuck canonical runner hangs the entire test
   and is unreachable from `finally`.
2. On the normal worker-result path, after a two-second poll the code sends only
   one default termination signal, then immediately calls blocking
   `stream_get_contents()` and `proc_close()`. It does not wait with a bounded
   escalation to SIGKILL as the `finally` path attempts. A child that has
   written its result but does not exit or ignores termination can hang review
   indefinitely.
3. In `finally`, after SIGKILL the code does not perform a second bounded status
   poll before blocking `proc_close()`. Cleanup exceptions, including failed
   `DROP USER`, `DROP DATABASE`, member unlink or directory removal, are all
   swallowed; the test can report its intended RED while leaking state. The
   external post-run probe proves this one execution cleaned successfully, but
   does not make failure cleanup self-verifying.

Action required: use one process-owner/reaper helper for migration and workers.
Capture each process immediately, make stdout/stderr nonblocking, poll process
status with a deadline, signal TERM, poll, escalate KILL, poll/reap, and close
pipes without an unbounded read. Apply it on normal and exceptional paths.
Collect cleanup failures and surface them after attempting all cleanup (while
preserving the primary failure context), including explicit verification that
the exact user/database/artifact root are absent.

### MDB2-02 — BLOCKER: “complete winner evidence” and replay/conflict results are under-asserted

The public query currently proves only that one DTO is non-null, the other is
null, and the winner reports current revision `1`. An implementation that
durably writes the operation but loses actor vs assigned-engineer attribution,
device/server timestamps, base/accepted revision, immutable template identity,
or installer snapshot values/order still passes this MariaDB persistence test.
That does not support the evidence claim that the winner's **complete** facts
exist through the production adapter.

Likewise replay and conflict assertions read only `status`. Approved example D
requires `DUPLICATE(1)` and example E requires the case to remain at revision
`1`; a result carrying a wrong revision passes. The fixed clock is injected but
its exact persisted `serverReceivedAt` is never observed, so composition may
ignore it while this test remains green.

Action required: through `getItemCompletion` only, strictly compare a literal
public DTO projection for the winner: winning operation id/item (allowing the
two legitimate winner alternatives), case/section, actual actor `7301`, assigned
engineer `7302`, device time, exact fixed server receipt time, base `0`, accepted
and current revision `1`, template id/version/hash, and exact installer snapshot
values/order. Assert replay result is exactly `DUPLICATE(1)`, conflict is
`OPERATION_PAYLOAD_CONFLICT` with revision/current revision `1`, and complete
winner evidence remains value-equivalent after both commands. Continue to
require loser query `null`; do not add repository/SQL business observations.

## Other checks that pass

- Worker concurrency uses two processes, separate runtime-user connections,
  separate factory applications and a coordinator-held exact revision row. The
  expected result set is correctly treated as unordered and canonicalized to
  one `ACCEPTED(1)` and one `STALE_REVISION(1)`.
- Replay reconstructs the exact winning operation's original command; conflict
  changes installer attribution to the other registered installer.
- Direct SQL remains limited to deployment/fixture, overlap proof and
  catalogue/decoy/cleanup checks. Business outcomes are observed through the
  approved command/query seams.
- The test introduces no migration, v9, runtime repair or inspection-planning
  dependency.

## Gate decision

MariaDB Gate 3 v2 remains `CHANGES_REQUESTED`. MDB-01, MDB-03 and MDB-04 are
closed and MDB-02 is materially improved, but all owned-process paths are not
yet bounded/self-verifying and the durable public evidence assertion is not
sensitive to most required facts. Return the test and append-only RED evidence
to Gate 2, close MDB2-01 and MDB2-02, reproduce the clean qualifying RED, and
request a fresh independent rereview before production composition work.
