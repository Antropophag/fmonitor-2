# Gate 3 correction rereview: YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001

- Reviewer: independent Gate 3 agent `/root/gate3_review`; authored none of the reviewed specification, lifecycle artifacts, tests, browser helper, or production code.
- Review date: 2026-09-22.
- Corrected exact commit: `2d4ebe3b7e9aed44a3305000905dcf6baf27c23f` (`test: cover construction control filter matrix`).
- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T235141Z-52b1fe520d/package.json`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T235141Z-52b1fe520d/verification-plan.json`, SHA-256 `a1161ac7919b80f40a51e127ba21e4cfd06baadc021fca9ba1bffc63a848f647`.
- Prepared head: `e59850f8049c6688a1c9f9422ddd68c1bf210f8e`; executable source digest `263fbdd5561d837476c3eeb18f951a0e76e38062883b7cf146608d9d819b65e0`. The subsequent commit changes metadata only; reviewed test/helper bytes match the plan bindings.
- Corrected test SHA-256: `335bb1e4b508aa906c8ef49b252f10331ec40dbf81bafd202033339f21fad3be`.
- Browser helper SHA-256: `8a5f9d0e6e7b7612f14df772b7e37938c7bf37c1f732d6464d8b7ad7f08cbf`.
- Planner decision: `CRITICAL`; required reviews: `gate3`, `final`; `missing_tests` is empty.
- Fresh reported browser-profile RED: exit `255`; default-mine assertion at current test line 12 expected one row and observed 50. Setup reached real Yii HTTP on the disposable database; source was bound to the prepared digest above.
- Verdict: `CHANGES_REQUESTED`.

## Prior-finding disposition

1. **Source binding: resolved.** The corrected plan binds both the PHP test and browser helper hashes, reports no missing test, and committed bytes are identical to prepared bytes. The fresh RED fails at the first new default-mine public-seam assertion for the intended absent server filtering, not fixture setup. The package itself still has an empty `evidence` array, so the final correction should retain the next RED record/path explicitly rather than relying only on the handoff summary.

2. **Ownership/authorization: partially resolved.** The corrected matrix now proves default mine, explicit combined mine+query, foreign-current exclusion, effective-detail search, and permission denial for `all`. It still does not distinguish current native ownership from historical-order/legacy-author fallback, an explicit normative rule and prior finding.

3. **Query/completion boundaries: substantially resolved.** Corrected address and registration details, trim/case behavior, literal LIKE metacharacters, malformed scalar shapes, the 161-character rejection, canonical completed inclusion/default exclusion, and PTO-only exclusion are now represented with independent fixtures. The accepted 160-character boundary is not directly exercised, but that omission is not independently blocking once the larger remaining findings are corrected.

4. **COUNT/page/URL behavior: partially resolved.** A 55-row filtered set now exercises two pages, unique membership, total text, filter-preserving page-2 link, refresh identity, and empty display. Controlled out-of-range behavior and an independently specified stable order remain absent; the browser page-reset assertions do not actually observe a transition.

5. **UI/offline preservation: open.** The added Playwright helper counts initial rows/total, but its sync/prefetch assertion is still only a source-name search and it does not exercise shipment disclosure, checklist/photo navigation, IndexedDB operations, sync, or prefetch.

6. **HEAD/read-only envelope: resolved for the bounded seam.** Valid and representative invalid HEAD requests are covered with empty bodies. The before/after inventory now includes facts, assignments, and effective-detail edits; the inherited permission denial is exercised.

## Remaining findings

1. **BLOCKING — the native-current ownership rule is still insensitive to prohibited fallback sources.** `tests/Yii2/yii2_construction_control_server_filtering_001_test.php:7-12` gives every new case a current native assignment and only varies whether its engineer is user 73 or 94. No object has a historical assignment to the actor followed by a current foreign assignment, and no object is actor-owned only through legacy `responsstroicontrol`/order-author data. An implementation that ORs current ownership with historical or legacy ownership would pass every assertion. Add deterministic historical-then-reassigned and legacy-only fixtures, require both absent from mine and available under all, and keep the positive current-native tail witness.

2. **BLOCKING — out-of-range and stable-order semantics remain unproved, while the browser reset checks are vacuous.** The PHP test exercises pages 1 and 2 but never asserts the contract's controlled out-of-range response and never derives expected row order independently; unique membership plus repeat equality permits a consistently unstable or incorrectly ordered query. In `tests/Support/construction_control_server_filtering_browser.cjs:2`, both `pageReset` and `clearReset` merely test whether serialized form data contains a `page` field. The rendered filter form has no such field initially, so both values are true even if input/change/clear handlers do nothing and an old `page=2` remains in the URL/submission target. Add an out-of-range request with the specified controlled result/no substituted rows, assert ordered object IDs from fixture facts, and initialize a page-2 URL/state then observe the actual submitted/navigation URL after filter change and clear (or intercept form submission and assert page omission plus current filters).

3. **BLOCKING — preservation coverage remains lexical rather than behavioral.** The helper's `syncFunctions` result is computed by `source.includes` for three function names, exactly the weakness identified in the first review. It can pass with dead functions while local operations, IndexedDB state painting, sync requests, and prefetch are broken. It also does not observe the normative shipment indicator/disclosure or checklist/photo navigation. The initial 50-row/55-total assertion is useful and will catch the old `apply()` rewrite, but does not cover the rest of item 7. Add browser fixtures/stubs that seed a local operation and observe its row state, exercise or spy on sync/prefetch entrypoints without production I/O, and assert shipment interaction plus checklist/photo link behavior. Existing focused browser/shipment/checklist controls may be explicitly mapped where they already provide these observable witnesses; function-name presence alone is not sufficient.

## Assessment

The correction is a material improvement: expected values are based on isolated fixtures, the public HTTP seam is real, malformed reads remain fact-free, effective details and literal query behavior are represented, and the fresh RED is sensitive to the central default-mine defect. Determinism is adequate for the covered cases, including fixed fixture IDs and a disposable database.

Gate 3 still cannot approve because three previously identified normative areas can be false while the suite is GREEN. These are bounded test corrections; they do not require widening production scope or changing the accepted contract.

## Required correction

Correct the three remaining findings as one Gate 2 delta. Regenerate the plan/package for the changed test/helper bytes, retain a fresh exact-source RED record with its path and no source drift, and request independent Gate 3 rereview before implementation. CI and deployment remain `UNKNOWN` and are not approval or GREEN.

## Verdict

`CHANGES_REQUESTED`
