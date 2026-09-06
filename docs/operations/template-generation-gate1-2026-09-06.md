# Independent Gate 1 review — ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification)
- Reviewed commit: `8122e71e70ffb440ea4963f6c4bab9d1132cae9e`
- Specification SHA-256: `6e93b096acb52ce6192fb4e43fa31b040c2ba35d67281275e7e210bfb6d4a685`
- Review date: 2026-09-06

## Blocking findings

1. **The valid renderer result is not closed.** The contract maps a renderer exception or “invalid result” to `failed/render_failure`, but it does not define which returned array shapes are valid. The reused renderer protocol is a list of artifact arrays and historically permits more than one artifact, while this feature returns one fixed PDF and persists no artifacts. Gate 1 must state whether success requires exactly one list item and pin its exact required keys/values (`type`, filename, media type, nonempty bytes), whether extra keys are allowed, and that zero/multiple items or any other shape produce `render_failure` before event insertion. Otherwise equally conforming implementations can select different bytes or accept extra artifacts.

2. **The object-snapshot field mapping is underspecified.** The renderer requires address, entrance, registration number, planned start, and planned finish, but the contract does not identify the authoritative columns/adapter or the precedence and null/legacy-zero rules. The existing legacy object data includes more than one possible finish field (`workdateendadjusted` and `plan_finish_date`). The spec must name the reused approved snapshot owner or pin exact source columns and precedence, including the outcome when an adjusted date is absent or malformed. This changes observable PDF content and `dependency_unavailable`, so a RED cannot derive one independent expected value from the current text.

## Coherent portions

The remaining design is coherent: one public generator, exact selection capability, current latest selection only, one clock instant with Moscow date, shared case lock through render/event commit, no byte storage or automatic retry, a single append-only generation event, and a latest-event date projection. Refusal/failure mappings, event payload exclusions, prefix/ID bounds, and the separation from original application, opening, HTTP, and deployment are otherwise sufficiently bounded. Reusing `fm2_process_events` introduces no schema migration.

## Gate decision

Gate 1 is **CHANGES_REQUESTED**. Amend only the renderer-return contract and object-snapshot source/precedence, update the candidate hash, and request fresh independent review before authoring RED. The owner-approved no-storage/date/audit policy is not reopened.
