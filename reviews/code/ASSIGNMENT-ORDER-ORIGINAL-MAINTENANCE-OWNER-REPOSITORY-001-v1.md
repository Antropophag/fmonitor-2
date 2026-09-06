# Независимый Gate 5 review: MAINTENANCE owner + repository v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Reviewed implementation: `9c48b442dd18769ba6b933df15901a1347df0612`
- Baseline: `3d4e852866ace2ef3abf776c8ffa027b7eca9d91`
- Specification SHA-256: `d4712f8e82cc7c2f9865bd61924ef24b0cd2640ddbbde3d96be4f5dc9cc7c72e`
- Verdict: **APPROVED**

Reviewer не писал specification, tests или implementation. Scope строго
ограничен maintenance application owner, value/page validation, native MariaDB
maintenance repository и их production/real-verification composition. Native
FileStorage orphan list/lock/delete остаются stubs и не входят в approval.

## Source assessment

Public contracts реализуют parent names/types без нового state-changing seam.
MaintenanceOwner принимает только MaintenanceDependencies. Invalid shape
завершается до logger/auth/lookup/clock/storage/audit. Configured authorization
идёт первой; denied invocation не читает terminal или storage, получает один
validated instant и пытается сохранить terminal denial через тот же repository.

Authorized lookup snapshots status/result once. Только закрытый валидный
COMPLETED/REJECTED/PARTIAL становится REPLAYED; PARTIAL сохраняет retryable,
counts и cursor. Malformed/contradictory lookup даёт fixed persistence diagnostic
до clock/storage. Future cutoff использует captured clock и terminal rejection;
unavailable clock/page не создаёт audit.

Page validation проверяет status, list/batch, unique identities, binary
time/identity ordering, cursor boundary, cutoff, kind/hash/size grammar и exact
next cursor до первого lock. Item owner получает один lock, валидирует status/id,
читает reference только для finalized, никогда не трактует NOT_FOUND/
UNAVAILABLE как unreferenced, выполняет delete один раз и release в finally.
Release Throwable даёт только phase-only diagnostic и не меняет counts.
Conservation и STORAGE_FAILURE-before-LOCKED priority соблюдены.

После item effects один immutable MaintenanceCommit передаётся repository.
Любой non-COMMITTED outcome даёт FAILED/PERSISTENCE_FAILURE с уже наблюдёнными
counts/cursor, без повторного delete/commit и без ложного утверждения об отсутствии
terminal/filesystem effects. Logger обёрнут existing best-effort guard.

MariaDB repository constructor passive. Commit DTO и prefix проверяются до SQL;
active borrowed transaction даёт ROLLED_BACK без observer/write/control.
Owned READ COMMITTED transaction проверяет begin/commit/rollback native false и
observer failures. Result и audit вставляются атомарно. Request-key collision
подтверждается key-only FOR UPDATE, затем confirmed rollback возвращает CONFLICT;
payload старого result не читается. Other rollback и commit acknowledgement
uncertainty классифицируются ROLLED_BACK/OUTCOME_UNKNOWN без retry.

Reader владеет read-only consistent snapshot через существующий SQL owner,
требует ровно один request и matching audit либо полное отсутствие обоих,
проверяет request echo, principal/time/audit ID, status/reason/retry/count/cursor
closure и byte-equal shared fields. Result копируется до transaction release;
active caller, malformed backing, SQL/observer/release failure дают UNAVAILABLE.
Missing audit и bad conservation не превращаются в replay.

Production factory валидирует configured non-test principal/capability до
resource I/O, затем открывает guarded safe-log и валидирует root/prefix. Она
связывает real authorizer, repository/reference adapters и system clock/no
faults; public production observer selector не добавлен. Acquisition failure
закрывает уже открытый safe-log и возвращает redacted configuration exception.
Runtime только объявляет/подключает существующие public contracts.

Новых DDL, schema/grants, HTTP/cron writers или production hotspots >=150 строк
нет. `make architecture-check` проходит.

Exact source hashes:

```text
c973ec70f2d9e9cff527602cddeebbc1a73e206c262a9745bcbf7f51e1c0397c  AssignmentOrderOriginalMaintenanceContracts.php
9daf51fade1d5271c7694a27d29bb17ee54c2f2b9dd0b588854b65bb9c76f99d  AssignmentOrderOriginalMaintenanceItems.php
ef1c004f7a17f789710bd397d0ce78a2ff02c36ba10bb290fc5bf21f07d0a4db  AssignmentOrderOriginalMaintenanceValues.php
569ff5d778f307a4133f9fea3e95a0ece9975259ed03d38dba5a313d0366bd2e  AssignmentOrderOriginalMaintenanceOwner.php
ac147854f4398347b9cff4e3dfebb64c3fd9512fce6f62241c69063a382ce852  MariaDbOriginalMaintenanceRows.php
0429ff7f9c21bb8c6e7459a2679546e5d67346ec3ed50b5a771c0d645c6a1e92  MariaDbOriginalMaintenanceRepository.php
616cc23fc2583d2d947d2462dc32d86c5459ad5c3b7b4436f610d8d6d45c2608  ProductionAssignmentOrderOriginalMaintenanceFactory.php
33bc1f02368039a57e96711093d81cc803f7c0cf7db91cdab3801dae847d791b  AssignmentOrderOriginalRuntime.php
```

## Verification evidence

Archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-maintenance-owner-repository-green-o0_21y48`.

```text
1c0456de9fad24bab3d726141da2eb05805e079343b83c78ad9c3985c8086248  evidence.json
```

Manifest имеет `complete=true`, clean before/after status и exact HEAD
`9c48b442dd18769ba6b933df15901a1347df0612`. Owner34/34 и repository17/17
завершились exit0. Отдельный architecture log exit0 имеет SHA-256
`e136384c4f780880dae297b69446fff60e3d8e6848597a6d20df1daf65bb9450`.

**APPROVED** закрывает только scoped owner/repository implementation на этом SHA.
Native storage, весь maintenance integration/Gate5, combined command,
`VERIFY_OK`, CI, deploy/restart и launch readiness остаются открыты.
