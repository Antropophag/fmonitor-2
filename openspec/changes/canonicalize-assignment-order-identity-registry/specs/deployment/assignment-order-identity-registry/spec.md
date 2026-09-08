## Purpose

Сохранить исторические идентичности распоряжений в общем registry без
перенумерации, создания новых предметных событий или выдумывания дат документов.

## ADDED Requirements

### Requirement: Registry backfill сохраняет исторические значения

Migration SHALL сохранять exact ID, case, version и UTC instant исходного
prepared timestamp; allocator frontier MUST сохранять исторические gaps.
Она MUST NOT изменять исходные order/member/artifact/original/process facts.

#### Scenario: Два исторических распоряжения с пропусками ID
- **WHEN** при остановленных writers оператор переносит orders2/7 и legacy next ID81
- **THEN** registry содержит ровно IDs2/7 с прежними case/version и next ID81; предметных событий нет

### Requirement: Immutable receipt отличает repeat от повреждённого backfill

Backfill rows и immutable completion receipt SHALL фиксироваться одной
transaction. Повтор SHALL проверять frozen historical subset, а не сравнивать
исторический count с выросшим после cutover полным registry.

#### Scenario: Подтверждённый repeat
- **WHEN** exact completed family уже существует и historical subset сохранён
- **THEN** migration не меняет rows, receipt или frontier

#### Scenario: Потеря acknowledgement
- **WHEN** после commit оператор не получил ответ и повторяет migration
- **THEN** immutable receipt позволяет подтвердить тот же backfill без дублей

### Requirement: Schema conflict отклоняется до изменения family

Migration SHALL проверять обе family tables и predecessor schema до первого
изменения; conflicting family MUST NOT автоматически ремонтироваться.
Prefix25 и exact generated identifiers SHALL оставаться поддержанными.

#### Scenario: Wrong existing receipt shape
- **WHEN** одна таблица отсутствует, а другая имеет incompatible shape
- **THEN** migration сообщает conflict, не создаёт отсутствующую таблицу и не меняет данные

### Requirement: Engine completion не разрешает mixed writers

Backfill completion MUST NOT считаться writer-cutover или selection readiness.
Canonical registration/enablement SHALL требовать отдельно reviewed all-writer
compatibility, original-reader handoff и selection-family schema.

#### Scenario: Старый writer ещё может писать
- **WHEN** deployment не доказал исключение N-1 writers
- **THEN** selection и новый release не включаются даже при complete registry backfill
