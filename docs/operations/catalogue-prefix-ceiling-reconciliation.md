# Catalogue-wide process-prefix ceiling reconciliation

Evidence date: 2026-09-02. This inventory supersedes earlier statements about
the *future composed catalogue* maximum. It does not rewrite historical review
records, approve a migration Gate 1, or change family-local class contracts.

## Authoritative maximum

The longest currently inventoried intended production table basename is:

```text
fm2_migration_classification_provenance
```

It is exactly 39 ASCII bytes. MariaDB table identifiers are limited to 64
characters/bytes for the accepted ASCII prefix alphabet, therefore the future
composed production runner maximum is exactly **25 bytes** (`25 + 39 = 64`). A
26-byte process prefix cannot be upgraded to this release-supporting table.

This discovery supersedes, in order, earlier catalogue maxima derived from:

- identity/access pending draft: 32 bytes;
- checklist-template pending draft: 29 bytes;
- inspection-planning: 28 bytes;
- migration-quarantine: 27 bytes.

Those calculations remain correct for their own longest table names, but are no
longer sufficient for the composed forward-upgradable catalogue.

## Family-local versus composed contracts

`BITRIX-WORKFORCE-SCHEMA-001 v0.3` has an approved **family-local** 37-byte
boundary determined by its own tables and prefix-derived symbols. Its focused
migration class test also deliberately proves that current v1-v4 classes do not
inherit that v5-only limit. This remains valid evidence about calling those
classes in isolation and must not be silently rewritten.

Before a runner version that registers classification provenance is approved,
the **top-level composed runner/configuration seam** must enforce 25 bytes before
DB access. Family classes may retain wider local input domains when invoked
directly, provided the production runner can never select such a prefix. New
Gate 1 documents must name which seam their boundary describes.

Accepting 26–37 bytes during an earlier fresh production install and rejecting
only during a later upgrade is not forward compatible. Therefore pending
composed-schema Gate 1 artifacts must adopt 25 before owner approval, even when
their own tables fit a longer prefix.

## Required reconciliation matrix

| Artifact family | Current claim | Required treatment |
|---|---:|---|
| `canonicalize-classification-provenance-schema` | 25, but oversized rejection is only normative before DB mutation | Update its existing delta spec so 26 bytes/invalid input are rejected before DB connection/access; keep symbolic order until predecessors land. Its evidence supplies the authoritative basename arithmetic, but the current OpenSpec wording is not yet the authoritative zero-DB-access contract. |
| `canonicalize-object-detail-snapshot-schema` | 28 composed / 30 local | Update existing proposal/design/tasks/spec to composed 25; keep family-local 30 only as explanatory evidence. Requires `openspec-update-change` confirmation. |
| `canonicalize-inspection-planning-schema` | 28 composed | Update all four planning artifacts to composed 25 and change boundary scenarios from 28/29 to 25/26. Preserve 28 as family-local math only. |
| `canonicalize-migrated-evidence-schema` | 28 composed | Update delta spec/tasks/design/proposal references to 25; preserve its own 28-byte basename result as historical/family-local evidence. |
| `canonicalize-migration-quarantine-schema` | 27 composed | Update spec/design/tasks/proposal to 25; preserve family-local 27 calculation. |
| `IDENTITY-ACCESS-SCHEMA-001` | pending composed 32 | Before owner approval, revise exact boundary cases to 25 success / 26 zero-DB rejection and obtain a fresh independent Gate 1 review. GRILL-005 remains separate. |
| `CHECKLIST-TEMPLATE-SCHEMA-001` | pending composed 29 | Before predecessor refresh/owner approval, adopt 25 and fresh review; its local identifier math remains evidence. |
| `INSPECTION-EVIDENCE-SCHEMA-001` | pending composed 29 | Before owner approval, revise input/boundary matrix to 25/26 and fresh review; do not change four-table fingerprints. |
| `BITRIX-WORKFORCE-SCHEMA-001 v0.3` | approved family-local 37 | Do not rewrite. Add composed-runner 25 coverage in the future runner Gate 1/RED; retain direct-class 37/38 tests. |
| `PRODUCTION-MIGRATION-RUNNER-001 v0.5` | approved composed process prefix 32 | Preserve v0.5 as historical approved behavior. A future version that composes the 39-byte table MUST supersede its process-prefix boundary with 25/26 and retain configuration rejection before opening the DB connection. |
| `PRODUCTION-COMPOSITION-001` | both routing prefixes 0..32 | A future version MUST separate `processTablePrefix` from `legacyTablePrefix`: process becomes 0..25 before SQL, while legacy remains at its independently justified boundary unless a legacy identifier inventory requires a different limit. Do not silently narrow legacy routing from this process-catalogue evidence. |
| `WORKFORCE-CANONICAL-RUNNER-001 v0.1` | pending composed process prefix 32 | Before owner approval, supersede its composed-runner cases with 25 success / 26 rejection before DB connection/access. Keep the direct workforce migration's family-local 37/38 contract unchanged. |
| Characterization specs mentioning “future 28-byte cap” | commentary only | Correct at the next spec revision/review; current verifier-owned prefixes are much shorter, so this wording does not alter their behavior oracle or authorize RED. |
| Historical evidence/reviews/status entries | 27–32 at capture time | Preserve as historical records. Add superseding current status/inventory rather than editing reviewer verdicts. |

## Verification contract for the composed runner

The future Gate 1 that first composes the 39-byte table into the production
catalogue must prove:

1. empty prefix and exact 25-byte ASCII prefix succeed through the entire
   sequential runner;
2. exact 26-byte prefix, invalid characters and non-ASCII input fail before DB
   connection/access and before migration ledger/schema mutation;
3. every table, index, FK and CHECK symbol for the whole landed catalogue is at
   most 64 bytes and collision-free;
4. direct family migration tests continue to prove their separately approved
   local domains without being mistaken for production configuration support;
5. two supported short non-empty prefixes coexist when a family contract
   requires namespace coexistence;
6. existing databases whose persisted/configured prefix is 26–37 bytes are
   detected before upgrade mutation and reported as an operator migration
   blocker; this planning work does not invent destructive rename/copy repair.

The superseding runner and composition contracts must name the two routed
prefixes separately. The 25-byte result applies to `processTablePrefix` and
`FMONITOR_PROCESS_TABLE_PREFIX`. It is not evidence for narrowing an independently
routed `legacyTablePrefix`; that limit remains governed by the legacy catalogue's
own identifier inventory.

## Scope boundaries

This reconciliation changes configuration/schema planning only. It does not
change table contents, domain behavior, migration taxonomy, external-source
selection or test-user authorization. No implementation or RED is authorized
until the specific Gate 1 owner approval for each affected slice.

The required planning edits are mechanical consequences of a later-discovered
longer basename, but OpenSpec update workflow still requires explicit owner
confirmation before modifying existing change artifacts. Gate 1 executable
specs require their own fresh review and owner decision; they are not edited by
this evidence inventory.
