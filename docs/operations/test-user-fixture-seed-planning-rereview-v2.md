# Fresh independent planning rereview v2 — TEST-USER fictional fixture seed

- Review date: `2026-09-02`
- Reviewer: fresh independent agent `fixture_seed_planning_rereview_20260902v`
- Reviewed change: `openspec/changes/seed-test-user-fixtures`
- Superseded review:
  `docs/operations/test-user-fixture-seed-planning-rereview.md`
- Verdict: `READY_FOR_GATE1_PLANNING_WHEN_PREDECESSORS_LAND`

## Scope and independence

I reviewed the current proposal, delta specification, design and tasks against
the one blocking finding in the superseded rereview and rechecked the three
corrections accepted there. I did not author or edit the reviewed OpenSpec
artifacts, implementation, tests or operations status. This record approves
planning coherence only; it does not approve Gate 1, RED or implementation.

## Closed blocking finding

The unresolved `canonicalize-installation-completion-schema` family is now
named consistently in every explicit optional/excluded-family list:

- the proposal's implementation impact;
- design migration-plan step 1;
- task 1.1's predecessor-landed verification.

Each location keeps installation completion outside the initial fixture
frontier alongside premium, migrated-evidence, quarantine and legacy-active
provenance. Task 1.1 also preserves the fail-closed expansion rule: if literal
Gate 1 introduces a consumer of any excluded family, that family must first be
added to the landed frontier and covered by the fresh exact-version/order/
transcript verification.

## Preserved prior corrections

1. **Opaque receipt ownership remains coherent.** The fixture initializer owns
   seed version, manifest semantics, fingerprint and validation.
   `PilotEnvironment` owns generation locking, identity, publication and only an
   opaque versioned prerequisite envelope/hash. Landing the corresponding
   `separate-pilot-generation-metadata` extension remains an explicit
   predecessor.

2. **The module boundary remains one-way.** A distinct fixture initializer may
   write only non-domain identity, workforce and fictional object-source setup
   facts through narrow ports. Domain facts may be created only through public
   `InstallationProcess` commands. Private persistence adapters and new domain
   ownership in `rapid-pilot` remain prohibited.

3. **The positive predecessor frontier remains exact at planning resolution.**
   Proposal, design and task 1.1 consistently order canonical workforce runner
   v5, identity/access, checklist-template, inspection-evidence,
   inspection-planning, classification provenance, object-detail snapshots and
   the generation opaque-receipt extension. Exact landed versions, setup order
   and transcript are deliberately deferred to still-unchecked task 1.1 and
   must be recorded before Gate 1 approval.

## Verification

- `openspec validate seed-test-user-fixtures --strict`: PASS
  (`Change 'seed-test-user-fixtures' is valid`).
- `git diff --check`: PASS.
- `make architecture-check`: PASS (`6 rules`).

## Verdict

`READY_FOR_GATE1_PLANNING_WHEN_PREDECESSORS_LAND`

The correction closes the last planning blocker without expanding capability
or implementation scope. The change may proceed to executable Gate 1 planning
only after the full declared predecessor frontier has landed and task 1.1 has
captured its exact versions, order and transcript. Executable
`TEST-USER-FIXTURE-SEED-001` still requires fresh technical and no-personal-data
reviews plus explicit owner approval before RED or production changes.
