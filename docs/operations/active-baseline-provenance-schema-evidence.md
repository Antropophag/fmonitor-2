# Active-baseline and classification-provenance schema evidence

This discovery tests the three-table family asserted by the runtime-DDL plan.
It records current ownership and dependencies only; it does not approve legacy
cutover, classification semantics, active-case fabrication, fixture content or
admission of legacy evidence to target behavior.

## The inventoried family is not one deployment unit

| Table basename | Bytes | Runtime owner | Actual reach |
|---|---:|---|---|
| `fm2_migration_classification_provenance` | 39 | `MigrationClassificationProvenanceTarget::reconcile` | native operational imports, historical snapshots, queue/origin/native-only/OTIZ consumers |
| `fm2_legacy_active_baselines` | 27 | `LegacyActiveBaselineTarget::apply` | legacy-active cutover/read/template binding only |
| `fm2_active_case_provenance` | 26 | `ActiveBaselineOperationalCaseConnector::ensureSchema` | legacy-active baseline connection into canonical cases only |

The 39-byte classification-provenance basename lowers the full production
catalogue table-prefix ceiling from the previously discovered 27 bytes to **25
bytes**. Earlier 27/28/29/32-byte drafts cannot be approved unchanged.

`fm2_migration_classification_provenance` is not optional active-baseline
storage. Native candidate import writes an `operational_case` proof after
creating a canonical case; historical import writes `historical_snapshot`.
`ObjectQueue`, `PilotHttp`, native-only generation verification, template
linking, native OTIZ input selection and profiling read it. A fresh native-only
TEST-USER contour therefore requires this table even when legacy-active and
historical routes are excluded.

The other two tables form an optional legacy-active cutover subfamily:
baseline capture is the content-addressed source snapshot; case provenance is
written only while connecting a ready baseline/template to a canonical process
case. They depend on checklist-template snapshots/associations and canonical
process tables. Classification provenance has broader/lower-order dependencies
and must be canonical before native import.

The runtime plan's single `canonicalize-active-baseline-provenance-schema`
change would either delay a release-required native proof behind an optional
cutover decision or promote legacy-active tables without need. Planning SHOULD
split it into:

1. release-supporting `canonicalize-classification-provenance-schema`;
2. optional `canonicalize-active-baseline-provenance-schema` containing only
   baseline plus active-case provenance, if GRILL-004/cutover selects it.

## Exact observed manifests

All three source DDLs request InnoDB and `DEFAULT CHARSET=utf8mb4` without
explicit collation. An isolated populated observation on MariaDB
`11.4.7-MariaDB-ubu2404`, with database default `utf8mb4_unicode_ci`, resolved
all three to `utf8mb4_uca1400_ai_ci`. Canonical migrations must preflight the
environment default for explicit utf8mb4 and emit the approved resolved value;
the observed name is not portable approval.

There are no FKs or CHECK constraints. Every column is NOT NULL with no
semantic default. Indexes are visible full-column ascending BTREE. Index
semantics are contractual; presentation names are not. Literal rows remained
readable. The two AUTO_INCREMENT tables advanced their next counters to `2`;
active-case provenance has no counter. The private observation namespace and
disposable DB volume were removed.

### `fm2_migration_classification_provenance`

Ordered columns: `id BIGINT UNSIGNED AUTO_INCREMENT`,
`output_kind VARCHAR(40)`, `legacy_object_id BIGINT UNSIGNED`,
`output_id BIGINT UNSIGNED`, `source_cutoff_at DATETIME`,
`classification_version VARCHAR(80)`, `category VARCHAR(40)`,
`reason_codes_json TEXT`, `classification_sha256 CHAR(64)`,
`created_at DATETIME`.

Indexes: primary `id`; unique `(output_kind,output_id)`; secondary
`(legacy_object_id)`. `reason_codes_json` is plain TEXT without JSON CHECK.

### `fm2_legacy_active_baselines`

