## 1. Contract and independent RED

- [x] 1.1 Создать нормативную `specs/DURABLE-BACKGROUND-JOBS-001.md` с exact public seams/outcomes, queue JSON limits, 5-minute lease/60-second heartbeat, retry delays, terminal states, outbox ambiguity, Europe/Moscow latest-only slots и operator authorization; сверить с issue #34, ADR0002 и approved workforce delivery
- [x] 1.2 Добавить executable RED через public seams: enqueue replay/conflict, parallel batch claim, lease expiry/stale token, graceful stop, attempts 1..5, atomic domain+outbox commit/rollback, provider ambiguity, scheduler concurrency/catchup, operator list/retry и DB-derived health; использовать fake clock/handlers/transports и real isolated MariaDB только для locking/transactions
- [x] 1.2a Зафиксировать bounded queue foundation RED: validation, stable claim, causal two-process `FOR UPDATE` race, lease/heartbeat/stale token, finite retry и attempt5 expiry/dead
- [x] 1.3 Зафиксировать characterization текущего `bin/fmonitor2-sync-workforce.php` → `MariaDbWorkforceSynchronization::run` для full success/failure/history/checksum/missing outcomes и получить RED переноса без вызова real Bitrix
- [x] 1.4 Получить независимый Gate3 review normative spec, tests и RED evidence до production implementation; сохранить exact hashes/source и явный verdict

## 2. Canonical persistence and queue owner

- [x] 2.1 Добавить additive canonical migrations jobs/events, scheduler slots, heartbeats, outbox intents/events на актуальном frontier; проверить clean/repeat, compatible populated preservation, incompatible zero-mutation и runtime DDL absence
- [x] 2.2 Реализовать `app/Jobs` enqueue с bounded versioned payload registry, secret-field rejection, durable idempotency replay/conflict и append-only created event в caller transaction; focused tests GREEN под DML-only principal
- [x] 2.3 Реализовать atomic stable-order batch claim с 5-minute opaque token lease, attempt event, 60-second heartbeat, expiry reclaim и token CAS completion/failure; real MariaDB parallel subprocess/connection-loss tests GREEN
- [x] 2.4 Реализовать handler registry и deterministic retry state machine `1m,5m,15m,1h`, attempt5 dead/permanent dead, safe diagnostics и stable handler identity; проверить exact timestamps/history, lost response replay и отсутствие второго domain effect

## 3. Transactional outbox

- [x] 3.0 Зафиксировать ambiguity foundation RED: transaction visibility/rollback, pre-commit dispatcher no-call, replay/conflict, nested/transport secret rejection и stable ambiguous retry без business replay; delivered/permanent/attempt5 остаются в 3.2
- [x] 3.1 Реализовать outbox repository, который application owner enlist-ит в свою открытую MariaDB transaction: unique domain-event/channel intent, commit/rollback/replay observable через representative test application seam
- [x] 3.2 Реализовать dispatcher handler после commit с fake transport, delivered/permanent/retryable/ambiguous outcomes и stable intent/provider idempotency reference; доказать, что timeout after acceptance повторяет только delivery и не выполняет business command
- [x] 3.3 Проверить bounded non-secret payload/diagnostics и запрет network transport внутри domain transaction architecture checker-ом без расширения baseline; реальные email/Bitrix endpoints не вызывать

## 4. Scheduler and workforce migration

- [x] 4.1 Реализовать scheduler tick с explicit clock/IANA Europe/Moscow, nominal `HH:07`, unique local-hour key и latest-only catchup audit; проверить before/after :07, multi-hour downtime и parallel scheduler processes
- [x] 4.2 Добавить workforce type/version handler: existing readonly delivery + `MariaDbWorkforceSynchronization::run`, stable job/run identity и direct production config без manifest/generation; fake batch/error tests подтверждают existing publication/history/checksum/missing parity и retry idempotency
- [x] 4.3 Заменить production `workforce-worker.sh` sleep/manifest/ready-file orchestration на общий scheduler/worker CLI из exact runtime image, сохранив operator-invoked sync compatibility через один application owner; проверить отсутствие второй SQL/fetch implementation
- [x] 4.4 Добавить runtime scheduler/worker services под DML-only principal с graceful claim stop и без migration/external live config; проверить same image digest, no DDL, bounded shutdown и reclaim после forced kill. Ранний in-process `JobWorker` test снят: его stop/neighbor/heartbeat assertions полностью заменены одобренными process-level signal, grace-expiry, cadence, lease-loss и protocol tests для единственного `JobWorkerProcess`
- [x] 4.4a Реализовать и независимо проверить source-level runtime CLI/Compose boundaries, DML-only daemons, SIGTERM/grace/reclaim и локальный HTTPS workforce smoke; exact candidate-image Compose smoke остаётся в 5.3

## 5. Operator operations, health and Done

- [x] 5.1 Реализовать авторизованные CLI list/retry с bounded pagination, safe fields, linked retry и operation-id replay; проверить denied access до mutation, no inline handler и пригодный #30 machine JSON
- [x] 5.2 Реализовать DB-derived worker/scheduler/queue health с thresholds/counters; проверить stale heartbeat/tick, overdue ready, expired lease/dead backlog и игнорирование старого filesystem ready marker
- [x] 5.3 Выполнить focused Jobs/Workforce regression, syntax, `git diff --check`, `make architecture-check` и isolated runtime smoke без real external calls; сохранить exact source/image и результаты
- [ ] 5.4 Получить независимый Gate5 review spec/approved tests/production diff/evidence, затем один полный CI `make test`; не заявлять #34 delivered/production ready без обоих GREEN
- [x] 5.4a Получить независимые Gate5 reviews bounded schema/queue/outbox/scheduler/workforce/worker/operator/runtime-wiring implementations; aggregate candidate review и полный CI остаются в 5.4
- [ ] 5.5 Сверить Done: один queue/outbox owner, atomic intent, DML-only lease worker, fixed retry/lease, unique Moscow schedule/latest-only catchup, workforce parity без pilot manifest, operator recovery/DB health, append-only history и no real transport; product triggers/templates/UI #30 и real external enablement остаются отдельными slices
