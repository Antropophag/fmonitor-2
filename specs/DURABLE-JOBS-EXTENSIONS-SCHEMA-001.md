# DURABLE-JOBS-EXTENSIONS-SCHEMA-001 — outbox, scheduler и heartbeat schema

## Contract

Перед final v23 candidate `FMonitor2\InstallationProcess\JobsSchemaMigration`
SHALL расширить reviewed jobs/events foundation четырьмя таблицами из literal
`tests/Support/jobs_extensions_schema_manifest.json`: `fm2_outbox_intents`,
`fm2_outbox_attempt_events`, `fm2_scheduler_slots`, `fm2_worker_heartbeats`.
Migration preflight SHALL проверить все шесть owned tables до любого DDL; clean,
compatible populated repeat, inverse conflict и два prefixes сохраняют rows/ambient
objects. Runtime worker/scheduler/dispatcher работают DML-only после migration.

Manifest фиксирует ordered columns, types/null/default/extra/charset/collation,
indexes, non-cascading FKs и checks. `@prefix` заменяется validated prefix. Все UTC
instants — exact `CHAR(27) ascii_bin`; JSON — `LONGTEXT utf8mb4_bin` + JSON_VALID.
Status outbox: pending/delivered/dead; delivery lease/retry живёт только в generic job.
Attempt 1..5. Scheduler slot key и
worker identity binary-comparable. Эта schema не вводит product trigger/template.
Каждый outbox attempt event содержит generic `job_id`; unique
`(intent_id,job_id,attempt)` позволяет linked manual retry начать attempt1 нового job,
а provider idempotency reference остаётся стабильным на intent.

После approval root объединяет foundation и extension manifests/tests в одну
canonical v23; отдельной v24 и runtime DDL не создаётся.
