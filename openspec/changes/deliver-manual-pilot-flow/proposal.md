## Why

Владелец потребовал сегодня заменить прежний preview чистым ручным стендом, на
котором можно пройти основной маршрут от входа и состава до документального
закрытия. Реализация маршрута уже собрана по утверждённым решениям; change фиксирует
границу фактической передачи, оставшиеся проверки и честный статус production gates.

## What Changes

- Передать единый manual-pilot flow: локальный owner-admin, пустой контур объектов,
  выбор состава, optional template, original upload/correction, явное application,
  отдельное opening, checklist/photo/corrections, ПТО, декларация и100% closure.
- Использовать native application owners и append-only storage; HTTP/rapid-pilot
  остаются adapters, кадровая дата не выдумывается.
- Загрузить разрешённый read-only Bitrix workforce batch из private evidence и
  подготовить его native publication в новый стенд без переноса FMonitor users.
- Заменить прежний preview чистым стендом по новому разрешению владельца; прежние
  volumes сохранить как резерв и не подключать к новому generation.
- После deployment выполнить реальный login/restart/golden-path smoke и настроить
  hourly workforce schedule; замечания владельца исправлять первыми.
- Не объявлять deployment, schedule, full `make verify`, Gate3/5 или production
  integration выполненными до соответствующего факта.

## Capabilities

### New Capabilities

- `pilot/manual-pilot-delivery`: наблюдаемая передача чистого, сохраняющего историю
  ручного стенда и граница между focused pilot evidence и отложенной production readiness.

### Modified Capabilities

## Impact

Actors: владелец-администратор и приглашённые им сотрудники пилота. Source oracles:
PRODUCT/CONTEXT, pilot contracts, owner decisions и focused native/live evidence.
Target seams: существующие native composition/original/application/opening/checklist/
completion/workforce applications и startup migrations. Затронуты Docker startup,
private state/secrets, MariaDB generation и операционная передача URL. Не входят:
legacy user import, production data import, Bitrix write methods, PR/CI publication,
удаление резервных volumes и утверждение отложенных gates.
