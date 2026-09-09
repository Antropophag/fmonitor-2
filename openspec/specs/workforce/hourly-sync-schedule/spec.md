# hourly-sync-schedule Specification

## Purpose

Сохраняет существующую ежечасную кадровую синхронизацию, перенося её с shell-loop
на уникальный Europe/Moscow scheduler slot и общий durable worker.

## Requirements

### Requirement: Scheduler ставит один workforce job на московский час
Scheduler SHALL вычислять due slots в `Europe/Moscow` с nominal time `HH:07` и
enqueue type/version workforce sync с unique key `workforce-hourly-v1/<local-hour>`.
Scheduler slot key использует эту строку; job idempotency/run identity — deterministic
masked-first16 SHA-256 UUID от `workforce-hourly-v1\0<local-hour>`. Payload exact:
`scheduleSlot,dueAtUtc,runIdentity`.
Scheduler SHALL только ставить job и SHALL NOT fetch-ить Bitrix или писать workforce
facts. Повторные/конкурентные ticks одного slot SHALL дать один job.
Tick transaction SHALL lock latest slot `FOR UPDATE` с fixed 2-second wait; waiter
после commit SHALL повторно оценить unique slot, а timeout не создаёт job/slot.

#### Scenario: Обычный hourly tick
- **WHEN** scheduler tick происходит после `HH:07` Europe/Moscow и slot ещё не поставлен
- **THEN** создаётся один workforce sync job с stable slot/run identity

#### Scenario: Конкурентные ticks
- **WHEN** два scheduler processes одновременно ставят один local-hour slot
- **THEN** unique key сохраняет один job и оба получают один identity/safe duplicate outcome

### Requirement: Пропущенное расписание схлопывается до последнего due slot
После downtime scheduler SHALL поставить только последний due slot на момент tick и
SHALL NOT backfill-ить каждый пропущенный час. Slot до наступления `:07` — предыдущий
local hour; после `:07` — текущий. Schedule history SHALL отметить число схлопнутых
пропущенных slots для operator visibility без создания jobs за них.

#### Scenario: Несколько пропущенных часов
- **WHEN** scheduler возобновляется после downtime более одного часа
- **THEN** он enqueue-ит только последний due slot, сохраняет catchup count и не создаёт burst внешних calls

#### Scenario: Restart до минуты 07
- **WHEN** scheduler запускается в `10:03` Europe/Moscow после пропуска
- **THEN** последним due является slot `09:07`, а slot `10:07` появится только после наступления времени

### Requirement: Workforce handler сохраняет текущую application operation
Worker handler SHALL использовать существующий readonly Bitrix delivery port и
`MariaDbWorkforceSynchronization::run` с stable job/run identity. Он SHALL сохранять
текущие full-delivery-before-publication, history/checksum/missing outcomes и SHALL
NOT использовать pilot manifest/generation. Повтор attempts SHALL не создавать
вторую completed sync run или повторные workforce observations для одного identity.

#### Scenario: Успешный fake delivery
- **WHEN** worker выполняет workforce job с полным fake transport batch
- **THEN** текущая synchronization operation публикует один completed run и job становится completed

#### Scenario: Delivery failure
- **WHEN** fake transport возвращает retryable TLS/timeout/response failure
- **THEN** workforce facts не публикуются, job следует общей retry policy и safe run failure остаётся наблюдаемым

#### Scenario: Real external transport запрещён в tests
- **WHEN** выполняются unit/integration acceptance checks этого среза
- **THEN** используется injected fake/local synthetic transport и ни один real Bitrix endpoint не вызывается
