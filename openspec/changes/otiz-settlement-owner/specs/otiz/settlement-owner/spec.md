## Purpose

Определяет три существующие операции выплат, удержаний и сторно ОТиЗ у единственного append-only владельца и исключает повторную выплату одного объекта через разные срезы.

## ADDED Requirements

### Requirement: Владелец предоставляет ровно три операции
Система SHALL предоставлять `recordDiscipline(actor,snapshotId,objectId,positiveCents,basis,artifact,operationId)`, `completeSnapshotPayments(actor,snapshotId,operationId)` и `reverse(actor,closureId,basis,operationId)`. Actor MUST быть активен с current `otiz.manage`. HTTP проверяет session/CSRF и parsing; arbitrary manual paid/deadline input SHALL NOT существовать.

#### Scenario: Удержание
- **WHEN** actor передаёт accepted snapshot/object, cents 1..1000000000000, basis 1..500, artifact 0..300 и canonical lowercase UUID
- **THEN** одна транзакция добавляет closure `paid=0, discipline=cents, deadline=0`, event и receipt

#### Scenario: Полное закрытие
- **WHEN** actor вызывает bulk для accepted snapshot
- **THEN** операция пропускает blocked/zero objects, под sorted locks добавляет exact remaining paid amounts с fixed basis и фиксирует весь batch all-or-nothing

#### Scenario: Отказ
- **WHEN** authority/input invalid, snapshot/object/closure отсутствует или snapshot не accepted
- **THEN** возвращается `FORBIDDEN`, `INVALID_COMMAND`, `NOT_FOUND` либо `SNAPSHOT_NOT_ACCEPTED` без closure/event/receipt

### Requirement: Budget сериализован по financial object
Financial basis SHALL быть legacy object_id. Под lock budget MUST быть `max(0, accepted accrued_cents − global signed sum(paid_cents+discipline_cents+deadline_cents))`; closed_before повторно не вычитается. Discipline сверх budget возвращает `AMOUNT_UNAVAILABLE`. Bulk повторно вычисляет budget под locks.

#### Scenario: A02
- **WHEN** S1 accrued=100000, S2 accrued=150000 и S1 закрыт на100000
- **THEN** S2 закрывает ровно50000 независимо от порядка build/payment

#### Scenario: Конкурирующие bulk
- **WHEN** S1/S2 concurrent complete одного object при accrued100000/150000
- **THEN** допустимы100000+50000 либо150000+zero; signed total не превышает150000, partial batch отсутствует

#### Scenario: Over-budget discipline
- **WHEN** requested discipline после lock превышает remaining budget
- **THEN** `AMOUNT_UNAVAILABLE` не создаёт facts

### Requirement: Replay, no-op и reversal append-only
Actor+operationId SHALL иметь fingerprint/stable result. Exact replay не создаёт facts; иной fingerprint даёт `OPERATION_CONFLICT`. Successful bulk zero MUST сохранить receipt `no_change` без money/event; refusal receipt не создаёт. Reverse MUST копировать exact original paid/discipline/deadline с обратным знаком, новым обязательным basis, пустым artifact и unique original link. Reversal нельзя reverse; второе даёт `ALREADY_REVERSED`.

#### Scenario: No-op replay
- **WHEN** accepted snapshot не имеет payable objects
- **THEN** сохраняется только receipt `no_change`; replay возвращает его без closure/event

#### Scenario: Historical reversal
- **WHEN** original имеет paid70000, discipline20000, deadline10000
- **THEN** reversal содержит -70000,-20000,-10000, original неизменен, а budget после commit увеличен на100000

#### Scenario: Concurrent reversal
- **WHEN** два commands сторнируют один original
- **THEN** один добавляет reversal, другой получает exact replay либо `ALREADY_REVERSED`
