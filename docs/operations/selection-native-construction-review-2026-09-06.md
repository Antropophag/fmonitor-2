# Independent Gate 1 review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, not author)
- Review scope: native construction contract only; no production or test authorship
- Candidate commit: `2b25ad66461978302014e29cf8c978cd0c40b487`
- Candidate spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- Controlling core spec: `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001` v0.11
- Controlling core spec SHA-256: `d9fe7f3c47e66d059138117f99609493b8a00b1d4e4166582b3185a8037d5f1e`
- Approved core review SHA-256: `e95c0933163387a3286ac47c1ef94f9d6b5b9a60dc8fbcd2d77e2c3070bf0c81`
- Review date: 2026-09-06

## Findings

No blocking ambiguity or contradiction was found in the scoped native construction contract.

The two public factories preserve the approved application owner and its exact eight-port dependency shape. The production factory binds all eight native ports through the existing core factory. The verification factory returns that same dependency aggregate, so a public RED can reconstruct only the aggregate with a deterministic `SelectionClock` while retaining the real authorizer, facts reader, terminal reader, case UoW, fresh-reader factory, independent audit writer, and no-case terminal UoW. This does not introduce a second action seam or allow a test to replace native persistence behavior.

The first RED tranche is observable without a new production observer or mutation hook. The command is invoked only through `selectAssignmentOrderComposition`; setup uses the approved migrations and `SelectionSchemaTestDatabase`; schema/readiness state is available through the approved verification snapshot; the approved registered-composition public reader observes the selected composition; and the contract explicitly permits a read-only fixture observer to compare exact request, selection, member, event, audit, registry, physical-order, original, opening, assignment, and artifact rows. These observers cannot affect the command result. Replay and replacement immutability can therefore be established by before/after byte comparisons, while the missing-object branch can prove the exact request-plus-audit footprint and absence of a fabricated case or identity.

The construction also supports later adapter-level tranches without broadening the public command: verification receives the real transaction ports directly, allowing their closed callback/session echo contracts to be exercised; database-controlled interruption, acknowledgement, constraint, counter, readiness, and concurrency cases remain native adapter evidence. The prohibition on renderer, storage, SQL in callers, and generic fault hooks is consistent with that boundary.

The candidate applies section 17's fresh-launch override correctly. It does not require legacy-writer conversion, historical migration, PDF/template storage, HTTP, original application, or opening as selection prerequisites. Reuse of the approved registry, five-table selection schema, and registered reader is explicit. Legacy object facts used to resolve a fresh case do not create a legacy selection-history obligation; legacy-owned registry history fails closed as stated.

The prefix, independent recovery connection, ambient-transaction prohibition, readiness behavior, request-race classification, generated-ID validation, and transaction ownership rules are sufficiently exact to author a sensitive public RED and subsequent bounded native tranches. Existing core and schema approvals are accepted as controlling evidence and were not reopened.

## Gate decision

**APPROVED** for Gate 1 of `ASSIGNMENT-ORDER-SELECTION-NATIVE-001` at the exact candidate commit and spec hash above. This approval authorizes Gate 2 RED authoring for the stated first native tranche. It does not approve a test, RED evidence, native implementation, integration wiring, or Gate 5.
