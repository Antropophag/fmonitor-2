## Why

#76 заменяет самописную инфраструктуру Yii2. Нужен отдельно проверяемый web/console
каркас, прежде чем подключать пользовательские маршруты и изменять admission.

## What Changes

- YII2-RUNTIME-001: оператор проверяет `/health/live` и `/health/ready` через Yii2.
- Один Composer lock и общая configuration для web и console.
- Сохраняется readiness owner `RuntimeReadiness`; HTTP не запускает prepare/DDL.
- Отдельный runtime, существующий production entrypoint не переключается.
- Первый пользовательский маршрут и assets остаются следующей частью этапа2;
  этот operational slice не объявляет весь этап завершённым. Auth/data не меняются.

## Capabilities

### New Capabilities

- `yii2-runtime-foundation`: изолированный Yii2 operational contour.

### Modified Capabilities

Нет.

## Impact

Composer, новый web/console entrypoint и isolated runtime configuration. Oracle:
`public/runtime.php`, `RuntimeReadiness`, #76 и existing runtime specifications.
