## Why

Канонический Yii route `GET /pilot/calendar` присутствует в security и migration inventories, но после перехода на Yii возвращает `404`; пользователи с правом чтения объектов потеряли календарное представление уже сохранённых планов инспекций. Срез восстанавливает read-only Yii projection и возвращает ссылку в общий раздел `Монтаж`, не меняя команды планирования или историю фактов.

## What Changes

- Добавить аутентифицированный Yii `GET /pilot/calendar` с детерминированной проекцией существующих inspection schedule facts по месяцу, дате и объекту.
- Требовать `objects.read`: разрешённый пользователь получает `200`, пользователь без permission — `403`, неаутентифицированный запрос сохраняет общий login flow.
- Включить `Календарь` в группу `Монтаж` общего Yii sidebar только для ролей, которым доступен route, и отмечать пункт текущим на calendar route.
- Сохранить GET строго read-only, существующие scheduling commands, schema ownership и append-only history.
- Добавить focused HTTP и browser coverage для данных, ordering, permissions, current navigation и отсутствия записи.

## Capabilities

### New Capabilities

- `runtime/yii-calendar`: Read-only Yii calendar projection существующих inspection schedule facts и её permission-aware navigation entry.

### Modified Capabilities

Нет.

## Impact

Затрагиваются Yii runtime routing/controller/view, общий `MainNavigation`, существующие calendar assets и focused Yii HTTP/browser tests. Источником поведения служат канонические inspection planning contracts и legacy `rapid-pilot/Calendar.php` только как read-only oracle; новые schema, DDL/DML, scheduling semantics и rapid-pilot domain logic не добавляются.
