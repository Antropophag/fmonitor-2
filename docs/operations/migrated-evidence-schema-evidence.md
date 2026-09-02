# Migrated-evidence schema ownership evidence

This discovery records current schema ownership for the six-table migrated
history/reconciliation family. It is schema evidence only: it does not approve
legacy evidence as operational truth, reconciliation outcomes, OTIZ authority or
premium admission semantics.

## Family and current owners

| Table basename | Bytes | Current runtime owner |
|---|---:|---|
| `fm2_history_source_snapshots` | 28 | `LegacyHistoryMySqlTarget::createSchema` |
| `fm2_history_import_quarantine` | 29 | `LegacyHistoryMySqlTarget::createSchema` |
| `fm2_migrated_evidence_projection` | 32 | `MigratedEvidenceProjectionStore::ensureSchema` |
| `fm2_migrated_evidence_conflicts` | 31 | `MigratedEvidenceProjectionStore::ensureSchema` |
| `fm2_migrated_evidence_decision_state` | 36 | `MigratedEvidenceProjectionStore::ensureSchema` |
| `fm2_migrated_evidence_decisions` | 31 | `MigratedEvidenceDecisionLedger::ensureSchema` |

The longest basename is 36 bytes, so the already discovered full-catalogue
prefix ceiling of 28 bytes is sufficient and necessary at current catalogue
maximum; this slice cannot restore an older 29/32-byte ceiling.

Ownership is overlapping and request/tool reachable:

- legacy import creates source snapshots, quarantine, then invokes projection
  ownership for the three derived tables;
- decision-ledger construction creates decisions, then invokes projection
  ownership;
- OTIZ construction invokes decision-ledger ownership before CSRF/path checks;
- projection backfill invokes projection ownership before writes;
- read/profiling tools assume some or all tables already exist.

No table has a foreign key. Referential relationships are enforced only by
application queries and transactions. Schema creation uses successive MariaDB
DDL statements, hence interruption can leave any compatible partial family.

## Exact observed source manifests

All six source DDLs request InnoDB. Four explicitly state
`DEFAULT CHARSET=utf8mb4` without `COLLATE`; MariaDB selects the default
collation for that charset. Conflicts and decision-state omit charset/collation
and inherit the target database defaults, which source does not require to be
utf8mb4.

An isolated populated observation on MariaDB `11.4.7-MariaDB-ubu2404` used
database default `utf8mb4_unicode_ci`. The four explicit-utf8mb4 tables became
`utf8mb4_uca1400_ai_ci`; conflicts and decision-state became
`utf8mb4_unicode_ci`. Canonical planning must preserve this two-source policy:
validate the database character set/collation for inherited tables and the
MariaDB default collation for explicit utf8mb4, then emit both resolved values
explicitly. It must not silently normalize populated tables or freeze these
environment-specific observed names.

The observation confirmed no semantic column defaults except nullable
`decisions.target_locator DEFAULT NULL`; three ids are `AUTO_INCREMENT`, every
other `EXTRA` is empty. All indexes are full-column ascending visible BTREE;
uniqueness and ordered columns are semantic while names are presentation.
There are no FKs. Only `projection.conflict_codes_json JSON` expands to
`LONGTEXT` with `utf8mb4_bin` and semantic CHECK
`json_valid(conflict_codes_json)`; other `*_json` columns are plain LONGTEXT.
After inserting one literal row per table, all rows remained readable and the
three next auto-increment values were `2`; non-auto tables had no counter. The
private observation namespace was then removed.

### `fm2_history_source_snapshots`

Ordered columns: `id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT`,
`legacy_object_id BIGINT UNSIGNED NOT NULL`, `source_system VARCHAR(40) NOT NULL`,
`source_locator VARCHAR(160) NOT NULL`, `cutoff_at DATETIME NOT NULL`,
`extractor_version VARCHAR(80) NOT NULL`, `content_sha256 CHAR(64) NOT NULL`,
`payload_json LONGTEXT NOT NULL`, `created_at DATETIME NOT NULL`.

