## Why

После исправлений ручного пилота архитектурный ratchet фиксирует три новых SQL-fingerprint в `rapid-pilot/ObjectQueue.php` и рост двух HTTP-hotspot: `PilotE2ECoordinator.php` с 268 до 308 строк и `rapid-pilot/router.php` с 286 до 289 строк. Поведение уже принято владельцем и работает на ручном стенде; сейчас требуется вернуть явное владение SQL, session-admin orchestration и asset dispatch без изменения публичного результата и без rebaseline.

## What Changes

- Перенести чтение и построение проекции очереди объектов из rapid-pilot adapter в один `MariaDb*` read owner внутри `app/PilotHttp`; adapter продолжит разбирать HTTP-фильтры, проверять `objects.read` и рендерить прежнюю страницу через публичный read seam.
- Вынести owner-session orchestration административных маршрутов пользователей из `PilotE2ECoordinator` в отдельный HTTP collaborator: распознавание user-admin маршрутов, передачу доверенной локальной identity и CSRF, invite PRG/flash и атомарную публикацию изменённого session state.
- Вынести обслуживание `/pilot/assets/file-types/{name}.svg` из корневого rapid-pilot router в отдельный presentation/asset dispatcher и оставить router владельцем только route composition.
- Сохранить все существующие HTTP status, headers, HEAD/body, CSP, permission, filter, pagination, projection, flash, session-fault и asset-fallback контракты.
- Подтвердить изменения focused regression checks, затем выполнить независимый review. Архитектурный baseline не изменять.

Behavior slice: сохранение публичного поведения manual-pilot HTTP при восстановлении архитектурного владения. Actor: аутентифицированный пользователь пилота, администратор доступа и браузер, запрашивающий публичный asset. Source oracle: текущие approved HTTP/DB tests и работающий manual-pilot contract. Target public seams: `GET/HEAD /pilot/objects`, `/pilot/admin/users*` и `GET/HEAD /pilot/assets/file-types/{name}.svg`. Release value: architecture-check снова способен отличать новый долг от существующего без риска для показанного владельцу маршрута.

Явные non-goals: новое продуктовое поведение, изменение ролей или полномочий, изменение данных/схемы, перенос доменной логики, изменение UI, rebaseline, полный architectural migration, stand/restart/import, production data, remote/CI действия. Новых продуктовых решений и NEEDS_GRILL нет: change сохраняет наблюдаемое поведение.

## Capabilities

### New Capabilities

Нет. Это чистое перераспределение существующей реализации; `.openspec.yaml` использует `skip_specs: true`.

### Modified Capabilities

Нет. Публичные требования и наблюдаемое поведение не меняются.

## Impact

Планируемые владельцы находятся в `app/PilotHttp` для MariaDB queue read и session-aware user-admin HTTP orchestration, а presentation asset dispatcher — в `rapid-pilot` либо существующем HTTP asset boundary без доменной логики. Wiring меняется в `rapid-pilot/ObjectQueue.php`, `app/PilotHttp/PilotE2ECoordinator.php` и `rapid-pilot/router.php`. Затрагиваются только focused tests/verifiers этих маршрутов и architecture check; schema, data, external dependencies и deployed stand не затрагиваются.
