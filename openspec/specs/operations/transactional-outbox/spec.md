# transactional-outbox Specification

## Purpose

Гарантирует атомарное сохранение намерения внешнего уведомления с предметным
изменением и отдельную post-commit доставку без ложного exactly-once обещания.

## Requirements

### Requirement: Outbox intent входит в транзакцию application owner
Application operation, для которой утверждён notification trigger, SHALL записать
domain facts/audit и immutable versioned outbox intent в одной DB transaction.
Intent SHALL содержать ссылку на committed domain event, channel/template reference,
bounded non-secret data и unique event/channel idempotency key. Этот механизм SHALL
NOT определять новые product triggers/templates.
Injected UTC clock SHALL в production читать `UTC_TIMESTAMP(6)` на caller connection;
append SHALL сохранить immutable `createdAtUtc`.

#### Scenario: Commit
- **WHEN** утверждённая domain operation и outbox insert успешны
- **THEN** fact, audit и один pending intent становятся видимы вместе после commit

#### Scenario: Rollback
- **WHEN** domain write либо outbox insert завершается ошибкой
- **THEN** ни domain change, ни notification intent не публикуются

#### Scenario: Replay domain command
- **WHEN** идемпотентная domain command повторяется после commit
- **THEN** unique event/channel key возвращает существующий intent без дубликата

### Requirement: Network delivery выполняется только после commit
Post-commit sweep SHALL идемпотентно enqueue-ить `outbox.dispatch` job для committed
intent через общий queue, не вызывая network и не входя во внешнюю domain transaction.
Generic worker SHALL claim-ить этот job своим lease, а delivery handler вызывать
injected transport без удержания outbox row lock. Transport tests SHALL использовать fake transport и SHALL NOT вызывать
real email/Bitrix/external endpoint.
Sweep SHALL использовать intent `createdAtUtc` как job `availableAtUtc`/fingerprint,
а handler SHALL отвергать ambient DB transaction до transport/attempt mutation.
Handler SHALL durable replay-ить exact `(intent,job,attempt)` delivered/permanent
outcome без transport/duplicate event, включая gap до/после queue complete/fail.
Dead intent SHALL отвергать unlinked dispatch job до transport/mutation; revive
разрешён только job с verified `retry_of_job_id` к authorized terminal dispatch.

#### Scenario: Незакоммиченный intent
- **WHEN** dispatcher читает queue одновременно с незавершённой domain transaction
- **THEN** sweep не создаёт dispatch job и transport не вызван

#### Scenario: Успешная доставка
- **WHEN** fake transport подтверждает delivery
- **THEN** dispatcher сохраняет delivered outcome/provider reference и не повторяет intent

### Requirement: Неоднозначность provider acceptance остаётся at-least-once
Если transport мог принять сообщение, но локальное подтверждение потеряно, система
SHALL сохранить ambiguous retryable outcome и MAY повторить только delivery того же
intent с stable idempotency reference. Она SHALL NOT повторно выполнять исходную
business command и SHALL NOT обещать абсолютное exactly-once email.

#### Scenario: Ответ потерян после provider acceptance
- **WHEN** fake transport фиксирует acceptance, а dispatcher получает timeout до durable confirmation
- **THEN** intent остаётся retryable/ambiguous, следующий attempt использует тот же intent identity, а domain facts/audit не меняются

#### Scenario: Terminal delivery failure
- **WHEN** delivery исчерпала 5 attempts либо transport вернул permanent failure
- **THEN** intent становится dead, видим operator CLI, и исходная domain operation остаётся committed

### Requirement: Outbox payload и diagnostics не раскрывают secrets
Outbox SHALL хранить только approved template reference и render data; transport
credentials и готовые sensitive provider responses SHALL оставаться вне payload,
operator output и logs.

#### Scenario: Fake transport failure содержит secret
- **WHEN** transport error включает credential либо recipient-sensitive response bytes
- **THEN** persisted/printed failure содержит только allowlisted code и bounded safe metadata
