# INITIAL-OWNER-PROVISIONING-001 — первый production owner-admin

## Простыми словами

После migrations оператор одной явной командой создаёт первого администратора.
Команда безопасно повторяется только для exact созданного состояния и никогда не
повышает уже существующего пользователя.

## Public seam

`php bin/fmonitor2-provision-initial-admin.php --email <owner@shlz.ru>` и
`FMonitor2\IdentityAccess\MariaDbInitialOwnerProvisioning::provision(...)`.

Input: exact direct DB/prefix config, один normalized `@shlz.ru` email, непустой
external `FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD`. Output — одна JSON line и exit.
Secrets, email и DB details не выводятся.

## Outcomes

- Clean canonical identity: одна transaction создаёт current LocalRoleCatalog,
  одного active user с `full_name`, равным normalized email, Argon2id credential и exact grants `user` plus
  `superadministrator`, origin bootstrap/null actor; exit0/status created.
- Exact repeat: ровно один same active user с empty phone/session_version1, password_verify,
  exact role definitions/bootstrap grants и пустыми invitations/role events/auth attempts/status events;
  zero mutation, exit0/status already_provisioned.
- Любой иной existing/partial identity table, invited/blocked/different-password/multiple-user state:
  zero mutation, exit65/IDENTITY_NOT_EMPTY; no promotion/repair.
- Invalid config exit64/CONFIGURATION_INVALID; missing schema exit70/SCHEMA_NOT_READY;
  lock busy exit75/PROVISIONING_BUSY; unexpected exit70/PROVISIONING_UNAVAILABLE.
- CLI is DML-only after migrations; no demo/bootstrap/rebuild/startup hook or send.

Concurrent clean calls create no more than one user/grant set. Any failure before
commit preserves the full identity preimage. Existing broad pilot bootstrap behavior
and ordinary authorized invitation/role/status operations remain unchanged.
