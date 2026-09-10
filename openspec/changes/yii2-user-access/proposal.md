## Why

После Yii login/roles административные действия и activation ещё обслуживает
старый runtime. Нужен весь пользовательский путь управления доступом в Yii2.

## What Changes

- Один IdentityAccess owner на Yii DB для invite/reissue/activate/role/status.
- Yii controllers/views/assets для полного administrative flow.
- Реальные HTTP/browser и concurrency/rollback checks по YII2-USER-ACCESS-001.

## Capabilities

### New Capabilities
- `yii2-user-access`: Yii управление пользователями и одноразовыми приглашениями.

### Modified Capabilities

Существующий login/session контракт сохраняется с session-bound Yii CSRF.

## Impact

IdentityAccess, YiiRuntime/config/assets, тестовый inventory/fixtures и ratchet.
Миграций/новой зависимости/переключения рабочего стенда нет.
