# Code review: DETERMINISTIC-KNOWN-CI-TRIAGE-001

- Reviewer: independent Gate 5 agent `/root/gate5_review` (authored no reviewed artifact)
- Scope: issue #164, bounded T03 of #145 only
- Base: `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`
- Reviewed candidate source: `960ede508c963f789d640257980627f872c1d92c15f3773fd887dc78f78e060e`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023709Z-13fbb17048/package.json`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T023709Z-13fbb17048/snapshot`; patch SHA-256 `36ff51d38c097719ff0b31f277b96e4a9fcf836d7ee54e1711781be32aebe7de`
- Verification plan: CRITICAL, required reviews `gate3`, `final`; package plan SHA-256 `6246d121b607b545cdcda189e50ac5f5397533afe60eb6d7b177129d9801e966`
- Prior review: Gate 3 final verdict `APPROVED` in `reviews/tests/DETERMINISTIC-KNOWN-CI-TRIAGE-001.md`
- Verdict: `CHANGES_REQUESTED`

## Evidence checked

The retained focused records both bind GREEN to the reviewed candidate source `960ede508c963f789d640257980627f872c1d92c15f3773fd887dc78f78e060e` and executable source `a4ce94724b9e18fc519db3b5b6103bb2aa4f65fadc7772c235282cce7a47e458`:

- `python3 tests/Verification/delivery_harness_ci_triage_001_test.py`: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789526175763794000-5566e9f51c0f40ba9cd2a5cd12b45364.json`
- `python3 tests/Verification/change_verification_001_test.py`: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789526182735974000-6a243a6d41934ef8bb9424c9dc4754d9.json`

The retained snapshot manifest declares the reviewed base and its patch digest matches the snapshot file. Read-only GitHub reproduction confirmed PR #144 run `34933440293`, attempt 1, head `b964901b73d567cf2a98efd41bcb31ef9b7b2a86`, failed job/check `Integration (2/2)` / `104266277494`, the exact `Mode --missing-revision exit`, `JsonException: Syntax error`, `inspection_item_complete_001_mariadb_test.php(21): json_decode()` stack and thrown line 21, followed by same-head successful attempt 2. No local full suite was run.

## Complete findings

1. **BLOCKING — native classification fabricates a unique primary instead of collecting the complete `REGRESSION_FAILURE` inventory.** In `tools/delivery/harness_context.py:317-325`, `regression` is true when the job log contains the three expected substrings anywhere, after which the collector appends exactly one synthetic `primary=True` entry. It never enumerates every `REGRESSION_FAILURE` in that failed job and therefore cannot establish that the PR #144 test is the sole applicable primary. A log containing the exact tuple plus another product regression is reduced to the same single-item inventory and can receive `INFRA_TRANSIENT` and retry permission. This conflicts with the constitution's complete failure-inventory rule, the contract's unambiguous/exact evidence requirement, and the design's claim that the historical failure is the unique primary. **Correction:** parse and retain the complete machine `REGRESSION_FAILURE` inventory for the applicable job before selecting a primary; fail closed when entries are malformed, ambiguous, or include another primary/product failure. Add a public native-collector-sensitive regression case for the exact tuple adjacent to a second regression.

2. **MAJOR — the setup signature excludes the specified e2e category.** Both target selection and diagnostic retrieval are hard-coded to `Integration (2/2)` (`tools/delivery/harness_context.py:133-134,312-313`). The normative setup rule and design explicitly apply the MariaDB precondition to integration/e2e category jobs, and `tools/verification/ci.py:276-282` runs that same preflight for both categories. An exact pre-test failure in the `e2e` job can never produce `verification-mariadb-precondition-v1`; it returns `UNKNOWN`. **Correction:** allow the setup signature for the exact supported integration and e2e category job/check identities while keeping the PR #144 transient restricted to `Integration (2/2)`, and add positive e2e plus neighboring non-category tests.

The remaining reviewed properties are coherent: the public `state`/`wait` seam shares one closed two-ID schema; replay fixtures bind repository/PR/run/attempt/job/check/head/candidate and fail closed on malformed/stale/contradictory neighbors; attempt 2 cannot retry; retained same-run/head history is not rewritten; diagnostics do not promote admission; no new store, waiter, publisher, workflow, product, FAST-classifier, dispatch, merge or deployment behavior is introduced; and measurement claims honestly keep token usage unknown and automatic retries at zero.

## Required disposition

Correct both findings, refresh the exact-source package and focused evidence, and request independent review of the resulting code/test delta. Gate 5 is not approved for this candidate.

---

## Gate 5 rereview v2 — corrected candidate

