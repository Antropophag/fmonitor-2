## Purpose

Задаёт отдельный сериализованный deployment seam для canonical migrations, чтобы
ни HTTP startup, ни обычная CLI-работа не владели DDL или частичным catalogue run.

## ADDED Requirements

### Requirement: Миграции выполняются отдельной явной командой
Авторизованный deployment operator SHALL запускать canonical migration catalogue
отдельно от web/worker startup, с явной прямой DB-конфигурацией и migration
principal, имеющим требуемые DDL privileges. Успешная команда SHALL вернуть
машиночитаемый успешный result; preflight/config/DB/migration failure SHALL дать
ненулевой exit без запуска web runtime.

#### Scenario: Успешный deployment migration
- **WHEN** оператор запускает migration command с допустимой конфигурацией и совместимой DB
- **THEN** команда применяет canonical catalogue по зарегистрированному порядку, сообщает success и завершается с кодом `0`

#### Scenario: Ошибка до или во время catalogue
- **WHEN** configuration/preflight не проходит либо migration завершается ошибкой
- **THEN** команда сообщает ограниченную причину без secrets, завершается ненулевым кодом и web runtime не выполняет продолжение catalogue

#### Scenario: Повтор после успеха
- **WHEN** оператор повторяет ту же команду на полностью мигрированной совместимой schema
- **THEN** команда является no-op, сохраняет rows/counters/ambient objects и завершается успешно

### Requirement: Один advisory lock охватывает весь catalogue
Migration command SHALL получить один MariaDB advisory lock с детерминированным
именем, ограниченным одной database и catalogue identity, до первого catalogue
preflight/read и SHALL удерживать его до завершения всего catalogue. Lock SHALL
использовать fixed timeout `0`; при занятости команда SHALL немедленно завершиться ненулево без
catalogue preflight или mutation. Команда SHALL освобождать свой lock при success и
при любом обрабатываемом failure; разрыв connection SHALL позволять MariaDB снять lock.

#### Scenario: Единственный runner
- **WHEN** lock свободен и оператор запускает migration command
- **THEN** command получает lock до preflight, выполняет весь catalogue и освобождает lock после результата

#### Scenario: Конкурирующий runner
- **WHEN** первый runner удерживает lock, а второй достигает lock acquisition
- **THEN** второй немедленно отказывает, не читает preflight и не меняет schema/data, затем возвращает отдельный ненулевой busy outcome

#### Scenario: Failure после получения lock
- **WHEN** preflight или migration бросает ошибку после успешного acquisition
- **THEN** runner прекращает catalogue, пытается освободить lock в `finally`, сохраняет исходный failure outcome и не запускает последующие migrations

### Requirement: Lock не подменяет безопасность отдельных migrations
Catalogue lock SHALL сериализовать production runner, но каждая canonical migration
SHALL сохранять собственные read-only preflight, compatible no-op и fail-closed
поведение. Runtime principals SHALL NOT иметь путь к migration command или DDL.

#### Scenario: Несовместимая существующая schema
- **WHEN** runner владеет lock и migration preflight обнаруживает конфликт
- **THEN** migration отказывает до своей mutation, последующие migrations не запускаются, ambient schema/data/history сохраняются и lock освобождается

#### Scenario: Runtime principal вызывает HTTP
- **WHEN** web request обслуживается под DML-only principal
- **THEN** ordinary HTTP/readiness не вызывает migration seam или `GET_LOCK`, а DDL отвергается правами MariaDB

### Requirement: Catalogue создаёт legacy object projection канонической v22
Следующая migration после зафиксированного frontier v21 SHALL зарегистрировать v22,
которая через существующий legacy-object schema adapter создаёт необходимую
`fm_maintable` projection для production composite. На чистой DB она SHALL создать
exact schema; известную populated 10-column predecessor shape SHALL additive
upgrade семью требуемыми семью columns; на уже совместимой schema SHALL быть no-op.
Во всех допустимых случаях rows/ids и ambient objects сохраняются; иная
несовместимость SHALL отвергаться до mutation этой таблицы. Никакой demo generation
sentinel для v22 не требуется; `id` остаётся вручную назначаемым primary key.

#### Scenario: Clean catalogue через v22
- **WHEN** migration operator запускает catalogue frontier v21 на чистой database
- **THEN** v22 создаёт совместимую prefixed `fm_maintable`, runner фиксирует новый frontier и production readiness может проверить projection

#### Scenario: Известная predecessor projection
- **WHEN** v22 видит поддерживаемую populated 10-column `fm_maintable`
- **THEN** migration добавляет exact семь недостающих columns и сохраняет каждый row/id и ambient object

#### Scenario: Уже совместимая populated projection
- **WHEN** v22 видит exact target `fm_maintable` с существующими rows
- **THEN** migration сохраняет rows, ids и ambient objects без rewrite

#### Scenario: Несовместимая projection
- **WHEN** preflight v22 видит конфликтующую `fm_maintable`
- **THEN** v22 возвращает schema conflict до mutation таблицы/data и catalogue прекращается
