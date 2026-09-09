## Why

#76 требует заменить `RapidPilotLocalAuth` штатным Yii2 authentication/session
lifecycle. Первый законченный пользовательский срез даёт локальному пользователю
вход, выход и защищённое чтение справочника ролей без переноса административных
команд и без совместимости со старой cookie.

## What Changes

- `YII2-AUTH-001`: `GET|POST /pilot/login`, `POST /pilot/logout` и защищённый
  `GET /pilot/admin/roles` обслуживаются Yii Request/User/Session/AccessControl.
- Canonical IdentityAccess остаётся владельцем пользователей, credentials, ролей
  и permissions; Argon2id hashes проверяются без изменения.
- Новый session cookie namespace принудительно требует повторный вход, что явно
  разрешено владельцем; legacy payload/storage bridge не создаётся.
- Приглашения, активация, reissue, управление статусами/ролями и прочие routes не
  входят в этот срез.

Актор — локальный пользователь FMonitor. Oracle — текущие `RapidPilotLocalAuth`,
`PilotUserAccessHttpHandler`, `LocalRoleCatalog` и утверждённые local RBAC contracts.
Целевой публичный seam — реальные HTTP routes изолированного Yii runtime.

## Capabilities

### New Capabilities

- `yii2-authentication`: Yii login/logout/session и exact RBAC admission первого
  read-only справочника ролей.

### Modified Capabilities

Нет.

## Impact

Yii web configuration, IdentityAccess read adapters, auth controller/form/view,
session/runtime configuration, roles controller/view и black-box HTTP verification.
Production stand и legacy routes этим срезом не переключаются.