- Reviewer: independent Gate 5 agent `/root/gate5_review` (authored no reviewed artifact)
- Reviewed candidate source: `69270010c5c78807c42947be009066f220b63e1f33a99d0b880b9c3564c0eaa6`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025143Z-97e2fb7073/package.json`
- Snapshot patch SHA-256: `5bf26e217153f28165123677176d4417bebf5aa7a3aca5da49d7e987e2923a5d`
- Verification plan: CRITICAL; required reviews `gate3`, `final`; package plan SHA-256 `2b917360b2eab5c7ec48a2b3d9df0728ead48fd06cd0c841e1b64b2cf19066ba`
- Focused evidence: both embedded records are GREEN and bind candidate source `69270010c5c78807c42947be009066f220b63e1f33a99d0b880b9c3564c0eaa6` plus executable source `f73d8b63ee187b97ad6317cf2efa25441ff0422a09a4a5ea4df4c15ffc1975cf`
- Superseding verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Not resolved on the real native seam.** The correction attempts to enumerate every regression before classification and its replay logic correctly denies retry when two structured regression entries are supplied. However, the native parser uses `re.fullmatch(r"REGRESSION_FAILURE: ([^\s]+)", line.strip())` over raw `gh run view --log` lines (`tools/delivery/harness_context.py:329-333`). Real GitHub CLI output prefixes each line with tab-separated job name, step name and timestamp. The independently reproduced PR #144 line is shaped as `Integration (2/2)\tUNKNOWN STEP\t2026-09-15T05:43:44...Z REGRESSION_FAILURE: tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php`, so the full match never succeeds. The real historical tuple therefore produces an empty regression inventory and cannot classify `INFRA_TRANSIENT`, despite the synthetic test being GREEN. The fixture's fake `gh` emits bare log payload lines (`tests/Verification/delivery_harness_ci_triage_001_test.py:329-333`) and does not reproduce the native CLI format. **Correction:** parse the bounded message payload after the exact GitHub log columns (or otherwise use a structured source), retain all exact regression entries, and make the native test use realistic prefixed lines including the verified PR #144 shape plus a second regression neighbor.

2. **Resolved.** Target selection and bounded retrieval now admit `Integration (1/2)`, `Integration (2/2)` and `e2e` for the shared MariaDB category precondition, while the transient branch remains explicitly restricted to `Integration (2/2)`. The public test covers both Integration and e2e setup positives, and unrelated jobs remain ineligible.

### Complete rereview findings

The unresolved native-log parsing defect above is **BLOCKING** because the primary goal is deterministic reproduction of the actual PR #144 evidence, not only classification of a caller-prestructured replay fixture. No additional scope, authorization, history, retry-bound, admission-separation, storage, waiter, publisher, workflow, measurement or exact-source evidence finding was identified in the corrected candidate.

Correct the real log parser and its native-format regression test, refresh the exact-source package and focused records, and request another independent review. Gate 5 remains unapproved.

---

## Gate 5 rereview v3 — final approval

- Reviewer: independent Gate 5 agent `/root/gate5_review` (authored no reviewed artifact)
- Reviewed candidate source: `fc4da30b2e82fa2e69d0c5eee3788d3d99447dba2e2751c0907db263b1b0f88e`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T025653Z-f1d07da022/package.json`
- Snapshot patch SHA-256: `532df473e09b92e553783f0695b447a60a49bab6ddd0b0105fa46a1602181db5`
- Verification plan SHA-256: `ef2f86dbc2a114785066fbd75ff472b48015fab763778f8d275af0aa9227d252`; CRITICAL with required reviews `gate3`, `final`
- Gate 3 test-delta rereview v7: `APPROVED` for the realistic prefixed GitHub log seam
- Superseding verdict: `APPROVED`

### Prior blocking finding disposition

**Resolved.** The native collector now accepts only a bare payload or the exact three-column GitHub CLI log shape with nonempty job/step columns and an ISO UTC timestamp, strips only that validated timestamp prefix, and then full-matches the closed `REGRESSION_FAILURE: <path>` payload (`tools/delivery/harness_context.py:335-349`). Against the independently fetched PR #144 attempt-1 log, this parser extracts exactly `tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php`; the exact JSON exception, line-21 decode stack, thrown site and parent `--missing-revision` failure predicates also remain present. The realistic native fixture supplies the same prefixed shape plus a second product regression and requires exact complete failed-job and regression inventories; GREEN proves that ambiguity remains `UNKNOWN` with retry denied rather than being collapsed into the known transient.

### Exact-source verification

Both retained focused records are GREEN and bind start/end candidate source `fc4da30b2e82fa2e69d0c5eee3788d3d99447dba2e2751c0907db263b1b0f88e` and executable source `0e5aae4b810adc27fb819b5c089a7074170ea336294ecb3003bdc5cb0596c5c0`:

