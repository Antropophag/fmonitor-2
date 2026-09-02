# Object-detail snapshot/quarantine schema ownership evidence

Evidence captured: 2026-09-01. This is derived evidence about the current
rapid-pilot implementation and an isolated MariaDB observation. It is not an
approved executable specification, migration contract, object-card product
decision, or authorization decision.

## Scope and sources

The family consists of exactly two process-prefixed tables:

1. `fm2_pilot_object_details`
2. `fm2_pilot_object_detail_quarantine`

The current production DDL owner is the top-level apply path in
`rapid-pilot/import-production-object-details.php`. Supporting call and consumer
evidence was read from:

- `rapid-pilot/initialize-native-only.php`, which invokes the importer with
  `--apply` after native installation cases have been imported;
- `Makefile` target `import-production`, which invokes the initializer inside
  the Compose pilot image;
- `rapid-pilot/README.md`, which intends a direct manual importer invocation
  after bootstrap, but its literal command omits required `--captured-at` and
  `--apply` and currently fails `CAPTURED_AT_INVALID` before DB access;
- `rapid-pilot/ObjectDetails.php`, which reads detail payload and capture time
  for the object card and fails closed when the projection is absent;
- `rapid-pilot/NativeOperationalPremiumInputs.php`, which reads the snapshot as
  evidence for premium operands;
- `rapid-pilot/Otiz.php`, which joins the snapshot into its pilot projection;
- `rapid-pilot/verify-object-detail-projection-import.php`, the current static
  importer characterization;
- native operational/OTIZ verifiers, which use reduced hand-built consumer
  fixtures rather than the authoritative importer schema;
- `docs/operations/runtime-ddl-migration-plan.md`.

No other production source creates or alters these two exact tables. The two
statements are recorded as baseline runtime-DDL debt by the architecture
ratchet.

## Current owner and execution path

The importer first validates arguments and the local-pilot apply guard, reads
the active manifest, connects to the guarded target generation, and starts a
read-only consistent snapshot against the external legacy source. It derives
technical fields for all canonical installation-case legacy ids and commits the
source snapshot. Dry-run exits at that point and performs no target DDL or data
writes.

Only `--apply` then executes these two consecutive statements, in detail then
quarantine order:

```text
CREATE TABLE IF NOT EXISTS {prefix}fm2_pilot_object_details (...)
CREATE TABLE IF NOT EXISTS {prefix}fm2_pilot_object_detail_quarantine (...)
```

They run before `begin_transaction()`. MariaDB DDL commits independently, so
the following data transaction cannot roll either statement back. After both
statements, the importer rechecks the active-generation sentinel under lock and
inserts immutable-by-hash detail or quarantine rows. Exact hash replay is
reported as already present; a different hash for the same `object_id` rejects
with `DETAIL_PROJECTION_CONFLICT` or `DETAIL_QUARANTINE_CONFLICT`.

`initialize-native-only.php` is the only PHP call site that executes the
importer. `make import-production` reaches it indirectly; the rapid-pilot README
also intends a direct operator surface, although its current literal example is
non-executable and does not reach DDL. The initializer calls
it after checklist-template capture and native-case import, and the importer
reads the complete external-source snapshot before current apply creates the
tables. These are execution-order facts, not schema dependencies: the empty
two-table schema refers to no case/source row and must be available before the
initializer, direct importer and read consumers run.

The importer therefore currently combines migration ownership, external-source
capture and data ingestion in one process. Moving DDL ownership must not change
the capture/hash/idempotency behavior without a separately approved behavior
slice.

## Exact source manifests

All columns below are in declaration order. Every column is `NOT NULL`, has no
explicit default, has no generated expression and has no `EXTRA` metadata.

### `${prefix}fm2_pilot_object_details`

| # | Column | Source type | Nullable | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `object_id` | `BIGINT UNSIGNED` | no | none | primary key |
| 2 | `schema_version` | `VARCHAR(80)` | no | none | none |
| 3 | `content_sha256` | `CHAR(64)` | no | none | none |
| 4 | `payload_json` | `LONGTEXT` | no | none | none |
| 5 | `captured_at` | `VARCHAR(40)` | no | none | none |

Its only index is visible unique BTREE `PRIMARY (object_id)`. There are no
secondary indexes, foreign keys, CHECK constraints, generated columns, JSON
type constraints, or auto-increment column. In particular, `payload_json` is
plain `LONGTEXT`; current DDL does not ask MariaDB to validate JSON.

### `${prefix}fm2_pilot_object_detail_quarantine`

