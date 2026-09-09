# Initial owner administrator provisioning — technical plan 2026-09-09

Status: **PROPOSED / NOT IMPLEMENTED**. Это минимальный явный clean-install шаг #27
после canonical migrations #33. Он не запускается из web/FPM startup, runtime
readiness, migration runner или Compose `up` и не утверждает production readiness.

## Найденные владельцы и повторно используемые paths

- `app/PilotHttp/MariaDbIdentityBootstrapApplication::apply()` — существующая
  публичная native application operation. Она нормализует один или несколько
  `@shlz.ru` email, хеширует password через `PASSWORD_ARGON2ID`, seed-ит утверждённый
  `FMonitor2\RapidPilot\LocalRoleCatalog`, создаёт/активирует credential и назначает
  только существующие роли `user` и `superadministrator` с `origin=bootstrap` и
  `assigned_by_user_id=NULL` в транзакции.
- `app/RapidPilot/LocalRoleCatalog::roles()` — единственный действующий каталог
  системных ролей/permissions. CLI не принимает role/permission arguments и не
  создаёт новые роли.
- `rapid-pilot/IdentityBootstrap.php::apply()` — временный adapter к native
  application operation; новый production CLI не должен зависеть от него.
- `rapid-pilot/docker-bootstrap.php` — demo composition, который одновременно
  занимается generation state. Он не переиспользуется production provisioning.
- Canonical identity migrations/readiness из `app/InstallationProcess` остаются
  единственным schema owner. Provisioning выполняет только DML после readiness и не
  вызывает rebuild/destructive seed.

Текущий `MariaDbIdentityBootstrapApplication::apply()` обновляет существующего
active user, заполняет отсутствующий password и назначает bootstrap grants. Такое
поведение нужно pilot bootstrap, но слишком широко для initial production owner:
оно могло бы повысить уже существующего пользователя. Нужен узкий новый метод того
же owner, а не прямой SQL в CLI.

## Предлагаемый command

```text
php bin/fmonitor2-provision-initial-admin.php --email <owner@shlz.ru>
```

Password не передаётся argv/stdin. CLI читает exact непустой
`FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD` из injected external secret environment и
стандартные production `FMONITOR_DB_HOST`, `FMONITOR_DB_PORT`, `FMONITOR_DB_NAME`,
`FMONITOR_DB_USER`, `FMONITOR_DB_PASSWORD`, `FMONITOR_PROCESS_TABLE_PREFIX`.
Aliases/defaults/demo manifest/generation запрещены. Email — ровно один argument;
existing normalization/validation остаётся lowercase trimmed exact `@shlz.ru`.
Password policy не расширяется этим техническим срезом: используется текущая
bootstrap проверка «непустой» и Argon2id. Значения email/password/DB config не
попадают в stdout/stderr.

CLI подключается под отдельным provisioning principal с DML на canonical identity
tables, без DDL. Он проверяет schema readiness, получает deterministic per-database/
prefix MariaDB advisory lock с timeout `0`, вызывает application operation и всегда
освобождает lock. Lock сериализует clean-state admission между операторами; это не
authorization mechanism. Доступ к secret и запуску CLI обеспечивает deployment
boundary.

## Новый узкий application seam

Предлагаемый seam в существующем owner namespace:

```php
MariaDbIdentityBootstrapApplication::provisionInitialOwner(
    mysqli $db,
    string $prefix,
    string $email,
    string $bootstrapPassword,
): InitialOwnerProvisioningResult
```

Operation MUST:

1. Проверить canonical schema без DDL и начать одну transaction.
2. Если identity user table пуста, seed/update exact definitions из
   `LocalRoleCatalog`, создать одного active user и Argon2id credential, назначить
   только `user` и `superadministrator` с текущим bootstrap provenance, подтвердить
   хотя бы одного active superadministrator и commit.
