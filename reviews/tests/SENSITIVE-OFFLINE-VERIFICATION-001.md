# Test review: SENSITIVE-OFFLINE-VERIFICATION-001

- Reviewer: independent Codex reviewer `/root/issue132_gate3`; authored neither the contract nor the tests
- Test author: root delivery agent
- Reviewed source: candidate source `b18d2d51bc42f990b11773c71b50fcba501adb9f7d08dbca6f8c08f4f9500d01`; executable source `80a4e9aad99bf557dfdd32503cdcda8fc58fdd2b066abf823f511c2d133962de`; base `22f3132bb1dfcf3c6758a66ed89aff5812d516a8` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T203903Z-6e60ca1bfa/snapshot/source.patch`, SHA-256 `962db53c8c54c0b07e0f33ae7de42b0f149a4e86e18b71f193f667b5cfe4f094`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T203903Z-6e60ca1bfa/package.json`; plan SHA-256 `3115a40a1ddc3a2d013bbf23eaee6d39bc41e49904b5cdf9ac01cd3eccbc152d`
- Agreed review scope / prior findings disposition (for rereview): initial Gate 3 review of issue #132 owner matrix A-L; no prior findings
- Specification: `specs/SENSITIVE-OFFLINE-VERIFICATION-001.md`, SHA-256 `bf4999a8b3b84b78750ab396cf36e64a94c4a6569ce1f36b70906699b21529d2`
- Public seam: isolated disposable repository invoking `python3 tools/delivery/change-verification.py plan` with shipped policy and canonical inventory
- Red command and intended failure: `python3 tests/Verification/change_verification_sensitive_offline_132_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789504731368686000-f4f6a857d00d419e9602b98b4163d1b8.json`; exit `1`, `INTENDED_RED`. Six test methods ran: the non-sensitive/policy controls and unknown-path fail-closed case remained green; all three exact sensitive paths planned `FAST`, sensitive plus bounded UI planned `FAST`, sensitive plus semantic planned `STANDARD`, and shipped policy had no `sensitive-offline-ui` boundary.
- Verdict: `APPROVED`

## Findings

None. The normative specification makes the repository-owned closed set, CRITICAL route, required reviews, direct registered oracle/category, monotonic mixed-path behavior, negative controls, deterministic whole-file classification, and fail-closed rejection observable at the public planner seam. The A-L regression is traceable to those requirements and independently fixes the expected values. It exercises each exact protected path, mixed bounded UI, delivery-policy, presentation-only asset, server-rendered view, lifecycle metadata, semantic integration composition, unknown protected-area classification, and exact shipped-policy explanation. It also requires the existing oracle command and canonical `e2e` category without introducing another inventory.

The fixture copies the planner, inventory implementation, shipped policy and canonical suite inventory into a temporary Git repository, materializes required paths locally, and uses no production or network state. The retained RED fails on the missing pre-#132 classification rather than setup; its green negative controls demonstrate that the fixture still reaches existing planner behavior. Authorization, persistence, replay, audit/history, deployment, backup/restore and runtime user-return behavior are unchanged by this policy-only slice and are correctly recorded as out of scope or invariant.

## Required changes

None. Gate 3 may advance to minimal policy implementation against the exact approved test source SHA-256 `042a696931c60ff4183b52621a1cfd9cabf18961492261baada1391ed0fc25ba`.

---

## Gate 3 restart — missing canonical oracle registration

- Reviewer: independent Codex reviewer `/root/issue132_gate3`; authored neither the contract nor the corrected test
- Restart reason: Gate 5 found that shipped policy admitted a boundary oracle absent from canonical `tools/verification/suites.tsv`; the root-authored test delta reopens Gate 2/3 before implementation correction
- Reviewed source: candidate source `d0d854be9530bb369e44bac95bdc2cb43b70e732ae404a131536b865ab6ec139`; executable source `9c340a817e42023e224db082061dea397074b6754bc12a69312665a8a914a5da`; base `22f3132bb1dfcf3c6758a66ed89aff5812d516a8` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T204603Z-2244e2b56e/snapshot/source.patch`, SHA-256 `7a031822d5240cd50b5df4415f4efc19adb81a5ba53fd120c6ade221a7ad7e16`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T204603Z-2244e2b56e/package.json`; plan SHA-256 `a9cedd75956f09d5b1ac1f415588af087938b31efef3f79158dacb6370fd44e4`
- Specification: unchanged `specs/SENSITIVE-OFFLINE-VERIFICATION-001.md`, SHA-256 `bf4999a8b3b84b78750ab396cf36e64a94c4a6569ce1f36b70906699b21529d2`; requirement 3 and the rejected-case contract already require a registered, correctly categorized direct oracle and fail-closed rejection when it is missing
- Corrected test: `tests/Verification/change_verification_sensitive_offline_132_test.py`, SHA-256 `079293140f6618cbbfb485369078f95781d8f5b78608657e95af333c543ec3f5`
- RED evidence: `python3 tests/Verification/change_verification_sensitive_offline_132_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789505149617659000-85ce110b2d2f43a79b17b8902891dd2d.json`; exit `1`, `INTENDED_RED`. Seven test methods ran; the six previously approved boundary, composition, negative-control and fail-closed cases are GREEN. Only `test_registered_direct_oracle_is_required_by_shipped_policy` fails because the planner returns `0` after the fixture removes the oracle's canonical inventory row.
- Verdict: `APPROVED`

### Restart findings

None. The new regression directly exercises the missing-registration half of the existing normative rejection at the public planner seam. It copies shipped policy and canonical inventory into the isolated repository, removes only the row containing `tests/Yii2/yii2_inspection_browser_001_test.php`, plans an exact sensitive path, and independently requires nonzero status, no emitted plan, and `SETUP_FAILURE`. The otherwise valid inventory and the six GREEN pre-existing cases keep the failure attributable to absent canonical registration rather than general fixture setup. The expected value is derived from the normative contract, not from the planned validation implementation, and the test uses no network or production state.

### Required changes

None to the specification or test. Gate 3 is re-approved for the minimal fail-closed inventory-validation correction against exact test source SHA-256 `079293140f6618cbbfb485369078f95781d8f5b78608657e95af333c543ec3f5`; the corrected implementation requires a new independent Gate 5 decision.