Indexes: primary `id`; unique `uq_content(content_sha256)`; secondary
`object_id(legacy_object_id)`.

### `fm2_history_import_quarantine`

Ordered columns: `id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT`,
`snapshot_id BIGINT UNSIGNED NOT NULL`, `issue_no INT UNSIGNED NOT NULL`,
`code VARCHAR(80) NOT NULL`, `diagnostic_json LONGTEXT NOT NULL`.

Indexes: primary `id`; unique `uq_issue(snapshot_id,issue_no)`.

### `fm2_migrated_evidence_projection`

Ordered columns: `snapshot_id BIGINT UNSIGNED NOT NULL`,
`legacy_object_id BIGINT UNSIGNED NOT NULL`,
`projection_version VARCHAR(80) NOT NULL`, `input_sha256 CHAR(64) NOT NULL`,
`projection_sha256 CHAR(64) NOT NULL`, `classification VARCHAR(40) NOT NULL`,
`evidence_grade CHAR(1) NOT NULL`, `confidence VARCHAR(20) NOT NULL`,
`quarantine_count INT UNSIGNED NOT NULL`, `conflict_codes_json JSON NOT NULL`,
`conflict_search VARCHAR(2000) NOT NULL`, `payload_json LONGTEXT NOT NULL`,
`projected_at DATETIME NOT NULL`.

Indexes: primary `(snapshot_id,legacy_object_id)`; secondary
`ix_filter(classification,evidence_grade,quarantine_count,legacy_object_id)` and
`ix_object(legacy_object_id,snapshot_id)`.

### `fm2_migrated_evidence_conflicts`

Ordered columns: `snapshot_id BIGINT UNSIGNED NOT NULL`,
`issue_code VARCHAR(80) NOT NULL`. Indexes: primary `(snapshot_id,issue_code)`;
secondary `ix_code(issue_code,snapshot_id)`.

### `fm2_migrated_evidence_decision_state`

Ordered columns: `snapshot_id BIGINT UNSIGNED NOT NULL`,
`issue_code VARCHAR(80) NOT NULL`, `decision_id BIGINT UNSIGNED NOT NULL`,
`outcome VARCHAR(40) NOT NULL`. Indexes: primary `(snapshot_id,issue_code)`;
secondary `ix_outcome(outcome,snapshot_id)`.

### `fm2_migrated_evidence_decisions`

Ordered columns: `id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT`,
`operation_id CHAR(36) NOT NULL`, `request_sha256 CHAR(64) NOT NULL`,
`snapshot_id BIGINT UNSIGNED NOT NULL`, `snapshot_sha256 CHAR(64) NOT NULL`,
`projection_sha256 CHAR(64) NOT NULL`, `source_locator VARCHAR(500) NOT NULL`,
`issue_code VARCHAR(80) NOT NULL`, `outcome VARCHAR(40) NOT NULL`,
`target_locator VARCHAR(500) NULL`, `reason VARCHAR(1000) NOT NULL`,
`actor_user_id BIGINT UNSIGNED NOT NULL`, `occurred_at VARCHAR(40) NOT NULL`.

Indexes: primary `id`; unique `uq_operation(operation_id)`; secondary
`ix_snapshot_issue(snapshot_id,issue_code,id)` and
`ix_actor(actor_user_id,id)`.

## Data ownership and mutation boundaries

- Source snapshots are content-addressed by unique hash. Import uses
  `INSERT IGNORE`, then looks up by hash; identical content reuses the id.
- Quarantine rows are append/ignore keyed by `(snapshot_id,issue_no)`.
- Projection is a replaceable derived row. Upsert preserves `projected_at` when
  projection hash is unchanged, but overwrites other projection columns.
