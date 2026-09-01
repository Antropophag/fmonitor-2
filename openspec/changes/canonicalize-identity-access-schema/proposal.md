## Why

Девять таблиц локальной identity/access модели сейчас создаются destructive bootstrap-операцией, а `fm2_pilot_user_status_events` дополнительно создаётся на request path. Из-за отсутствия строгого canonical migration contract чистое или частично подготовленное развёртывание не может доказать готовность login/invitation, role grant и block/unblock путей без runtime DDL.

## What Changes

- Добавить strict canonical ownership для `fm2_pilot_users`, `fm2_pilot_roles`, `fm2_pilot_role_permissions`, `fm2_pilot_user_roles`, `fm2_pilot_auth_credentials`, `fm2_pilot_invitations`, `fm2_pilot_user_role_events`, `fm2_pilot_auth_attempts` и `fm2_pilot_user_status_events` как literal canonical migration v6 после landed workforce v5.
- Зафиксировать full-family preflight: clean schema, безопасный repeat,
  полностью populated compatible schema, restartable exact-compatible partial
  recovery, несовместимый fingerprint одной таблицы, conflict внутри family,
  table-prefix isolation и отсутствие runtime DDL. Missing members создаются
  только когда каждый existing member exact-compatible; любой incompatible
  member даёт zero-mutation conflict до первого DDL.
- Сохранить текущие auth/RBAC данные и наблюдаемое local RBAC поведение при переносе ownership; canonical migration не seed-ит роли/пользователей, не пересобирает таблицы и не выполняет destructive reset.
- Оставить destructive seed/rebuild отдельной явно вызываемой bootstrap-операцией и удалить runtime/request-path DDL только после прохождения strict migration verification.
- Пометить local RBAC authority и точную authorization semantics как `NEEDS_GRILL (GRILL-002)`: этот ownership slice не утверждает текущую security модель и блокирует behavior-contract implementation, которое потребовало бы выбрать между current local RBAC и прежним legacy-role contract.
- Не фиксировать непроверенные literal fingerprints в OpenSpec: точные nine-table definitions, индексы, foreign keys, enum/default/collation и совместимость должны быть утверждены executable schema spec на Gate 1 до RED.

## Capabilities

### New Capabilities

- `deployment/canonical-identity-access-schema`: строгий canonical migration contract для существующей identity/access table family с сохранением данных, prefix isolation и запретом runtime DDL.

### Modified Capabilities

Нет.

## Impact

- **Behavior slice / actor:** deployment operator применяет canonical runner до запуска test-user HTTP/auth путей; runtime users продолжают наблюдать существующие login, invitation, role-grant и block/unblock outcomes без принятия новых security semantics.
- **Source oracle:** `rapid-pilot/IdentityBootstrap.php`, `rapid-pilot/UserAccessView.php`, `rapid-pilot/LocalAuth.php`, `app/RapidPilot/LocalRoleCatalog.php`, `app/PilotHttp/AccessPolicy.php` и identity/access verifiers являются evidence текущей схемы и поведения, но не получают canonical ownership.
- **Target public seam:** `bin/fmonitor2-migrate.php` и его strict migration/version contract; destructive bootstrap остаётся отдельным operator seam.
- **Release value:** clean и populated test deployments смогут доказать identity/access readiness до HTTP traffic, а architecture debt уменьшится без потери строк или скрытого repair на request path.
- **Explicit non-goals:** изменение permission catalogue, role meanings, legacy fallback, login/session/password/invitation policy, block/unblock authorization, audit semantics, UI/API, seed contents или destructive reset lifecycle; эти behavior решения остаются в GRILL-002 и последующих Gate 1 slices.
- **Dependencies:** landed canonical runner заканчивается workforce v5;
  identity/access занимает literal v6. Любая вставка нового predecessor до
  implementation требует fresh version reconciliation и повторного Gate 1.
- **Owner decision:** 2026-09-02 approved restartable exact-compatible partial
  recovery and authorized coherent updates to all four OpenSpec artifacts;
  GRILL-005 is resolved for this schema-ownership slice.
