## Why

Сотрудник и Руководитель ФКР должны загрузить оригинал и получить сохранённый PDF через портал. Текущий HTTP predecessor сохраняет registration facts самостоятельно; approval application command ещё не доказывает целевой пользовательский путь.

## What Changes

- Добавить HTTP adapter загрузки/correction, который вызывает только `submitAssignmentOrderOriginal` и передаёт actor из доверенной session.
- Добавить авторизованное чтение metadata/history и скачивание immutable revision через отдельный read-only application seam.
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

Не входит: opening, применение состава, OCR, интеграция 1С ДО, изменение blocked `PILOT-E2E-FLOW-001`, PR #10 или Quality Graph. Implementation заблокирован до полного command Gate 5 и independent Gate 1 этого slice. NEEDS_GRILL: если аудит действующего RBAC не устанавливает круг читателей original evidence, read/download остаются заблокированы до решения владельца; права чтения не выводятся автоматически из upload/correct.
