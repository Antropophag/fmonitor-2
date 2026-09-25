## Why

Текущий блок «Загрузка монтажников и динамика» визуально показывает шесть календарных недель, но питается только прошлыми ежедневными наблюдениями: будущие недели пусты и не помогают ФКР планировать назначения.

## What Changes

- Добавить на `GET|HEAD /pilot/dashboard` живой шестинедельный прогноз загрузки монтажников от текущей московской календарной недели.
- Для каждой недели показывать число занятых, полностью свободных, освобождающихся и конфликтующих монтажников; неизвестные/неполные интервалы показывать отдельно и никогда не считать свободными.
- Строить прогноз из authoritative workforce, latest application/selection, factual opening/PTO и эффективных плановых дат объекта, без ожидания ежедневного observation и без записи domain facts при чтении.
- Добавить авторизованный drill-down по точной неделе и группе с основаниями расчёта и устойчивым порядком.
- Сохранить историческую динамику отдельным блоком; обнаруженный дефект штатного capture-job остаётся отдельной срочной коррекцией этапа 2 и не смешивается с семантикой прогноза.
- Не выполнять backfill истории и не превращать прогноз в автоматическое распределение людей.

## Capabilities

### New Capabilities

- `workforce/installer-utilization-forecast`: живой шестинедельный прогноз занятости и доступности монтажников с недельной детализацией и fail-safe UNKNOWN.

### Modified Capabilities

Нет.

## Impact

- Yii dashboard/controller и workforce read model получают отдельную forecast projection и drill-down.
- Используются существующие таблицы workforce, application/selection, installation case, PTO/deadline и effective object details; новых state-changing product seams нет.
- Потребуются public-seam HTTP/DOM, projection, authorization и narrow-viewport regression tests; полный локальный suite не запускается.
