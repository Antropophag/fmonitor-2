# Independent migration-quarantine schema evidence review

Date: 2026-09-01  
Reviewer: fresh agent `migration_quarantine_schema_review_0901s`  
Artifact: `docs/operations/migration-quarantine-schema-evidence.md`

## Scope and method

This was an evidence review only. I did not author or modify the evidence,
production code, verifier code or an OpenSpec artifact. I checked the document
against the authoritative dirty worktree, including:

- `PRODUCT.md`, `CONTEXT.md`, `docs/development-process.md`,
  `docs/operations/status.md` and
  `docs/operations/runtime-ddl-migration-plan.md`;
- `MigrationQuarantineRegistry`, `MigrationQuarantineDecisionLedger` and
  `MigrationQuarantineReadModel`;
- the OTIZ construction/read/decision call sites, batch registration, focused
  quarantine verifiers and native-only-generation counter inventory;
- exact basename byte lengths and the MariaDB manifest claims recorded by the
  evidence.

This review assesses whether the discovery is sufficiently accurate and
bounded to enter OpenSpec planning. It does not approve behavior, Gate 1, RED,
implementation, quarantine taxonomy or release promotion.

## Findings

No blocking findings.

1. **Family boundary and ownership are exact.** The runtime plan and source
   identify precisely the registry, observations and decisions tables.
   `MigrationQuarantineRegistry::record` lazily executes the first two DDLs,
   while `MigrationQuarantineDecisionLedger::ensureSchema` independently owns
   the third. The read model performs existence checks and no DDL.
2. **The manifests match the source owners.** Column order, types, nullability,
   absence of semantic defaults/FKs/CHECKs, primary/unique/secondary indexes,
   InnoDB and explicit `DEFAULT CHARSET=utf8mb4` agree with the literal DDL.
   Treating the two JSON payloads as application-validated `TEXT` is accurate.
3. **Prefix arithmetic is correct and materially updates the catalogue
   constraint.** `fm2_migration_quarantine_observations` is 37 ASCII bytes;
   under MariaDB's 64-byte identifier ceiling the safe prefix is therefore 27
   bytes. The evidence correctly identifies this as stricter than the current
   28-byte planning ceiling and defers final synchronization to canonical
   planning/landing.
4. **Behavioral dependencies are described without promoting them to target
   semantics.** The two registry uniqueness dimensions, observation replay,
   decision operation-id replay, locked registry reference check, broad
   `otiz.manage` dependency and read-model joins are all source-supported. The
   document keeps authorization tables outside this physical family and does
   not invent an FK.
5. **The proposed verification surface addresses the demonstrated ownership
   risks.** All eight exact compatible present/absent states, separate
   incompatible-member cases, populated row/counter/decoy preservation,
   implicit-DDL partial recovery, runner order, prefix enforcement and
   DDL-denied runtime behavior are appropriate planning requirements. They do
   not authorize implementation or persistence redesign.
6. **Scope and dependency classification agree with operations truth.** The
   family remains PILOT_ONLY migration control, ordered after migrated evidence
   and release-critical operational predecessors. Taxonomy, decisions,
   retention/correction, cutover and financial use remain explicitly outside
   this ownership discovery.

## Residual planning cautions (non-blocking)

- OpenSpec must carry the newly discovered 27-byte full-catalogue ceiling
  consistently; older 28/29-byte planning artifacts will need reconciliation
  before any affected executable schema contract can be approved.
- The eventual exact migration version and predecessor catalogue must remain
  symbolic until predecessors actually land, as the evidence requires.
- A later executable schema specification must turn the high-level conflict
  categories into literal normalized fingerprints and exact expected runner
  outcomes; this evidence review does not itself satisfy Gate 1.

## Verdict

`READY_FOR_OPENSPEC`

The evidence is source-traceable, technically coherent, correctly bounded to
schema ownership and sufficiently complete for a planning-only OpenSpec
package. No RED or implementation is authorized by this verdict.

## Verification

- `git diff --check` — PASS

