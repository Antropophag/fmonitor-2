# INSPECTION-ITEM-COMPLETE-001 — independent MariaDB Gate 3 rereview v3

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
test, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `APPROVED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved composition-amended spec: SHA-256
  `cdd85ba009e3bbb6993fd50b26ab199caf5017086d43d43bc474586ff0982e7b`.
- Composition amendment approval: SHA-256
  `638658b9ed5300d0ae3f6dfb32d10cf4600ea1587415c3d50228ed0185de67c7`.
- Canonical v8 schema contract: SHA-256
  `82b82114ab7db34c63a06ec34dd287d38a0f25e52e71b4dd314545f97f0f58d7`.
- Canonical v8 main OpenSpec: SHA-256
  `5708fbaf7f2b98bea23c80c81c193b76a9db66b06061d2a124646a838671d3e9`.
- MariaDB RED test: SHA-256
  `80c742b6c95819761d7faeff9918cd7c440bfa70f4cd8655d84f8e29a00cefa8`.
- MariaDB RED evidence v3: SHA-256
  `be14541e57db99b1abe77bda906696e1c57327a23f34cad85cf72274ef255242`.
- Prior MariaDB Gate 3 v2 review: SHA-256
  `7572f42b49e447537fccb0aae44324d22045972e6aeba3117a453b3b6c539387`.
- Canonical runner regression: SHA-256
  `81df9e82583c489380d917d615e00d89021c8e35e199a3639364545a5deb1d03`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Compose test definition: SHA-256
  `ee3b6fd4e82441b6066f1adfc3020ebde4f8ba576f95db81796f4ec50d5ad16d`.

## Independently reproduced lifecycle

I ran the complete lifecycle:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-up
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-down
docker compose -f compose.test.yaml ps --all
```

Syntax passed and Compose became healthy. The RED runner exited `0` after the
test reached the intended missing `ProductionInspectionEvidenceFactory` seam.
That failure occurs only after the exact v1-v8 runner result, literal 26-table
catalogue, fixture/decoy creation and unique DML-only principal creation, so it
is a qualifying composition RED rather than setup failure.

Before Compose teardown I independently observed
`POST_CLEANUP databases=0 users=0`. No `.test-artifacts/iic-*` directory and no
worker PHP process remained. Compose removed its container, volume and network;
final `ps --all` was empty.

## MDB2-01 closure: bounded ownership and cleanup

MDB2-01 is closed.

- Migration runner and workers share `iicReap`. Their stdout/stderr pipes are
  nonblocking; reads are chunked and capped. The helper polls to a caller-fixed
  deadline, drains during polling, sends TERM on expiry/cleanup, polls again,
  escalates to KILL, polls to a final deadline, and calls `proc_close` only after
  `proc_get_status` reports the child stopped. No live-child path uses blocking
  `stream_get_contents` or unbounded `proc_close`.
- Runner ownership begins immediately after `proc_open`; worker process and all
  pipes are stored before another worker is started. Worker go wait, coordinator
  overlap wait and result arrival each have explicit deadlines.
- Normal processing reaps both workers before checking their exit/result
  assertions. Exceptional cleanup first rolls back/closes the coordinator,
  closes the query handle, then bounded-terminates/reaps every still-owned
  worker before closing fixture DB and dropping user/database.
- Cleanup attempts are aggregated rather than short-circuited. Exact database
  and user absence are queried, every exact artifact member/root is checked,
  and worker resources are checked after cleanup. A cleanup failure becomes
  `CLEANUP_FAILURE` and includes the original throwable class/message; with no
  cleanup failure the original RED is rethrown unchanged. Cleanup therefore
  neither silently masks the primary failure nor silently accepts a leak.

## MDB2-02 closure: complete public evidence

MDB2-02 is closed.

- `iicEvidence` independently projects only the public query DTO. The expected
  value is literal except for the legitimate unordered winner id and its
  independently fixed id-to-item mapping. It checks case/section/item, actual
  actor `7301`, assigned engineer `7302`, device time, fixed server receipt time,
  base/accepted/current revisions, immutable template id/version/hash and the
  complete ordered installer snapshot values.
- Exactly one of the two public queries must expose evidence and the other must
  be `null`. The winner projection must equal the complete expected value; a
  non-null placeholder cannot pass.
- Replay uses the exact winner command and must return exactly
  `DUPLICATE(1)`. Conflict changes only installer attribution to the other
  registered installer and must return exactly
  `OPERATION_PAYLOAD_CONFLICT(1)`. The complete winner projection is queried and
  compared unchanged after each command.
- The query/replay/conflict application's injected counting clock must remain at
  zero, while the winner evidence must contain the workers' independently fixed
  `2026-09-01T09:05:00+03:00` receipt timestamp. Thus query, replay and conflict
  cannot refresh or manufacture receipt time.

## Reconfirmed prior closures and sensitivity

- Both worker connection ids are distinct and correlated through MariaDB
  `INNODB_LOCK_WAITS`/`INNODB_TRX`/`INNODB_LOCKS` metadata to the exact
  coordinator blocker, private revision table, `PRIMARY` record lock and seeded
  case key `4512`. Unrelated server waits cannot satisfy overlap.
- The exact sorted literal 26-table catalogue is checked with binary prefix
  equality before fixtures. The private decoy and complete post-command
  catalogue remain unchanged.
- Admin owns migration, fixture, coordination and cleanup. Factory workers and
  query/replay/conflict authenticate as a new unique user granted only
  `SELECT, INSERT, UPDATE` on the private database, so runtime DDL/repair cannot
  pass. The exact user is self-verified absent during cleanup.
- Each worker has its own caller-created connection, application and fixed
  clock. Result ordering is correctly treated as unordered and must be exactly
  one `ACCEPTED(1)` plus one `STALE_REVISION(1)`.
- Behavioral results are observed only through `completeItem` and
  `getItemCompletion`. SQL is confined to migration/fixture, deterministic lock
  proof, catalogue/decoy and cleanup. No v9, runtime schema repair, direct
  business persistence assertion or concurrency-by-sequential-call claim is
  introduced.

## Gate decision

All findings MDB-01 through MDB-04 and MDB2-01 through MDB2-02 are closed. The
MariaDB RED is deterministic, bounded, isolated, public-seam sensitive and
cleanly reproducible. MariaDB Gate 3 is `APPROVED` for the exact hashes above.
Gate 4 may implement only enough production composition/persistence behavior to
turn this reviewed RED green without changing its expectations.
