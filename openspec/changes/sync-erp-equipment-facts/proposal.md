## Why

К продуктовому пилоту FMonitor 2 нужны три независимых актуальных факта оборудования из 1С ERP, которые сейчас доступны только через legacy-интеграцию. Bounded slice переносит подтверждённый контракт в native application seam без переноса legacy-архитектуры и без изменения процесса монтажного дела.

## What Changes

- Добавить native application owner атомарного применения authoritative snapshot по дате готовности, первой отгрузки и полной отгрузки.
- Получать данные прямым read-only запросом к подтверждённым BI-представлениям 1С ERP и сопоставлять их только по точному номеру заказа с `fm_maintable.zavnumber`.
- Считать `NULL` отдельного поля в успешно полученной записи явным снятием этого факта; отсутствие записи, невалидный/неполный ответ и техническая ошибка не меняют сохранённые факты.
- Хранить current projection, append-only историю, provenance, последний успешный запуск и безопасную диагностику несопоставленных/неоднозначных записей.
- Вызывать синхронизацию каждый час через существующий native scheduler/worker и показать три даты со freshness/source status в существующей карточке объекта.
- Проверить public integration/application seam на disposable DB, включая обязательные regressions A–L и отсутствие изменений process/progress/assignment facts.
- Не затрагивать issues #13, #16, #30, #11, #135, email, redesign, общий integration framework, новый scheduler/outbox и `rapid-pilot`.

## Capabilities

### New Capabilities

- `erp-equipment-facts`: Контракт получения, безопасного применения, хранения, истории, диагностики, hourly-вызова и отображения трёх независимых фактов оборудования из 1С ERP.

### Modified Capabilities

Нет.

## Impact

Изменения ограничены native InstallationProcess/application persistence, ERP adapter/CLI composition, существующим durable jobs scheduler/worker, миграцией таблиц, чтением карточки и focused integration tests. Внешняя 1С ERP остаётся read-only; production credentials, реальные вызовы, merge и deployment не входят в delivery.
