## Why

Карточке объекта нужны ссылки на техническую документацию из Битрикс. Legacy подтверждает единственный ключ сопоставления — строковый `fm_maintable.zavnumber`; `regnumber` для этого непригоден.

## What Changes

- Read-only получить direct child folders настроенного Bitrix Disk root и их external links.
- Разобрать обычное имя и подтверждённый legacy-диапазон `A.F-B.F`.
- После полной успешной загрузки транзакционно заменить текущий набор ссылок; при ошибке оставить прежний.
- Читать ссылки exact по `zavnumber` и показать их в существующей карточке под `objects.read`.
- Перенести nullable `zavnumber` через существующий native legacy mirror/import.
- Зарегистрировать hourly запуск через существующий native Jobs scheduler, без нового scheduler/worker framework.
- Не менять #12/#30/#40/#66, object identity, `rapid-pilot`, общий integration/recovery design и shared verification/harness.

## Capabilities

### New Capabilities

- `integration/bitrix-order-document-links`: минимальное получение, актуализация и отображение ссылок документации заказа.

### Modified Capabilities


## Impact

Новый bounded adapter, одна current links projection, application/read owner в `InstallationProcess`, nullable mirror field, console trigger, минимальная регистрация hourly job и секция существующей Yii2 object card. Файлы документов не копируются.
