# Fresh independent planning rereview — TEST-USER fictional fixture seed

- Review date: `2026-09-02`
- Reviewer: fresh independent agent `fixture_seed_planning_rereview_20260902u`
- Reviewed change: `openspec/changes/seed-test-user-fixtures`
- Superseded review:
  `docs/operations/test-user-fixture-seed-planning-review.md`
- Verdict: `CHANGES_REQUESTED`

## Scope and independence

I reviewed the corrected proposal, delta specification, design and tasks against
the three blocking findings in the superseded review, `PRODUCT.md`, `CONTEXT.md`,
`docs/development-process.md`, the approved TEST-USER data/reset decision, the
current `separate-pilot-generation-metadata` artifacts and their
`READY_FOR_GATE1_WHEN_PREDECESSORS_LAND` planning rereview, and the current
runtime-DDL migration order. I did not author or edit the reviewed OpenSpec
artifacts, code, tests or operations status. This record changes no Gate 1,
RED or implementation authority.

## Closed findings

1. **Receipt ownership is now coherent.** The fixture initializer owns seed
   version, manifest semantics, fingerprint and validation. `PilotEnvironment`
   owns only the generation lock, validated identity, atomic publication and an
   opaque versioned prerequisite-envelope registry/hash. The fixture change is
   explicitly blocked until that extension lands in
   `separate-pilot-generation-metadata`; otherwise it must declare a generation
   capability delta before Gate 1. Therefore `Modified Capabilities: Нет` does
   not currently conceal fixture-owned mutation of generation semantics.

2. **The module boundary is now one-way and separately named.** The corrected
   design and delta spec keep `app/PilotEnvironment` free of domain/application
   persistence. A distinct fixture initializer may write only non-domain
   identity, workforce and fictional object-source setup facts through narrow
   ports, while every domain fact must cross public `InstallationProcess`
   commands. Private process adapters and new `rapid-pilot` domain ownership are
   explicitly prohibited.

3. **The positive predecessor catalogue is materially improved.** Proposal,
   design and task 1.1 consistently name the ordered landed frontier from the
   canonical v5 runner through identity/access, checklist-template,
   inspection-evidence, inspection-planning, classification provenance,
   object-detail snapshots and the generation opaque-receipt extension. Task
   1.1 remains unchecked and requires a fresh record of the exact landed
   versions, order and transcript before owner Gate 1 approval. Deferring the
   not-yet-assigned successor runner versions until those slices land is sound;
   inventing them in this planning package would be unsafe.

## Blocking finding

1. **The optional-family exclusion list is still not exhaustive.** The current
   runtime migration catalogue places
   `canonicalize-installation-completion-schema` between inspection planning and
   object detail and marks it dependent on unresolved completion semantics. The
   fixture frontier intentionally skips it—which is consistent with the stated
   pre-open golden initial state and with completion being a non-goal—but the
   proposal, design and task 1.1 explicitly exclude only premium,
   migrated-evidence, quarantine and legacy-active families. Because the prior
   review required an exact frontier with explicit optional exclusions, the
   unnamed completion family leaves room to read the skipped sequence as an
   accidental omission or as a hidden prerequisite.

   Add `canonicalize-installation-completion-schema` consistently to the
   explicit exclusions, unless the literal Gate 1 scenario is changed to
   consume completion facts. Preserve the existing rule that any newly consumed
   family must first be added to the landed frontier with its exact runner
   version and fresh catalogue verification. No capability or implementation
   expansion is needed for this correction.

## Verification

- `openspec validate seed-test-user-fixtures --strict`: PASS
  (`Change 'seed-test-user-fixtures' is valid`).
- `git diff --check`: PASS.
- `make architecture-check`: PASS (`6 rules`).

## Verdict

`CHANGES_REQUESTED`

The opaque-envelope ownership and initializer boundary are now safe, and the
positive predecessor frontier is appropriately named and version-gated. One
narrow cross-artifact correction remains: explicitly exclude the unresolved
installation-completion schema family alongside the other non-consumed
families. After correcting all references, assign a fresh independent planning
rereviewer. Gate 1 drafting, RED and implementation remain prohibited until the
full declared frontier lands and executable `TEST-USER-FIXTURE-SEED-001` is
separately reviewed and owner-approved.