Ordered columns: `id BIGINT UNSIGNED AUTO_INCREMENT`,
`legacy_object_id BIGINT UNSIGNED`, `contract_version VARCHAR(80)`,
`cutover_at DATETIME`, `content_sha256 CHAR(64)`, `payload_json LONGTEXT`,
`created_at DATETIME`.

Indexes: primary `id`; unique `(legacy_object_id)`; unique `(content_sha256)`.
`payload_json` is plain LONGTEXT without JSON CHECK.

### `fm2_active_case_provenance`

Ordered columns: `installation_case_id BIGINT UNSIGNED`,
`legacy_object_id BIGINT UNSIGNED`, `baseline_id BIGINT UNSIGNED`,
`baseline_sha256 CHAR(64)`, `cutover_at DATETIME`,
`template_snapshot_id BIGINT UNSIGNED`,
`template_snapshot_version VARCHAR(80)`,
`template_content_sha256 CHAR(64)`, `connector_version VARCHAR(80)`,
`created_at VARCHAR(40)`.

Indexes: primary `(installation_case_id)`; unique `(legacy_object_id)`; unique
`(baseline_id)`.

## Ownership and transaction behavior

- Classification reconcile validates non-quarantined routing, then creates its
  table before `INSERT IGNORE`. It does not open a transaction. Uniqueness is
  output identity `(output_kind,output_id)`; replay also compares legacy object,
  cutoff and classification hash. It can be called after a case/history write,
  so failure can leave an output without provenance.
- Baseline apply creates its table before opening a data transaction. It stores
  canonical JSON/hash keyed independently by legacy object and content hash.
  Same-object changed content fails after `INSERT IGNORE` lookup.
- Active-case connector reads baseline/template before apply. Apply calls
  `ensureSchema()` before beginning the transaction, then creates a canonical
  case, an operational template association and active-case provenance in one
  data transaction. The association target currently has its own runtime DDL
  owner; MariaDB DDL before/inside the chain can break expected atomicity until
  predecessors are canonical and DDL-free.
- None of the physical relationships has an FK. Application transactions and
  hashes enforce provenance links.

## Consumers and existing verification gaps

Focused verifiers cover active baseline read readiness, connector dry-run/
apply/replay/incompatible case and migrated active projection. Native-only and
OTIZ verifiers cover simplified classification-provenance consumers. Many
fixtures create reduced nullable schemas that are not exact manifests.

No verifier proves exact fingerprints, populated preservation, all compatible
present/absent states, family-wide conflicts, counter preservation, canonical
runner ordering, DDL-denied native import, or the atomic gap between canonical
case creation and provenance reconcile.

## Planning implications

The release-supporting classification-provenance slice should:

1. precede native case import and any TEST-USER bootstrap that requires
   `migration_native` origin;
2. preflight/create its one exact populated-preserving table and enforce the
   full-catalogue 25-byte prefix ceiling;
3. remove runtime DDL and require schema before source/data transactions;
4. characterize and then close or explicitly preserve the case/history-output
   without-provenance failure window without changing classification semantics;
5. keep output kinds/categories/reason codes literal PILOT_ONLY evidence, not
   approved target taxonomy.

The optional two-table active-baseline slice should, only if cutover is selected:

1. follow canonical process, classification provenance and checklist-template
   ownership;
2. preflight both members before DDL and cover all four compatible partial
   states plus separate zero-mutation conflicts;
3. preserve rows/counters and recover from implicit-commit partial creation;
4. remove runtime DDL only after connector transaction prerequisites are
   canonical and DDL-free;
5. avoid approving `working` without opening facts, cutover template semantics
   or legacy-active admission merely by preserving storage.

## Classification and blockers

The ownership split is READY for independent evidence review. Classification-
provenance schema is release-supporting discovery and is not blocked by choosing
legacy cutover content. Literal migration ordering remains symbolic until its
predecessors land.

Active-baseline schema promotion remains conditional on GRILL-004/test-contour
cutover and does not block native-only TEST-USER readiness. Exact connector
behavior and its fabricated `working` state remain separate behavior work.
