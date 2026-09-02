# Independent classification-provenance schema planning rereview

Date: 2026-09-02  
Reviewer: fresh agent `classification_provenance_planning_rereview_0902c`  
Change: `canonicalize-classification-provenance-schema`

## Scope and method

This was a planning-only rereview after the prior `CHANGES_REQUESTED`. I did
not author or modify the reviewed OpenSpec package, runtime code or verifier
code. I compared the corrected proposal, delta specification, design and tasks
against the accepted ownership evidence and evidence review, the prior planning
review, literal runtime DDL/call sites, current runtime-DDL plan and mandatory
delivery process.

I independently rechecked the one-table ownership boundary, all three observed
output kinds, the ten-column/index manifest, collation and constraint semantics,
the 25-byte ASCII process-prefix ceiling, migration ordering/preconditions,
populated/conflict/repeat/concurrency requirements, optional-table exclusion,
PILOT_ONLY boundaries and preservation of every SDD/TDD gate. This rereview
does not approve Gate 1, RED or implementation.

## Prior findings

Both prior blocking findings are corrected.

1. **`active_baseline` consumer coverage is now explicit and bounded.** The
   proposal inventories `operational_case`, `historical_snapshot` and
   `active_baseline`. The delta spec separately requires literal
   `active_baseline` append/replay/conflict compatibility against the exact
   pre-existing classification-provenance table under a DDL-denied principal.
   It also says this proof neither creates
   `fm2_legacy_active_baselines`/`fm2_active_case_provenance` nor approves
   legacy-active cutover. Task 1.2 carries all three output kinds into the exact
   Gate 1 contract.

2. **The output-without-provenance window is now observable without being
   normalized into target behavior.** The new scenario injects a provenance
   conflict after current orchestration has successfully created a native case,
   historical snapshot or active baseline. Its exact observable classification
   is `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE`: the output remains and no new
   provenance row exists. The requirement explicitly declines to claim the
   window closed or approved, while the separate missing-schema scenario still
   requires fail-closed behavior before source fetch or output mutation. This
   distinguishes schema-readiness failure from the existing post-output write
   failure rather than implying that preflight provides transaction atomicity.

## Independently confirmed package properties

- The migration owns only
  `fm2_migration_classification_provenance`. Optional baseline and active-case
  tables remain ambient/excluded and conditional on GRILL-004; the old combined
  runtime-plan family has already been split into distinct backlog rows.
- The manifest matches the runtime owner: ten ordered NOT NULL columns, unsigned
  `BIGINT` identities/references, AUTO_INCREMENT only on `id`, exact lengths,
  plain `TEXT` reasons, primary `id`, unique
  `(output_kind,output_id)`, secondary `(legacy_object_id)`, InnoDB, explicit
  approved utf8mb4 collation and no FK/CHECK. Index presentation names are
  correctly non-contractual.
- Absent and exact-present are the only compatible states. Incompatible
  column/index/engine/collation state fails before any table, row, counter,
  decoy or migration-ledger mutation. Compatible populated history preserves
  exact bytes, ids and the next AUTO_INCREMENT value; safe repeat and two real
  runners preserve a single ledger version.
- The 39-byte ASCII basename leaves exactly 25 bytes under the current
  ASCII-only 64-character identifier contract. Boundary and oversized/invalid
  cases are required before SQL, and task 1.1 correctly requires catalogue-wide
  reconciliation without grandfathering earlier drafts.
- Canonical ordering stays symbolic until its process-schema predecessors land.
  The migration must land before import/bootstrap output creation. Runtime
  adapters must check the exact table before source fetch/output mutation and
  may no longer repair it with DDL.
- DDL-denied coverage is scoped to classification-provenance reconcile for all
  three literal output kinds. Gate 1 must keep that storage proof distinct from
  end-to-end legacy-active DDL freedom, because the two excluded optional table
  owners are not promoted by this package.
- Taxonomy, category/reason literals, hashes, routing policy, import ordering
  and the multi-step transaction remain PILOT_ONLY. The package adds no rapid-
  pilot domain logic, backfill, legacy admission or persistence redesign.
- Tasks retain approved executable specification, demonstrated RED, fresh
  independent test review, minimal GREEN, focused/full regression and
  architecture checks, and a fresh independent code review. No task is marked
  complete and no planning language authorizes RED before explicit owner Gate 1
  approval.

## Residual Gate 1 cautions (non-blocking)

- Express DDL-denied `active_baseline` coverage at the provenance reconcile
  seam unless the optional legacy-active predecessors have separately landed;
  do not reinterpret it as approval or canonicalization of the full cutover
  pipeline.
- The injected post-output conflict transcript must prove that the output was
  newly/successfully created in that run and that no matching provenance row
  was added. A merely pre-existing output would not characterize the window.
- Choose the literal migration version, predecessor ledger and approved
  collation only after the named predecessors and target environment are
  authoritative, as task 1.1 already requires.

## Verdict

`READY_WHEN_PREDECESSORS_LAND`

The corrected package is coherent, source-traceable and complete for
planning. It resolves both prior findings while preserving the one-table
ownership boundary and PILOT_ONLY behavior limits. Predecessor landing,
catalogue-wide 25-byte reconciliation, exact Gate 1 drafting and explicit owner
approval remain mandatory before RED.

This verdict does not authorize Gate 1 approval, RED or implementation.

## Verification

- `openspec validate canonicalize-classification-provenance-schema --strict` —
  PASS
- `git diff --check` — PASS
- `make architecture-check` — PASS (6 rules)
