## Purpose

Определяет canonical deployment ownership и безопасную additive evolution
installation-completion facts без runtime DDL, потери истории или превращения
временного adapter-кода в владельца доменных данных.

## ADDED Requirements

### Requirement: Canonical runner единолично владеет completion family
Deployment runner SHALL создавать и проверять exact completion family: исходные
факты ПТО/декларации и append-only corrections. Runtime HTTP, screen, import,
cron и bootstrap consumers MUST NOT выполнять DDL или repair этой family.

#### Scenario: Clean deployment
- **WHEN** deployment operator запускает canonical runner на clean compatible predecessor schema
- **THEN** runner создаёт всю exact completion family, публикует следующую literal schema version и возвращает deterministic result

#### Scenario: DML-only runtime
- **WHEN** ObjectQueue, card, checklist или completion consumer работает под principal без DDL privileges после успешной migration
- **THEN** consumer проходит read-only readiness и выполняет только разрешённые read/DML операции без попытки schema mutation

#### Scenario: Missing runtime schema
- **WHEN** любой member completion family отсутствует или несовместим во время runtime request
- **THEN** request fail closed с существующим infrastructure outcome, не создаёт/не исправляет schema, не пишет domain/audit facts и не публикует partial response

### Requirement: Existing completion history сохраняется additive
Migration SHALL сохранять каждый существующий исходный ПТО/декларация fact,
его identifiers, payload, actor и timestamps byte-equivalent. Она MUST NOT
UPDATE или DELETE исходный fact и MUST NOT выводить correction из отсутствующих
данных.

#### Scenario: Populated compatible pilot table
- **WHEN** predecessor содержит exact populated pilot completion table без correction table
- **THEN** runner сохраняет все rows и auto-increment state, добавляет недостающую correction storage и не создаёт synthetic corrections

#### Scenario: Exact repeat
- **WHEN** runner повторно запускается на exact complete populated family
- **THEN** result детерминирован, schema/data/history не меняются и новые facts не создаются

#### Scenario: Incompatible member
- **WHEN** existing member имеет extra, missing или changed column/index/constraint/engine/charset/collation
- **THEN** full-family read-only preflight отклоняет migration до первого DDL/DML statement и возвращает deterministic conflict inventory

### Requirement: Schema представляет append-only correction chain
Completion schema SHALL позволять сохранить новый correction fact для
существующего ПТО или декларации с обязательной bounded non-empty reason,
replacement date, actor, timestamp и явной ссылкой на предыдущую версию той же
root chain. Исходный `details` и предыдущие correction facts MUST оставаться неизменными
навсегда.

#### Scenario: Correction storage shape
- **WHEN** exact schema fingerprint проверяется после migration
- **THEN** correction member содержит immutable identity, root fact reference, adjacent previous-version reference, replacement date, bounded reason, actor и recorded timestamp с FK/CHECK/UNIQUE constraints для одной same-root gap-free chain

#### Scenario: Concurrent branch protection
- **WHEN** storage получает branch, cross-root previous reference, skipped ordinal или отсутствующую previous version
- **THEN** schema constraints отклоняют row и не переписывают принятую историю; это storage invariant, а не approval будущей command admission policy

### Requirement: Completion meaning не теряется при schema ownership
Schema SHALL различать ПТО и декларацию и поддерживать target projection, где
checklist составляет 85%, документы — 15%, а terminal completion требует
effective ПТО и effective декларацию. Migration сама MUST NOT создавать
terminal state, progress или premium/payment facts.

#### Scenario: Existing pair remains projectable
- **WHEN** compatible predecessor содержит исходные ПТО и декларацию для одного installation case
- **THEN** после migration downstream projection может получить оба effective facts и terminal completion meaning без изменения исходных rows

#### Scenario: No declaration
- **WHEN** case имеет effective ПТО, но не имеет effective декларации
- **THEN** schema data не может интерпретироваться как достаточное основание для terminal completion

### Requirement: Prefix, collation и partial recovery проверяются до mutation
Runner SHALL валидировать full-catalogue ASCII prefix ceiling до database
connection/access, использовать validated explicit database-default utf8mb4
collation и завершать только exact-compatible partial family после family-wide
preflight.

#### Scenario: Boundary prefix
- **WHEN** prefix имеет максимальную утверждённую full-catalogue длину
- **THEN** runner принимает его, а следующий byte и invalid characters отклоняет до database access

#### Scenario: Interrupted compatible creation
- **WHEN** только deterministic subset exact family существует после прерванного предыдущего запуска
- **THEN** runner сохраняет existing members/rows и создаёт только missing members

#### Scenario: Partial family with conflict
- **WHEN** хотя бы один existing member partial family несовместим
- **THEN** runner не создаёт missing members и не изменяет existing members/data
