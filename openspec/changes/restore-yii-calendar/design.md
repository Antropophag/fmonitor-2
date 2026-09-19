## Context

Канонический Yii runtime уже владеет authentication/session, permission-aware общим shell, object detail routes, planning application/read seams и calendar CSS/JS assets. `rapid-pilot/Calendar.php` остаётся behavioral oracle для периода, selected date, safe failures и представления, но не является местом новой логики. Planning schema создаётся только canonical migration; runtime обязан fail closed без repair.

## Goals / Non-Goals

**Goals:**

- один Yii read controller/view для обоих calendar path variants;
- детерминированный bounded read через Yii/runtime-owned planning dependency;
- единое permission решение для route и `MainNavigation` membership;
- повторное использование уже vendored `shlz-ui` Calendar Grid и calendar behavior assets.

**Non-Goals:**

- новые schedule facts, schema/migrations, cadence или mutation commands;
- перенос новой логики в `rapid-pilot/`;
- изменение object-detail authorization, sidebar styling или несвязанной navigation hierarchy;
- redesign полного legacy calendar event catalogue вне inspection schedules из issue #203.

## Decisions

- Владельцем HTTP behavior и bounded read-model будет `CalendarController` в Yii integration boundary, использующий canonical schema readiness seam. SQL во view и вызов `rapid-pilot/Calendar.php` отклонены: первый смешивает transport и persistence, второй сохраняет временный adapter как production owner. Выделение нового protected application owner не входит в bounded restoration.
- Projection читает только существующие schedule rows в bounded date window, связывает их с object display identity и выполняет explicit SQL ordering `inspection_date`, `legacy_object_id`, stable schedule identity; presentation дополнительно сохраняет month/date grouping. Надежда на insertion order отклонена как недетерминированная.
- Route и navigation используют одну capability check `objects.read`. Отдельная calendar permission отклонена, потому что issue задаёт существующий permission и не разрешает RBAC migration.
- GET проходит planning readiness guard до query. Missing/incompatible schema и overflow превращаются в opaque/safe `503`; runtime DDL, repair и silent truncation запрещены.
- View повторно использует общий Yii shell, existing object links, `pilot.css`, `calendar.js` и публичный `shlz-ui` Calendar Grid export. Копирование rapid-pilot HTML целиком отклонено; его observable labels/selection semantics служат oracle.

## Verification impact

- Focused real-HTTP test обязан независимо seed-ить schedule facts в непоследовательном порядке, лишний out-of-range факт и permission-negative user; затем проверить exact projected dates/objects/order, оба path variants, current navigation, `403`, `400`/`503` и unchanged facts/schema.
- Focused browser test обязан открыть calendar через пункт `Монтаж`, проверить rendered chronological grouping, selected agenda/current link и возврат к object detail без mutation.
- Изменения Yii HTTP/runtime boundary требуют `make architecture-check`; если затрагивается `app/PilotHttp/*.php`, дополнительно обязателен `php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php`.
- Schema, backup/restore, imports, jobs и deployment topology не меняются; существующие planning runtime-no-DDL и Yii navigation regressions остаются adjacent checks.

## Risks / Trade-offs

- [Большой видимый период создаёт тяжёлую HTML grid] → сохранить bounded period/source limit и fail closed вместо silent truncation.
- [Route membership и authorization могут разойтись] → использовать одну permission predicate и проверить negative role на обоих seams.
- [Legacy oracle включает дополнительные planned start/end events] → этот slice сознательно ограничен inspection schedule facts из #203; расширение event catalogue требует отдельного product contract.
- [Physical DB order может маскировать нестабильность] → тестовая fixture сохраняет rows не в ожидаемом presentation order и сравнивает semantic order.

## Migration Plan

Новая schema и data migration отсутствуют. Deploy добавляет Yii route/projection/view/navigation atomically; rollback удаляет эти элементы и возвращает прежний `404`, не преобразуя и не удаляя schedule facts.
