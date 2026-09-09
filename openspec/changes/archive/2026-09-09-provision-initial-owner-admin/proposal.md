## Why

Clean production runtime после migrations не имеет пользователя и намеренно не
запускает demo bootstrap. Оператору нужен один явный native CLI для создания первого
owner-admin без автоматического повышения существующих пользователей.

## What Changes

- `INITIAL-OWNER-PROVISIONING-001`: deployment operator запускает explicit CLI с
  одним corporate email; password поступает только из external secret environment.
- Native IdentityAccess owner создаёт ровно одного active пользователя и назначает
  существующие `user` и `superadministrator` из `LocalRoleCatalog` в одной transaction.
- Exact повтор уже созданного bootstrap state возвращает replay без mutation;
  любое другое existing/partial/blocked/invited состояние отвергается без изменений.
- Command работает DML-only после canonical migrations, сериализует concurrent
  attempts и выдаёт stable secret-free JSON/exit outcomes.

Не входят: новые роли/permissions, UI, invitation, изменение password policy,
автоматический startup hook, demo manifest/bootstrap/rebuild, email или external sends.

## Capabilities

### New Capabilities

- `operations/initial-owner-provisioning`: явное одноразовое создание первого
  production owner-admin и безопасный exact replay/refusal contract.

### Modified Capabilities

Нет.

## Impact

Actor — deployment operator. Source oracle: accepted runtime provisioning plan,
existing bootstrap semantics, IdentityAccess-owned `LocalRoleCatalog`, canonical identity schema.
Public seams: `bin/fmonitor2-provision-initial-admin.php` и новый узкий method того же
IdentityAccess owner. Затрагиваются native identity application/CLI, tests,
production README/runbook; HTTP/startup и migrations не вызывают operation.
