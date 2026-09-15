# Test review: CHANGE-VERIFICATION-SEMANTIC-CLOSURE-001

- Reviewer: independent Codex reviewer `/root/gate3_review`; authored neither the contract nor the tests
- Test author: root delivery agent
- Reviewed source: candidate source `44f0b65ec440d5e3ce3ce963a23d511f6af1c8418c3158fefb49900180afbba0`; base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T190017Z-bde80bc77f/snapshot/source.patch`, SHA-256 `645c002c247642624700a0361fd70d1ab9b567635a7c266ff52543d789f86984`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T190017Z-bde80bc77f/package.json`; plan SHA-256 `2eee72873f3f4cb55eba77e0fd8248e6649ac109e0325bada22fd86857f855a1`
- Agreed review scope / prior findings disposition: initial Gate 3 review of #153 Slice A matrix A-L and the PR #148-style direct-GREEN/integration-omitted failure class; no prior findings
- Specification: `specs/CHANGE-VERIFICATION-SEMANTIC-CLOSURE-001.md`
- Public seam: disposable repository invoking public `change-verification.py plan` and `check`
- Red command and intended failure: `python3 tests/Verification/change_verification_semantic_closure_153_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789498806634154000-aff686c767144ee8a0cbd96aaf97815d.json`; exit `1`, `INTENDED_RED`. Seven test methods ran: H/I and J remained green; A-D, E, F/G, K and L failed for omitted semantic integration behavior. The direct fixture itself exited `0`, while the pre-Slice-A plan omitted integration closure.
- Verdict: `RETURNED` (`CHANGES_REQUESTED` at Gate 3)

## Findings

1. **Blocking — the fixture cannot prove that closure comes solely from the complete canonical integration inventory.** In `tests/Verification/change_verification_semantic_closure_153_test.py:36-38,63,108-117`, the inventory contains only one integration verifier and `category_argv["integration"]` contains that identical command. An implementation that reads only the representative policy `category_argv`, never derives the registered verifier set from `suites.tsv`, and therefore preserves the #148 failure class would satisfy the positive assertions. This misses contract clauses 2-3 and matrix G/L's category-level closure requirement. Add at least two registered integration verifier commands, make policy `category_argv["integration"]` deliberately incomplete or distinct, and assert the exact complete sorted inventory-derived set in both plan commands and `semantic_escalations[].added_checks`.

2. **Blocking — stable and sorted machine-readable evidence is not tested.** At `tests/Verification/change_verification_semantic_closure_153_test.py:114-117`, single-path/single-check fixtures make sorting unobservable and `assertTrue(item["reason"])` accepts any changing or unrelated prose. Contract clause 5 requires stable reason plus sorted changed paths and sorted commands/checks for each escalation. Exercise one surface with multiple changed paths supplied out of order and multiple inventory checks supplied out of order, then assert exact sorted values and the exact policy-owned reason. Also assert deterministic ordering when more than one semantic surface is matched.

3. **Blocking — malformed or ambiguous semantic policy metadata has no fail-closed coverage.** The accepted design task 2.1 requires validation of malformed/ambiguous metadata, while the contract requires deterministic repository-owned classification and stable surface evidence. The candidate only tests valid metadata and absence of integration inventory. Add table-driven public-seam rejection cases for the foreseeable invalid forms introduced by this slice: duplicate surface names, missing/empty name, patterns, reason or category, unsupported category, and overlapping definitions that make one changed path ambiguous (or specify and test the deterministic multi-match rule if overlap is intentionally valid). Assert nonzero status and stable diagnostics so invalid policy cannot silently disable or distort closure.

Other Gate 3 qualities were checked: A-D and the fifth domain-contract surface are traceable; E, F/G, H/I, J and K cover the stated positive/negative lane cases; the disposable Git repositories are isolated from production/network state; the retained RED is sensitive to the missing pre-Slice-A behavior and fails for the intended reason. The findings above prevent approval because plausible non-conforming implementations can still turn the suite green.

## Required changes

- Strengthen the canonical inventory fixture and assertions as finding 1 describes.
- Add observable exact reason and ordering coverage as finding 2 describes.
- Add fail-closed semantic metadata validation coverage as finding 3 describes.
- Re-run the focused RED command on a newly prepared exact source and submit the complete corrected test delta for independent Gate 3 rereview before implementation.

---

## Gate 3 rereview — corrected RED source

- Reviewer: independent Codex reviewer `/root/gate3_review`; authored neither the contract nor the corrected tests
- Reviewed source: candidate source `cf67f88355b6a36511a62e9c6ece2ec98e2821824f44e6e3f42d6e4bcef804f1`; base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T190352Z-89b7f34904/snapshot/source.patch`, SHA-256 `6c0cb8cfc3f8dfc34b139eef82640c06c9653a5c8acc9527e86875226fb7f947`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T190352Z-89b7f34904/package.json`; plan SHA-256 `4f61323da061f17ce7e8ada3c6369313063b2820130ba4dbe7c108752739e8dc`
- Corrected test blob: SHA-256 `d7a4ad175da6695c6d515fd68f42c5c60b5e52b0ccad732963d0070bd0f6d3fd`
- RED evidence: `python3 tests/Verification/change_verification_semantic_closure_153_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789499019326088000-d443f429979e49d4bfc1f52e7ae02d80.json`; exit `1`, `INTENDED_RED`. Presentation/docs and healthy FAST controls remain green; all missing semantic behavior, metadata validation and deterministic overlap cases fail against the pre-Slice-A planner for the intended reason.
- Prior finding 1: substantially resolved. The fixture now registers three integration verifiers while `category_argv` contains only the representative verifier, and asserts inventory-only consumers are included.
- Prior finding 2: partially resolved. Exact policy reason, sorted per-escalation paths/checks and deterministic surface/multi-match order are asserted; emitted plan command ordering remains untested as described below.
- Prior finding 3: resolved. Duplicate, empty, missing and unsupported semantic metadata receive table-driven fail-closed diagnostic assertions, and overlap has an explicit deterministic multi-match contract.
- Rereview verdict: `RETURNED` (`CHANGES_REQUESTED` at Gate 3)