| # | Column | Source type | Nullable | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `object_id` | `BIGINT UNSIGNED` | no | none | primary key |
| 2 | `code` | `VARCHAR(80)` | no | none | none |
| 3 | `schema_version` | `VARCHAR(80)` | no | none | none |
| 4 | `content_sha256` | `CHAR(64)` | no | none | none |
| 5 | `captured_at` | `VARCHAR(40)` | no | none | none |

Its only index is visible unique BTREE `PRIMARY (object_id)`. It likewise has
no secondary indexes, FKs, CHECKs, generated columns or auto increment.

Both source statements specify `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` and omit
an explicit `COLLATE`; MariaDB therefore chooses the default collation for the
explicit charset, not necessarily the target database's current collation.

## MariaDB 11.4.7 observation

Observation environment:

- server/image: `11.4.7-MariaDB-ubu2404`;
- database charset/collation: `utf8mb4` / `utf8mb4_uca1400_ai_ci`;
- isolated prefix: `odsev1_`;
- exact source DDL was executed manually because the full importer deliberately
  accepts only a guarded `fmonitor2_demo` endpoint and also requires an external
  legacy source; neither guard was bypassed and no legacy/source data was read;
- no production, shared-prefix, or external-source table was touched.

MariaDB rendered `BIGINT UNSIGNED` as `bigint(20) unsigned`. Both tables used
InnoDB and resolved the default collation for explicit charset `utf8mb4` to
`utf8mb4_uca1400_ai_ci`. All varchar/char/longtext columns used that resolved
charset-default collation; the numeric primary key had
no character metadata. `information_schema.STATISTICS` reported the primary
indexes as ascending visible BTREE (`IGNORED=NO`) with no prefix length. There
were no other constraints or generation expressions.

On a clean family both tables had zero rows and `AUTO_INCREMENT=NULL`. Exact
`SHOW CREATE TABLE` hashes including the isolated physical name were:

| Physical table | Clean SHA-256 |
|---|---|
| `odsev1_fm2_pilot_object_details` | `7f4ff84a76cef1fb731ddcf7d2e67650cf151b6f683ffb38cf43a65209ef2aa2` |
| `odsev1_fm2_pilot_object_detail_quarantine` | `229b33c5e0d3e366a82e2bb6b3547fe2160927df01150212cccd5527bb7c99d8` |

After inserting one derived sentinel row into each table, each had one row,
the details id was `7001`, the quarantine id was `7002`, and
`AUTO_INCREMENT` remained NULL. Since neither table has an AI option, its
`SHOW CREATE` representation remained structurally unchanged. No source or
person-identifying values were used.

Reproducibility hashes, with only the runtime prefix normalized to `{prefix}`:

| Table | Source-literal SHA-256 | Normalized SHOW CREATE SHA-256 |
|---|---|---|
| details | `e1b49b9acc4b38c82b4dfc0088dc6858da7a65d1c6286c7c42f9e907b54e8f04` | `6c29fe73aced48ce62dff783ecc2c4aa8a0a7109d659b4dd901fe55e8f14a8b7` |
| quarantine | `2321b5d0273fc5f3897b9b15910ef4e755068a6d3ca93476c20239566de8bf82` | `93a4a532e358070b354949506327c8b80df939967cb9401fa82824114e889c93` |

The observed collation is the server/environment default for explicitly named
`utf8mb4`; it was not inherited from the database merely because the DDL omitted
`COLLATE`. The hashes are evidence, not yet an approved portable compatibility
fingerprint. Optimizer cardinality and
`TABLE_ROWS` estimates must not be fingerprint fields.

## Data state and preservation behavior

The current importer writes one of two mutually intended outcomes per active
legacy object id:

- a details row contains schema version `technical-object-detail-v1`, a SHA-256
  of the canonical material, the JSON projection including capture metadata,
  and the requested canonical `captured_at` value;
- a missing legacy source row produces quarantine code
  `SOURCE_OBJECT_NOT_FOUND`, the same schema version, a deterministic SHA-256
  over the missing-object material, and capture time.

The database does not enforce cross-table exclusivity. The same `object_id`
can physically exist in both tables, and neither table references
`fm2_installation_cases`. Current application flow normally classifies an id
into one collection before writing, but the schema alone does not prove that
fact. Adding an FK, cross-table exclusion, JSON validation, status lifecycle or
new quarantine codes would be a behavior/schema redesign and is outside this
ownership evidence.

