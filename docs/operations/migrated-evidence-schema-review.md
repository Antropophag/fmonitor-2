# Independent migrated-evidence schema evidence review

Reviewed: 2026-09-01  
Artifact: `docs/operations/migrated-evidence-schema-evidence.md`  
Reviewer: fresh independent agent `migrated_evidence_schema_review_0901p`  
Verdict: **CHANGES_REQUESTED**

## Sources inspected

- `AGENTS.md`, `PRODUCT.md`, `CONTEXT.md`, and
  `docs/development-process.md`
- `docs/operations/runtime-ddl-migration-plan.md`
- `rapid-pilot/legacy-migration/LegacyHistoryMigration.php`
- `rapid-pilot/legacy-migration/MigratedEvidenceProjectionStore.php`
- `rapid-pilot/legacy-migration/MigratedEvidenceDecisionLedger.php`
- the import, batch-import and projection-backfill entry points
- OTIZ, historical-premium, reconciliation, projection and decision-ledger
  consumers/verifiers found by exact table/class reference
- existing canonical migration fingerprint conventions in
  `tests/InstallationProcess/production_migration_runner_001_test.php` and
  `tests/InstallationProcess/bitrix_workforce_schema_001_test.php`

## Findings

### 1. MariaDB semantic fingerprint is incomplete

The six source manifests correctly transcribe the declared columns and index
column order, but they are not yet exact canonical-schema evidence. They omit
column defaults, `EXTRA`, character set/collation per textual column, index
uniqueness/type/full-vs-prefix length/direction/visibility, CHECK constraints,
and observed server expansion of `JSON`. In particular,
`fm2_migrated_evidence_projection.conflict_codes_json JSON NOT NULL` is a
MariaDB JSON alias whose binary collation and `json_valid` CHECK are semantic
fingerprint fields under the repository's established convention. A generated
constraint name may be presentation-only, but the expression cannot be
omitted. Capture an isolated MariaDB observation (including populated
preservation/auto-increment state) or otherwise provide equivalent authoritative
metadata before OpenSpec derives a compatibility contract.

### 2. The charset claim is internally overbroad

“All six are ... utf8mb4” is not guaranteed by the owning source. The conflicts
and decision-state DDL omit both `DEFAULT CHARSET` and `COLLATE`, so they inherit
the target database defaults and can be non-utf8mb4 on a differently configured
database. The later sentence correctly acknowledges inheritance. State the
source fact conditionally, and distinguish the observed configured database
from a future canonical requirement to validate utf8mb4 and its exact default
collation.

### 3. Backfill atomicity is stated incorrectly

The consumer section says apply rebuilds projections and the entire decision
state “transactionally after DDL”. The script starts a transaction and then
`backfill()` calls `ensureSchema()`. MariaDB `CREATE TABLE`, including
`CREATE TABLE IF NOT EXISTS`, implicitly commits, so the caller's transaction
cannot cover the later projection writes and full state delete/reinsert as one
transaction merely because a final `commit()` is issued. Record the actual
implicit-commit boundary and treat interruption during data rebuild separately
from the 64 schema-presence combinations.

### 4. Consumer and prerequisite inventory is too compressed

Name the direct entry points and consumers that constrain cutover:
`import-legacy-history.php`, `batch-import-legacy-history.php`,
`backfill-migrated-evidence-projection.php`,
`MigratedEvidenceReconciliation`, `HistoricalPremiumReplayReadModel`, and
`Otiz`, plus the decision ledger's identity/access tables used by
`AccessPolicy`. The family has no physical FK predecessor, but behavior has
optional workforce enrichment and authorization/read dependencies. This
distinction is needed to justify both canonical runner ordering and the claim
that the slice remains behind release-critical families.

### 5. Partial-state and zero-mutation evidence needs an explicit matrix oracle

The document correctly derives `2^6 = 64` compatible present/absent family
combinations, the 28-byte ceiling (`64 - 36`), deterministic owner-specific DDL
orders, and family-wide preflight intent. It should additionally specify that
each of the 64 cases contains only absent or exact-compatible members, while
every incompatible-member case must be tested separately with all other family
schema, rows, auto-increment values and ambient decoys unchanged. This prevents
the phrase “all 64 ... combinations” from being misread as covering conflict
placement or populated preservation.

## Confirmed accurate evidence

- The family contains exactly the six named tables and the stated runtime
  owners.
- Declared column order, source types/nullability, primary/secondary key column
  order, absence of FKs, and owner-specific creation order match the source.
- The longest basename is 36 ASCII bytes, making 28 bytes the maximum family
  prefix under MariaDB's 64-byte identifier limit.
- Source/quarantine, replaceable projection/conflicts, append-only decisions,
  and mutable decision-state data ownership are correctly separated.
- Migration execution must not perform import, projection backfill, decision
  state rebuild, cleanup, or approve target premium/reconciliation semantics.

The evidence can advance to OpenSpec after the five findings above are
resolved and a fresh independent rereview returns `READY_FOR_OPENSPEC`.
