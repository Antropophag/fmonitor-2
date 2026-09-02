# Independent classification-provenance schema planning review

Date: 2026-09-02  
Reviewer: fresh agent `classification_provenance_planning_review_0902b`  
Change: `canonicalize-classification-provenance-schema`

## Scope and method

This was a planning-only review. I did not author or modify the OpenSpec
artifacts, runtime code or verifier code. I compared the complete proposal,
design, delta specification and tasks against the accepted ownership evidence
and its independent review, the corrected runtime-DDL plan, the literal runtime
owner/call sites, current OpenSpec artifact instructions and the mandatory
delivery process.

The review covers the one-table/two-table split, exact manifest, 25-byte prefix
ceiling, migration and runtime-precondition timing, unsupported atomicity
claims, optional legacy-active exclusion and PILOT_ONLY taxonomy boundaries. It
does not approve Gate 1, RED or implementation.

## Blocking findings

1. **The executable requirements do not name or exercise the third current
   output kind.** The accepted evidence and source call sites establish three
   literal PILOT_ONLY compatibility values:
   `operational_case`, `historical_snapshot` and `active_baseline`. The proposal
   promises unchanged output-kind compatibility, and the evidence review
   explicitly cautions that planning must name every current output kind.
   However, the populated-history and literal-fixture scenarios name only the
   first two. Exact-table row preservation is necessary but does not prove the
   runtime adapter still accepts/replays an existing `active_baseline`
   provenance row after runtime DDL removal. Add `active_baseline` to the
   PILOT_ONLY compatibility inventory and require literal preservation/runtime
   compatibility without pulling either optional legacy-active table into this
   migration or approving cutover semantics.

2. **The promised output-without-provenance characterization has no observable
   OpenSpec scenario.** The design correctly avoids claiming atomicity and says
   Gate 1 will characterize the existing window; task 1.2 requires the same
   contrast. Yet the delta specification only says atomicity remains
   PILOT_ONLY, with a scenario about taxonomy literals. It never defines the
   observable contrast between (a) missing schema, which must fail before source
   fetch/output mutation, and (b) a post-output provenance-write failure, which
   this ownership-only change explicitly does not make atomic. Add a bounded
   PILOT_ONLY scenario that injects a provenance failure after an output has
   been created and records the existing non-atomic outcome, or explicitly
   remove that characterization from this package and assign it to a named
   predecessor before Gate 1. Do not state or imply that preflight alone closes
   all output-without-provenance paths.

## Confirmed planning properties

- The split is correct: classification provenance is a release-supporting
  one-table owner with native and historical reach; baseline plus active-case
  provenance remain a separate, conditional legacy-active subfamily.
- The ten-column manifest, signedness, lengths, nullability/default/extra
  semantics, InnoDB, explicit approved utf8mb4 collation, index compositions,
  plain TEXT reasons and absence of FK/CHECK match the accepted evidence.
- The 39-byte ASCII basename correctly lowers the catalogue-wide ASCII process
  prefix ceiling to 25 bytes, with validation required before SQL and without
  grandfathering earlier drafts.
- The absent/exact/incompatible compatibility model, populated row/counter
  preservation, zero-mutation conflicts, repeat, real-process concurrency and
  ledger expectations are appropriate for the one-table migration.
- The precondition is deliberately moved to each native/historical import
  adapter before source fetch and output mutation. The runtime target itself is
  only a later reconcile seam, so implementation and tests must prove this at
  the adapter boundary.
- Optional `fm2_legacy_active_baselines` and
  `fm2_active_case_provenance` are explicitly excluded and treated as ambient
  objects. No cutover choice is made by this change.
- Taxonomy, hashes, classification policy, import ordering and transaction
  semantics remain literal PILOT_ONLY evidence; no new rapid-pilot domain logic
  or mass persistence redesign is authorized.
- Migration version/order correctly remains symbolic until predecessors land,
  and tasks preserve every SDD/TDD gate with fresh independent test and code
  reviews.

## Verdict

`CHANGES_REQUESTED`

The ownership boundary and migration design are sound, but the package is not
yet complete enough for `READY_WHEN_PREDECESSORS_LAND`: it omits explicit
compatibility coverage for a current stored output kind and leaves the promised
non-atomic failure contrast outside the executable requirements. Correct both
without admitting optional legacy-active storage or asserting target taxonomy,
then send the revised package to a fresh independent planning reviewer.

This verdict does not authorize Gate 1, RED or implementation.

## Verification

- `openspec validate canonicalize-classification-provenance-schema --strict` —
  PASS
- `git diff --check` — PASS
- `make architecture-check` — PASS (6 rules)
