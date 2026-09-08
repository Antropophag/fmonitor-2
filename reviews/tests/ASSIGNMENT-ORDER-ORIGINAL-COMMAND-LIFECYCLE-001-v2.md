# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001 v2

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `4c23ee1402e48c1d4225210758a0e0ac4a2e705b`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md` v0.1, SHA256 `4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332`
- Parent: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v64, SHA256 `de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52`
- Public seam: existing `AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(Command): Result`
- Red command and intended failure: `php tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php`; 95 cases, 94 intended failures and one control, aggregate exit `255`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
3eca7735cc73846e3838aa61656c59b9e163afce5e52dca5706028509457279f  docs/operations/original-command-lifecycle-gate1-review-v01-2026-09-06.md
de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
beec61b817c3234debf85a80f806054e85e9ab4e31b6b03f9a40b03454d313c9  tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php
1153ae35c0bf052ffffe498561c9636575111026a3e91e57fea1edc4ed73646e  tests/Support/AssignmentOrderOriginalLifecycleFixture.php
cfc9662d3e2fd392372c0d0285afdff4bf2a1303e2e8756382e70cb34a583572  docs/operations/original-command-lifecycle-red-v2-2026-09-06.md
34bac9c481aa5bb77cda028dbd0c27e99415291b1339e99e9abdee526e4f170d  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001-v1.md
ecf451034d11807c270431a11dbbb541274ba21c8e6ad183cc60fcf033f5c769  /Users/antropophag/.local/state/fmonitor2-verification/original-lifecycle-red-61vhs_tf/lifecycle-red-v2.log
```

The v1 `CHANGES_REQUESTED` review remains immutable. Specification, parent and production bytes are unchanged. Only the focused lifecycle test and fixture changed to address G3-LIFE-01. The separately approved empty-probe expectation patch remains unchanged and unapplied and is outside this verdict.

## Finding disposition

G3-LIFE-01 is closed by four independently distinguishable cases.

`outcome_status_throw` makes the first outcome getter throw. The exact assertion requires status call 1, lease call 0, no inspection of the fixture's inaccessible internal lease, no release, storage-failure result, stage abort/close and stream close once, no FINALIZE_DONE and no commit/audit/delivery.

`outcome_lease_throw` returns a successful status and throws from the one lease getter before returning a lease object. It requires status call 1, lease call 1, no lease status/content inspection or artificial adoption/release, and the same exact failure/cleanup boundary.

`outcome_locked` returns typed `LOCKED` with a non-null lease. It requires status and lease getter once, retryable storage failure, ordered owned-stage cleanup followed by exactly one lease release, and no FINALIZE_DONE/commit/audit/delivery. This proves application ownership begins when the lease object is actually returned even though finalize status is non-success.

`outcome_locked_null` returns the same non-success status with null lease. It requires status/lease once, no lease inspection/adoption/release, and otherwise identical cleanup. Together the two LOCKED cases prevent both leaked returned leases and invented lease ownership.

All four use the full public application seam and literal trace:

```text
... fingerprint miss -> ID allocation -> FINALIZE_BEGIN -> finalize
-> ABORT_BEGIN/abort/ABORT_DONE -> STAGE_CLOSE/close -> stream close
-> optional returned-lease release
```

The absence of `FINALIZE_DONE`, commit, attempt audit, post-commit lifecycle and delivery is asserted both by the trace and unchanged repository/delivery evidence. Primitive counts independently enforce one abort, one stage close, one finalize, one stream close and the expected zero/one release.

The unchanged 91-case matrix retains the v1 strengths: complete successful capture-once ordering; malformed stream/stage/inspector/finalize/lease/content cases; causal observer events; cleanup callback/primitive/log combinations; terminal and fingerprint replay; typed/generic commit recovery and conflict rereads; phase-aware release failure; accepted-candidate exceptional unwind; response-loss exception and same-request replay. Winner facts remain separate literal prior/concurrent committed facts rather than current-invocation accepted facts.

Independent reproduction matches the new archive:

```text
passes=1 failures=94 cases=95
exit=255
```

The raw log SHA256 is exact. Both PHP artifacts lint and `git diff --check` passes. No setup, environment or missing-fixture failure is counted as a lifecycle RED.

No blocking traceability, expected-value independence, branch constructibility, one-owner sensitivity, deterministic ordering or public-seam finding remains. Gate 3 is approved for the exact test/helper hashes above; minimal lifecycle GREEN may proceed without changing expectations.

## Required changes

None.

This approval does not apply or approve the separate empty-probe patch and does not establish physical FD behavior, fresh database recovery, data-integrity completion, Gate 5, combined command approval or release readiness.
