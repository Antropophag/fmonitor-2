## Context

Existing bootstrap owner уже знает email/password/role semantics, но `apply()` может
активировать и повысить existing user и вызывает migration apply. Production нужен
более узкий clean-only method после migration readiness.

## Goals / Non-Goals

**Goals:** один deep IdentityAccess operation, atomic create, exact replay, fail-closed
existing-state conflict, DML-only CLI и usable clean-install handoff.

**Non-Goals:** изменение pilot `apply/rebuild`, role catalogue/password policy,
runtime startup, invitation/UI или automated deployment.

## Decisions

Добавить IdentityAccess-owned result value object и
`MariaDbInitialOwnerProvisioning::provision(mysqli,prefix,email,password)`.
Method validates prefix/email/password, проверяет schema readiness, начинает transaction,
lock-ит identity admission через deterministic MariaDB advisory lock wrapper CLI и
различает empty/exact replay/conflict. Role seed переносится в transaction для нового
method. `FMonitor2\IdentityAccess\LocalRoleCatalog` становится policy owner, а
existing RapidPilot catalog — thin compatibility forwarder; pilot apply сохраняет поведение.

Exact replay требует одного user, active/nonblocked state, password_verify и exact
`user`/`superadministrator` memberships с bootstrap provenance. Это не позволяет
признать manually promoted/partial state. Role definitions/permissions должны
соответствовать current catalog; иначе conflict, не repair.

CLI парсит args/env, подключается напрямую, вызывает owner и отображает stable JSON.
Expected failures map to sysexits 64/65/70/75. Unexpected details redacted. No DDL,
network или rapid-pilot dependencies. Architecture checker preserves one owner seam.

## Risks / Trade-offs

- [Потерян ответ после commit] → exact state/password replay.
- [Concurrent clean attempts] → named lock timeout0 plus transaction checks.
- [Partial prior bootstrap] → refuse, operator diagnosis; no silent repair.
- [Existing pilot relies on broad apply] → retain apply/rebuild and focused regression.

## Migration Plan

No schema migration. Deploy after #33 migrations, execute once with external secrets,
verify ordinary login, then remove bootstrap secret. Rollback does not delete owner;
subsequent changes use authorized IdentityAccess flows.
