## Why

Карточка объекта после переноса из rapid-pilot имеет слабую иерархию, разнородные действия и ломаную адаптивность. Этот первый bounded slice создаёт `shlz-ui` foundation и приводит в порядок только карточку объекта; ОТиЗ и остальные active screens следуют отдельными changes.

## What Changes

- Вводится bounded vocabulary для page identity, next action, action groups, fact regions и timeline на public primitives `shlz-ui`.
- Карточка `/pilot/objects/{id}` показывает идентичность, статус, инженера и одно следующее действие без визуального шума.
- Композиция адаптируется на 320/768/1024/1440 px, 200% zoom и coarse pointer; JS-off остаётся работоспособным.
- Routes, forms, permissions и domain facts не меняются.

## Capabilities

### New Capabilities

- `ui/shlz-operational-surfaces`: foundation и карточка объекта как первый operational surface.

### Modified Capabilities

- Нет.

## Impact

Затронуты `object-card.php`, shared application CSS/assets и object-card browser tests. ОТиЗ и остальные routes не меняются в этом slice; их перенос обязателен в follow-up changes `refresh-otiz-shlz-ui` и `refresh-active-yii2-shlz-ui`.
