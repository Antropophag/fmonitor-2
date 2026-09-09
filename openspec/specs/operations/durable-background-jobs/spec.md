# durable-background-jobs Specification

## Purpose

Задаёт общую durable MariaDB queue для безопасного фонового исполнения прикладных
операций, конкурентных workers, ограниченных retries и операторского recovery.

## Requirements

### Requirement: Application owner ставит versioned job через один queue seam
Система SHALL принимать enqueue только через публичную queue operation с
`job_type`, положительной `payload_version`, canonical payload без secrets,
`available_at`, idempotency key и actor/source context. Успешный enqueue SHALL
durably сохранить identity и append-only created event; повтор exact key/fingerprint
SHALL вернуть тот же job, а другой fingerprint SHALL дать conflict без записи.
Create SHALL вернуть `created+jobId`, exact repeat `replayed+same jobId`, conflict
`conflict`. Claim item SHALL содержать `jobId/jobIdentity/jobType/payloadVersion/
payload/attempt/leaseToken/leasedAtUtc/leaseExpiresAtUtc`; отсутствие работы — `[]`.

#### Scenario: Новый job
- **WHEN** application owner ставит поддерживаемый type/version с новым idempotency key
- **THEN** один ready/scheduled job и created event сохраняются и возвращается его identity

#### Scenario: Replay и conflict
- **WHEN** enqueue повторяется с тем же key
- **THEN** exact payload возвращает прежний identity, а другой type/version/payload даёт conflict без второго job

#### Scenario: Неподдерживаемый или секретный payload
- **WHEN** type/version неизвестен, payload не проходит bounded schema либо содержит credential field
- **THEN** enqueue отвергается до записи job/event

### Requirement: Claim выдаёт одному worker ограниченный lease
Worker SHALL атомарно получить не более configured batch доступных jobs в stable
order через `FOR UPDATE` с fixed 2-second lock wait и без `SKIP LOCKED`, исключая
active leases. Waiter SHALL повторно оценить state после winner commit; timeout
SHALL fail-safe без claim. Claim SHALL записать opaque lease token, worker
identity, `leased_at`, `lease_expires_at = leased_at + 5 минут`, номер attempt и
append-only claimed event. Только current token SHALL heartbeat/complete/fail job.
Heartbeat не чаще одного раза в минуту SHALL продлевать lease до 5 минут от heartbeat.
Heartbeat outcomes: `accepted+leaseExpiresAtUtc`, `too_soon`, `stale_lease`.
Completion: `completed` или `stale_lease`. Fail: `retry_scheduled+availableAtUtc`,
`dead` или `stale_lease`.

#### Scenario: Параллельный claim
- **WHEN** два workers одновременно claim-ят один ready job
- **THEN** только один получает token/attempt, другой его не видит, и существует один claimed event

#### Scenario: Expired lease reclaim
- **WHEN** worker исчез после claim attempts 1..4 и 5-минутный lease истёк
- **THEN** следующий worker получает новый token/attempt, old token больше не изменяет job, а history сохраняет expired/reclaimed

#### Scenario: Expired fifth lease
- **WHEN** lease пятой attempt истёк
- **THEN** job становится dead с expired/dead events и никогда не получает attempt6

#### Scenario: Graceful stop
- **WHEN** worker получает stop signal
- **THEN** он прекращает новые claims, завершает либо явно освобождает текущий job в пределах runtime grace period и обновляет heartbeat перед выходом

### Requirement: Handler completion и retries имеют устойчивые outcomes
Registry SHALL сопоставлять exact job type/version одному application handler.
Success SHALL атомарно сохранить terminal result и completed event. Retryable
failure SHALL назначить delays `1m`, `5m`, `15m`, `1h` после attempts 1..4;
failure пятой attempt SHALL сделать job `dead`. Permanent failure SHALL стать
`dead` сразу. Failure code и bounded safe detail сохраняются без secrets; вся
attempt history append-only.

#### Scenario: Retryable failure
- **WHEN** handler возвращает retryable failure на attempts 1, 2, 3, 4 и 5
- **THEN** первые четыре получают exact next available delays, пятая становится dead и каждый outcome добавлен в history

#### Scenario: Late result старого lease
- **WHEN** handler старого worker возвращает результат после reclaim
- **THEN** completion отвергается как stale token и не меняет result нового attempt

#### Scenario: Идемпотентный предметный эффект
- **WHEN** job повторно выполняется после потери ответа либо lease expiry
- **THEN** stable job/run identity передаётся application handler и повтор не создаёт второго предметного эффекта

### Requirement: Operator видит и безопасно повторяет сбои
Авторизованный operator CLI SHALL list-ить failed/dead jobs и expired leases с
type/version, safe failure code, attempt timestamps/counts и correlation identity,
но без payload secrets. Retry terminal job SHALL требовать job identity и новую
operation identity, создавать новый linked job с тем же validated payload и SHALL
NOT редактировать history либо выполнять handler inline.

#### Scenario: Failure inspection
- **WHEN** оператор запрашивает failed/dead/stale jobs с bounded pagination
- **THEN** CLI возвращает machine-readable ordered summary и ненулевой health status при operational backlog

#### Scenario: Manual retry replay
- **WHEN** оператор дважды повторяет dead job с одной operation identity
- **THEN** создаётся один linked retry job; новый operation id создаёт отдельный явно аудированный retry

#### Scenario: Неавторизованный retry
- **WHEN** caller не имеет deployment/operator authority
- **THEN** CLI fail-closed до queue mutation и не раскрывает payload

### Requirement: Health отражает текущую работоспособность
Health SHALL вычисляться из DB heartbeat/schedule/queue state, а не ready-file.
Он SHALL сообщать stale worker heartbeat, stale scheduler tick, overdue ready jobs,
expired leases и dead count через bounded counters/timestamps. Успех прошлой работы
не SHALL скрывать текущую остановку.

#### Scenario: Healthy processing
- **WHEN** worker/scheduler heartbeats свежие, expired leases нет и oldest ready job в допустимом age
- **THEN** health успешен и возвращает safe freshness summary

#### Scenario: Старый ready-file
- **WHEN** filesystem marker существует, но DB heartbeat устарел
- **THEN** health неуспешен и marker не влияет на outcome
