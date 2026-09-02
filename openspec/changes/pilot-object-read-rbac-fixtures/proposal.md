## Why

Object-list verifier всё ещё создаёт legacy-only identity или
неполный local RBAC, поэтому после утверждения `LOCAL-RBAC-AUTH-CONTRACT-001`
положительные сценарии получают 401/403/503 либо сравнивают predecessor DOM.
Это скрывает реальные regressions read routes и блокирует TEST-USER verify.

## What Changes

- Ввести canonical test-owned active local users, roles, assignments и exact
  `objects.read` grants ровно для `GET /pilot/objects`.
- Явно разделить trusted local actor ID и legacy `REMOTE_USER`; legacy facts не
  получают fallback authority.
- Сохранить отрицательные 401/403/503, revoke, inactive, near-match,
  no-handler-read и current-snapshot assertions.
- Сохранять approved object-list representation assertions только после
  успешной route authorization.
- **Actor:** test/integration operator. **Source oracle:** object-list HTTP
  verifier. **Target public seam:** реальный `GET /pilot/objects`,
  object-list response через local authorization boundary.
- **Release value:** воспроизводимые object-read regressions после local-RBAC
  cutover. **Non-goals:** новые permissions, card-route authorization policy,
  production fallback, object-card/UI-shell authorization, login/session
  redesign и E2E artifact contract.

## Capabilities

### New Capabilities

- `verification/pilot-object-read-rbac-fixtures`: Определяет canonical local-RBAC fixtures и сохранение positive/negative object-read HTTP contracts.

### Modified Capabilities

Нет.

## Impact

Затронуты только test/support fixtures и object-list verifier.
Стабильный dependency — owner-approved `LOCAL-RBAC-AUTH-CONTRACT-001`; production
authorization semantics этим change не расширяются.
