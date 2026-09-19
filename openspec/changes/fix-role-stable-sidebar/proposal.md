## Why

Один и тот же пользователь сейчас видит разный состав основного sidebar при переходе с корневых Yii2-разделов на вложенные экраны: общий permission-aware renderer сменяется устаревшим статическим fallback. Это нарушает ожидаемую навигацию и повторяет уже исправлявшийся дефект на непокрытых surfaces.

## What Changes

- Сделать effective permissions текущего пользователя единственным источником видимости пунктов основной навигации на всех Yii2 HTML-экранах, использующих общий sidebar.
- Сохранять один и тот же упорядоченный набор разрешённых пунктов при переходах между корневыми и вложенными экранами; route влияет только на `aria-current`, а неизвестный вложенный route не скрывает и не добавляет пункты.
- Удалить статический fallback `ViewSupport`, который формирует иной набор меню.
- Расширить bounded HTTP regression вложенными экранами и несколькими permission combinations, не меняя server-side authorization.
- Не менять роли, permissions, маршруты, дизайн sidebar, внутреннюю навигацию ОТиЗ и доменную логику.

## Capabilities

### New Capabilities

- `runtime/role-stable-sidebar`: одинаковая permission-derived видимость основной Yii2-навигации на корневых и вложенных HTML-экранах.

### Modified Capabilities

Нет.

## Impact

Затрагиваются presentation seam `app/YiiRuntime/ViewSupport.php`, при необходимости явный current-section mapping вызывающих Yii2 views/controllers, общий navigation regression и его verification registration. API, persistence, schema, RBAC facts и route guards не меняются.
