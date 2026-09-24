## Why

Yii уже умеет добавить отдельный план выезда, но identity плана ошибочно включает назначенного инженера, отсутствуют перенос/отмена и object-scope authorization. Первый slice issue #14 создаёт canonical object-bound owner; Yii-интерфейс, календарь и приоритет очереди вынесены в зависимую issue #255.

## What Changes

- Сделать объект монтажа владельцем inspection plan: смена назначенного инженера не создаёт другой план, не переносит и не отменяет существующий.
- Разрешить активному инженеру стройконтроля и Руководителю ФКР планировать инспекцию по доступному им объекту; capability и object scope проверяются server-side в одном public application seam.
- Поддержать один текущий будущий план по объекту, его добровольный перенос на другую допустимую дату и добровольную отмену без обязательной причины; каждое принятое действие сохраняет actor/time append-only.
- Предоставить canonical current-plan read seam для последующего UI slice #255; этот change не меняет Yii calendar/queue presentation.
- Классифицировать current plan по `Europe/Moscow`; прошедший план остаётся историческим планом и не становится автоматически фактом инспекции, нарушением или незавершённым результатом.
- Адаптировать существующую canonical planning schema и сохранённые schedule facts без runtime DDL и без потери append-only audit history.
- Не добавлять контроль исполнения, статусы `состоялась` / `не состоялась`, требование результата, связь с checklist/фото/прогрессом, уведомления или обязательную периодичность.

## Capabilities

### New Capabilities

- `inspection-planning/object-bound-plans`: Object-bound команды создания, переноса и отмены плана инспекции, server-side authority/scope, append-only аудит и canonical current-plan read seam.

### Modified Capabilities

Нет.

## Impact

Затрагиваются owner `InstallationProcess`, canonical additive migration/fingerprint существующей planning family, permission/scope composition и focused application/migration tests. Источник product truth — issue #14 и уточнения владельца 2026-09-24; `rapid-pilot/InspectionSchedule.php` служит behavioral oracle, но новая предметная логика в `rapid-pilot/` не добавляется. Не входят Yii routes/views/assets, calendar/queue presentation и today-priority (issue #255), inspection evidence/checklist writers, progress, автоматизация выездов, уведомления, #45 и production deployment.
