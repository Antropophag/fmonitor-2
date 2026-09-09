# DURABLE-BACKGROUND-JOBS-001 — durable queue, outbox и hourly workforce

## Простыми словами

Фоновые действия сохраняются в MariaDB до исполнения. Несколько workers не берут
одно задание одновременно, потерянный worker не блокирует очередь навсегда, а
уведомления уходят только после commit исходного действия. Текущий кадровый sync
остаётся ежечасным, но больше не зависит от вечного ready-file и pilot manifest.

## 1. Public seams

Owning namespace `FMonitor2\Jobs`:

- `JobQueue::enqueue(array $command): array` с closed shape из §2;
- `JobQueue::claim(array{workerId:string,batch:int}): list<array>`;
- `JobQueue::heartbeat|complete|fail(array $leaseCommand): array`;
- `Outbox::append(array $intent): array` on caller-owned open transaction;
- `OutboxDispatchScheduler::enqueuePending(): array` after commit;
- `OutboxDeliveryHandler::handle(array $claimedJob): array` under generic worker lease;
- `Scheduler::tick(string $nowUtc): array`;
- `OperatorJobs::{listFailed,retry}` and `JobsHealth::read`.

Production MariaDB adapters implement these interfaces. Worker dispatches exact
`jobType/payloadVersion` to one registered application handler. Scheduler, worker,
CLI and transport MUST NOT write another module's facts directly.

Exact queue results:

- enqueue create `{status:"created",jobId:int}`; exact repeat
  `{status:"replayed",jobId:same-int}`; conflict `{status:"conflict"}`;
- claim item contains exact `jobId,jobIdentity,jobType,payloadVersion,payload,
  attempt,leaseToken,leasedAtUtc,leaseExpiresAtUtc`; no work is `[]`;
- heartbeat returns `{status:"accepted",leaseExpiresAtUtc}` or
  `{status:"too_soon"|"stale_lease"}`;
- complete returns `{status:"completed"}` or `{status:"stale_lease"}`;
- retryable fail attempts1..4 returns
  `{status:"retry_scheduled",availableAtUtc}`, terminal fail/expiry returns
  `{status:"dead"}`, stale token returns `{status:"stale_lease"}`.

## 2. Enqueue contract

`EnqueueJob` contains:

- `jobType`: `[a-z][a-z0-9_.-]{2,79}`;
- `payloadVersion`: integer 1..65535;
- canonical JSON-object payload, encoded size at most 65535 bytes;
- `availableAtUtc`: exact UTC `Y-m-d\TH:i:s.u\Z`;
- `idempotencyKey`: UUIDv4;
- bounded actor/source context without credentials.

Payload keys matching `password|secret|token|credential|authorization` at any depth
are rejected. Registry rejects unsupported type/version before persistence.
Production registry содержит только явно зарегистрированные production handlers;
tests inject собственный registry `test.echo/test.dead` и не расширяют production allowlist.
Unique `(job_type,idempotency_key)` returns the existing job only for byte-exact
fingerprint; another fingerprint returns `conflict`. Success atomically writes one
current row and append-only `created` event.

## 3. Claim and lease

Все queue transitions используют injected `callable(): string` UTC-clock seam. Production implementation
получает authoritative instant одним `SELECT UTC_TIMESTAMP(6)` на operation;
deterministic tests передают fixed callable в тот же constructor slot. Claim transaction задаёт
fixed 2-second InnoDB row-lock wait, использует `FOR UPDATE` без `SKIP LOCKED` и
stable order `available_at_utc, job_id`. Batch is 1..50. Concurrent waiter после
winner commit повторно оценивает state; lock timeout fail-safe не создаёт claim.
One atomic claim excludes active leases
and returns an opaque 32-byte lowercase-hex token. It sets attempt number, worker id,
`leased_at_utc`, and expiry exactly 5 minutes later, plus one `claimed` event.

Only the current token may heartbeat, complete or fail. Heartbeat accepted no more
often than once per 60 seconds and moves expiry to exactly 5 minutes after heartbeat.
A stale token returns `stale_lease` with zero mutation. After expiry attempts 1..4
дают другому worker новый attempt/token и append-only `expired`,`reclaimed` events.
Expiry attempt 5 атомарно переводит job в `dead` с `expired`,`dead` и не создаёт
attempt 6. Worker graceful
stop ceases new claims; an unfinished job becomes reclaimable only by expiry.

## 4. Result and retry

Success atomically records terminal bounded result and `completed`. Retryable failure
after attempts 1,2,3,4 schedules exact delays 1 minute, 5 minutes, 15 minutes, 1 hour.
Attempt 5 becomes `dead`. Permanent failure becomes `dead` immediately. Safe failure
code matches `[A-Z][A-Z0-9_]{2,79}`; diagnostics contain no payload/transport secret.
Every transition appends history. Stable job identity is passed to the handler so a
reclaimed/lost-response execution cannot create a second domain effect.

## 5. Transactional outbox

An approved application owner calls `Outbox::append` on its existing MariaDB
connection and transaction. Domain fact, audit and immutable versioned intent commit
or rollback together. Intent contains domain-event identity, channel/template
reference and bounded render data, never transport credentials. Unique event/channel
key gives exact replay or conflict.

Outbox receives the same injected UTC callable policy as queue. Production clock
queries `UTC_TIMESTAMP(6)` on the caller connection; append persists this immutable
`createdAtUtc`. Sweep uses exact intent `createdAtUtc` as dispatch job
`availableAtUtc` and fingerprint input, never its current wall clock. Queue claim
clock must be equal or later.

