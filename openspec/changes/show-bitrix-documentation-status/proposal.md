## Why

Администратор уже видит кадровую и ERP-интеграции, но не может понять, запускалась ли синхронизация технической документации Битрикс, чем завершилась последняя фактическая попытка и был ли после неё более старый подтверждённый успех. Существующие durable `fm2_jobs` и `fm2_job_events` позволяют дать эту видимость без запуска синхронизации и без новой схемы.

## What Changes

- Добавить на существующий `GET|HEAD /pilot/admin/integrations` отдельную read-only сводку `bitrix.order-document-links.sync`.
- Раздельно показывать состояние очереди, последнюю начавшуюся попытку, её сохранённый outcome, последний подтверждённый успех и относящийся к нему счётчик опубликованных связей.
- Добавить компактную серверно пагинируемую историю фактически начатых попыток, построенную по `claimed` и последующим durable events.
- Перестроить локальную композицию экрана интеграций для ясной иерархии, независимой рабочей пагинации и narrow viewport, не меняя общие CSS/JS.
- Сохранить неизвестные и повреждённые состояния честными: не считать отсутствие запусков отключением, старый успех — успехом последней попытки, enqueue time — временем запуска, а общий failure code — точной причиной.

## Capabilities

### New Capabilities

- `admin/bitrix-documentation-status`: безопасная read-only проекция durable очереди и истории попыток синхронизации технической документации Битрикс.

### Modified Capabilities

- Нет. Существующий capability `admin/integration-status` сохраняет route, authorization и общие read-only гарантии; новый блок добавляется самостоятельным bounded capability.

## Impact

Actor — активный пользователь с `access.administer`; source oracle — только `fm2_jobs` и `fm2_job_events` для `bitrix.order-document-links.sync`; public seam — server-rendered `GET|HEAD /pilot/admin/integrations`. Затрагиваются только `IntegrationStatusController`, локальный read adapter, `integration-status` view/partial и адресные тесты/контракты delivery. Не входят handlers/registry/scheduler, получение документов, credentials, schema/migrations, router, общие assets, retry, внешние запросы, полный #251, merge и deployment.
