## Context

См. proposal и `docs/operations/migrated-evidence-schema-evidence.md`. Family имеет три overlapping DDL owners, split collation semantics и implicit-commit partial states. Implementation blocked until earlier canonical families land and exact catalogue/version is refreshed.

## Goals / Non-Goals

**Goals:** exact six-table canonical owner; restartable 64-state recovery; populated preservation; DDL-free consumers; architecture debt reduction.

**Non-Goals:** import/backfill/rebuild, reconciliation/premium policy, FK/storage redesign, destructive rollback.

## Decisions

### 1. Один family migration с family-wide preflight

Migration сначала fingerprints все existing members, затем создаёт missing в order source snapshots → quarantine → projection → conflicts → decisions → decision state. Альтернатива owner-specific migrations оставляет промежуточные consumers ответственными за repair.

### 2. Exact-compatible partial recovery

Каждый member absent или exact; любая incompatibility останавливает до DDL. Это необходимо из-за per-statement MariaDB implicit commit и позволяет безопасно продолжить после interruption.

### 3. Split collation policy вычисляется, не hard-code-ится

Inherited tables используют validated database utf8mb4 default; explicit-charset tables — validated MariaDB default for utf8mb4. Оба values emitted explicitly. Existing populated semantic-exact members не конвертируются.

### 4. JSON alias fingerprint semantic

`conflict_codes_json` требует LONGTEXT/utf8mb4_bin plus `json_valid` CHECK независимо от generated constraint name. Остальные JSON-labelled LONGTEXT не получают новый CHECK.

### 5. Runtime adapters становятся data-only

Canonical persistence owner находится в migration layer. Legacy importer, projection store and decision ledger retain data responsibilities but replace CREATE with shared exact precondition. OTIZ remains adapter/oracle; no new domain logic enters rapid-pilot.

Allowed future module dependencies: canonical migration contracts and MariaDB metadata adapter. Application/domain modules cannot depend on importer/HTTP/rapid-pilot/concrete migration.

### 6. Data repair отделён

Migration never calls persist/backfill/rebuild. Existing implicit-commit backfill risk receives separate hardening contract; ownership change only makes its schema precondition DDL-free.

### 7. No destructive down

Before deployment code rollback is possible. After schema version, forward fix only; populated evidence/history is never dropped.

### 8. Composed prefix contract отделён от family-local arithmetic

Longest basename family задаёт direct-family ceiling 28 bytes, но composed
release-supporting catalogue использует 25-byte success / 26-byte rejection до
DB connection/access. Direct migration evidence не объявляется production
configuration support.

## Risks / Trade-offs

- [Split collations surprise operators] → exact environment resolution and explicit fingerprint/output.
- [64-state matrix large] → generated deterministic state catalogue with per-case row/counter fingerprints.
- [Partial CREATE interruption] → exact-compatible restart.
- [Existing incompatible family blocks tools] → explicit conflict/operator remediation, never silent repair.
- [Ownership appears to approve migrated facts] → non-goals and status retain semantic decisions outside change.
- [Backfill remains non-atomic] → separate risk/hardening slice after runtime DDL removal.

## Migration Plan

1. After predecessors land, refresh catalogue/composed 25/26 prefix/version and create exact Gate 1 schema spec; obtain owner approval.
2. Reviewed RED covers clean/repeat/64 partial/conflicts/collations/JSON/populated preservation/runtime DDL denial.
3. Implement additive migration/register runner, then replace three owners with exact precondition.
4. Run import/projection/decision/OTIZ regressions, architecture, fresh lifecycle and full verify.
5. Fresh code review confirms ownership/data preservation/non-promotion.

Rollback is code-only before deployment and forward-fix after migration; no destructive `down()`.
