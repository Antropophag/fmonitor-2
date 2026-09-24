## Purpose

Позволяет уполномоченным пользователям вести object-bound планы выездов по объектам монтажа через canonical owner, не превращая план в контроль фактического исполнения. Yii presentation определяется зависимой issue #255.

## ADDED Requirements

### Requirement: Object-bound inspection plan
Система SHALL идентифицировать план инспекции объектом монтажа и датой, а не назначенным инженером или автором. Для одного объекта одновременно MUST существовать не более одного текущего плана с датой `сегодня` или в будущем по timezone `Europe/Moscow`. Смена назначенного инженера MUST NOT создавать, переносить, отменять или скрывать план.

#### Scenario: Смена инженера сохраняет план
- **WHEN** по объекту сохранён текущий план и затем меняется назначенный инженер стройконтроля
- **THEN** identity, дата, version и история object-bound плана остаются прежними

#### Scenario: Один текущий план объекта
- **WHEN** два concurrent запроса пытаются создать разные текущие планы одного объекта
- **THEN** принимается не более одного плана, второй запрос получает детерминированный conflict без частичных facts

### Requirement: Server-authorized planning
Создать, перенести или отменить план SHALL только активный пользователь с exact capability `inspection.schedule` и доступом к конкретному объекту. Активный инженер стройконтроля SHALL иметь возможность планировать только по объектам своего текущего native-закрепления. В текущем пилоте только exact активные роли `manager` и `fkr_operator`, одновременно имеющие exact `objects.read`, SHALL иметь planning scope всех eligible объектов. Проверка capability, read permission и актуального object scope MUST выполняться server-side непосредственно перед записью.

#### Scenario: Инженер планирует доступный объект
- **WHEN** активный инженер с capability планирования создаёт план по объекту в своей текущей области доступа на допустимую дату
- **THEN** система принимает план и сохраняет объект, дату, автора и точное время действия

#### Scenario: Руководитель ФКР планирует доступный объект
- **WHEN** активный Руководитель ФКР с capability планирования создаёт план по любому eligible объекту своего global pilot read scope
- **THEN** система принимает тот же object-bound план без назначения автора или текущего инженера исполнителем выезда

#### Scenario: Объект вне scope
- **WHEN** пользователь с capability передаёт существующий объект вне своей актуальной области доступа
- **THEN** система отклоняет команду без создания или изменения plan/audit facts и без раскрытия закрытых сведений об объекте

#### Scenario: Scope изменился до записи
- **WHEN** object scope пользователя изменился между открытием формы и выполнением команды
- **THEN** повторная server-side проверка отклоняет команду без записи

### Requirement: Create, reschedule and cancel without execution control
Public planning seam SHALL поддерживать явные команды создания, переноса и отмены текущего плана. Дата создания или переноса MUST быть строгой календарной датой `YYYY-MM-DD`, не раньше текущей даты `Europe/Moscow`. Перенос и отмена SHALL быть добровольными и MUST NOT требовать, принимать как обязательное или валидировать текст причины. Принятое действие MUST добавлять immutable audit event с actor и server time; прежние plan events MUST NOT переписываться.

#### Scenario: Создание плана
- **WHEN** уполномоченный пользователь создаёт план на сегодняшнюю или будущую допустимую дату для объекта без текущего плана
- **THEN** система атомарно сохраняет plan identity и `inspection_scheduled` event, после чего возвращает текущую дату плана

#### Scenario: Перенос без причины
- **WHEN** уполномоченный пользователь переносит текущий план на другую допустимую дату и не передаёт причину
- **THEN** система атомарно добавляет `inspection_rescheduled` event, сохраняет прежнюю дату в истории и делает новую дату текущей

#### Scenario: Отмена без причины
- **WHEN** уполномоченный пользователь отменяет текущий план и не передаёт причину
- **THEN** система атомарно добавляет `inspection_cancelled` event и текущего плана у объекта больше нет

#### Scenario: Недопустимая дата
- **WHEN** команда создания или переноса содержит malformed, несуществующую или прошедшую по `Europe/Moscow` дату
- **THEN** система отклоняет её без изменения plan/audit facts

### Requirement: Replay and concurrency safety
Каждая planning command SHALL принимать opaque request identity и обеспечивать semantic replay: идентичный повтор принятой команды MUST вернуть исходный результат без нового события, а повтор identity с иным намерением MUST быть отклонён. Concurrent create/reschedule/cancel MUST сериализоваться по объекту так, чтобы current projection и append-only events описывали одну детерминированную последовательность без lost update.

#### Scenario: Ответ потерян после commit
- **WHEN** клиент повторяет ту же принятую команду с той же request identity после потери ответа
- **THEN** система возвращает исходный semantic result, а количество plan и audit facts не меняется

#### Scenario: Request identity reused for another intent
- **WHEN** та же request identity повторно используется с другой датой, другим объектом или другим типом действия
- **THEN** система отклоняет конфликт без изменения истории

#### Scenario: Конкурентные перенос и отмена
- **WHEN** перенос и отмена одного текущего плана поступают конкурентно с одной ожидаемой версией
- **THEN** ровно одно действие принимается, второе получает stale conflict, а projection соответствует принятому event

### Requirement: Canonical current-plan read seam
Owner SHALL возвращать current plan объекта из canonical plan/event source. Результат MUST содержать object identity, plan identity, current date и version либо однозначное отсутствие current plan. Отменённая, superseded или прошедшая дата MUST отсутствовать как current, сохраняясь в append-only history. Seam MUST быть read-only и предназначен для зависимого Yii slice #255.

#### Scenario: Перенос меняет current projection
- **WHEN** план перенесён с `2099-10-15` на `2099-10-20`
- **THEN** read seam возвращает только `2099-10-20` как current date и version принятого reschedule event

#### Scenario: Отмена очищает current projection
- **WHEN** текущий план отменён
- **THEN** read seam сообщает отсутствие current plan, а прежние события остаются неизменными

#### Scenario: Existing calendar compatibility
- **WHEN** plan rescheduled, cancelled или recreated и существующий Yii calendar adapter читает тот же object
- **THEN** adapter показывает только effective current date либо отсутствие inspection event и MUST NOT показывать stale/cancelled/synthetic storage date

### Requirement: Planning does not assert execution
Прошедшая дата плана SHALL оставаться только историческим планом. Система MUST NOT автоматически создавать факт проведённой или пропущенной инспекции, менять progress/checklist, требовать результат, показывать статус нарушения или связывать план с inspection evidence. Новый будущий план после прошедшего SHALL быть допустим как независимое planning action.

#### Scenario: Дата прошла без действий
- **WHEN** календарный день плана завершился без plan command
- **THEN** система не создаёт новых facts, не показывает `Результат выезда не зафиксирован` и не изменяет прогресс, checklist или inspection evidence

#### Scenario: Новый план после прошедшего
- **WHEN** уполномоченный пользователь создаёт новую будущую дату по объекту, чей предыдущий план уже в прошлом
- **THEN** система принимает новый текущий план и сохраняет прежний как immutable planning history

### Requirement: Bounded fail-closed behavior
Planning commands и current-plan read seam MUST использовать canonical schema без runtime DDL/repair. При отсутствующей или несовместимой schema либо небезопасном clock система SHALL fail closed с безопасной ошибкой, без partial writes или изменения unrelated facts.

#### Scenario: Schema unavailable during command
- **WHEN** canonical planning schema отсутствует или несовместима
- **THEN** команда возвращает безопасную недоступность, не создаёт таблицы и не записывает plan/audit facts
