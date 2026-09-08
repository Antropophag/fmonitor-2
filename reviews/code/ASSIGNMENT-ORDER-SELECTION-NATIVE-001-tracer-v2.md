# Independent Gate 5 rereview — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 tracer v2

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification, tests, or implementation)
- Reviewed corrective delta: `1b53793c7728f9948da34bfc30c3063749762c9d..21b4ad0e6b08ac3fd00c841bf22c04e52f2710e5`
- Exact reviewed clean commit: `21b4ad0e6b08ac3fd00c841bf22c04e52f2710e5`
- Gate 1 spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- Approved corrective Gate 3: `reviews/tests/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-authority-v1.md`
- Superseded Gate 5 review: `reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-tracer-v1.md`
- Verification archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-authority-green-5hgvkalc`
- Manifest SHA-256: `ec4898324447d863557e76568abf713917a24f732873a147cc3f409cee2468f2`
- Review date: 2026-09-06

## Corrective findings

Both v1 blockers are closed by the reviewed delta.

`MariaDbSelectionFacts::authorize()` now checks every canonical table named by `IdentityAccessDefinitionSchemaMigration::tables()`. Presence of any member selects local mode and requires `IdentityAccessSchemaMigration::isCompleteCompatible()` before reading a grant. A partial or incompatible local family therefore returns unavailable and cannot fall through to a granting legacy authority. Legacy mode remains available only when the whole canonical local identity family is absent.

`MariaDbSelectionFreshReaders` captures the primary connection's database, authenticated `CURRENT_USER()`, and connection charset once during native construction. Every distinct candidate connection must be idle and return the identical metadata tuple before it can become a terminal reader. A mismatch or metadata failure closes the candidate and propagates failure; the primary connection remains caller-owned. The existing same-authority path still returns a usable closeable reader.

The independently approved correction test now passes all five cases: damaged local-family replay fails closed without disclosure or writes; wrong database, user, and charset candidates are rejected and closed; and the valid same-authority independent reader works. The original three-case native tracer remains green.

## Preserved tranche scope

The earlier passing findings remain unchanged: one public application owner, exact eight-port construction, atomic first selection, silent replay, immutable pending replacement, registered-reader projection, missing-object terminal request plus audit, narrow MariaDB SQL ownership, unchanged DDL policy, and no architecture-baseline growth.

This approval covers only the first native tracer, its narrow architecture policy, and the two reviewed fail-closed corrections. It does not approve the full native binding, portal wiring, or launch. Denial/revocation, eligibility and locked state, request corruption, concurrent races, rollback/interruption, unknown acknowledgement recovery, capacity, invalid echoes, ambient transactions, boolean API failures, prefix/readiness edges, and the other Gate 1 tranches still require demonstrated RED, independent Gate 3, implementation, and Gate 5.

## Verification evidence

The terminal manifest records exact commit `21b4ad0e6b08ac3fd00c841bf22c04e52f2710e5`, clean-before and clean-after state, and exit `0` for authority 5 cases, tracer 3 cases, `make architecture-check`, both changed PHP lints, and `git diff --check`. Source-unchanged evidence for the approved core 73 cases, registered reader 15 cases, and architecture tool 35 tests is reused. Full `make verify` was not run and no `VERIFY_OK`, integration, or launch claim is made.

## Gate decision

Gate 5 is **APPROVED** for this bounded tracer/policy/correction tranche at the exact commit and manifest above. The v1 `CHANGES_REQUESTED` findings are resolved. Further native behavior proceeds through its remaining independent gate tranches.
