# JOBS-RUNTIME-WIRING-001 — production CLI и optional jobs services

## Простыми словами

Один production image запускает scheduler, worker и operator commands с прямой DB
config. По умолчанию jobs profile выключен. Кадровый handler проверяется через
локальный HTTPS transport; email transport пока отсутствует и отказывает безопасно.

## CLI contract

`bin/fmonitor2-jobs.php` имеет только modes:

- `schedule-once --now-utc <instant>`: один Moscow workforce tick;
- `scheduler`: daemon tick/heartbeat, SIGTERM stops, no catchup кроме latest-only;
- `worker`: `JobWorkerProcess`, role heartbeat `worker:<instance>`;
- `health`: DB-derived exact safe JSON and exit0 healthy/70 unhealthy;
- `list-failed --page <n> --limit <n>`: fixed deployment authority, no user-provided
  permission/authority flag;
- `retry --job-id <n> --operation-id <uuid> --now-utc <instant>`: same fixed authority.

`bin/fmonitor2-job-handler.php` is internal. It reads exactly one claimed-job JSON
object from bounded stdin and dispatches only registered type/version. The object has
exactly `jobId`, `jobIdentity`, `jobType`, `payloadVersion`, `payload`, `attempt`,
`leaseToken`, `leasedAtUtc`, and `leaseExpiresAtUtc`; malformed JSON, missing/extra
keys, and unknown type/version exit 64 before domain or transport access. `workforce.sync`
builds existing verified Bitrix client from explicit origin/user/departments/token
file/optional CA and delegates `MariaDbWorkforceJobHandler`; child opens its own DB
connection. `outbox.dispatch` without configured production transport returns exact
permanent `OUTBOX_TRANSPORT_UNCONFIGURED`, performs no network and leaks no intent.
For `workforce.sync`, payload `runIdentity` is the immutable original scheduler
identity and remains unchanged on an operator-linked retry. The claimed job's
`jobIdentity` is the current execution-family identity; both are valid UUIDs and may
differ. The handler passes the current `jobIdentity` to the native idempotent owner.

All modes require direct canonical `FMONITOR_DB_*` and process prefix, no demo
manifest/generation/default DB endpoint. Worker/scheduler use DML-only principal,
never migrations/DDL. Output/error are closed JSON, exclude credentials, paths,
payload and native errors. Unknown mode/config/input exits64 CONFIGURATION_INVALID;
infrastructure exits70 JOBS_UNAVAILABLE. One-shot commands write one JSON object to
stdout: `schedule-once` has exactly `slot,dueAt,created,jobId,skippedSlots`; `health`
has the exact `MariaDbJobsHealth::read()` shape; `list-failed` and `retry` have the
exact `MariaDbOperatorJobs` shapes. Invalid invocation writes only
`{"ok":false,"error":"CONFIGURATION_INVALID"}` and performs no DB mutation.

## Compose contract

`deploy/runtime/compose.yaml` adds `jobs-worker` and `jobs-scheduler` from the exact
same `FMONITOR_RUNTIME_IMAGE`, both under profile `jobs`; default config/start for
db/php/web is unchanged and jobs services are inactive unless profile selected.
Neither publishes a port. Both use runtime DML config/state, `stop_signal: SIGTERM`,
60s grace, no startup migration/bootstrap. Only the worker receives Workforce private token/optional CA as explicit read-only
external mounts/config; the scheduler receives no transport configuration or secrets.
Absent worker transport config prevents the external handler call.

Scheduler records `scheduler:<FMONITOR_SESSION_INSTANCE>` heartbeat at start and at
least each 60 seconds even within one immutable hourly slot. Worker uses
`worker:<instance>`. Slot rows remain immutable schedule history; health freshness
comes from role heartbeats, not slot timestamps or files.

The historical `rapid-pilot/workforce-worker.sh --once` command remains a thin
compatibility adapter to the existing native `bin/fmonitor2-sync-workforce.php`.
It requires the explicit process prefix, invokes the native CLI exactly once, and
preserves its exit and output. It no longer discovers pilot manifests, loops,
sleeps, or writes a filesystem readiness marker; invocations without `--once` are
invalid. Missing explicit prefix, no arguments, or any other argument exits 64 with
the same closed `CONFIGURATION_INVALID` JSON and never invokes the native CLI.

## Executable acceptance

1. Static contract proves closed modes, same image/profile/no ports, no demo/DDL and
   default profile isolation.
2. Local verified-HTTPS Bitrix fixture returns 51 rows through the real internal CLI;
   native sync publishes exactly 51 under DML-only principal and output excludes
   token/PII/path. No real endpoint is called.
3. `schedule-once`, health, list and retry return their approved exact outcomes;
   missing/invalid config and unconfigured outbox fail safely.
4. Real daemon SIGTERM/heartbeat/grace behavior inherits approved worker/scheduler
   process tests; production wiring adds no test-only bypass.
