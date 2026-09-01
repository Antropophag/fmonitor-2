# Identity/access partial recovery owner decision

- Дата: `2026-09-02`
- Scope: `canonicalize-identity-access-schema` planning / `GRILL-005`
- Owner decision: `APPROVED`

Canonical identity/access migration использует restartable exact-compatible
partial recovery. До первого DDL она классифицирует все девять members. Если
каждый existing member exact-compatible, migration сохраняет его schema/data и
создаёт только missing members в dependency-safe order. Любой incompatible
member возвращает deterministic conflict с zero mutation.

Owner также явно разрешил согласованно обновить все четыре существующих
OpenSpec artifacts. Решение разрешает planning update, но не Gate 1 approval,
RED или implementation. GRILL-002 authority/authorization semantics остаётся
отдельным blocker только для behavior changes.
