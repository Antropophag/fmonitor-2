# Code review: SENSITIVE-OFFLINE-VERIFICATION-001

- Reviewer: independent Codex reviewer `/root/issue132_gate5`; authored neither the contract/tests nor the implementation
- Implementation author: separate executor dispatched by the root delivery agent; the prepared package does not record a more specific identity
- Reviewed source: base `22f3132bb1dfcf3c6758a66ed89aff5812d516a8` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T204323Z-55f4eda5e8/snapshot/source.patch`, SHA-256 `7187e5143a6e4a94abeef0657436dd374e49c4562e36bf70ccd776d814f983c7`; candidate source `32d2c0bd2d3e2b108b95c9a6e2d0288eca80326a18b849d19b8bc4216edcc105`
- Agreed review scope / prior findings disposition (for rereview): initial Gate 5 review of issue #132 complete bounded candidate; no prior Gate 5 findings
- Specification: `specs/SENSITIVE-OFFLINE-VERIFICATION-001.md`, SHA-256 `bf4999a8b3b84b78750ab396cf36e64a94c4a6569ce1f36b70906699b21529d2`
- Approved test review: `reviews/tests/SENSITIVE-OFFLINE-VERIFICATION-001.md`, Gate 3 `APPROVED`
- Verification commands: exact-source GREEN `python3 tests/Verification/change_verification_sensitive_offline_132_test.py` (record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789504935895955000-a4142dc67b7544e486ae65462330d26a.json`); exact-source GREEN `python3 tests/Verification/change_verification_001_test.py` (record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789504942829383000-2ba4a50dbad844f298b2b05034fc9c0b.json`); reviewer probe removing the oracle row from copied canonical inventory unexpectedly returned exit `0`, CRITICAL plan, and the oracle command
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — missing canonical registration does not fail closed.** `tools/delivery/change-verification.py` in the reviewed base, exercised through the changed `.quality-graph/verification-policy.json`, permits a boundary test absent from `tools/verification/suites.tsv`: `validate_policy_inventory()` rejects only a present test with the wrong category (`registered is not None and ...`) and accepts an unregistered test. The reviewer reproduced this at the public planner seam by removing `tests/Yii2/yii2_inspection_browser_001_test.php` from the copied canonical inventory; planning a sensitive path still exited `0`, selected CRITICAL, and emitted the oracle command. This violates normative requirement 3 and the rejected case “Missing/wrong-category registered direct oracle ... rejects plan.” The A–L test does not catch the missing-registration half: cases A–C supply the oracle as the acceptance test, while case L checks the boundary/reason but not canonical registration failure. Correction: make strict policy/inventory validation reject boundary tests absent from canonical inventory, and add a public-seam regression that removes the oracle registration and expects fail-closed `SETUP_FAILURE`; because this changes tests, recompute the plan and restart at Gate 2/Gate 3 as required by the delivery process.

2. **MEDIUM — OpenSpec task state is stale.** `openspec/changes/protect-sensitive-offline-fast/tasks.md:10` and `:11` leave tasks 2.1 and 2.2 unchecked although the policy implementation is present and both focused commands are recorded GREEN. This conflicts with `docs/development-process.md`, which makes OpenSpec the owner of lifecycle/task state. Correction: mark 2.1 and 2.2 complete when rebuilding the corrected candidate; leave Gate 5 and CI tasks pending until they actually complete.

The remaining reviewed behavior conforms: the exact three assets form one unambiguous `sensitive-offline-ui` boundary outside `bounded-ui`; CRITICAL and Gate 3/final precedence are preserved; the existing e2e oracle/category is selected; presentation-only FAST remains available; delivery-policy remains CRITICAL; #153A semantic integration closure composes monotonically; and the patch changes no runtime/product or `rapid-pilot/` files. No material Fowler code smell was found in the declarative implementation.

## Required changes

1. Enforce and test fail-closed rejection when a boundary's direct oracle is missing from canonical `tools/verification/suites.tsv`; recompute the verification plan and obtain the test review required by that plan.
2. Reconcile OpenSpec task checkboxes 2.1 and 2.2 with the completed implementation/focused verification before preparing the next exact review source.

---

## Gate 5 rereview — canonical registration correction

- Reviewer: independent Codex reviewer `/root/issue132_gate5`; authored neither the corrected test nor implementation
- Reviewed source: base `22f3132bb1dfcf3c6758a66ed89aff5812d516a8` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T205106Z-9bfd553cb9/snapshot/source.patch`, SHA-256 `68c33c5286e841407ccf9187db0568bedc2bcf61af001108abbdd1f6ac9f3cdc`; candidate source `75e3e98978152563d44414f5f24ed25b02322ed754816d9e2a0429055ace0890`; correction delta SHA-256 `0a1c42d94e53caf0f7dbaa3b8817f68fe8cdd8f6a8d3abd889624ebb9e7b132b`
- Agreed review scope / prior findings disposition: rereview the HIGH canonical-registration correction and MEDIUM OpenSpec task correction, plus regressions and scope growth caused by the delta
- Updated approved test review: `reviews/tests/SENSITIVE-OFFLINE-VERIFICATION-001.md`, Gate 3 restart `APPROVED` for corrected test SHA-256 `079293140f6618cbbfb485369078f95781d8f5b78608657e95af333c543ec3f5`
- Verification commands: exact-source GREEN `python3 tests/Verification/change_verification_sensitive_offline_132_test.py` (record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789505405993694000-2d52682f85f14c0fa6778c5cc85c81d9.json`, 7 tests); exact-source GREEN `python3 tests/Verification/change_verification_001_test.py` (record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789505413085513000-dface69d75054a9f8decb9b6dd99abe8.json`)
- Verdict: `APPROVED`

### Findings disposition

1. **HIGH resolved.** `tools/delivery/change-verification.py` now rejects `registered is None` for every boundary test before category comparison. The root-authored public-seam regression removes the direct oracle row from the copied canonical inventory and requires nonzero status, no plan, and `SETUP_FAILURE`; its intended RED received a fresh independent Gate 3 approval, and the exact corrected source is GREEN. Wrong-category rejection remains intact. This is the minimal general correction to the existing strict boundary inventory validation rather than an issue-specific bypass.
2. **MEDIUM resolved.** `openspec/changes/protect-sensitive-offline-fast/tasks.md` now marks implementation and focused verification tasks 2.1 and 2.2 complete while correctly leaving Gate 5/CI delivery tasks pending at the reviewed point.

No new findings. The correction delta is limited to strict planner validation, its focused regression, required Gate 3/review history, and lifecycle bookkeeping. The complete snapshot preserves the previously approved exact sensitive boundary, canonical e2e oracle/category, presentation-only FAST behavior, delivery-policy CRITICAL behavior, and monotonic #153A composition. It contains no runtime/product or `rapid-pilot/` file changes. Exact-source GitHub CI remains a post-review delivery step and is not represented as GREEN here.

### Required changes after rereview

None.
