## Why

TEST-USER contour уже имеет local authentication и таблицы local RBAC, но HTTP-маршруты и verifiers всё ещё используют неоднородные источники доступа: legacy identity, имя пользователя, один лишь факт authentication либо неполные local fixtures. Это не позволяет доказать единый fail-closed authorization contract до допуска тестовых пользователей.

Slice `LOCAL-RBAC-AUTH-CONTRACT-001` фиксирует принятое владельцем решение `GRILL-002`: authority даёт только активная локальная учётная запись с завершённой активацией, хотя бы одной активной назначенной ролью и exact permission для конкретного маршрута.

## What Changes

- Вводится единый read-only public authorization seam, который по local authenticated user ID и требуемому exact permission возвращает разрешённого actor либо стабильный отказ.
- Первый vertical consumer `GET /pilot/objects` передаёт exact `objects.read`;
  последующие route slices обязаны переиспользовать seam со своей mapping,
  permissions не наследуются и не подменяют друг друга.
- **BREAKING**: legacy users/roles/rights, `REMOTE_USER`, display name, role name и один лишь факт успешной authentication перестают быть fallback authorization для local-authenticated pilot routes.
- Неактивный пользователь, незавершённая/заблокированная активация, отсутствие активной назначенной роли, отсутствие exact permission, неоднозначная либо недоступная конфигурация отклоняются fail closed до выполнения защищённого handler-а.
- Gate 1 executable contract создаётся как `specs/LOCAL-RBAC-AUTH-CONTRACT-001.md`; tests, production implementation, route fixture migrations и изменения экранов остаются последующими gated tasks.
- Source oracle: текущие `rapid-pilot/LocalAuth.php`, `app/PilotHttp/AccessPolicy.php`, route mappings и pilot verifiers рассматриваются как evidence, но противоречащие owner decision fallback-наблюдения не становятся требованиями.
- Target public seam: application-owned local authorization contract; HTTP/UI только выбирает требуемый permission и вызывает seam.
- Release value: критические TEST-USER routes получают одну доказуемую least-privilege boundary до отдельных fixture/E2E slices.
- Non-goals: новый login/session protocol, управление пользователями и ролями, schema migration, аудит административных изменений, изменение набора route permissions, CSP и PDF artifact contract.

## Capabilities

### New Capabilities

- `local-rbac-auth-contract`: Единый fail-closed contract авторизации local-authenticated actor по активной учётной записи, завершённой активации, активной назначенной роли и exact route permission.

### Modified Capabilities

Нет.

## Impact

- Будущий implementation затронет application authorization contract/composition и вызовы из `app/PilotHttp`; `rapid-pilot` останется wiring-адаптером без новой domain logic.
- Существующие legacy-authenticated specs остаются историческим контрактом их маршрутов до отдельной миграции; этот slice не меняет upstream authentication и session lifecycle.
- Следующие slices `PILOT-OBJECT-READ-RBAC-FIXTURES-001`, `PILOT-PREPARE-RBAC-FIXTURES-001` и `PILOT-E2E-RBAC-FIXTURES-001` будут опираться на этот contract, но не входят в него.
- Независимый Gate 1 review, RED evidence, test review, minimal GREEN и code review обязательны по `docs/development-process.md`.
