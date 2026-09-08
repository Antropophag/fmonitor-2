# Assignment-order original setup — seed gap-lock observer portability

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

The replacement implementation passes the complete capability migration matrix
and reaches seed contention. Parent holds `SELECT ... user_id=18 FOR UPDATE` on
an empty identity range. Child is durably blocked on the exact
`INSERT INTO ... fm2_pilot_users`; repeated independent `PROCESSLIST` snapshots
show its exact connection, database, `STATE=Update` and SQL for more than five
seconds. On this MariaDB build that empty-range insert-intention wait is not
published in `INNODB_LOCK_WAITS`/`INNODB_TRX` as `LOCK WAIT`, so the verifier's
single observer returns no row and fails.

Cleanup contention on an existing row can continue to require the stronger
InnoDB lock-wait join. Seed needs a separately reviewed fail-closed alternative
using exact child connection/process state plus pre-release no-terminal and
post-release success, or another deterministic existing-row barrier that does
not alter fixture semantics. Current production corrections are backed up
outside the repository and remain uncommitted. Task 2.2 is reopened.