После commit отдельный sweep читает pending intents и идемпотентно enqueue-ит
`outbox.dispatch` jobs через общий queue; он не вызывает network. `OutboxDeliveryHandler`
получает intent identity из generic claimed job, поэтому lease/retry/dead принадлежат
общему JobQueue, а outbox row не держит DB lock во время network. Handler invokes an
injected fake/production transport. Provider acceptance followed by lost local
response is `ambiguous_retryable`: generic job retry повторяет только тот же intent с stable provider
idempotency reference. The originating business command is never repeated. Absolute
exactly-once email is not promised. Sweep SHALL NOT call nested transaction-owning
enqueue из caller domain transaction. No product trigger/template is introduced here.
Delivery handler SHALL refuse an ambient DB transaction before transport and attempt
mutation; network callback observes `@@in_transaction=0`.
Каждый `(intent_id,job_id,attempt)` outcome durable. Повтор после outbox commit, но
до/после generic queue complete/fail, SHALL вернуть сохранённый delivered/permanent
result без transport call и без второго attempt event. Уже delivered/dead intent
не отправляется снова старым или новым stale job invocation.
Dead intent SHALL отвергать любой unlinked generic dispatch job до transport/event/
status mutation. Только job с `retry_of_job_id`, verified against authorized terminal
dispatch, может revive intent; handler проверяет relation в Jobs DB.

## 6. Workforce schedule and handler

Scheduler interprets instants in IANA `Europe/Moscow`. Nominal due time is minute 07;
unique schedule key is `workforce-hourly-v1/YYYY-MM-DDTHH`. Job idempotency/run
identity is deterministic UUID: first 16 SHA-256 bytes of
`workforce-hourly-v1\0<YYYY-MM-DDTHH>`, with UUID version4/variant masks and lowercase
canonical formatting. Payload is exactly `{scheduleSlot,dueAtUtc,runIdentity}`.
For 2026-09-09T09 identity is `c3e263b2-0dff-43f9-82d6-dc9193f201e9`;
T10 `027243e1-01f7-4ebd-a387-2facc0eb21ec`; T14
`b5728c62-4fbb-406f-ad9d-1eaa949801ba`. At/after `HH:07` the current hour
is due; before it the previous hour is latest due. Scheduler transaction locks latest
slot `FOR UPDATE` with fixed 2-second wait before inserting next unique slot/job;
waiters re-evaluate after commit and timeout creates nothing. Concurrent/repeated
ticks create one job. After downtime only latest due slot is enqueued; skipped slot count is
recorded, with no burst backfill.

Handler uses existing `BitrixWorkforceDeliveryClient` and
`MariaDbWorkforceSynchronization::run` with stable job identity as run identity.
Full-delivery-before-publication, checksum, missing and history outcomes remain.
Retries do not create a second completed run/observations. Production handler reads
direct config, not pilot manifest/generation. All tests use fake or local synthetic
transport; no real Bitrix/email write is allowed.

## 7. Operator and health

Authorized operator list is bounded/paginated and returns type/version, state,
attempt timestamps/count, safe failure and correlation only. Manual retry requires
terminal job id plus new UUIDv4 operation id, creates one linked queued job, never
edits old history/payload and never runs handler inline. Exact operation replay
returns the linked job. Unauthorized call mutates nothing.
Retry command contains explicit exact `nowUtc` from the same UTC clock policy;
linked job availability is this current instant, never the old terminal timestamp.
`listFailed` accepts page>=1/limit1..100 and returns exact
`{items,total,page,pages,limit}`; items are ordered `jobId` and contain only
`jobId,jobType,payloadVersion,status,attempt,failureCode,createdAtUtc,completedAtUtc`.
Unauthorized list/retry returns exact `{status:"forbidden"}` with no items/mutation.

Health is derived from MariaDB: latest worker/scheduler heartbeats, oldest ready age,
overdue-ready count, expired leases and dead count. A filesystem ready marker is
ignored. Thresholds are explicit configuration; stale processes cannot remain
healthy because an earlier run succeeded.
Result is `{ok,reasons,counters,oldestReadyAtUtc,workerHeartbeatAtUtc,
schedulerHeartbeatAtUtc}`. Counters contain exact `deadJobs,expiredLeases,
overdueReady`. Reasons are sorted from `dead_jobs,expired_leases,overdue_ready,
stale_scheduler,stale_worker`. Existing `FMONITOR_WORKFORCE_READY_FILE` is ignored.

## 8. Acceptance examples

1. Jobs A/B share `available=10:00`; A has smaller id. Two concurrent batch1 claims
   yield A and B exactly once. A token expires 10:05; reclaim at 10:05:00.000001 is
   attempt2 and old completion is stale. Expired attempt5 becomes dead, never attempt6.
2. Retry failures at 10:00 yield availability 10:01, then 10:06, 10:21, 11:21;
   fifth failure is dead.
3. Domain transaction rollback leaves zero fact/audit/outbox rows. Commit exposes all
   three together. Ambiguous delivery retry changes only outbox attempt history.
4. Scheduler tick 2026-09-09 10:03 Europe/Moscow selects 09:07. Tick at 10:07 selects
   10:07. Resume 14:20 after last 10:07 enqueues only 14:07 and records 3 skipped.
5. Worker/scheduler under a DML-only account pass all queue flows and a CREATE probe
   is denied. Old ready-file with stale DB heartbeat is unhealthy.

## 9. Non-goals

A02/A03 money rules, notification triggers/templates, #30 UI, broker, live external
calls, production enablement and full migration of every future handler are outside
this contract.
