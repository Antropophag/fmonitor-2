# Test review: ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `927acdbe0337d92cc6b432d88070f2fbe6d82e6c`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md` v0.1, SHA256 `4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332`
- Parent: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v64, SHA256 `de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52`
- Public seam: existing `AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(Command): Result`
- Red command and intended failure: `php tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php`; 91 cases, 90 intended failures and one terminal-replay control, aggregate exit `255`
- Verdict: `CHANGES_REQUESTED`

## Exact reviewed inputs

```text
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
3eca7735cc73846e3838aa61656c59b9e163afce5e52dca5706028509457279f  docs/operations/original-command-lifecycle-gate1-review-v01-2026-09-06.md
de9622d1d7691330fe905b0cfefc49b8ad4f7985489b6b4fab51d9e750a2cd52  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
92b0140d5486c99bd33267af6a9b4786bad2813046a982053c9f14ffcb670db6  tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php
92530f0e9d4993579bd014ab80d3bc22fabe55c983b11bf934f2a49ccd15ae0d  tests/Support/AssignmentOrderOriginalLifecycleFixture.php
468d866c5e117313729cb6d98dcfa440e9158e55b0549ff8dd8fa3b03f72b313  docs/operations/original-command-lifecycle-red-v1-2026-09-06.md
0ed62080fd90c973f6ecf1724143105161efc8cab95032ffda0561c8331ae9f9  /Users/antropophag/.local/state/fmonitor2-verification/original-lifecycle-red-61vhs_tf/lifecycle-evidence.json
caf8498984024f57b0ab6af2f376e4b8d070a44194ba201641fbac12e1376692  /Users/antropophag/.local/state/fmonitor2-verification/original-lifecycle-red-61vhs_tf/lifecycle-red.log
```

The specification and parent have exact independent Gate 1 approval. Production resource behavior is unchanged in the reviewed test commit. The separately assigned empty-fingerprint-probe expectation patch is outside this review and receives no approval here.

## Blocking finding

### G3-LIFE-01: finalize outcome getter and LOCKED branches are not executable

Section 4 requires the application to capture finalize outcome status and lease exactly once and states that **any getter Throwable** maps to retryable `FAILED/STORAGE_FAILURE`. Every returned lease object becomes application-owned for one release attempt, even when the outcome status is non-success. Section 3 likewise requires every finalize non-success outcome to follow storage-failure cleanup.

The fixture makes `OriginalLifecycleOutcome::status()` and `lease()` countable but incapable of throwing. The matrix covers Throwable from `lease->status()`, `lease->content()` and each content-field getter, but has no outcome-status-getter Throwable and no outcome-lease-getter Throwable. It also covers `FAILED` with a returned lease but never returns the other approved non-success status `LOCKED`.

An implementation can therefore call the outcome getters outside the resource-owner boundary, misclassify their Throwable, repeat cleanup, or accept/mishandle `LOCKED`, yet pass every reviewed case. Exact-once count assertions on successful getters do not provide sensitivity to the missing throw paths.

Add independently named cases and fixture controls for:

1. `outcome->status()` throws before a lease is obtained: exact storage-failure result, acquired-stage abort/close and stream close once, no finalize DONE/commit/audit/delivery, and no invented lease release because no lease object was returned;
2. `outcome->lease()` throws after successful status capture: the same result/cleanup and no invented release;
3. outcome status `LOCKED` with a non-null returned lease: storage failure, ordered abort/stage-close/stream-close, then exactly one `rolled_back` lease release, with no finalize DONE/commit/audit/delivery;
4. preferably `LOCKED` with null lease as the complementary control, proving no artificial lease requirement or release on a non-success result that returned none.

The first two cases should assert status/lease call counts so no getter is repeated. The LOCKED-with-lease case must assert that the returned lease remains owned despite the non-success status. Preserve fresh RED evidence and request a new independent Gate 3 before implementation.

## Covered behavior assessment

Apart from G3-LIFE-01, traceability, expected-value independence and public-seam choice are strong. Literal traces cover the full successful acquisition, one-time outcome/lease/content capture, both success statuses, exact byte fingerprint, ID allocation, finalize, candidate closes, commit, release, post-commit lifecycle and delivery.

Stream cases cover Throwable, empty BYTES, payload on EOF/FAILED, oversized chunk, write failure/Throwable, completed-bytes Throwable, inspector Throwable and typed INSPECTOR_FAILED. Each asserts the port-specific failure reason, 65536 read bound, exact storage events, owned primitive counts and zero facts/delivery. The explicit `failed()` factory and private-constructor checks prevent a missing normative PDF API from being misreported as inspector behavior.

Lease/content cases cover bad lease status, status Throwable, null content, content Throwable, invalid identity/hash/size and all content getter Throwables. The accepted capture-once controls assert outcome status/lease, lease status/content and each content field are read exactly once and reused.

Observer matrices distinguish acquisition callbacks from cleanup callbacks. BEGIN/DONE causality, observer-only failures, primitive diagnostics, attempt-always abort/stage-close/stream-close, first-failure preservation and absence of synthetic primitive-failure logs are all fixed by literal traces. Accepted-candidate stage/stream close failures correctly avoid repeats and retain/release the returned lease in the specified order.

Rejected invalid-PDF and post-stream fingerprint replay each run eight cleanup variants, including primitive failure/Throwable, cleanup-observer failure and throwing safe log. Terminal replay owns and closes the unread stream once; its winner evidence is represented by a literal prior accepted commit, not only a fabricated result. Fingerprint replay likewise carries explicit winner facts separately from current-invocation accepted facts.

The commit matrix covers known commit, rollback, typed unknown recovery FOUND/NOT_FOUND/UNAVAILABLE, recovery/getter Throwable, generic commit Throwable with all three recovery outcomes, conflict replay/ordinary conflict and fingerprint/lineage/result-getter failures. Every row crosses release FAILED and Throwable plus throwing diagnostic, asserting one commit, no blind retry, exact recovery reads, phase-aware one-shot release/log, conflict audit ordering and the expected prior/concurrent winner fact count. Pure fixtures make no real fresh-DB claim, consistent with the contract's proof boundary.

Post-commit lifecycle and delivery Throwables assert the fixed redacted response-loss exception, no returned Result, durable accepted facts, no re-entry into resource cleanup, and an ordinary same-request replay that closes only the fresh unread stream without duplicating persistence. The sole currently passing terminal control confirms the RED is not a setup failure.

Independent reproduction matches the archive:

```text
passes=1 failures=90 cases=91
exit=255
```

Both PHP artifacts lint, `git diff --check` passes, and evidence/log hashes match. This is a valid public-seam RED for the covered branches, but Gate 3 cannot approve a mandatory resource branch that the fixture cannot express.

## Required changes

1. Add outcome-status and outcome-lease getter Throwable fixtures/cases with exact one-owner cleanup assertions.
2. Add the `LOCKED` finalize outcome, including non-null returned-lease ownership and preferably a null-lease complement.
3. Preserve fresh RED output and obtain a new independent Gate 3 review before lifecycle GREEN.

No conclusion is made about the separate empty-probe patch, data-integrity package, physical FD behavior, Gate 5, combined command review or release readiness.