- `python3 tests/Verification/delivery_harness_ci_triage_001_test.py`: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789527377495643000-bbdaf482cb4b470dae9bb7a9d09d2447.json`
- `python3 tests/Verification/change_verification_001_test.py`: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789527382609415000-1193a09609f94e4e90d63bc6392db125.json`

No local canonical full suite was run. The planner-selected full integration command remains for the one exact-source GitHub CI run after publication.

### Final assessment

No findings remain. The candidate implements one closed two-signature classifier through the existing `state`/`wait` seam; binds exact repository/PR/run/attempt/job/check/head/candidate facts; distinguishes integration/e2e preflight setup from product failures; fails closed for malformed, stale, generic and ambiguous neighbors; permits at most one attempt-1 same-source retry; retains prior-attempt history and complete diagnostic inventories; and keeps diagnostics separate from admission. It adds no broad semantic regex/LLM classifier, store, waiter, publisher/workflow permission, dispatch, product behavior, FAST classifier, merge or deployment action. Measurement remains limited to materialized diagnostic payloads, model triage steps and automatic retry count, with token usage honestly `UNKNOWN`.

Gate 5 is `APPROVED` for the exact candidate above. This approval does not substitute for the required exact committed-source GitHub CI result and authorizes no merge, deployment or settings change.

---

## Gate 5 rereview v4 — exact-source CI correction

- Reviewer: independent Gate 5 agent `/root/gate5_review` (authored no reviewed artifact)
- Reviewed base commit: `3137b47f8efe477ddb3a5b8dac2d313c203a7a1c`
- Reviewed candidate source: `ac18c11d2d009d2da23b36e653709876858edf0bbafb8c006d5d872d88847ec6`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T032030Z-e7b4d6bc45/package.json`
- Snapshot patch SHA-256: `343211a75b1a9a30b1b0cc9fc9d9706bb20a278646b55596248b5994c2b791e0`
- Verification plan SHA-256: `2c00af0fafeb4f98a659c0a9e92454628e1d95134f6ae275b352c19fd49465ac`; CRITICAL with required reviews `gate3`, `final`
- Gate 3 test-delta rereview v8: `APPROVED`
- Superseding verdict: `APPROVED`

### Failed-CI disposition

The first exact-source CI run `35050060163` on commit `3137b47f8efe477ddb3a5b8dac2d313c203a7a1c` had one primary failed job, `unit` (`104648309184`), plus downstream aggregate `verify` (`104650909742`) and graph result `Quality Graph` (`104651097052`). The complete unit log identifies only `tests/Verification/delivery_execution_107_i1_test.py`: 23 assertions received exit 1 from the `state` alias where the established admission alias contract expected parity with `admission` and exit 0 for those merge-ready observations. `verify` then reported only the expected aggregate consequence of unit failure. No product, integration, e2e, governance, setup or known-signature failure was present.

The T03 classifier disposition was correct: the failed job was outside both closed known signatures, so classification remained `UNKNOWN`, retry was denied, complete failed-job references were retained, and no diagnostic job-log payload was materialized before that decision. No same-source retry was attempted. Normal bounded triage, rather than signature inference, identified the deterministic compatibility regression.

### Correction and verification

The implementation delta removes only the two-line `state`/`wait` CI-status exit override from `command_admission`; all explicit-observation aliases again return success exactly when the existing admission result is `merge_ready`. This preserves diagnostic/admission separation: a successful retry may make CI status successful, but cannot make an unreviewed or otherwise inadmissible candidate exit successfully. The T03 test now asserts that inherited alias contract rather than the incompatible CI-status override. No classifier, signature, collection, history, retry, publisher, workflow or authorization behavior changed.

All correction evidence binds start/end candidate source `ac18c11d2d009d2da23b36e653709876858edf0bbafb8c006d5d872d88847ec6` and executable source `ef59ecf74935b760b9b86aaf6f457262a0e5912616eacd300265eeb0fa3fe820`:

- `python3 tests/Verification/delivery_harness_ci_triage_001_test.py`: GREEN, `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789528711790317000-6e1619557b3d48c490cecf7555b5d508.json`
- `python3 tests/Verification/change_verification_001_test.py`: GREEN, `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789528787936580000-dd4238eac0b8424182516ff7a3b2fae3.json`
- `python3 tests/Verification/delivery_execution_107_i1_test.py`: GREEN, 18/18 tests, `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789528719613804000-b5ba31340796469b8f5c8a25aeb9975c.json`

### Final assessment

No findings remain. The correction is the minimum compatible change, preserves the full approved T03 contract and resolves the complete observed CI regression without misclassifying it or spending the one-retry permission. Gate 5 is `APPROVED` for candidate `ac18c11d2d009d2da23b36e653709876858edf0bbafb8c006d5d872d88847ec6`. A fresh exact committed-source GitHub CI result is still required; this review authorizes no merge, deployment or settings change.
