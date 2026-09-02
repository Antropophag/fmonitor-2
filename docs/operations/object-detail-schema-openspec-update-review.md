# Independent review: object-detail schema OpenSpec update

Reviewed: 2026-09-02  
Reviewer: fresh independent planning reviewer `object_detail_openspec_rereview_20260902c`  
Change: `canonicalize-object-detail-snapshot-schema`  
Verdict: **READY_WHEN_PREDECESSORS_LAND**

## Scope and independence

I independently reviewed all four existing planning artifacts reported by
`openspec status --change canonicalize-object-detail-snapshot-schema --json`:

- `proposal.md`;
- `specs/deployment/canonical-object-detail-snapshot-schema/spec.md`;
- `design.md`;
- `tasks.md`.

I compared them with the accepted
`docs/operations/object-detail-schema-evidence.md`, its independent review and
rereview records, and the accepted
`docs/operations/catalogue-prefix-ceiling-reconciliation.md` plus rereview. I
did not modify the OpenSpec artifacts, executable specifications, tests or
production code. This is a planning review only.

## Evidence and coherence checks

### Complete ownership scope

The proposal, delta spec, design and tasks consistently scope the change to the
exact two-table family `fm2_pilot_object_details` and
`fm2_pilot_object_detail_quarantine`. They require canonical, data-free schema
ownership before importer or consumer access; whole-family read-only preflight;
exact-compatible partial completion; zero-mutation conflict handling; existing
row preservation; and removal of runtime importer DDL. The package preserves
current hash/payload/quarantine behavior and explicitly excludes FK, CHECK,
JSON, exclusivity, reconciliation, source-selection and test-data redesign.

### Prefix reconciliation

All four artifacts consistently use the authoritative composed production
process-prefix maximum of **25 ASCII bytes**, derived from the 39-byte
classification-provenance basename. The delta spec and tasks require a valid
26-byte prefix, invalid characters and non-ASCII input to fail before DB
connection/access and before ledger/schema mutation. The object-detail-local
30-byte result from its 34-byte longest basename is retained only as explanatory
identifier arithmetic and is explicitly not exposed as supported composed
configuration. No stale composed 28-byte boundary remains.

### Exact collation target

The delta spec requires the clean family to use the exact database-default
collation, and the design makes the mechanism testable: validate the exact
database-default `utf8mb4` collation through allowlist/membership checks and emit
it explicitly rather than relying on implicit source-DDL resolution. Exact
collation is also part of compatibility preflight and conflict coverage. This
is coherent as a proposed canonical target while the evidence correctly keeps
the observed source behavior separate: source DDL explicitly names `utf8mb4`
and lets MariaDB resolve that charset's environment default. The planning choice
does not promote the isolated observed value into a portable fingerprint.

### Ordering and delivery gates

No literal migration version is reserved. Every artifact defers exact catalogue
position/version until the release-order predecessors have actually landed.
Tasks require a separately approved executable specification and recorded owner
approval before RED; a demonstrated RED and fresh independent test review before
production changes; regression and architecture verification; and a different
fresh independent code review before Done. GRILL-004 blocks only population and
provenance choices, not the data-free schema-ownership plan.

## Findings

No blocking or non-blocking planning inconsistency was found. Strict OpenSpec
validation passes for the updated package.

## Verdict boundary

`READY_WHEN_PREDECESSORS_LAND` means the four-artifact planning package is
coherent and may wait for its actual release-order predecessors. It does **not**
approve Gate 1, assign a migration version, authorize RED, authorize
implementation, choose test-data population, or permit changes in
`rapid-pilot/` before the required executable-spec approval and delivery gates.
