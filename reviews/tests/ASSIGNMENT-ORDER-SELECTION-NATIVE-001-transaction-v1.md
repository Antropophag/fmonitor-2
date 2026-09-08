# Independent Gate 3 test review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 transaction v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed test)
- Reviewed commit: `ae679897ccceb404c21aeacd22462dbd1e440c07`
- Reviewed test SHA-256: `97a468e9a61b1b3f127acd5b7f3e8694ca74d24d6218dfcb5d0691600cf47cbb`
- Controlling native spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- Inherited transaction contract: `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001` section 9
- RED archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-transaction-red-8x67uifv`
- RED manifest SHA-256: `3782cc71682bc3c7eeece370a64d4200e0a44112c3a52b4b8127c12bcfd756c8`
- RED log SHA-256: `9f67df3ab161861a6e8c77cfb02134259b1dfaa255380d5292f6bd26db544f06`
- Review date: 2026-09-06

## Findings

No blocking test finding was found in this focused transaction-contract tranche.

The intended case obtains the real `SelectionUnitOfWork` through the public native dependencies factory and supplies a typed transaction callback. It passes a concrete selected result to the terminal-only stage and requires `PERSISTENCE_ERROR` before any insert, followed by a confirmed `persistence_failure` rollback and unchanged durable state. The approved read-only fixture observer sees rows inside the owned transaction, making the test sensitive to an adapter that writes first and relies on rollback later.

Six controls establish the surrounding contract: commit without a stage is rejected; a valid request-plus-audit stage is visible atomically and removed by a typed rollback; a second stage invalidates commit and removes the first write; a wrong audit actor echo fails before mutation; the no-case UoW rejects a reason other than `object_not_found`; and a public command invoked during a caller transaction returns dependency unavailable while leaving the transaction and its uncommitted row under caller control. No private method or property is inspected.

Expected statuses, rollback causes, and row effects follow the closed section 9 types. Fixture SQL establishes preconditions or performs read-only observation and does not implement the command. Each case owns an isolated database and confirms setup and cleanup.

## Reproduced RED

The retained exact run executed seven cases and exited `1`. The terminal-only stage returned `STAGED` instead of `PERSISTENCE_ERROR`; the other six cases passed. All seven reported `SETUP_OK` and `CLEANUP_OK`. This isolates the missing pre-mutation type/status restriction rather than a setup, transaction-owner, or inherited regression failure.

## Gate decision

Gate 3 is **APPROVED** at the exact reviewed commit and hash. Gate 4 may add the minimal terminal-stage guard that accepts only the permitted rejected/conflict terminal result shape before any request or audit write. Existing echo, one-stage, rollback, no-case, and ambient-transaction behavior must remain unchanged. Fresh Gate 5 review is required.
