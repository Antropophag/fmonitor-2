## Why

На пяти существующих Yii2-разделах один и тот же пользователь видит разный состав основной навигации, хотя его effective permissions не меняются. Срез #150 устраняет это presentation-расхождение минимальным общим renderer без изменения RBAC, маршрутов, серверной авторизации или внутренней навигации ОТиЗ.

## What Changes

- Ввести общий permission-aware renderer основной навигации для `/pilot/objects`, `/pilot/construction-control`, `/pilot/otiz`, `/pilot/admin/users` и `/pilot/admin/roles`.
- Формировать одинаковый набор разрешённых canonical links по effective permissions текущего пользователя; между страницами меняется только `aria-current="page"`.
- Сохранить внутреннюю навигацию ОТиЗ рядом с общей MAIN navigation.
- Добавить bounded Yii HTTP/browser regression, проверяющий semantic navigation DOM, permission combinations, прежнюю route authorization и наличие внутренних OTIZ links.
- Не менять product sections, route architecture, RBAC semantics, sidebar design, mobile layout, frontend stack или другие issues.

## Capabilities

### New Capabilities

- `runtime/yii-main-navigation`: единое permission-aware основное меню на пяти Yii2 surfaces issue #150.

### Modified Capabilities

Нет.

## Impact

Затрагиваются только Yii2 presentation code пяти указанных surfaces, небольшой общий renderer/view seam, один focused HTTP/browser regression и штатная регистрация теста. Источник решений доступа — существующий `canonicalAccess`; новые permissions, routes, dependencies, state changes и schema changes отсутствуют.
