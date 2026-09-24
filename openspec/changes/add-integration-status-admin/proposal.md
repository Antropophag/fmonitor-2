## Why

Администратору FMonitor сейчас приходится читать серверные журналы и таблицы, чтобы отличить успешную синхронизацию кадров и ERP от сбоя, отсутствия запуска или несопоставленных данных. Первый read-only slice issue #30 даёт безопасный рабочий экран на уже сохраняемых фактах, не возобновляя интеграционные writers и не создавая новую систему журналирования.

## What Changes

- Добавить защищённую `access.administer` страницу `/pilot/admin/integrations` активного Yii-приложения.
- Раздельно показать последнюю попытку и последний успех Bitrix workforce и ERP equipment facts, сохранённый результат, доступные счётчики и allowlisted причину ошибки.
- Показать реально сохраняемые workforce/ERP несопоставления и failed jobs ограниченными серверными страницами.
- Различать never-run, успешный пустой результат, сохранённый сбой после более старого успеха и ошибку чтения; не выводить disabled/not-configured без явного durable признака.
- Использовать существующие shlz-ui contracts и точечно добавить административную навигацию без изменений общего CSS/JS shell.
- Гарантировать, что GET/HEAD читают только локальные durable projections и не запускают интеграции, queue/retry, cron, внешние запросы или отправки.
- Оставить за пределами slice retry UI, новые таблицы/writers/logging, raw payload/stack trace/secrets и показатели, которые текущая модель не сохраняет.

## Capabilities

### New Capabilities

- `admin/integration-status`: Read-only административное представление состояния двух подключённых источников, несопоставленных записей и failed jobs.

### Modified Capabilities

- Нет.

## Impact

- Новый application reader, Yii controller/view и связанные focused HTTP/browser tests.
- Точечные additions в Yii routes и `MainNavigation`; существующие роли и capability не меняются.
- Чтение существующих `fm2_workforce_*`, `fm2_equipment_fact_*` и `fm2_jobs` records без migrations и mutations.
- Не затрагиваются `MariaDbYiiObjectQueue`, календарь, effective-values owner, ОТиЗ, Compose/Makefile/deployment tools, общий CSS/JS shell и current delivery goal.
