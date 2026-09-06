## Why

Сотрудник и Руководитель ФКР должны загрузить оригинал и получить сохранённый PDF через портал. Текущий HTTP predecessor сохраняет registration facts самостоятельно; approval application command ещё не доказывает целевой пользовательский путь.

## What Changes

- Добавить HTTP adapter загрузки/correction, который вызывает только `submitAssignmentOrderOriginal` и передаёт actor из доверенной session.
- Добавить авторизованное чтение metadata/history и скачивание immutable revision через отдельный read-only application seam.
- Выдать exact read permission ФКР и Руководителю ФКР в пределах доступных объектов, инженеру — по закреплённым за ним объектам, ОТиЗ — по всем распоряжениям. Доступ включает прошлые revisions; административная роль сама по себе его не даёт.
- **BREAKING**: заменить pilot registration upload маршрут и его capability на original upload/correct; исторические registration facts сохранить.
- Зафиксировать exact routes, DTO, local permissions, CSRF/session admission и HTTP mapping в отдельной executable spec до RED. Это planning candidate, не утверждение готовности этих контрактов.

## Capabilities

### New Capabilities

- `pilot/assignment-order-original-http`: доставка одного original PDF через публичный портал с безопасным чтением evidence.

### Modified Capabilities

Нет изменений main capabilities в этом planning пакете.

## Impact

Источник требований: `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`, approved command contract `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`. `PilotE2ECoordinator` — evidence поведения predecessor, не источник новых acceptance expectations.

Затрагиваются `app/PilotHttp`, route/session admission, local RBAC и application read-only interface. State mutation принадлежит только original command. Release value — воспроизводимый browser upload→metadata→download без ручного номера.

Не входит: opening, применение состава, OCR, интеграция 1С ДО, изменение blocked `PILOT-E2E-FLOW-001`, PR #10 или Quality Graph. Native selection, selected-original binding и PDF generation имеют Gate5 APPROVED (restart handoff2026-09-06-2123Z). Implementation HTTP требует independent Gate1 своего executable contract; повторно review approved domain owners не выполняется. Owner decision от 2026-09-05 разрешает указанный read scope, включая ОТиЗ по всем распоряжениям; права чтения не выводятся автоматически из upload/correct.

## Current fresh-launch integration — 2026-09-07

Исторических данных/PDF нет; старые writers не мигрируются ради совместимости.
Перед upload UI отдельный узкий change expose-assignment-order-composition-http
подключает native selection new_order/replace_pending и PDF-on-demand. Original
HTTP использует ProductionAssignmentOrderOriginalFactory::createForSelections,
не create/createRecoveryReady и не hidden prepare. Последняя template date читается
через approved dateReader после authorization; files/versions шаблона не хранятся.
Opening и composition application остаются следующими отдельными lifecycle slices.
