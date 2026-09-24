# Test review: YII2-ORIGINAL-OBJECT-IDENTITY-001

- Reviewer: `issue243_gate3` (independent Gate 3 reviewer; did not author the specification or tests)
- Test author: `root` (commit author recorded as Timofey Grishin)
- Reviewed source: candidate source `cc5bb45f308dccada7f71babaa3659dea977d47b4d47416f6f0f41e3e41278cc`; Git commit `b9099b0e3aa654f28570e7aa22e0502031156054`; base `b1542f92009b8dc4216a36962ff38a51e0b6c388`; prepared package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T020608Z-86560a05be/package.json` (SHA-256 `0eee798e73ef79770504e53570aa1312d6701c69083175ee634c1bd20c8450c4`)
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T020608Z-86560a05be/verification-plan.json`; SHA-256 `59473ded70b216be9616abb89cdd17b5b5ad1eddfe44b51d120ae282fba78063`; embedded verification plan digest `6dba2c9067726ae94738ff4915836040cb13a0a0f1796e8325950ed1119a3ec6`; lane `CRITICAL`; required reviews `gate3`, `final`; required categories `e2e`, `governance`, `integration`, `unit`
- Required context: task-context manifest SHA-256 `fbaa04deeb2b808f2775e6ba12fb8d97f7449225058ca98587fa44cb51b32110`; required-context SHA-256 `386682b1a6b81b33d0a611537a1d1b5286dbdcc6463082887f17c6a7bf588133`; all inline required documents and the complete prepared source/spec/test/evidence set were reviewed
- Agreed review scope / prior findings disposition: first Gate 3 review; no prior findings
- Specification: `specs/YII2-ORIGINAL-OBJECT-IDENTITY-001.md`, examples A/B/C and acceptance A1–A8; OpenSpec proposal/design/delta/tasks were checked for consistency
- Public seam: real authenticated/denied Yii HTTP GET submit/history surfaces, existing POST upload/replay and download seams, plus Playwright desktop and 320px rendering on a disposable `PreopeningFixture`
- Evidence IDs: RED record `1790215555427665000-5f9fba34313440608b6f124b0f757d98` (`php tests/Yii2/yii2_original_object_identity_001_test.php`, exit 255, intended failure `initial effective identity contains TEST-4512`); regression record `1790215489816517000-05d6abb72391485a8fee91536793f157` (`php tests/Yii2/yii2_original_transport_001_test.php`, GREEN)
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — A1 does not prove the required breadcrumb identity.** `tests/Yii2/yii2_original_object_identity_001_test.php:23` only searches the complete response for each expected value and excludes two legacy strings. An implementation can place `TEST-4512` only in the form header while leaving the breadcrumb without the required effective registration number, and the test passes. OI-01.1 and A1 explicitly require both the initial form and its breadcrumb. Assert the breadcrumb (and the intended primary form identity block) through scoped DOM/browser selectors rather than response-wide substring presence. Apply equivalent scoped coverage to the correction breadcrumb required by OI-01.1.

2. **High — A6/read-only evidence is incomplete for correction and history GETs.** `tests/Yii2/yii2_original_object_identity_001_test.php:41-45` snapshots only original revision rows and the private-file listing after the manual edit. Unlike the initial GET check at lines 21-24, it does not compare all domain/audit facts before and after correction/history reads. It also does not capture historical download bytes before those GETs and compare the same bytes afterward; the downloads at line 61 establish integrity only at the end. A GET that writes an audit/domain row while preserving original rows/files, or mutates and consistently updates stored hash/bytes before the sole download observation, can pass. Capture a post-setup/pre-GET baseline of all facts, original rows, private file hashes and both historical bytes, then compare it after the applicable authorized GET sequence. Keep the deliberate fixture manual edit outside that comparison.

3. **High — the security test proves response secrecy but not deny-before-effective-read ordering.** At `tests/Yii2/yii2_original_object_identity_001_test.php:58-60`, guest/no-read/unavailable responses are checked for status and leaked strings, but no observable probe establishes that the effective-details owner was not called before original-surface admission. A controller can read manual/imported details first and discard them before returning 403/404, satisfying these assertions while violating OI-05.2 and the design decision “Effective read performs only after existing admission.” Add deterministic instrumentation at the DB/query or replaceable-resource boundary that proves zero effective-details reads on denied/unavailable submit and history requests. Cover unavailable order/history as well as the currently tested missing-object submit route.

4. **Medium — A8 omits the correction form browser surface.** `tests/Yii2/original_object_identity_browser.mjs:11` routes every mode other than `initial` to `/history`; therefore `manual` and `missing` never render the correction form. The PHP test checks correction HTML strings, but it cannot detect narrow-screen overflow, invisibility, or hostile-value DOM behavior on that form. OI-06 applies to initial/correction/history, and the design/proposal explicitly promise browser evidence for all three. Add a distinct correction browser mode at 1440×900 and 320×568, including hostile text/non-element assertions and page-overflow checks.

