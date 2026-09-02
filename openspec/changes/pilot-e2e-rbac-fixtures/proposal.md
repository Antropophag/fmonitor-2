## Why

Golden/demo E2E использует смешанные legacy/local identities и одновременно
содержит отдельный устаревший two-artifact PDF contract. Требуется изолировать
local-RBAC fixture alignment, чтобы authorization failures не маскировали
golden journey, не пытаясь внутри security slice исправить combined-PDF.

## What Changes

- Seed-ить canonical fictional local users/active roles/exact `objects.read`
  grant для actor-ов, которые вызывают уже migrated `GET /pilot/objects`, и
  передавать trusted local actor IDs.
- Доказывать отсутствие legacy/name/authenticated-only fallback и current
  committed revoke на E2E boundaries.
- Разделить snapshot boundary: authorization reads доказывают full
  equality, а после prepare до artifact boundary разрешён только exact
  approved order/event/artifact-metadata/storage delta; RBAC facts и counters
  остаются byte-equivalent.
- Не утверждать local-RBAC mappings для prepare/register/open/card/control:
  текущие process capabilities/legacy route contracts остаются до собственных
  migration slices.
- Классифицировать combined-PDF assertion как dependency отдельного
  `PILOT-E2E-COMBINED-PDF-001`, не ослаблять и не исправлять его здесь.
- **Actor:** test-environment operator. **Source oracle:** demo bootstrap и
  golden E2E. **Target public seam:** exact object-list steps внутри multi-step
  public HTTP journey через local authentication/RBAC. **Release value:** воспроизводимая security admission
  test-user contour. **Non-goals:** PDF artifact semantics, production data,
  password/session redesign, новые route permissions, command-route migration и broad admin role.

## Capabilities

### New Capabilities

- `verification/pilot-e2e-rbac-fixtures`: Определяет canonical least-privilege local-RBAC actor fixtures для golden journey и границу combined-PDF dependency.

### Modified Capabilities

Нет.

## Impact

Затронуты demo/E2E test fixtures и setup evidence. Зависимости:
`LOCAL-RBAC-AUTH-CONTRACT-001`, test-user synthetic data decision и отдельный
future `PILOT-E2E-COMBINED-PDF-001`; production authorization/PDF code вне scope.
