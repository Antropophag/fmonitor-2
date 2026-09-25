## ADDED Requirements

### Requirement: Строка стройконтроля управляет одним текущим планом

Для каждого доступного объекта сервер SHALL получить canonical current plan из object-keyed read seam. При отсутствии плана строка SHALL показывать «Запланировать инспекцию»; при наличии — дату `ДД.ММ.ГГГГ`, «Перенести» и «Отменить». Прошедший план SHALL быть отсутствующим current plan и SHALL NOT публиковаться как результат, нарушение или пропуск.

#### Scenario: create, reschedule и cancel согласуют поверхности

- **WHEN** полномочный actor последовательно создаёт, переносит и отменяет план через реальные Yii POST routes
- **THEN** queue и calendar после каждого redirect/read показывают ровно одну одинаковую canonical current date либо её отсутствие
- **AND** cancelled/superseded даты не остаются current

### Requirement: Команды сохраняют security и неопределённость

Create/reschedule/cancel SHALL вызывать поставленные `createInspectionPlan`, `rescheduleInspectionPlan`, `cancelInspectionPlan` с текущим actor, object identity, opaque request identity и expected version. Routes SHALL требовать POST и CSRF; application seam SHALL повторно проверить capability и object scope. UI SHALL NOT выполнять скрытый retry. Подтверждённый domain rejection SHALL отличаться от неизвестного transport/server outcome; при любом отказе введённая дата и object/action context SHALL сохраняться.

#### Scenario: stale и replay

- **WHEN** отправлены stale version, identical replay или request identity с другим intent
- **THEN** seam определяет canonical result без лишнего event
- **AND** UI сообщает подтверждённый отказ либо неизвестный исход, не утверждая успех и не повторяя команду

### Requirement: Один доступный shlz-ui dialog

Все точки входа SHALL открывать один dialog на публичных `shlz-ui` primitives. Dialog SHALL иметь видимый заголовок/описание «Инспекция появится в календаре и списке стройконтроля», label даты, явные submit/cancel actions, initial focus, Escape/close и возврат focus trigger. Действия SHALL оставаться текстовыми на desktop и narrow viewport.

#### Scenario: keyboard create and reschedule

- **WHEN** пользователь открывает create или reschedule с клавиатуры
- **THEN** один dialog сохраняет object/action/current-version context, фокус и введённую дату до подтверждённого результата

### Requirement: Московское сегодня сортируется до pagination

Сервер SHALL вычислять today через injected/current clock в `Europe/Moscow`. В пределах уже применённых authorization scope и filters все объекты с current plan на today SHALL образовать первую группу до COUNT/LIMIT; внутри групп порядок SHALL быть детерминирован существующим business sort с numeric object identity tie-breaker. GET/HEAD SHALL быть read-only. Следующий московский день SHALL менять marker/priority без DML.

#### Scenario: сегодняшняя группа пересекает страницы

- **WHEN** число доступных сегодняшних объектов больше размера страницы
- **THEN** они занимают первые последовательные страницы без вытеснения будущими/неплановыми объектами
- **AND** повторное чтение даёт тот же порядок

#### Scenario: смена московского дня

- **WHEN** clock пересекает московскую полночь без записи факта
- **THEN** вчерашний план перестаёт быть current/today, а новый today marker и priority вычисляются на чтении без DML

### Requirement: Fail closed publication

Schema/current-plan projection outage или multiple-current overflow SHALL fail closed до partial HTML. Calendar event SHALL вести на canonical object card. Engineer и Руководитель ФКР SHALL видеть и выполнять действия только в действующем server-side scope.

#### Scenario: projection outage

- **WHEN** canonical current-plan read недоступен или неоднозначен
- **THEN** queue/calendar не публикуют частичный или stale план и не выполняют запись
