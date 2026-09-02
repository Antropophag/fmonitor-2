# INSPECTION-ITEM-COMPLETE-001 — MariaDB Gate 2 RED evidence v2

Date: 2026-09-01  
Test author: `/root/item_red_author`  
Purpose: close MDB-01 through MDB-04 from the independent MariaDB Gate 3 review.

This record supplements the first MariaDB RED evidence. Approved spec SHA-256
remains `cdd85ba009e3bbb6993fd50b26ab199caf5017086d43d43bc474586ff0982e7b`.

## Reviewed test

`tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php`  
SHA-256: `d319bce936ce6638433ed875fd97526dab4a363fbc16ecd69185d30ae5885b7d`

Review addressed:
`reviews/tests/INSPECTION-ITEM-COMPLETE-001-mariadb.md`  
SHA-256: `6cb0958e2be8a525a5c55b72e0281bbd55b96db8608e27b485a218deea86680b`.

## Blocker closure

- MDB-01: each worker publishes its exact caller-owned `mysqli::thread_id` in
  its ready record. The coordinator records its own id and joins
  `INNODB_LOCK_WAITS`, `INNODB_TRX` and `INNODB_LOCKS`. It requires exactly both
  distinct worker ids as requesters, the exact coordinator id as blocker, exact
  private revision table, `PRIMARY`, `RECORD`, and lock data beginning with case
  `4512`. Missing metadata/correlation is a bounded setup failure; no global wait
  count is accepted.
- MDB-02: worker go wait, readiness, lock correlation, result arrival and process
  completion are bounded. Every process and pipe is recorded immediately. The
  top-level `finally` first rolls back/closes the coordinator, closes query
  connection, terminates then escalates/reaps only owned children, closes all
  pipes and DB handles, drops the exact runtime user/database, and deletes only
  five exact test-owned artifact members before its exact directory. Every
  cleanup action is independently exception-contained and idempotent.
- MDB-03: setup compares a literal sorted list of all 26 approved prefixed table
  names using binary `LEFT` equality. The decoy is created and checked
  separately after this exact catalogue assertion.
- MDB-04: admin performs migration/fixture/lock observation only. Before the
  production seam check it creates a unique runtime principal granted only
  `SELECT, INSERT, UPDATE` on the private database. Both workers and the public
  query/replay application use that principal, which has no DDL privilege.
  Catalogue and decoy preservation remain additional assertions.

## Exact lifecycle and RED

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-up
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-down
docker compose -f compose.test.yaml ps --all
```

Relevant result:

```text
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
Uncaught TestFailure: Approved production MariaDB composition seam is missing after healthy canonical v1-v8 setup and DML-only principal creation: FMonitor2\InspectionEvidence\ProductionInspectionEvidenceFactory
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
```

The RED occurs after exact v1–v8 runner output, exact literal 26-table catalogue,
all fixtures, decoy, and successful DML-only principal creation. It is therefore
not setup failure.

An independent post-RED admin probe before Compose teardown reported:

```text
POST_CLEANUP databases=0 users=0
```

No `.test-artifacts/iic-*` directory was printed. Compose then removed container,
volume and network; final `ps --all` was empty.

Production/spec/OpenSpec tasks were not edited. The test requires a fresh
independent Gate 3 rereview before implementation.