### Rereview finding

1. **Blocking — exact sorted closure is still not asserted in the emitted plan command sequence.** At `tests/Verification/change_verification_semantic_closure_153_test.py:117-122`, `selected_integration = sorted(...)` normalizes the planner's actual output before comparison. A planner that emits the complete canonical verifier set in nondeterministic or reverse order would pass this assertion. The original finding required the exact complete **sorted** set in both plan commands and `semantic_escalations[].added_checks`, and contract clause 5 requires sorted commands/checks. Preserve the emitted integration subsequence without sorting it, then compare that actual sequence directly with `self.integration_checks` (or assert the applicable exact ordering of the complete `plan["commands"]`). The existing exact `added_checks` assertion may remain.

### Required change

- Remove test-side sorting of the emitted plan integration commands and assert their actual order directly against the canonical expected sorted order; capture a newly prepared exact-source RED record and resubmit the bounded delta for Gate 3 rereview.

---

## Gate 3 final rereview — emitted ordering correction

- Reviewer: independent Codex reviewer `/root/gate3_review`; authored neither the contract nor the tests
- Reviewed source: candidate source `750b903674404c76da12c29e7182a4379ab1d74d058fbdf07eca49f87cd029f7`; base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T190545Z-4668a9b81c/snapshot/source.patch`, SHA-256 `027a613608e17740ad4fe560b42cfeef8dd38dd3dae2198627ec89945ccf6a4a`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T190545Z-4668a9b81c/package.json`; plan SHA-256 `d611ba8cb975a68df128c4eac31da1ef30ee6edc5ae43b37619acf57af40a226`
- Corrected test blob: SHA-256 `b828cb12c9abc94c260836078b84503912e5d43d9334b7fd46e56afa90446719`
- RED evidence: `python3 tests/Verification/change_verification_semantic_closure_153_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789499132165363000-54ca66ea566740cc9c97ffd7a206a9a6.json`; exit `1`, `INTENDED_RED`
- Prior findings disposition: all resolved. In particular, `selected_integration` now preserves the actual emitted plan command subsequence and compares it directly with the independently sorted canonical expected sequence; no test-side normalization can hide incorrect planner ordering.
- Final verdict: `APPROVED`

### Final findings

None. The corrected suite is traceable to matrix A-L, exercises the public planner/check seam, distinguishes the complete canonical inventory from incomplete representative `category_argv`, asserts exact deterministic paths/reasons/checks/surface and overlap ordering, covers fail-closed metadata and unavailable closure diagnostics, preserves negative presentation/docs/FAST controls, and retains an isolated intended RED for the #148-style failure class.

### Required changes

None. Gate 3 may advance to minimal implementation against this exact approved test source.

---

## Gate 3 restart — shipped-policy false-negative regression

- Reviewer: independent Codex reviewer `/root/gate3_review`; authored neither the contract nor the new test delta
- Restart reason: Gate 5 identified a real repository-policy false negative for `app/YiiRuntime/FeedbackApplication.php`; the test change reopens Gate 2/3 before correcting planner policy
- Reviewed source: candidate source `b18ca2b06ab71973b8013e918bc94078d1c14609901eef1408f2965dcedf3d9b`; base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T191707Z-24a4e0becd/snapshot/source.patch`, SHA-256 `45cfd8fe6ae9abd5a2384005a61623fb9edbc1da2f22e8cfedf1a46f77eae01b`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T191707Z-24a4e0becd/package.json`; plan SHA-256 `d0a173ba3463cd4835506995e5d6990f58d2c63685399853bb79f541cfc9f6a3`
- Test blob: SHA-256 `805e65284db225f982f988e170a5ab17cac101bf90178663c715c4b9644f088a`
- RED evidence: `python3 tests/Verification/change_verification_semantic_closure_153_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789499813110425000-f6c1ac4cc2824529be2693c479efa7f3.json`; exit `1`, `INTENDED_RED`. Ten previously approved cases are GREEN; only the new shipped-policy case is RED with `INTENDED_RED shipped Yii application contract is unprotected`.
- Verdict: `APPROVED`

### Restart review findings

None. `test_repository_evidenced_yii_application_contract_is_protected` copies the repository's shipped `.quality-graph/verification-policy.json` and canonical `tools/verification/suites.tsv` into the isolated disposable repository, materializes inventory paths so planner validation is meaningful, commits that exact fixture baseline, changes `app/YiiRuntime/FeedbackApplication.php`, invokes the public planner seam, and independently requires a `domain-application-contract` escalation containing that exact path. The focused RED isolates the observed policy false negative rather than failing setup or weakening earlier matrix A-L expectations.

### Required changes

None to the test. Gate 3 is re-approved for minimal policy/planner correction against candidate source `b18ca2b06ab71973b8013e918bc94078d1c14609901eef1408f2965dcedf3d9b`; the corrected production/policy delta requires a new independent Gate 5 decision.
