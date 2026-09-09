# PRODUCTION-JOBS-RECOVERY-001 — recovery durable jobs/outbox после canonical v23

## Простыми словами

Production backup после #34 сохраняет очередь и outbox вместе с остальными данными.
Restore возвращает их ровно в записанное состояние и ничего не запускает сам.
Повтор, reclaim и внешняя доставка начинаются только после явного запуска jobs
services; неизвестный ответ провайдера остаётся неизвестным, а не превращается в
ложный success.

## 1. Public seam и версия

Оператор использует тот же deployment-only
`bin/fmonitor2-runtime-recovery.php backup|restore` из exact reviewed source image.
V23 bundle сохраняет `formatVersion=fmonitor-runtime-backup-v1`, но содержит exact:

- `database.schemaVersion=23`;
- 69 canonical base-table names;
- 39 canonical AUTO_INCREMENT table/value records;
- `deferred=[]`;
- прежние literal `policyDecisions` без назначенных retention/RPO/RTO значений.

Исторический `RuntimeRecoverySchemaV22` и его v22/63/35/deferred contract MUST
оставаться неизменными. V23 tooling SHALL принимать только exact v23 inventory и
source/image attestation. V22 tooling SHALL отвергать v23 bundle как
`BUNDLE_INVALID` до target mutation. V23 tooling также SHALL NOT изображать exact
v22 source image: v22 bundle восстанавливается exact v22 image, затем обновляется
forward migration23.

## 2. Ordered quiesce

`--writers-stopped` для v23 является операторской attestation следующего порядка:

1. остановлен `jobs-scheduler`, поэтому новые slots и outbox dispatch jobs не
   появляются;
2. остановлен `jobs-worker`: завершённый в grace child сохраняет terminal outcome,
   forced child оставляет current lease и неизвестный result без guessed success;
3. остановлены `web` и `php`;
4. только после подтверждённой остановки создаются DB/state snapshots.

CLI не выводит container/process details и не пытается угадать arbitrary
orchestrator state. Private drill evidence MUST содержать exact commands, process
outcomes и image/source identity. Отсутствующая attestation возвращает
`SOURCE_NOT_QUIESCED` до bundle publication.

## 3. Exact persisted Jobs state

Backup/restore SHALL сохранять exact rows, AUTO_INCREMENT values и append-only
history всех шести v23 Jobs tables:

- ready jobs, включая future `availableAtUtc`;
- leased attempts1–5 с current worker/token/leased/heartbeat/expiry;
- completed jobs/result и dead jobs/failure;
- все created/claimed/heartbeat/expired/reclaimed/retry/completed/dead events;
- scheduler slots и worker/scheduler role heartbeats;
- pending intents без dispatch job, pending ambiguous intents с linked generic job
  и attempt events, delivered/dead intents;
- manual `retry_of_job_id`, provider reference и intent-wide idempotency reference.

Dump, tokens, payload, provider details и primary evidence остаются private mode0600
и не попадают в repository report/stdout. Restore SHALL выполнить zero queue
transition: не обновляет heartbeat, не очищает/продлевает lease, не создаёт dispatch
job, не dead-letter/retry и не вызывает handler/transport. Runtime readiness проверяет
exact schema/storage/DB, но SHALL NOT считать stale heartbeat, expired lease или dead
backlog повреждением restore. Operational `JobsHealth` после restore может честно
быть unhealthy до запуска services/recovery.

## 4. Явный fake-only resume

Recovery начинается отдельным операторским действием после `RESTORE_COMPLETED`.
Acceptance использует только injected fake/local transport и DML-only account:

- committed pending intent без job получает ровно один `outbox.dispatch`; repeat
  sweep не создаёт duplicate;
- delivered/dead intents не получают автоматический dispatch;
- unexpired lease не выдаётся другому worker;
- после exact expiry attempt1–4 получает новый token/attempt, а old token возвращает
  `stale_lease` без mutation;
- expiry attempt5 добавляет `expired/dead`, не создаёт attempt6;
- ambiguous delivery повторяет только тот же intent со stable provider idempotency
  reference и не повторяет исходную domain operation;
- dead job возобновляется только explicit authorized linked retry;
- completed/dead rows и старая append-only history не переписываются.

Restore и acceptance MUST NOT выполнять real Bitrix/email request. Runtime DML
account проходит resume operations, а actual CREATE остаётся запрещён MariaDB.

## 5. Forward update и rollback boundary

Forward drill SHALL:

1. восстановить v22 bundle exact v22 image в empty contour;
2. сохранить exact 63-table rows/state и 35 AUTO_INCREMENT values;
3. применить только additive migration23;
4. доказать, что прежние rows/state/counters не изменились, а шесть Jobs tables
   exact и первоначально пусты;
5. создать v23 Jobs states, выполнить v23 backup/restore и fake-only resume.

Schema downgrade отсутствует. V22 binary не применяет v23 bundle и не удаляет Jobs
tables/history. Возврат только web/php image допустим лишь после отдельного
доказательства HTTP schema compatibility; jobs services при этом MUST быть
остановлены, а background recovery/health не объявляются работающими. Если в v23
есть pending/leased/ambiguous work, полный contour rollback к v22 не поддерживается:
оператор создаёт новый contour из подходящего bundle и выполняет исправленную
forward migration.

## 6. Executable evidence

До implementation независимый RED через public recovery/queue/outbox seams MUST
проверить:

- exact v23 69/39 bundle и restore;
- wrong/missing/extra Jobs table, wrong frontier и omitted/extra AUTO key как
  `BUNDLE_INVALID` с zero target DDL/state;
- exact rows/history/AI до и после restore без service/transport invocation;
- ordered quiesce: graceful terminal snapshot и forced unknown-lease snapshot;
- fake-only resume matrix раздела4;
- v22→v23 preservation и v23→v22 zero-mutation rejection.

Каждый execution task остаётся pending до отдельного Gate3. Этот contract не
определяет notification triggers/templates, provider policy, broker, real sends,
retention/RPO/RTO или arbitrary downgrade.