The remaining reviewed properties are sound: the specification identifies the actor, public seams, explicit missing-number behavior, technical/document identity, authorization outcomes, escaping and responsive requirements; examples provide independently authored literal expected values rather than production-helper-derived expectations; the intended RED is sensitive to the missing effective identity and fails before browser execution for that exact reason; the transport regression is GREEN; the fixture uses unique disposable DB/storage/session/runtime resources and deterministic fixed business inputs; replay, correction append-only rows, immutable first revision, dates/hashes and two exact downloads are exercised. Root-authored spec/test and separate-executor authorization are recorded, and no product implementation is present in this Gate 3 candidate.

## Required changes

- Scope assertions to prove effective identity in both the breadcrumb and primary object-context block on initial and correction surfaces.
- Strengthen A6 with before/after all-facts and exact historical-byte/hash evidence around every authorized GET surface after fixture state is established.
- Add an observable deny-before-read probe for guest/no-read/unavailable submit and history paths, including unavailable order coverage.
- Add desktop/narrow browser coverage for the correction form, including hostile DOM and overflow checks.
- Re-capture intended RED and the related regression on the corrected exact source, rebuild the prepared reviewer package, and return for independent Gate 3 rereview.

## Gate 3 rereview — corrected candidate 03b8576d

- Reviewer: `issue243_gate3` (same independent reviewer; authored neither specification nor test correction)
- Corrected exact candidate: `03b8576d6e9a8ee7ee8e2928c5a664d3d1527de7d1baffd3a8dedb62e82033ed`; Git HEAD `a0053a0205a5f82fd2e3faad1ff386a15596e64b`; correction delta from reviewed Git commit `b9099b0e3aa654f28570e7aa22e0502031156054`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T021209Z-4a0b6efbb5/package.json`; SHA-256 `224b7884dc2426511567d4df573af80e575790d47b25ac7e59e64d9164597505`
- Verification plan: SHA-256 `54e1f192373cb09d6e78d94baa9a2e4c480971432787768b8b7f2adec0b5d2e8`; lane `CRITICAL`; required reviews `gate3`, `final`; required categories `e2e`, `governance`, `integration`, `unit`
- Context manifest: SHA-256 `e1febd78f0abd5d7eab5d253eaa854590ff6f9bb2a15f8c421341f23b7ac0f98`; required context remains SHA-256 `386682b1a6b81b33d0a611537a1d1b5286dbdcc6463082887f17c6a7bf588133`
- Corrected RED evidence: record `1790215884552780000-a82e6fdb7698464c8b49c794a39c0057`, exact source `03b8576d…`, command `php tests/Yii2/yii2_original_object_identity_001_test.php`, exit 255, intended failure `initial effective identity contains TEST-4512`
- Regression evidence: record `1790215893488877000-c91b9e49deae4153849b4fa939068241`, exact source `03b8576d…`, command `php tests/Yii2/yii2_original_transport_001_test.php`, exit 0 / GREEN
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Fixed.** Initial and correction responses now extract the `.fm2-breadcrumb` and `.fm2-order-object` fragments independently and assert the required effective values in each. The implementation cannot satisfy A1 merely by placing the value elsewhere in the response.
2. **Fixed.** After the deliberate manual edit, the test captures all database facts, original rows, private-file inventory and exact historical bytes; correction/history/download GETs are followed by byte equality and full facts/rows/files equality. The setup mutation is outside the protected comparison.
3. **Fixed in behavior coverage.** The custom DB command records effective-owner query counts per request. Guest, no-read, missing-object and missing-order submit/history paths must show zero effective reads, while an authorized 200 control must show a positive read. This proves admission ordering rather than only response secrecy.
4. **Fixed.** Browser execution now separates `mode` from `surface` and exercises both form and history at desktop and 320px for manual hostile values and missing values. The correction form therefore receives the same visibility, overflow and hostile-script checks.

### New finding

1. **High — the deny-before-read probe breaks the existing runtime-boundary assertion after the intended RED is fixed.** `tests/Yii2/yii2_original_object_identity_001_test.php:13-14` replaces `PreopeningFixture`'s default router with a custom router that writes only `effective-read-probe.jsonl`. The default router in `tests/Yii2/PreopeningFixture.php:117-123` is the sole producer of `artifacts/includes.jsonl` records containing the fixture nonce and `get_included_files()`. The corrected test still calls `$f->noLegacy()` at its end, and `PreopeningFixture::noLegacy()` unconditionally reads `includes.jsonl` and requires at least one matching request trace. Current RED stops at line 26 before this latent failure. Once product behavior makes the identity assertions GREEN, the test cannot complete successfully: it will encounter a missing include trace rather than validate the no-legacy boundary. Preserve the standard include/nonce trace in the custom router (alongside the effective-read counter), or provide an equivalently strict combined trace consumed by `noLegacy()`, then demonstrate that the complete test can reach only the intended product RED before implementation.

### Matrix conclusion

A1–A8 mapping, expected-value independence, disposable resources, replay/append-only behavior, exact downloads, escaping, responsive coverage and deny-before-read semantics are otherwise complete in the corrected candidate. The new router setup regression prevents Gate 2 from being a runnable complete specification after implementation and therefore blocks Gate 3 approval.

### Required change

- Restore the `PreopeningFixture::noLegacy()` include-trace contract while retaining the effective-read probe; recapture exact-source intended RED and transport GREEN, rebuild the package, and return for rereview.