For an existing row, the importer compares only `content_sha256`; exact hash
replay preserves the row without comparing `schema_version`, JSON bytes or
`captured_at`. This is current behavior, not proof that hash-only compatibility
is sufficient for a canonical schema or future data audit.

## Prefix and identifier boundary

`WorkforceCatalogReconciliationCandidate::assertGeneration()` requires a
non-empty prefix matching `[A-Za-z0-9_]+`, but imposes no length ceiling. The
importer interpolates that validated prefix directly into identifiers.

MariaDB table identifiers are limited to 64 bytes. The accepted prefix alphabet
is ASCII, so bytes and characters coincide:

| Basename | Bytes | Maximum prefix |
|---|---:|---:|
| `fm2_pilot_object_details` | 24 | 40 |
| `fm2_pilot_object_detail_quarantine` | 34 | 30 |

This family alone therefore requires a prefix ceiling of **30 bytes**. The
repository-wide ceiling now discovered from classification provenance is
25 bytes and is sufficient here. A 26-byte or longer prefix must already be
rejected catalogue-wide; a 31- or 32-byte prefix accepted by the
current generation guard cannot form the quarantine table name and would fail
at MariaDB DDL. Canonical runner validation must reject an overlong prefix
before connection/schema inspection or mutation.

No source-owned secondary, FK or CHECK symbol creates an additional identifier
limit.

## Partial, interrupted and incompatible states

Because the two DDL statements commit independently and occur before the data
transaction, all of these physical states are relevant:

| Existing family | Current apply outcome | Risk / preservation fact |
|---|---|---|
| neither table | creates details, then quarantine, then starts ingestion transaction | clean path |
| exact details only | preserves details and creates quarantine | compatible source-order recovery |
| exact quarantine only | creates details and preserves quarantine | reverse partial recovery |
| both exact, empty or populated | `IF NOT EXISTS` performs no structural or row rewrite before ingestion | existing rows are then governed only by hash replay/conflict logic |
| incompatible details, quarantine absent | silently skips details and creates quarantine | mutates the family before a later detail query/write exposes incompatibility |
| details absent, incompatible quarantine | creates details, silently skips quarantine | leaves a newly created member beside an incompatible member |
| either incompatible table already present | no column/index/engine/collation fingerprint is checked | failure may be deferred until prepared statements or inserts |
| first CREATE commits and second fails/process stops | details remains committed | application rollback cannot restore the absent family |

Even an incompatible shape that happens to support the importer's narrow
queries can be silently accepted, so a successful run is not compatibility
evidence. Conversely, current `IF NOT EXISTS` can safely fill either exact
single-table partial form, but it does not distinguish that form from conflict.

The canonical additive migration must preflight the entire family before its
first DDL. It may fill only absent members of an explicitly compatible partial
family; it must preserve every row and non-structural state in exact compatible
members; and an incompatible member must produce a deterministic conflict with
zero schema or row mutation. Recovery must never drop or rebuild populated
tables implicitly.

## Canonical ownership target, without storage redesign

An ownership-only target should:

- register a sequential canonical migration after its landed schema
  predecessors but before initializer, importer or read consumers; no imported
  installation-case/source row is a schema prerequisite;
- reproduce or explicitly approve the two manifests above on fresh MariaDB;
- explicitly validate the selected default collation for charset `utf8mb4` and
  emit it rather than rely on a potentially different environment default;
- inspect both names before mutation and handle clean, exact present, exact
  partial and incompatible present states deterministically;
- preserve populated rows, object ids, schema versions, hashes, JSON bytes and
  capture times;
- remove both `CREATE TABLE IF NOT EXISTS` calls from the importer and make
  missing/incompatible schema an environment/deployment failure;
- keep importer dry-run free of target DDL and writes;
- preserve generation guarding, consistent source snapshot, canonical capture
  validation, deterministic hashes, repeat/conflict outcomes and fail-closed
  consumers unless separately changed through approved behavior specs;
- avoid adding FKs, JSON CHECKs, cross-table exclusivity, lifecycle fields or
  other persistence redesign merely while moving ownership.

The current static verifier proves selected source tokens and fail-closed UI
intent, but does not execute clean/repeat/conflict/partial DB cases. A future
Gate 1 schema spec and RED must cover those cases under a DDL-denied importer
principal, followed by independent test review, minimal GREEN and independent
code review.

## Cleanup evidence

Both exact `odsev1_` tables were dropped after clean and populated observation.
An `information_schema.TABLES` query for escaped prefix `odsev1_%` returned an
empty list. The disposable Compose test database, network and tmpfs volume were
then removed with `make test-env-down`.
