## Why

После landing canonical object-bound planning seam из #14 пользователю всё ещё недоступен законченный Yii-путь create → calendar/queue → reschedule → cancel. Issue #255 публикует этот seam без второго хранилища или новой модели назначения.

## What Changes

- Добавить в строку «Стройконтроль» состояние текущего плана и видимые действия планирования.
- Добавить один доступный `shlz-ui` dialog и реальные Yii POST routes create/reschedule/cancel с CSRF, request identity и expected version.
- Перевести queue и calendar на одну canonical current-plan projection после каждой команды.
- Вычислять московское «сегодня» и приоритет всей сегодняшней группы на сервере внутри действующих filters/scope до COUNT/LIMIT со стабильным порядком.
- Различать подтверждённый отказ и неизвестный исход, сохранять введённую дату/контекст и не повторять команду скрыто.

## Capabilities

### New Capabilities

- `runtime/inspection-planning-ui`: Yii UI, HTTP commands, shared current-plan presentation and Moscow-day priority for issue #255.

### Modified Capabilities

Нет.

## Impact

Затрагиваются только Yii construction-control/calendar projections, controllers/routes, локальный inspection asset, точечные asset/test registrations и focused tests. Не затрагиваются #258 installer surfaces, `object-ui.js`, picker, `navigation.js`, `pilot.css`, persistence/migrations, inspection outcomes, checklist/progress writers, assignments, ОТиЗ и invitations.
