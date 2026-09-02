# Migration-quarantine schema ownership evidence

This discovery records current ownership of the three-table migration
quarantine family. It is schema evidence only: it does not approve quarantine
classification, decision outcomes, migrated data, financial semantics or
production release scope. The family remains migration-control/PILOT_ONLY.

## Family and runtime owners

| Table basename | Bytes | Current runtime owner |
|---|---:|---|
| `fm2_migration_quarantine_registry` | 33 | `MigrationQuarantineRegistry::record` |
| `fm2_migration_quarantine_observations` | 37 | `MigrationQuarantineRegistry::record` |
| `fm2_migration_quarantine_decisions` | 34 | `MigrationQuarantineDecisionLedger::ensureSchema` |

The 37-byte observations basename is longer than the current full-catalogue
maximum recorded by earlier discovery. A MariaDB identifier is at most 64
bytes, so this family reduces the safe process-table prefix ceiling to **27
bytes**. Canonical migration preflight and runtime schema preconditions must
enforce the catalogue-wide ceiling, not retain the earlier 28-byte value.

Registry and observations are created together, lazily, only after `record()`
has validated an input. Decisions are created separately. `Otiz` constructs the
decision ledger and calls `ensureSchema()` before request-path CSRF and command
validation. Batch registration reaches registry creation while importing
classified legacy rows. The read model creates nothing and returns an empty
projection when registry or observations is absent.

There are no foreign keys. `observations.registry_id` and every decision
reference are application-enforced relationships. Three successive MariaDB DDL
statements can commit independently, so interruption can leave any compatible
partial family.

## Exact observed source manifests

All three DDLs request InnoDB and `DEFAULT CHARSET=utf8mb4` without a collation.
An isolated populated observation on MariaDB `11.4.7-MariaDB-ubu2404`, whose
database default was `utf8mb4_unicode_ci`, resolved every family table to
`utf8mb4_uca1400_ai_ci`. Canonical planning must validate the environment's
default collation for explicit utf8mb4 and emit the resolved value explicitly;
the observed name is evidence, not a portable constant.

Every column is NOT NULL and has no semantic default. Each `id` is
`AUTO_INCREMENT`; other `EXTRA` values are empty. All indexes are visible,
full-column, ascending BTREE indexes. There are no CHECK constraints or JSON
column types: both JSON-bearing registry fields are plain `TEXT`, and JSON
validity is application-owned. One literal row was inserted into each table;
all remained readable and every next auto-increment value was `2`. The private
observation namespace and disposable database volume were then removed.

### `fm2_migration_quarantine_registry`

Ordered columns: `id BIGINT UNSIGNED AUTO_INCREMENT`,
`source_system VARCHAR(40)`, `source_locator VARCHAR(80)`,
`source_cutoff_at DATETIME`, `source_digest CHAR(64)`,
`classification_version VARCHAR(80)`, `category VARCHAR(40)`,
`reason_codes_json TEXT`, `quarantine_codes_json TEXT`,
`first_seen_at VARCHAR(40)`.

Indexes: primary `id`; unique `uq_digest(source_digest)`; unique
`uq_locator_cutoff(source_locator,source_cutoff_at)`.

### `fm2_migration_quarantine_observations`

Ordered columns: `id BIGINT UNSIGNED AUTO_INCREMENT`,
`registry_id BIGINT UNSIGNED`, `run_id CHAR(36)`, `observed_at VARCHAR(40)`.

Indexes: primary `id`; unique `uq_registry_run(registry_id,run_id)`; secondary
`run_id(run_id)`.

### `fm2_migration_quarantine_decisions`

Ordered columns: `id BIGINT UNSIGNED AUTO_INCREMENT`, `operation_id CHAR(36)`,
`request_sha256 CHAR(64)`, `source_locator VARCHAR(80)`,
`source_cutoff_at DATETIME`, `source_digest CHAR(64)`,
`classification_version VARCHAR(80)`, `quarantine_code VARCHAR(100)`,
`outcome VARCHAR(40)`, `reason VARCHAR(1000)`,
`actor_user_id BIGINT UNSIGNED`, `occurred_at VARCHAR(40)`.

Indexes: primary `id`; unique `uq_operation(operation_id)`; secondary
`ix_reference(source_locator,source_cutoff_at,source_digest,classification_version,quarantine_code,id)`.

## Data ownership and behavioral dependencies

- Registry identity is simultaneously constrained by unique source digest and
  unique `(source_locator, source_cutoff_at)`. Replay with mismatched digest or
  classification payload fails `QUARANTINE_REGISTRY_CONFLICT`.
- Observations are append/ignore by `(registry_id, run_id)`; a replay with a
  different timestamp fails `QUARANTINE_OBSERVATION_CONFLICT`.
- Decisions are append-only and operation-id idempotent. A changed replay fails
  `QUARANTINE_OPERATION_CONFLICT`.
- A decision locks the registry reference, then verifies digest, classification
  version and inclusion of the selected quarantine code. It depends on pilot
  identity/access tables and broad `otiz.manage` for authorization, but those
  tables are behavioral prerequisites, not physical members or FKs.
- The read model joins registry and observations, filters category/code,
  produces redacted references and bounded pages. OTIZ reads and appends
  decisions; native-only generation verification counts the decision table.

Existing focused verifiers cover bounded/redacted source registration, read
projection and decision behavior through OTIZ. They do not prove the exact
three-table fingerprint, all eight compatible present/absent combinations,
family-wide zero-mutation conflicts, populated counter preservation, canonical
runner ordering or DDL-denied runtime behavior.

## Canonical ownership implications

A future ownership-only change should:

1. remain behind its real landed migration predecessors; keep the literal
   version symbolic until those predecessors land;
2. preflight all three tables before the first DDL and exercise exactly all
   `2^3 = 8` compatible present/absent states;
3. create only missing members while preserving every row, next
   auto-increment value and ambient decoy;
4. test separate incompatible cases for each member and representative
   column/index/engine/collation conflicts, proving zero mutation of the family,
   migration ledger, counters and decoys;
5. recover safely from each compatible partial family produced by implicit DDL
   commits;
6. preserve plain-TEXT JSON fields, uniqueness and ordered index semantics,
   without treating index names as semantic;
7. enforce the resulting catalogue-wide 27-byte prefix ceiling;
8. remove DDL from registry recording and decision-ledger construction only
   after clean/repeat/partial/conflict tests are green;
9. replace runtime repair with an exact fail-closed schema precondition before
   source reads or business/data transactions;
10. keep registration, classification, decisions, reconciliation, cleanup and
    any destructive data operation outside migration execution.

## Classification and blockers

Ownership discovery is READY for independent evidence review. Implementation is
`BLOCKED_PREDECESSORS`: this migration-control family follows release-critical
operational families and migrated-evidence schema in the current order. Exact
migration version/catalogue state must be refreshed after predecessors land.

Quarantine taxonomy, allowed decision outcomes, correction/retention policy,
source cutover and any financial use remain separate unresolved behavior. This
ownership slice must not assert them or add domain logic to `rapid-pilot/`.
