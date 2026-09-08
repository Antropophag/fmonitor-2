# Independent Gate 5 code review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 tracer v1

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification, tests, or implementation)
- Reviewed source range: `9cfb90c..247036f2fc53ba89ac7870c73cc6c6526a929b5b`
- Exact reviewed clean commit: `247036f2fc53ba89ac7870c73cc6c6526a929b5b`
- Gate 1 spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- Approved tracer Gate 3: `reviews/tests/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-tracer-v1.md`
- Approved policy Gate 3: `reviews/tests/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-policy-v2.md`
- Final verification archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-final-green-lwdxo6a0`
- Final manifest SHA-256: `f8f1b9bcbd83325b6d0fd44a897325c103f69d1ba75338077a688e668194f133`
- Review date: 2026-09-06

## Blocking findings

1. **A damaged local identity family can fall through to legacy authorization.** `MariaDbSelectionFacts::authorize()` selects local mode only when `fm2_pilot_users` exists. If any other member of the canonical local identity family exists but that one table is absent, the implementation executes the legacy authorization query. With a valid legacy user, role, and capability row, this can authorize the command even though the Gate 1 contract says local identity-schema corruption must not switch to legacy mode. The complete-compatibility check protects only the branch entered after the users table is found; it does not distinguish a wholly absent family from a partial family missing that table.

   Add a focused test that creates a partial canonical identity family without `fm2_pilot_users` alongside an otherwise granting legacy authority and requires `failed/dependency_unavailable`, zero confidential terminal lookup, and no mutation/audit. Demonstrate RED and obtain independent Gate 3 approval before changing the adapter. The minimal fix must select legacy mode only when the entire canonical local identity family is absent; any partial or incompatible family is unavailable.

2. **The recovery connection is not proven to have the same database, user, and charset.** `MariaDbSelectionFreshReaders::open()` rejects reuse of the primary object and checks that the returned connection is idle, but it never compares database, authenticated database principal, or connection charset with the primary connection. A closure can return a distinct healthy connection to a different schema or authority and its terminal record can determine unknown-commit recovery. This violates the explicit construction contract and can turn unrelated persisted state into the public result.

   Add focused tests for a distinct connection using a different database, database user, or charset and require open/recovery to fail closed while closing the supplied connection. Demonstrate RED and obtain independent Gate 3 approval before implementing identity comparison. Preserve the current requirements for a distinct connection, no ambient transaction, and closure in every outcome.

These are implementation defects in delivered native adapters, rather than missing completeness-only matrix entries. They therefore block this tranche's Gate 5 approval even though the reviewed happy-path tracer is green.

## Passing review points

The approved tracer behavior is implemented through the single public application owner. The factory supplies the exact eight ports and permits only deterministic clock replacement in verification. The three native cases persist the selection ledger atomically, replay without new rows or clock acquisition, create immutable replacement history, expose both identities through the registered composition reader, and store the missing-object terminal request plus audit without a fake case or allocation.

The SQL policy change is the reviewed minimal two-line owner extension: only `app/AssignmentOrderComposition/MariaDb*.php` gains SQL ownership. DDL ownership remains unchanged, and the architecture baseline did not grow. No renderer, PDF storage, HTTP command owner, lazy DDL, or legacy writer conversion was added.

This review remains limited to the first native tracer and narrow policy implementation. Denial/revocation, eligibility and locked-state failures, corruption, concurrent requests, rollback/interruption, unknown acknowledgement recovery, capacity boundaries, invalid echoes, ambient transactions, and boolean API failures still require their specified RED and independent Gate 3 tranches before the full native binding can be approved.

## Verification evidence

The final exact-SHA manifest records clean-before and clean-after state at `247036f2fc53ba89ac7870c73cc6c6526a929b5b`, with all of the following passing: the three-case native tracer, `make architecture-check`, OpenSpec validation, and `git diff --check`. It also records the native/core/registered-reader/baseline inputs unchanged from regression archive `selection-native-tracer-green-d_1jm_8s`, whose core 73 cases, registered-reader 15 cases, native tracer, and 13 PHP lints passed. The focused architecture tool suite separately passed 35 tests at the reviewed commit. Full `make verify` was not run and no `VERIFY_OK` or launch claim is made.

## Gate decision

Gate 5 is **CHANGES_REQUESTED**. Return the two findings to focused Gate 2/Gate 3 cycles, implement only the reviewed fail-closed corrections, rerun the relevant native/core/reader/architecture checks, and request a fresh independent Gate 5 review. Existing core, schema, registered-reader, native Gate 1, tracer Gate 3, and policy Gate 3 approvals remain intact.
