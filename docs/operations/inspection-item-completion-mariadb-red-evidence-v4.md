# INSPECTION-ITEM-COMPLETE-001 — MariaDB overlap oracle evidence v4

Date: 2026-09-01  
Test author: `/root/item_red_author`

This append-only record corrects a MariaDB 11.4.7 metadata portability defect
discovered after the approved production factory became available. No
production or specification file was changed.

Test:
`tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php`  
SHA-256: `927a1470deaf3af3b90c2f53df765d6cfd77ca5620158dab0d2fc9dcf8bac353`.

## Diagnosis

During a real coordinator-held row lock, both exact worker connection ids were
visible in `information_schema.PROCESSLIST` executing the exact prepared
revision `SELECT ... FOR UPDATE`, while MariaDB 11.4.7 exposed neither worker in
`INNODB_TRX` nor either wait in `INNODB_LOCK_WAITS`. Only the coordinator
transaction was present. Therefore the old InnoDB metadata join produced a
false setup failure despite a genuine blocked overlap.

The revised oracle uses metadata actually exposed by this server:

1. the coordinator begins a transaction, executes the exact private-prefix
   revision-row `FOR UPDATE`, fetches it and independently asserts revision
   `0` before workers start;
2. both workers publish distinct caller-owned MariaDB connection ids;
3. while the coordinator transaction remains open, the admin observer requires
   `PROCESSLIST` to contain exactly those two ids continuously for at least
   300 ms, sampled every 10 ms under a bounded 10-second acquisition deadline;
4. each row must be `COMMAND=Query`, have only MariaDB's observed executing
   states (`Statistics`, `Executing` or `Execute`), and normalize to the exact
   private-prefix revision query with either the prepared `?` marker or literal
   case `4512`;
5. any missing sample resets the sustained window; unrelated connections,
   queries, tables or cases cannot satisfy it;
6. only after the sustained window does the coordinator release its fetched row
   lock.

This is not a sequential or delay-only approximation: the exact two worker
connections must remain inside the exact query against the only coordinator-
locked private row for the whole observation window.

## Controlled sensitivity RED

The test-only selector `FMONITOR_IIC_BREAK_OVERLAP=1` deliberately commits the
coordinator before releasing the workers. It leaves every other production,
fixture, connection-id and cleanup path unchanged. The corrected oracle must
then reject the missing overlap.

```sh
make test-env-up
FMONITOR_IIC_BREAK_OVERLAP=1 tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-down
docker compose -f compose.test.yaml ps --all
```

Result:

```text
SETUP_FAILURE: both exact worker revision-lock queries continuously visible in PROCESSLIST for 300ms.
Expected: true
Actual: false
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
```

The controlled RED proves the new overlap oracle is sensitive to premature lock
release. The test's bounded reaper, cleanup self-verification, DML-only runtime
principal, exact 26-table catalogue, decoy and full public DTO assertions remain
unchanged. No `iic-*` artifact directory remained; Compose container, volume and
network were removed and final `ps --all` was empty.

Per the delivery gate, the default GREEN-compatible run is reserved until fresh
independent review of this test correction.
