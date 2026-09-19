## Purpose

Восстанавливает защищённое read-only календарное представление существующих планов инспекций в каноническом Yii runtime и делает его доступным из общего меню монтажа.

## ADDED Requirements

### Requirement: Authenticated read-only calendar route
Система SHALL обслуживать `GET /pilot/calendar` и `GET /pilot/calendar/` в каноническом Yii runtime. Route MUST использовать общий session/login flow, MUST требовать permission `objects.read` и MUST устанавливать `Cache-Control: no-store`. Любой GET, включая успешный, отклонённый и ошибочный, MUST оставлять product facts и schema неизменными.

#### Scenario: Разрешённое чтение
- **WHEN** аутентифицированный пользователь с `objects.read` выполняет `GET /pilot/calendar`
- **THEN** система возвращает `200` и HTML календаря без DDL, DML или вызова scheduling command

#### Scenario: Permission denied
- **WHEN** аутентифицированный пользователь без `objects.read` выполняет `GET /pilot/calendar`
- **THEN** система возвращает `403`, не раскрывает календарные данные и не создаёт новых фактов или audit events

#### Scenario: Неаутентифицированный запрос
- **WHEN** пользователь без действующей session выполняет `GET /pilot/calendar`
- **THEN** система применяет тот же login return-path contract, что и остальные защищённые Yii pilot routes, и не читает календарную проекцию

### Requirement: Deterministic inspection schedule projection
Календарь SHALL строиться из существующих принятых inspection schedule facts и связанного существующего object display identity, не выводя новые доменные факты. В видимом периоде события MUST группироваться по календарному месяцу и дате в хронологическом порядке; события одной даты MUST следовать по `legacy_object_id` по возрастанию, затем по устойчивой identity schedule fact по возрастанию. Повторный GET при тех же facts и clock MUST возвращать тот же semantic event order независимо от physical row order.

#### Scenario: Independently ordered fixture
- **WHEN** хранилище содержит schedule facts для объектов `42`, `7`, `19` на датах `2026-11-03`, `2026-10-15`, `2026-10-15`, сохранённые в другом порядке, а также факт вне видимого периода
- **THEN** calendar projection показывает сначала октябрь, затем ноябрь; `2026-10-15` показывает объект `7` перед объектом `19`; `2026-11-03` показывает объект `42`; факт вне периода отсутствует

#### Scenario: Повторное чтение
- **WHEN** пользователь дважды читает тот же период при неизменных facts и clock
- **THEN** набор и semantic order событий совпадают, а schedules, schedule events, object facts и audit history остаются byte-equivalent

### Requirement: Calendar date selection and failures
Календарь SHALL использовать timezone `Europe/Moscow` для текущей даты и допустимого периода. Неуказанная selected date SHALL выбирать текущий день. Exact `date=YYYY-MM-DD` в допустимом периоде SHALL выбирать этот день; malformed либо out-of-range date MUST вернуть `400` с безопасным сообщением. Недоступная или несовместимая planning schema и переполнение bounded projection MUST fail closed с `503`, без partial calendar HTML и без runtime repair.

#### Scenario: Exact selected date
- **WHEN** clock фиксирован на `2026-09-19T12:00:00+03:00` и разрешённый пользователь запрашивает `/pilot/calendar?date=2026-10-15`
- **THEN** agenda отмечает `15.10.2026` как выбранную дату и показывает только события этой даты в нормативном порядке

#### Scenario: Invalid selected date
- **WHEN** разрешённый пользователь передаёт malformed или out-of-range `date`
- **THEN** система возвращает `400`, не возвращает partial calendar HTML и не изменяет facts или schema

#### Scenario: Planning schema unavailable
- **WHEN** planning schema отсутствует или несовместима
- **THEN** система возвращает `503`, не выполняет DDL/repair и не возвращает partial calendar HTML

### Requirement: Mount navigation entry
Общий Yii sidebar SHALL показывать один пункт `Календарь` в группе `Монтаж` непосредственно после `Объекты` только пользователю, который может читать calendar route. На `/pilot/calendar` и `/pilot/calendar/` этот пункт SHALL быть единственным calendar link с `aria-current="page"`; на других routes он MUST NOT быть current.

#### Scenario: Calendar is current
- **WHEN** пользователь с `objects.read` открывает `/pilot/calendar`
- **THEN** группа `Монтаж` содержит ordered children `Объекты`, `Календарь`, `Стройконтроль`, а `Календарь` имеет `aria-current="page"`

#### Scenario: Calendar is absent without permission
- **WHEN** пользователь без `objects.read` открывает доступную ему Yii page
- **THEN** sidebar не содержит ссылку `Календарь`

### Requirement: Existing scheduling behavior remains unchanged
Срез MUST NOT менять acceptance, authorization, replay, concurrency, persistence или audit semantics существующих scheduling commands. Calendar link на объект SHALL использовать существующий read-only object detail route и MUST NOT становиться обходом его authorization.

#### Scenario: Existing command equivalence
- **WHEN** до и после установки среза выполняется один и тот же существующий scheduling command при одинаковых preconditions
- **THEN** observable command result и записанные append-only facts эквивалентны, а последующий calendar GET только проецирует принятый schedule fact
