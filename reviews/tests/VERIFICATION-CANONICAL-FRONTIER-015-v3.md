# Test review: VERIFICATION-CANONICAL-FRONTIER-015 — v3 CHECK transition

- Reviewer: `/root/original_gate3`, independently tasked agent; did not author the amendment or test.
- Test author: root implementation agent.
- Reviewed base: `29397bd27e3af856e626908da722f474a272fe39`, bounded workforce test correction.
- Specification: frontier v0.2, independent Gate 1 v2 APPROVED.
- Public seam: full native migration CLI and complete before/after table snapshots.
- Verdict: `APPROVED`.

## Findings

Read the v0.2 amendment, independent Gate 1 record, exact workforce preservation loop and inherited test-only original-audit catalogue. The existing original-audit13 contract adds exactly the original upload/correct capability literals and changes the constraint name to `ck_fm2_process_user_capability_v5`. No new grant or production behavior is introduced.

The correction is limited to the exact `$partialPrefix . 'fm2_process_user_capabilities'` table. It first asserts one occurrence of the entire old constraint clause in the expected pre-upgrade DDL, then substitutes a fixed entire new clause. The four old capability literals, their order and surrounding CHECK syntax are preserved; exactly two approved literals are appended. Expected values are literal, not copied from the post-migration DDL.

The complete resulting expected table snapshot is still compared with the actual post-recovery snapshot. All rows and all DDL outside that one exact replacement remain sensitive, including other constraints, indexes, defaults and counters. Every other table retains its unmodified snapshot comparison. There is no excluded table, broad normalization, regex allowance or weakening of completed-repeat/early-conflict checks. This implements precisely the narrow Gate 1 v0.2 contract.

## Evidence

Current test and isolated pre-registration overlay share SHA-256 `e9423970b4ba3a6bac510553fc9879176d824cf490482e3b575e565d9a10d2f0` for `tests/InstallationProcess/workforce_canonical_runner_001_test.php`.

Root ran `php tests/InstallationProcess/workforce_canonical_runner_001_test.php` on canonical13 source; `/Users/antropophag/.local/state/fmonitor2-verification/canonical-frontier-20260907/workforce-check-transition-red-v3.log` shows intended missing canonical14/15 failure in the clean exact catalogue/result assertion. This preserves frontier slice RED; it does not claim the later partial CHECK assertion executes before that failure. The preceding current-source `workforce_canonical_runner_001_test-green-v2.log` supplies diagnostic evidence of precisely the old/new CHECK discrepancy while rows remain equal; it is not substituted for pre-registration RED. Reviewer inspected evidence and oracle identity, without claiming another execution.

No blocking findings. Current-source GREEN and independent Gate 5 may continue. Prior v1/v2 records remain preserved; unrelated auth and protected E2E work are outside this approval. Only this review record was written by the reviewer.
