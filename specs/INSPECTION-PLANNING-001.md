# INSPECTION-PLANNING-001

Версия 0.1, 2026-09-24. Issue #14.

## Простыми словами

Инженер стройконтроля или Руководитель ФКР планирует дату выезда по доступному объекту. План принадлежит объекту, а не исполнителю; перенос и отмена не требуют причины. Yii-интерфейс, календарь и очередь доставляются зависимой issue #255.

## 1. Актор и публичный seam

Актор — активный пользователь с exact capability `inspection.schedule`. Инженер стройконтроля ограничен объектами своего текущего native-закрепления. В текущем пилоте только exact активные роли `manager` и canonical `fkr_operator`, одновременно имеющие exact `objects.read`, получают planning scope всех eligible объектов. Публичный seam — единый application owner команд create/reschedule/cancel и object-keyed current-plan read. Capability, read permission и актуальный object scope повторно проверяются непосредственно перед записью.

Команда принимает object identity, opaque request identity, expected version и для create/reschedule strict дату `YYYY-MM-DD`, не раньше server today `Europe/Moscow`. План не имеет исполнителя: автор и текущий назначенный инженер не входят в identity.

## 2. Состояние и история

У объекта не более одного current plan с датой today/future. Create, reschedule и cancel атомарно добавляют immutable events с actor/server time; reschedule сохраняет old/new date, cancel не требует причины. Идентичный replay возвращает исходный результат без event; reuse request identity для другого intent отклоняется. Concurrent writers дают одного победителя, stale action не пишет facts.

Смена инженера не меняет план. Прошедший план перестаёт быть current только как read-time классификация и не создаёт outcome, missed status, checklist/progress/evidence fact. После него допустим новый будущий план.

## 3. Canonical current-plan read

Read seam возвращает по object identity либо отсутствие current plan, либо exact plan identity, current date и version. После reschedule возвращается только новая дата; после cancel и после завершения календарного дня возвращается отсутствие current plan. Чтение не выполняет DML и не создаёт outcome. Issue #255 обязана использовать этот seam, а не интерпретировать event tables самостоятельно.

До доставки #255 существующий Yii calendar/queue read adapter SHALL потреблять ту же effective current-plan projection, чтобы reschedule/cancel/recreate не раскрывали stale, cancelled или synthetic storage dates. Routes, views, sorting, pagination и visual contract этим compatibility wiring не меняются. Retired `rapid-pilot` не является production target.

## 4. Rejections и fail-closed

Отсутствующая capability, объект вне current scope, stale version/request conflict, malformed/past date и несовместимая schema отклоняются без partial writes или disclosure. Runtime command/read paths не создают и не ремонтируют schema.

## 5. Acceptance matrix

- **A1:** engineer создаёт план только в current native-assignment scope; exact `manager`/`fkr_operator` с `inspection.schedule` + `objects.read` — для любого eligible объекта global pilot scope; смена инженера сохраняет identity/date.
- **A2:** create/reschedule/cancel без причины сохраняют append-only actor/time history; replay/concurrency детерминированы.
- **A3:** current-plan read и existing Yii calendar compatibility adapter детерминированно отражают create/reschedule/cancel/past-date без DML или stale storage dates.
- **A4:** past plan не становится фактом результата/нарушения и не меняет checklist, progress или evidence.
- **A5:** canonical additive migration сохраняет ids/history, fail closed на нескольких future/current legacy rows и исключает runtime DDL.

## 6. Non-goals

Не входят Yii routes/views/assets, кнопки/dialog, calendar/queue presentation, today-priority/pagination (issue #255), контроль исполнения, результат или пропуск выезда, причины, персональный календарь инженера, cadence/уведомления, inspection evidence/checklist/progress writers, #45, merge и deployment.
