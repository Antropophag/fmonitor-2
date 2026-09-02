## Context

См. `proposal.md`, `docs/operations/object-detail-schema-evidence.md` и
`docs/operations/object-detail-import-behavior-evidence.md`. Importer выполняет
два independently committed `CREATE TABLE IF NOT EXISTS` непосредственно перед
data transaction. Consumers доверяют сохранённому payload/hash и не являются
schema owners. Native-only bootstrap всё ещё вызывает production-source import,
поэтому ownership и population необходимо разделить.

## Goals / Non-Goals

**Goals:**

- Один canonical persistence owner для exact two-table family.
- Restartable exact-compatible partial recovery, populated preservation и
  family-wide zero-mutation conflict preflight.
- Data-only importer и DDL-free read consumers.
- Независимость clean schema deployment от external source и personal data.

**Non-Goals:**

- Literal synthetic fixture contents и любой будущий отдельно утверждённый
  production cutover/import.
- Изменение six-field extraction, dictionary mapping, hashes или quarantine code.
- Проверка payload hash consumers, mutual exclusivity, reconciliation/retention.
- Добавление FK/CHECK/AI/indexes, JSON column conversion или storage redesign.

## Decisions

### 1. Deployment migration владеет schema, importer владеет только data ingest

Migration регистрируется после фактически landed release prerequisites; importer
до source read проверяет exact target schema precondition и больше не выполняет
DDL. Card/premium/OTIZ remain read adapters and cannot repair schema.

Альтернатива — оставить importer DDL — связывает fresh deployment с внешним
source access и выполняет schema mutation вне canonical runner.

### 2. Family-wide preflight разрешает exact partial recovery

Каждый member может быть absent или exact. Только после проверки всех existing
members создаются missing tables. Любая другая форма conflict до DDL. Это
восстанавливает запуск после interruption между двумя auto-committed CREATE без
destructive rollback.

### 3. Fingerprint exact, environment metadata нормализована

Сравниваются ordinal column types/null/default/generated state, PK/indexes,
constraints, engine, charset и validated exact database-default utf8mb4
collation. Collation явно emitted после allowlist/membership validation.
Auto-increment отсутствует. SHOW CREATE formatting не является oracle.

### 4. Existing data и ambiguous coexistence не repair-ятся

Migration не recompute-ит hashes/payload, не удаляет stale quarantine/detail и
не делает взаимоисключение. Ownership-first сохраняет evidence; content integrity
и reconciliation требуют отдельного behavior contract.

### 5. Full-catalogue process-prefix ceiling равен 25 bytes

Longest family basename — 34 bytes, собственная граница 30. Более строгий
25-byte composed-runner contract определяется release-supporting 39-byte
classification-provenance basename и полностью покрывает эту family. Production
runner отклоняет 26 bytes до DB connection/access; local 30-byte arithmetic не
становится поддерживаемой composed configuration.

### 6. Architecture ratchet удаляет importer-owned DDL debt

Guard запрещает family-targeted runtime/importer DDL. Baseline уменьшается только
на реально удалённые statements; rapid-pilot adapter может выполнять approved
ingest/characterization, но не schema repair или новую domain logic.

## Risks / Trade-offs

- [Existing incompatible tables остановят import] → explicit conflict и operator
  remediation вне runtime вместо silent acceptance.
- [Partial CREATE после interruption] → exact-compatible restart на следующем
  canonical run.
- [Approved synthetic contour без object-detail fixture блокирует premium
  operands] → явный fail-closed consumer outcome; отдельный approved
  `seed-test-user-fixtures`/preview contract решает population scope.
- [Stored payload/hash may already be inconsistent] → preserve rows; отдельный
  integrity characterization/hardening before relying on premium decisions.
- [Personal data accidentally enters test contour] → migration is data-free;
  any population requires separate approved provenance/privacy contract.

## Migration Plan

1. После landing predecessors зафиксировать exact version/catalogue и executable
   schema spec; получить owner approval.
2. Independent Gate 2 покрывает clean/repeat/both partial/conflicts/collation/
   prefix/preservation и DDL-denied importer; fresh reviewer approves RED.
3. Реализовать migration и register runner, затем заменить importer CREATE на
   exact fail-closed precondition.
4. Проверить source-free clean deployment, import characterization, consumers,
   architecture, fresh lifecycle и full verification.
5. Fresh Gate 5 reviewer подтверждает ownership, data preservation и отсутствие
   скрытого population/semantic redesign.

Rollback destructive `down()` не имеет. До deployment можно откатить code; после
применения schema version используется forward fix, а existing rows сохраняются.
