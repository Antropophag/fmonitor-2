# Test review: ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `f0862b08d1922f9e3ab2f31b39ac9c32078579e8`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001.md` v0.1, SHA256 `ef245ec06ad9ed0a7d9c365386077a22be5547eb625ba276ba4f603d7b310942`
- Public seam: `AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(Command): Result` through the verification factory and direct service construction
- Red command and intended failure: `php tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php`; all 13 cases expose unisolated diagnostic failure at the public application seam, followed by the aggregate `TestFailure`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
ef245ec06ad9ed0a7d9c365386077a22be5547eb625ba276ba4f603d7b310942  specs/ASSIGNMENT-ORDER-ORIGINAL-SAFE-LOG-ISOLATION-001.md
e39895f27c9041d14d85e6f9d51ac1f411ef4e7c7178c165756dfbb66a2a2056  tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php
20cdecb500b01c721c90b7fb4fb95878f4b612fe0e73f850850da452b4fb18df  tests/Support/AssignmentOrderOriginalLogIsolationFixture.php
2a43accb2e23ee135e07674054a4b47c2b8335ef34cf90d50fce2be1d981f55d  docs/operations/original-safe-log-isolation-red-v1-2026-09-06.md
569c792d7d48ba7842281881362693d0b3bf40e83cf66a045fa925705ba1279f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
87c27a61753d1b182c7c00af5277e6730093eb6a145fede4a5491985e6d59efb  /Users/antropophag/.local/state/fmonitor2-verification/safe-log-isolation-red-yuh3tnwu/evidence.json
9a25785146dfb36ea24e2b35d316acb9bf79c8333a1356f1e44fe98632e6fc42  /Users/antropophag/.local/state/fmonitor2-verification/safe-log-isolation-red-yuh3tnwu/red.log
```

The independent Gate 1 record `docs/operations/original-safe-log-isolation-gate1-review-v01-2026-09-06.md` approves the exact specification hash. Production runtime bytes match the RED archive and were unchanged by the test commit.

## Findings

Traceability and public-seam choice are complete. The test invokes the real application returned by `AssignmentOrderOriginalVerificationFactory::create` and also constructs `AssignmentOrderOriginalService` directly for the generic-observer composition. Fault fixtures implement existing public ports. They do not call private methods, inspect guard internals, or introduce a construction observer, environment selector, filesystem/native hook or alternate application seam.

Expected values are independent and literal. The test fixes Example A's 327 PDF bytes and SHA-256, command identity, actor, case/order, document date, clock, original/revision IDs and accepted evidence. Rejected, accepted, conflict and persistence-failure result tuples are fully asserted, including retryability and absence/presence of every evidence field. Expected event names, sole phase fields, request IDs, call sequences and audit values are written from the specification rather than captured from a successful production run.

The five invalid-PDF cases distinguish typed abort failure, abort Throwable, stage-close Throwable, stream-close Throwable and all three independent cleanup failures. Exact traces require abort, stage close, stream close and terminal attempt commit once and in order. Each applicable diagnostic is attempted exactly once with unchanged event/fields/context; a failure in one diagnostic cannot suppress a later independent cleanup diagnostic. The selected rejection, one terminal audit, no acceptance and no delivery remain fixed.

The four accepted-release cases cross typed release failure and release Throwable with request-aware and generic observers. They require one accepted commit followed by one release, one diagnostic and one delivery, with the full accepted result preserved. Generic observers receive no invented request-binding callback while retaining their ordinary diagnostic behavior. Request-aware observers receive exactly one binding for the exact request.

The CAS-conflict fixture is constructible and sensitive. `commitAccepted` returns `CONFLICT`; the accepted-fingerprint reread remains not found; assignment lineage returns a preexisting root/revision with matching composition. The before/after canonical evidence sentinel proves this preexisting root is unchanged and no accepted fact is added. The exact trace requires both rereads before release, one conflict diagnostic and one required terminal conflict audit; delivery remains zero.

Request-context isolation is exercised in both directions. A binding Throwable on valid accepted input must not escape, alter the accepted tuple, skip close/release/delivery or emit any record under the old context. On the same application, a failed binding for the first invalid request suppresses that invocation's diagnostic, while the next request retries binding, uses its new exact ID, emits its required diagnostic once and preserves both terminal audits. This detects stale correlation, sticky suppression, skipped reset and global request-ID state.

The final audit-failure sensor prevents an overbroad catch from returning the previously selected rejection. Even with diagnostic failure, the real terminal audit must be attempted once; its unconfirmed result must remain `FAILED/PERSISTENCE_FAILURE/retryable=true`. The exact trace catches the current duplicate logging and repeated abort behavior and would catch a skipped audit.

All fixtures are in-memory and deterministic. The only bytes are the fixed synthetic PDF and the logger's deliberately empty diagnostic buffer. There is no database, filesystem, privilege, permission, interval, native operation, production data, real document, PII or remote dependency. Fresh graph construction isolates cases except for the one deliberate same-application reset scenario.

Independent reproduction at reviewed commit produced 13 named failures:

```text
invalid-abort_failed
invalid-abort_throw
invalid-stage_close
invalid-stream_close
invalid-all_cleanup
accepted-release_failed
accepted-release_failed-generic
accepted-release_throw
accepted-release_throw-generic
cas-conflict
binding-failure-accepted
binding-reset-same-application
real-audit-failure-remains
```

The first ten return or expose the wrong selected result, the two binding cases allow `useRequest` Throwable to escape, and the audit-failure case shows duplicate diagnostic/abort work with the required audit absent. The script exits `255` only after aggregating all 13 failures. The output and test/helper/runtime hashes match the private RED archive. Both PHP files lint and `git diff --check` passes.

The test is sensitive to the specified isolation boundary without weakening real failures: diagnostic Throwable alone is suppressed, while the independent failed terminal audit must still select persistence failure. Passing requires result preservation, exact-once cleanup/release/log/audit/delivery behavior, unchanged diagnostic arguments, generic-observer compatibility and per-invocation request reset.

No blocking traceability, seam-choice, expected-value independence, branch-constructibility, sensitivity, rejected-case, determinism or isolation finding remains. Minimal GREEN may add only the total diagnostic guard required by the approved contract; production and direct construction paths remain Gate 5 obligations.

## Required changes

None.

This approval does not establish GREEN, Gate 5, shared opened-owner approval, combined original-command approval, or broader product readiness.