- Conflict rows for one snapshot are deleted and reinserted by projection.
- Decisions are append-only with operation-id replay detection.
- Decision state is a mutable latest-decision index. Normal decision recording
  upserts one key; `rebuildDecisionState()` deletes the entire table content and
  rebuilds it from decisions. This is data rebuilding, not schema migration.

Canonical schema ownership MUST preserve populated rows and must not run
projection/backfill/state rebuild as part of migration. DDL ownership and data
population/reconciliation are separate operations.

## Consumers and behavioral evidence

- `MigratedEvidenceReconciliation` reads source/quarantine and computes derived
  payload/hash/conflicts.
- `MigratedEvidenceProjectionStore` owns projection persistence, paging,
  conflict filters and latest decision state.
- `MigratedEvidenceDecisionLedger` reads projection, authorizes broad
  `otiz.manage`, appends decisions and updates latest state.
- `import-legacy-history.php` and `batch-import-legacy-history.php` invoke the
  ownership chain while importing source/quarantine/projection data.
- `backfill-migrated-evidence-projection.php` dry-run reads source snapshots;
  apply calls DDL ownership, backfills projection/conflicts and rebuilds the
  entire decision state.
- `HistoricalPremiumReplayReadModel` and OTIZ historical/reconciliation screens
  consume source/projection/conflicts/decisions.
- workforce/checklist profiling reads latest source snapshots; projection
  optionally enriches from `fm2_workforce_catalog` when present.
- decision recording depends behaviorally on identity/access tables via
  `AccessPolicy` and broad `otiz.manage`; they are not physical FK/schema
  prerequisites and this family does not create them.

Backfill is not one atomic data transaction: `ensureSchema()` issues MariaDB
`CREATE TABLE IF NOT EXISTS`, whose implicit commit breaks the caller's earlier
transaction. Later projection writes and decision-state delete/reinsert can be
interrupted separately. Canonical migration must remove DDL from this path;
data-backfill restartability/atomicity remains a separate hardening concern.

Existing focused verifiers cover legacy import, projection/reconciliation and
append-only decision behavior, but frequently create simplified prerequisite
tables. They do not prove exact six-table fingerprints, all 64 compatible
present/absent family combinations, family-wide zero-mutation conflicts,
split collation portability, populated/auto-increment preservation, canonical
runner sequencing or DDL-denied runtime behavior.

## Canonical ownership implications

A future ownership-only change should:

1. register one additive family migration only after its real landed
   predecessors; version remains symbolic until then;
2. preflight the entire six-table family before the first DDL;
3. exercise exactly all `2^6 = 64` compatible states, where each member is
   independently absent or exact-compatible; create only missing members and
   preserve every populated row, auto-increment next value and ambient decoy;
4. exercise separate incompatible cases for each of six member positions and
   representative column/index/check/engine/collation conflicts; preflight must
   leave every other schema/row/counter, migration ledger and decoy unchanged;
5. recover from every exact-compatible partial state because MariaDB DDL commits
   per statement;
6. emit the validated split inherited-database/default-utf8mb4 collations and
   exact JSON CHECK/index semantics without relying on presentation names;
7. remove `CREATE TABLE` from legacy importer, projection store and decision
   ledger only after canonical clean/repeat/partial/conflict tests are green;
8. replace runtime repair with an exact fail-closed schema precondition before
   source reads or business/data transactions;
9. ratchet six runtime-DDL owners without adding architecture exceptions;
10. keep import, projection backfill, decision-state rebuild and any destructive
    cleanup explicitly outside migration execution.

## Classification and blockers

This is migration-control schema, ordered behind release-critical operational
families unless premium preview/fixture cutover makes it necessary. Exact table
ownership discovery is READY and does not require approval of migrated evidence
semantics. Implementation remains `BLOCKED_PREDECESSORS`; literal migration
version/catalogue must be refreshed after preceding families land.

Separate unresolved behavior remains outside this ownership slice: whether
migrated evidence is admitted to target premium decisions, exact reconciliation
authority/outcomes, corrections, retention, source cutover and privacy policy.
