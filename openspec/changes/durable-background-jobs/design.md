## Context

Issue #34 и ADR0002 выбирают MariaDB queue. Сейчас native Bitrix delivery и
`MariaDbWorkforceSynchronization::run` существуют, но `workforce-worker.sh` сам
ищет pilot manifest, запускает sync и пишет вечный ready-file. Общего queue/outbox,
lease ownership, scheduler history и operator recovery нет.

## Goals / Non-Goals

**Goals:** глубокий `app/Jobs` module; атомарные enqueue/outbox seams; безопасный
конкурентный DML-only worker; deterministic schedule/retry/lease policies; перенос
workforce hourly orchestration без изменения его delivery/publication semantics.

**Non-Goals:** новый product trigger/template, реальный внешний call в проверках,
message broker, exactly-once transport, UI #30, новая workforce normalization и
перенос всех будущих #11/#12/#13/#15 handlers в одном срезе.

## Decisions

### 1. Jobs — глубокий application/infrastructure module

Public interfaces: `Queue::enqueue(command)`, `Worker::runOne(worker, now)`,
`Scheduler::tick(now)`, `OperatorJobs::{list,retry}`, `JobsHealth::read(now)`.
HTTP/domain modules зависят только от enqueue port; Jobs владеет lifecycle tables,
attempt/events и outbox dispatch state. Handler registry зависит от публичных
application operations владельцев, а не пишет их tables. `rapid-pilot` adapter не
используется. Architecture checker запрещает handler SQL в scheduler/CLI и network
transport внутри domain transaction.

MariaDB подходит уже принятому deployment/backup/transaction model. Broker сейчас
добавил бы второй durable owner без измеренной необходимости.

### 2. Canonical schema отделяет current projection и append-only history

Additive canonical migration создаёт jobs, job_attempt_events, scheduler_slots,
worker_heartbeats, outbox_intents и outbox_attempt_events. Jobs row хранит current
claim/result projection; каждое изменение одновременно добавляет immutable event.
Payload — canonical bounded JSON с type/version schema; credentials только config
reference. Unique `(job_type,idempotency_key)` и outbox event/channel keys дают replay.

### 3. Lease и retry policy фиксированы технически

Injected `callable(): string` возвращает exact UTC instant. Production callable делает
один `SELECT UTC_TIMESTAMP(6)` на operation; fixed callable используется в tests.
Все eligibility/lease/retry CAS сравнения одной operation используют этот
instant. Claim использует короткую transaction с fixed
`innodb_lock_wait_timeout=2`, `FOR UPDATE` без `SKIP LOCKED` и stable order
`available_at, id`; после winner commit waiter повторно видит current state.
Lock timeout fail-safe не создаёт claim. Пятиминутный token lease,
heartbeat interval 60 секунд. Worker не держит DB transaction во время handler/network.
Completion compare-and-set требует current token. Stop прекращает claim; bounded
in-flight завершается, иначе новый worker reclaim-ит после expiry.

Attempts 1..4 при retryable failure получают delays 1m/5m/15m/1h; attempt5 dead.
Expiry attempts1..4 допускает reclaim, expiry attempt5 создаёт expired/dead и не
выдаёт attempt6. Permanent failure dead сразу. Jitter не используется в первом срезе, чтобы contract
и tests были deterministic; thundering-herd risk ограничивает claim batch/worker count.

### 4. Outbox записывает application owner, доставляет dispatcher

Владелец domain transaction вызывает outbox repository в том же MariaDB connection/
transaction и сохраняет immutable intent. После commit отдельный sweep на новом
connection идемпотентно enqueue-ит `outbox.dispatch` job; generic JobQueue владеет
lease/retry/dead. Delivery handler по claimed job читает intent и вызывает injected
transport без outbox row lock. Append не вызывает transaction-owning Queue::enqueue
внутри caller transaction. Provider timeout после возможного acceptance
повторяет только тот же intent; stable provider idempotency reference передаётся где
поддерживается. Исходная business command никогда не повторяется ради notification.

### 5. Scheduler использует unique local slot и latest-only catchup

Scheduler tick получает explicit UTC instant и преобразует в IANA
`Europe/Moscow`; nominal slot `HH:07`. Unique schedule key сериализует replicas.
После downtime ставится только последний due slot, а skipped count сохраняется.
Это сохраняет регулярную свежесть workforce и избегает burst Bitrix calls. Полный
hour-by-hour backfill не имеет product value для snapshot sync.

### 6. Workforce adapter становится одним handler

Payload хранит только schedule/run identity и config reference. Handler вызывает
approved `BitrixWorkforceDeliveryClient` и `MariaDbWorkforceSynchronization::run`.
Stable job identity становится sync run id на retries. Удаляются production
manifest lookup/sleep loop/ready-file из `rapid-pilot/workforce-worker.sh`; временная
CLI compatibility может делегировать enqueue/operator run, но не содержать вторую sync.

### 7. Runtime roles и operations

Scheduler/worker/dispatcher запускаются из exact production image под DML-only
principal; migrations остаются separate. Worker/scheduler health — DB timestamps and
counters. Operator CLI имеет отдельную deployment authorization boundary, bounded
pagination и operation-id replay. #30 сможет обернуть этот CLI/query seam UI без
доступа к payload mutation.

## Risks / Trade-offs

- [Lease истекает во время долгого transport] → heartbeat каждые 60s, token CAS и
  idempotent handler; fake clock/race tests.
- [MariaDB `SKIP LOCKED` behavior зависит от transaction] → real MariaDB parallel
  subprocess proof, rollback/connection-loss cases.
- [Provider принял email, ответ потерян] → explicit ambiguous outcome, retry только
  intent, stable reference, без exactly-once claim.
- [Dead backlog незаметен] → DB health counters/freshness и operator list/retry.
- [Scheduler downtime вызывает нагрузку] → latest-only catchup и skipped audit.
- [Старый workforce shell остаётся второй реализацией] → удалить orchestration после
  parity GREEN; native delivery/application operation сохраняются.

## Migration Plan

1. Нормативная spec и executable RED: queue races/expiry/retries, atomic outbox,
   scheduler slots/catchup, operator recovery и workforce fake-transport parity;
   независимый Gate3.
2. Additive migrations и `app/Jobs` minimal implementation; DML-only focused GREEN.
3. Подключить workforce schedule/handler, удалить pilot manifest loop/ready-file,
   проверить один current operation/result на retries.
4. Запустить новый worker/scheduler в изолированном runtime без real external config,
   затем independent Gate5 и один full CI. Real Bitrix enable/deploy — отдельное
   разрешённое действие с exact image/config.
5. Rollback останавливает scheduler/workers и возвращает operator-invoked workforce
   sync; additive queue/outbox/history не удалять, pending jobs не терять и не
   выполнять автоматически старым runtime.
