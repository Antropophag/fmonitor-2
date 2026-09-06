# Independent Gate 5 review — ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001 tracer v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification, tests, or implementation)
- Reviewed production diff: `989ecc8..9eab3b159945c53767574549bba4b91911883fa0`
- Exact reviewed clean commit: `9eab3b159945c53767574549bba4b91911883fa0`
- Gate 1 spec SHA-256: `81c1c686d20075345564451626063a60741393ed039592b2aa674cb41d80aaf4`
- Approved tracer Gate 3: `reviews/tests/ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001-tracer-v1.md`
- Verification archive: `/Users/antropophag/.local/state/fmonitor2-verification/selected-original-green-w7cwj56u`
- Manifest SHA-256: `e1ae2654cd89a08161514c2e5ddeb5d8acb3831f18ca404b5e11de72c18f473d`
- Review date: 2026-09-06

## Findings

No blocking correctness, security, ownership, or regression finding was found in this first direct-selection tracer tranche.

The historical registered-composition reader now delegates to `MariaDbRegisteredCompositionQuery` while retaining its read-only snapshot, other-case nondisclosure, registry/source dispatch, canonical member/hash validation, observer release point, and unavailable mapping. The shared query adds only an optional lock suffix for use inside an already-owned write transaction.

The selected constructors are explicit and bind the existing `AssignmentOrderOriginalService` owner. Verification supplies only the approved clock and persistence observer; production uses the system clock and inert observer. Both retain real original authorization, PDF inspection, private storage, safe log, audit, fresh recovery, and repository. Existing `create` and `createRecoveryReady` remain physical-only with no schema-presence fallback.

Preflight uses the selected write-target reader in one RR snapshot. It accepts the latest valid selection, permits a non-latest selection only through a fully validated accepted original lineage, returns empty `NOT_CURRENT` for a replaced unsigned selection, and fails closed for malformed, dual, orphan, legacy-owned, or other-case state. The new lookup status maps to the existing `conflict/target_not_current` result and persisted audit vocabulary.

The selected repository locks the exact installation-case row before repeating source/currentness validation and comparing the locked composition with the accepted commit payload. A proven stale composition raises its dedicated internal signal; only a confirmed rollback returns `COMPOSITION_NOT_CURRENT`. The commit protocol then releases existing resources and uses the normal terminal-request/audit path. Commit or rollback uncertainty remains `OUTCOME_UNKNOWN`; the new status is not inferred during recovery. Physical writes retain their old guard behavior.

The approved two-case tracer is green for both constructors. Native selection 81 is accepted directly without a physical order or template; exact composition/hash, revision/date/PDF bytes, one immutable original fact per table, unrelated selection/opening/assignment preservation, stream closure, and silent replay without clock/database/file writes are all demonstrated.

## Verification and scope

The exact clean manifest records 26 passing commands: the two direct tracer cases, registered-reader 15-case suite, relevant original composition/write/lifecycle/recovery/audit regressions, native selection tracer, architecture check, OpenSpec validation, diff check, and 14 changed-file PHP lints.

Gate 5 is **APPROVED** for this first direct-selection tracer only. Replaced-target preflight, correction of accepted historical selections, both replace/upload race orders, malformed source nondisclosure, and the broader original regression matrix remain required before full selected-original binding approval. This review does not approve HTTP, template generation, composition application, opening, or deployment.
