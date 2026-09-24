## Context

См. `proposal.md`. В актуальном Yii runtime уже есть `YiiInspectionPlanning`/`MariaDbYiiInspectionPlanning`, POST из object queue, read-only calendar projection и canonical migration v9 для `fm2_pilot_inspection_schedules` + `fm2_pilot_inspection_schedule_events`. Текущая uniqueness включает `control_engineer_user_id`, поэтому смена инженера создаёт другую identity; command поддерживает только create, не имеет durable request receipt и не проверяет object scope отдельно от global capability. Queue today-marker в rapid-pilot — только behavioral oracle.

`InstallationProcess` остаётся владельцем состояния. Этот slice предоставляет command/current-read seams; Yii integration и presentation принадлежат issue #255. `rapid-pilot/` не получает новой domain logic.

## Goals / Non-Goals

**Goals:**

- один object-bound current-plan aggregate и append-only event history;
- атомарные create/reschedule/cancel с replay и optimistic version;
- canonical object current-plan read seam для issue #255;
- server-side capability + object-scope authorization;
- additive, fail-closed migration существующих planning facts.

**Non-Goals:**

- completion/result/missed-visit semantics и связь с inspection evidence;
- причина переноса или отмены;
- выбор исполнителя выезда и персональный календарь;
- cadence, уведомления, checklist/progress writers, #45 и redesign календаря.
- Yii routes, forms, calendar/queue joins, today-priority, pagination и visual acceptance — issue #255.

## Decisions

### 1. Object identity owns a plan aggregate

Public seam принимает actor, object, action, expected version, request identity и, для create/reschedule, дату. Текущий инженер не входит в aggregate identity и не записывается как предполагаемый исполнитель. Альтернатива — сохранить tuple `(case, engineer, date)` — отвергнута: она противоречит owner decision и дублирует планы при смене назначения.

На объект допускается один current plan (`today`/future, not cancelled/superseded). Прошедший plan перестаёт быть current по clock без write и не получает outcome. Это позволяет создать следующий план без искусственного `completed` event.

### 2. Append-only events derive current projection

Root plan identity и immutable events `inspection_scheduled`, `inspection_rescheduled`, `inspection_cancelled` хранят actor/time и последовательную version. Reschedule содержит old/new date; cancel не содержит обязательной reason. Current date/status выводятся из последнего принятого event; существующие rows не переписываются.

Отдельная mutable current-row table не вводится, пока bounded event fold удовлетворяет лимитам. Если performance evidence покажет необходимость projection table, она может быть транзакционно производной и не станет source of truth.

### 3. Additive canonical migration replaces engineer-bound uniqueness

Новая последовательная migration расширяет family request receipts/versions и заменяет uniqueness на object/current-plan invariant, сохраняя существующие schedule ids/events. Preflight сначала классифицирует legacy data. Если один объект имеет несколько today/future rows, migration MUST fail closed как semantic conflict: автоматически выбирать победителя нельзя. Исторические rows мигрируют как object-bound planning history; `control_engineer_user_id` может остаться compatibility snapshot, но не участвует в identity, authorization или projection.

Runtime paths продолжают только проверять fingerprint и никогда не выполняют DDL/repair. Migration/restore inventories обновляются через canonical seams.

### 4. Capability and object scope are separate gates

Command owner сначала проверяет active identity + exact `inspection.schedule`. Глобальная ветка требует exact active role code `manager` либо `fkr_operator` и exact `objects.read`; для инженера scope опирается на действующее native-закрепление объекта. Проверка повторяется внутри транзакции перед mutation. Ни role без read, ни capability без соответствующего role/object rule недостаточны.

### 5. Current-plan seam is the only UI dependency

Owner предоставляет object-keyed current-plan result без HTML, pagination и screen-specific ordering. Issue #255 обязана использовать этот seam для calendar и construction-control queue и не интерпретировать event tables независимо.

Пока #255 не доставлена, существующий `MariaDbYiiObjectQueue::readCalendar` переводится только с raw root date на effective current-plan projection. Это compatibility consumer wiring, а не UI slice: routes/views/assets, ordering и pagination не меняются. Retired `rapid-pilot` остаётся неизменным по owner decision об active Yii application.

### 6. Delivery and architecture boundaries

Root пишет executable spec/RED. Отдельный executor изменяет только owner/read seams в `app/InstallationProcess` и canonical migration/composition; Yii adapters/views/assets принадлежат issue #255. Независимые reviewers проверяют Gate 3 и final exact source согласно planner-selected `required_reviews`. Architecture check должен подтвердить единственного state owner, отсутствие DDL в runtime command/read paths и отсутствие новой domain logic в `rapid-pilot/`.

## Risks / Trade-offs

- [На базе уже есть несколько будущих engineer-bound rows одного объекта] → migration preflight останавливается с deterministic conflict inventory; данные не схлопываются автоматически.
- [Clock-derived current status меняется без event] → это намеренно только read-time классификация даты, а не факт исполнения; тесты фиксируют московскую границу дня.
- [Зависимый UI начнёт читать event tables напрямую] → public current-plan seam и issue #255 фиксируют единственную допустимую зависимость.
- [Параллельные перенос/отмена дают lost update] → row/object lock, expected version и request receipts обеспечивают одного победителя и replay исходного результата.
- [Compatibility column воспринимается как исполнитель] → публичные payloads/projections не используют её; delivery docs помечают её только historical snapshot до отдельного cleanup.

## Migration Plan

1. Выполнить read-only preflight legacy planning family и сформировать deterministic conflict inventory.
2. Применить additive canonical migration: receipts/version support, object-bound indexes и совместимый event contract; сохранить ids, bytes и history.
3. Переключить application owner и canonical current-plan read seam; Yii composition остаётся в issue #255.
4. На несовместимом состоянии rollback выполняется остановкой до mutation. После успешной migration rollback к engineer-bound writer запрещён; откат приложения сохраняет новые facts и требует forward correction, а не удаления истории.
