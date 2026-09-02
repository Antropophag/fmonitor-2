# INSPECTION-ITEM-COMPLETE-001 — MariaDB Gate 2 RED evidence v3

Date: 2026-09-01  
Test author: `/root/item_red_author`

This append-only record closes MDB2-01 and MDB2-02 from
`reviews/tests/INSPECTION-ITEM-COMPLETE-001-mariadb-v2.md` (SHA-256
`7572f42b49e447537fccb0aae44324d22045972e6aeba3117a453b3b6c539387`).

Reviewed test:
`tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php`,
SHA-256 `80c742b6c95819761d7faeff9918cd7c440bfa70f4cd8655d84f8e29a00cefa8`.

## MDB2-01 closure

Migration and worker processes now share one bounded owner/reaper path:

- stdout/stderr pipes are nonblocking and capped at 64 KiB each;
- status and pipe draining are polled to a fixed deadline;
- expiry sends TERM, polls, escalates to KILL, polls again, closes every pipe and
  reaps only after the process is observably stopped;
- no normal or exceptional path uses blocking `stream_get_contents` on a live
  child or `proc_close` before bounded termination;
- migration ownership begins immediately after `proc_open`; workers are stored
  with all pipes before the next worker starts.

Top-level code captures the primary throwable, attempts every cleanup step and
collects every cleanup error. It then self-verifies the exact database and user
are absent, all five exact artifact members/root are absent and every owned
worker resource is reaped. Cleanup failure is reported as `CLEANUP_FAILURE`
with the primary failure context instead of being swallowed.

## MDB2-02 closure

Winner business evidence is converted independently from the public query DTO
to a complete scalar projection and compared with literals:

- winner operation id and its independently mapped item `28` or `29`;
- case `4512`, section `1`, actor `7301`, assigned engineer `7302`;
- exact device time and fixed server receipt time;
- base `0`, accepted/current revision `1`;
- template `9101`, version `fixture-v1`, exact 64-character hash;
- ordered installer snapshot `1042`, full name and position.

The other operation query must be `null`. Replay is compared exactly as
`DUPLICATE(1)` and conflict as `OPERATION_PAYLOAD_CONFLICT(1)`. A counting fixed
clock must remain at zero for query/replay/conflict application use. The complete
winner projection is queried and compared unchanged after replay and conflict.

## Reproduced RED and cleanup

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-up
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-down
docker compose -f compose.test.yaml ps --all
```

Result:

```text
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
Uncaught TestFailure: Approved production MariaDB composition seam is missing after healthy canonical v1-v8 setup and DML-only principal creation: FMonitor2\InspectionEvidence\ProductionInspectionEvidenceFactory
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
POST_CLEANUP databases=0 users=0
```

No owned artifact directory was printed. Compose teardown removed container,
volume and network; final `ps --all` was empty. The RED remains after healthy
canonical setup and runtime-principal creation, not a setup failure.

No production/spec/OpenSpec task was edited. Fresh independent Gate 3 rereview
is still required.