3. Если в базе ровно один user и он byte-exact совпадает с requested normalized
   email, active/not blocked, имеет credential, incoming password проходит
   `password_verify`, а обе grants уже имеют exact bootstrap provenance, вернуть
   `already_provisioned` без UPDATE/INSERT/password rehash. Это единственный
   идемпотентный replay.
4. При любом другом существующем user state — включая тот же email без exact
   bootstrap grants, invited/blocked user, другой password или более одного user —
   вернуть `identity_not_empty` без изменения users, credentials, grants, role
   definitions, invitations или events. Нельзя активировать/повышать существующего
   пользователя и нельзя «доделывать» частичное состояние.
5. Любой failure до commit откатывает user/credential/roles/grants вместе. CLI не
   вызывает существующий `apply()` так, чтобы role seed commit-нулся отдельно от
   clean-state admission.

Full name сохраняет текущую bootstrap политику и равен normalized email. Изменение
имени выполняется позже обычной утверждённой identity operation, не аргументом этого
CLI. Существующая политика отзывать open invitations для bootstrap user фактически
no-op на clean install; replay ничего не меняет.

## Exact observable outcomes

Stdout содержит одну JSON line; stderr пуст для ожидаемых outcomes:

| Условие | Exit | stdout |
|---|---:|---|
| Новый owner создан | `0` | `{"ok":true,"status":"created"}` |
| Exact безопасный replay | `0` | `{"ok":true,"status":"already_provisioned"}` |
| Любой иной existing user/state | `65` | `{"ok":false,"reason":"IDENTITY_NOT_EMPTY"}` |
| Invalid/missing args или config | `64` | `{"ok":false,"reason":"CONFIGURATION_INVALID"}` |
| Canonical schema не готова | `70` | `{"ok":false,"reason":"SCHEMA_NOT_READY"}` |
| Provisioning lock занят | `75` | `{"ok":false,"reason":"PROVISIONING_BUSY"}` |
| DB/hash/unexpected failure | `70` | `{"ok":false,"reason":"PROVISIONING_UNAVAILABLE"}` |

Ни один result не содержит email, password/hash, DB endpoint, prefix, SQL или native
exception. Успешный result не является login token. После `created` оператор
проверяет обычный `/pilot/login` через trusted production Host и затем удаляет secret
из ephemeral deployment environment.

## Проверочная матрица перед реализацией

- Clean migrated DB: один active user, Argon2id verifies, exact `user` и
  `superadministrator`, exact current role catalogue/permissions, no other user.
- Exact repeat с тем же password: byte-exact DB snapshot unchanged и
  `already_provisioned`.
- Same email с другим password, invited/blocked/partial existing user, другой user и
  multiple users: `IDENTITY_NOT_EMPTY`, zero mutation и никакого promotion.
- Два concurrent clean invocations: один `created`; проигравший busy либо после
  release exact `already_provisioned`; никогда два users/grant sets.
- Missing/incompatible schema под DML-only principal: no DDL/repair, stable failure.
- Injected failures после role definitions, user, credential и первой grant:
  transaction rollback оставляет clean preimage.
- Args/env/DB/native error matrix доказывает secret-free stdout/stderr.
- Existing `MariaDbIdentityBootstrapApplication::apply()` pilot behavior и ordinary
  invitation/role/status flows остаются regression-green.

## Clean install sequence after #33

1. Backup/volume preparation and exact image selection.
2. `fmonitor2-runtime-prepare.php`.
3. Separate canonical `fmonitor2-migrate.php`.
4. Provision DML runtime DB account and separate provisioning authority.
5. Run this CLI exactly once; safe retry допускается только с теми же email/password.
6. Start PHP-FPM/nginx and verify readiness/login.
7. Subsequent users are invited and roles managed only through existing authorized
   IdentityAccess application/UI seams.

До реализации/independent review этого command clean production installation не
имеет безопасного initial-admin шага. Не использовать вместо него demo bootstrap,
`rebuild`, ручные INSERT/UPDATE или автоматическое promotion при startup.
