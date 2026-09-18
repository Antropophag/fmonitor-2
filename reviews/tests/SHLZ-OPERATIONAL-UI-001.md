# Gate 3 test review — SHLZ-OPERATIONAL-UI-001

- Reviewer: separately tasked agent `/root/ui_gate3_review`; authored none of the reviewed specification or tests.
- Review date: 2026-09-18.
- Test author: root agent.
- Reviewed source: dirty snapshot over base `5bc6a2254bfaa4f5abef283795189d83f98348e3`; candidate source `0dec82036a58f312ccee6b125ab31a23615993aa450eb053f4915cfd4baa623e`; retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T175037Z-30cfc8d8e4/snapshot/source.patch`, SHA-256 `cc17929c1e783cfd271a8f0fff74d0eab96ccbb136a95b1aeb799055d6fb5c6a`.
- Agreed review scope: stable contract `specs/SHLZ-OPERATIONAL-UI-001.md`, root-authored tests and intended RED record `1789753821923885000-60adbedf0e6e4a36a8c2aea0f34b1e02` from prepared reviewer package `20260918T175037Z-30cfc8d8e4`.
- Specification: `SHLZ-OPERATIONAL-UI-001`, A1–A11.
- Public seam: authenticated Yii2 HTTP GET/HEAD/POST and rendered browser DOM below `/pilot/*`, led by object card and ОТиЗ.
- Red command and intended failure: `php tests/Yii2/yii2_shlz_operational_ui_001_test.php` exited `255` at the first assertion, missing `data-work-surface` in the object-card source. The record is exact-source bound, the command blob matches the reviewed test, and the failure is deterministic rather than setup-related.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **CRITICAL — the acceptance mapping claims A1–A11 through a private source-inspection test, not the specified public seam.** `openspec/changes/refresh-yii2-shlz-ui/verification-input.json` maps the aggregate `A1-A11-operational-ui-contract` only to `tests/Yii2/yii2_shlz_operational_ui_001_test.php`. Lines 7–28 of that test read PHP/CSS files and search for marker and token substrings. Comments, dead markup, unrelated global CSS, or unused selectors can satisfy those assertions while the authenticated rendered UI remains wrong. This conflicts with the declared HTTP/browser seam and the Gate 2 requirement to exercise observable behavior. Map A1–A11 individually to deterministic authenticated HTTP/browser witnesses; use rendered roles, names, states, computed styles, geometry, network requests and persisted facts as outcomes. Source checks may remain only as supplemental architecture guards.

2. **CRITICAL — the captured RED proves only the first marker is absent and provides no reachability evidence for the rest of the acceptance matrix.** The recorded command stops at `tests/Yii2/yii2_shlz_operational_ui_001_test.php:15` on `data-work-surface`; all later assertions are unexecuted. The prepared candidate also plans `tests/Yii2/shlz_operational_ui_browser.mjs`, but that file is absent, and the browser edits are not included in the acceptance's test mapping. Split independent cases or aggregate failures so every acceptance has a reachable intended RED (or explicit pre-existing GREEN characterization), capture that evidence, and regenerate the verification plan and reviewer package.

3. **HIGH — object-card and ОТиЗ state coverage is materially incomplete.** The marker/count assertions at `tests/Yii2/yii2_shlz_operational_ui_001_test.php:14-19`, `tests/Yii2/preopening_browser.mjs:41-45`, and `tests/Yii2/otiz_settlement_browser.mjs:31-37` do not prove A2 first-viewport containment of identity/address/status/engineer/action and semantic regions; A3 long-history, long-name, missing/corrupt technical data, unknown-date, and permission-limited states; or A4 blocking issue-to-object association and publication denial under blocking/unknown readiness. Add fixture-backed browser cases with explicit visible content/state and bounding assertions at the specified viewports.

4. **HIGH — responsive, accessibility, motion and progressive-enhancement requirements lack behavioral witnesses.** The browser edits check only root `scrollWidth` at four widths, while the PHP test searches CSS text. There is no 200% zoom/effective reflow case, overlap or clipped-control detection, table mobile strategy, measured 44×44 coarse targets, Tab/Shift+Tab order, focus-visible result, icon accessible names, hover independence, actual use of the motion tokens/easing, page-load choreography prohibition, removal of transform/stagger/stacking with retained focus/color feedback, or JavaScript-disabled/failed SSR and native form/link operation. Add browser-level geometry, keyboard, accessibility-name, computed-style/mutation, reduced-motion and JavaScript-failure cases for A5–A8.

5. **CRITICAL — permission, read-only and domain-preservation invariants A9–A10 are not covered by the mapped test.** No test matrix proves allowed versus denied action visibility/disabled state, GET and HEAD fact snapshots, exact href/form action/method/CSRF/request or operation IDs/revisions/return URLs/outcomes, idempotent replay, or stale/concurrent conflict without fact changes. Existing preopening and ОТиЗ flows exercise only selected happy paths and are neither mapped nor sufficient for these invariants. Add public HTTP tests with independently captured before/after facts, denied actors, HEAD/GET, replay and stale/concurrent cases, while retaining the exact established route/form payloads.

6. **HIGH — A1/A11 coverage does not span the named active screens or required states.** `tests/Yii2/yii2_shlz_operational_ui_001_test.php:25-28` checks one queue class and a brittle regex across only three source files. The regex misses multi-class attributes, reordered attributes, nested labels and the remaining active views. There are no route-specific witnesses for construction control, checklist, completion, installers or access administration, nor their real loading/empty/error/success/disabled/permission states. Exercise every named surface through rendered DOM semantics and state fixtures; assert command versus navigation roles and consistent status/focus behavior.

7. **MEDIUM — expected values overfit undocumented implementation markers and literal serialization.** `tests/Yii2/yii2_shlz_operational_ui_001_test.php:14-24` mandates `data-*` names and exact CSS substrings such as whitespace-sensitive easing text that the stable contract does not prescribe. A compliant alternative composition could fail, while inert strings could pass. Derive expectations from the stable user-visible contract; use test selectors only as locators and validate the resulting semantics, state, geometry and behavior independently.

The reviewed RED record and retained snapshot are internally consistent and exact-source bound. The failure is intended, deterministic and isolated; that evidence does not compensate for the missing behavioral reachability above.

## Required changes

Correct findings 1–7, provide complete public-seam A1–A11 mapping and reachable RED/characterization evidence, regenerate the prepared package, and resubmit the complete corrected test candidate for independent Gate 3 review before Gate 4 implementation.

---

## Correction rereview — 2026-09-18

- Corrected reviewed source: dirty snapshot over base `5bc6a2254bfaa4f5abef283795189d83f98348e3`; candidate source `a7cdf0a3fa3bbff556f69a8426939e4a0c91fda265e3bcd4c56879949c1e6271`; retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T180150Z-f21e9d3857/snapshot/source.patch`, SHA-256 `9a58a6210f62989444e1f74dea730ac088839c415c8e66ef1294d9917472f7bb`.
- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T180150Z-f21e9d3857/package.json`.
- Independence is unchanged; this reviewer authored none of the corrected artifacts.
- Corrected RED evidence: `php tests/Yii2/yii2_shlz_operational_ui_001_test.php` ran both real browser children and retained both failures: object card lacked the rendered work-surface marker and ОТиЗ lacked the rendered workflow marker. Exact-source records also show GREEN existing journeys for object queue, construction control/inspection, documentary closeout, installer directory and access administration.
- Correction verdict: `CHANGES_REQUESTED`.

### Prior findings disposition

1. **Partially resolved; still blocking.** The private PHP/CSS substring inspection is gone: `tests/Yii2/yii2_shlz_operational_ui_001_test.php:7-24` now invokes authenticated object-card and ОТиЗ browser suites, and five existing real browser journeys are mapped. However, the verification input still presents one aggregate `A1-A11-operational-ui-contract` rather than mapping each acceptance to its concrete assertions. The mapped GREEN journeys characterize selected existing workflows but do not demonstrate the new A1/A11 action/status/focus vocabulary or required state matrix.

2. **Partially resolved; still blocking.** The aggregate now executes both lead suites even when both fail, so object-card and ОТиЗ each have independent public-seam RED. Each child still aborts at its first new marker assertion (`tests/Yii2/preopening_browser.mjs:41` and `tests/Yii2/otiz_settlement_browser.mjs:31`), leaving all later new geometry, focus and motion assertions unreachable in the captured evidence. There remains no per-A RED or pre-existing characterization inventory proving reachability for A2–A11.

3. **Unresolved.** The lead suites still do not assert A2 first-viewport containment of registration/address/status/engineer/action and separate semantic regions; A3 long-history/name, missing/corrupt technical data, unknown-date and permission-limited states; or A4 blocking issue-to-object association and publication denial for blocking/unknown readiness. Rendered markers and primary-action counts alone are insufficient.

4. **Partially resolved; still blocking.** Four width checks, 44×44 bounding boxes, authored `zoom: 2`, and one Tab/outline assertion were added. Missing are overlap/occlusion and clipped-control checks, labelled-mobile-row or contained-scroll behavior, coarse-pointer emulation, a reliable 200% browser reflow witness, Tab and Shift+Tab order, actual `:focus-visible`, icon-only accessible names and hover independence. Motion still checks only one custom property; it does not prove actual token/easing use, absence of page-load choreography, transform/stagger/stacking removal, or retained focus/color feedback. No JavaScript-disabled or failed-script SSR/native link/form case exists.

5. **Unresolved.** The corrected mapping adds no permission matrix, GET and HEAD before/after fact snapshots, exact href/action/method/CSRF/request-or-operation-ID/revision/return-URL inventory, idempotent replay, stale/concurrent conflict, or comprehensive unchanged-fact witness for A9–A10. Existing happy-path workflow coverage does not establish these preservation requirements.

6. **Partially resolved; still blocking.** Queue, inspection/construction control, documentary closeout, installer directory and access administration now have exact-source GREEN journeys. Those tests do not assert the shared action/link/status/focus vocabulary or the required loading/empty/error/success/disabled/permission states across each named surface; checklist coverage is not separately identified. A pre-existing GREEN journey cannot prove an unasserted UI requirement.

7. **Partially resolved; still blocking.** Literal source serialization checks and the brittle command-link regex were removed. The principal new outcomes remain undocumented `data-*` marker presence (`preopening_browser.mjs:41-43`, `otiz_settlement_browser.mjs:31-33`), which inert markup can satisfy without the accessible roles, visible content, state, hierarchy or geometry required by the stable contract.

### New findings

1. **CRITICAL — the aggregate wrapper misclassifies every child failure as intended RED.** `tests/Yii2/yii2_shlz_operational_ui_001_test.php:17-23` collects any nonzero child exit and unconditionally prefixes the result `INTENDED_RED`. A missing browser/runtime dependency, fixture/bootstrap failure, timeout, unrelated regression or wrong assertion therefore becomes acceptable Gate 2 evidence. The retained harness record correspondingly has command verdict `REGRESSION_FAILURE`; only the wrapper's text labels it intended. Require structured child outcomes or exact expected assertion signatures, reject setup/infrastructure and unexpected regression failures, and bind each allowed RED signature to a specific acceptance.

2. **HIGH — the browser aggregate is registered in the wrong verification category.** `tools/verification/suites.tsv` registers `yii2_shlz_operational_ui_001_test.php` as `unit`, although it launches two complete browser/integration fixtures. This weakens category/isolation meaning and duplicates already registered browser journeys in the full matrix. Register it in the appropriate browser/e2e category, or remove the aggregate from the inventory and map the canonical child suites directly.

### Required changes

Resolve the new failure-classification and suite-category findings. Complete the per-A1–A11 public-seam mapping and add the missing observable state, geometry, accessibility, progressive-enhancement, permission, read-only, protocol, replay/concurrency and active-screen vocabulary witnesses. Capture reachable exact-source RED or explicit pre-existing characterization for each acceptance, regenerate the package, and resubmit before Gate 4.

---

## Full-matrix rebuild rereview — 2026-09-18

- Rebuilt reviewed source: dirty snapshot over base `5bc6a2254bfaa4f5abef283795189d83f98348e3`; candidate source `e813a7be46ae6f784efc95d7852bf6175875146707370dcb99c6670d3067fc32`; retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T181246Z-4c70921334/snapshot/source.patch`, SHA-256 `29249d1d5102a67fdb40633529ec671992c7f4f10e12c74094d8f38425374a1f`.
- Rebuilt package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T181246Z-4c70921334/package.json`.
- Independence is unchanged; this reviewer authored none of the rebuilt artifacts.
- Final Gate 3 verdict for this source: `CHANGES_REQUESTED`.

### Resolved findings

- The lead contract executes authenticated browser seams rather than inspecting private source strings.
- The aggregate wrapper allowlists the two expected missing-surface signatures, requires both, and reports other child failures as `REGRESSION_FAILURE`; the recorded failures are the expected assertions rather than setup failures.
- The aggregate is registered as `e2e`, not `unit`.
- The plan separates lead A1–A8, preservation A9–A10, and route-specific A11 groups.
- A9–A10 now have exact-source GREEN public HTTP/read/concurrency evidence from object-card, preopening-concurrency, ОТиЗ-concurrency and ОТиЗ-evidence-read suites. This materially resolves the former absence of preservation evidence, although the final delivery record should explicitly trace each enumerated HTTP/form invariant to its existing assertion.
- A11 now has exact-source route journey RED for object queue, construction control, documentary closeout/checklist return, installer directory and access administration, and those failures occur after their pre-existing workflow/state assertions.

### Remaining blocking findings

1. **CRITICAL — A1–A8 still lack reachable evidence for most of their requirements.** The A1–A8 mapping contains one aggregate wrapper and two browser children. Both children stop at their first new marker assertion (`tests/Yii2/preopening_browser.mjs:41`; `tests/Yii2/otiz_settlement_browser.mjs:31`). Consequently the retained RED demonstrates only absent shared-surface structure; later responsive, focus and motion assertions have not been reached, and no case at all witnesses several requirements below. Split A1–A8 into concrete cases or use aggregation inside each child so every required assertion has exact-source RED or explicit pre-existing GREEN characterization.

2. **HIGH — A2–A4 behavior remains under-specified by the tests.** The lead tests do not verify that registration/address/status/engineer and the single primary action are inside the 1024×768 and 1440×900 first viewport, that documents/composition/technical data/deadlines/history are distinct semantic regions, or that long names/history, missing or corrupt technical data, unknown dates and permission-limited actors remain usable. ОТиЗ does not assert blocking issue-to-object association or that publication is unavailable under blocking and unknown readiness. Add fixture-backed content, state and bounding assertions.

3. **HIGH — A5–A7 coverage remains partial.** Width/root-overflow and generic button-box checks do not detect text/control/region overlap or clipping, prove labelled mobile rows/contained table scroll, or emulate a coarse pointer. Authored CSS `zoom: 2` is not a reliable witness for user 200% browser reflow. One Tab plus `outlineStyle !== none` does not establish Tab/Shift+Tab DOM/visual order, `:focus-visible`, icon accessible names or hover independence. One reduced-motion variable does not prove actual 120/220/360 ms and easing use, absence of page-load choreography, removal of transform/stagger/stacking, or retained focus/color feedback.

4. **CRITICAL — A8 still has no executable witness.** No test disables or fails UI JavaScript and then verifies that all SSR content and native links/forms remain visible and operational.

5. **HIGH — A11 tests prove only marker adoption, not the specified vocabulary and states.** Each route journey ends with a `[data-work-surface]` count. Adding an inert wrapper would make all five GREEN while button versus navigation-link semantics, status/focus vocabulary, and loading/empty/error/success/disabled/permission states remain inconsistent. Add semantic action/link/status/focus assertions and the applicable real-state matrix for each named surface; identify checklist coverage explicitly rather than relying only on a return navigation step.

6. **MEDIUM — expected-value independence remains weak for the principal new assertions.** The stable contract does not prescribe the `data-*` marker names used as the main RED outcomes. Treat them only as locators and assert independently observable accessible semantics, visible hierarchy, state and geometry so inert marker-compliant markup cannot satisfy the suite.

### Required changes

Add reachable, public-seam behavioral witnesses for the missing A1–A8 and A11 obligations above, with independent observable expectations rather than marker presence. Capture exact-source RED/GREEN evidence for each case, make the A9–A10 invariant-to-assertion trace explicit, regenerate the reviewer package, and resubmit before Gate 4.

---

## Observable-contract correction rereview — 2026-09-18

- Reviewed source: dirty snapshot over base `5bc6a2254bfaa4f5abef283795189d83f98348e3`; candidate source `2905635c7a84cd2ee3941483b15c54e741788cc2486db4bb7130a52067716e74`; retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T183746Z-fba14d2446/snapshot/source.patch`, SHA-256 `4cc0d7b2efc588f3f2341884fcadc7e7319c00a48a7aff1b865a23370f04e015`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T183746Z-fba14d2446/package.json`.
- Independence is unchanged; this reviewer authored none of the corrected artifacts.
- Verdict: `CHANGES_REQUESTED`.

### Progress accepted

- Lead journeys now complete the existing object original/opening and ОТиЗ settlement/reversal workflows before the new RED point.
- Principal lead expectations use visible identity/status/engineer/summary and rendered geometry rather than structural marker presence.
- A8 now has a reachable JavaScript-disabled authenticated SSR card characterization: native login forms work and SSR identity plus a native workflow link remain visible.
- A11 route journeys now inspect rendered command vocabulary after their existing workflows. Exact-source evidence matches the plan: queue, documentary/checklist return and access administration are RED; construction control and installer directory are GREEN characterizations.
- A9–A10 exact-source GREEN preservation evidence, E2E categorization, expected-signature classification and snapshot integrity remain valid.

### Remaining findings

1. **CRITICAL — the current A5 RED imposes 44×44 targets in the wrong input context.** `tests/Yii2/preopening_browser.mjs:48` and `tests/Yii2/otiz_settlement_browser.mjs:125` require every visible button to be at least 44×44 while running the default fine-pointer desktop context, starting at 1440 px. A5 requires that minimum specifically for coarse-pointer targets. An implementation that correctly applies the size only under `@media (pointer: coarse)` would remain RED. Use a browser context/device profile that actually reports a coarse pointer and scope the size assertion to that context.

2. **CRITICAL — most A1–A8 outcomes remain unreachable in the retained RED.** Both lead children stop at the first 1440 target-size assertion. The evidence therefore does not reach later widths, zoom, keyboard or reduced-motion checks. Split the acceptances/cases or aggregate individual outcomes inside each journey so every obligation has independently classified RED or explicit pre-existing GREEN evidence.

3. **HIGH — A2–A4 edge and workflow states remain incomplete.** Visible identity/status/engineer checks do not prove first-viewport bounds at 1024×768 and 1440×900, exactly one permitted primary action, or distinct composition/document/technical/deadline/history regions. There are no browser fixtures for long names/history, missing or corrupt technical data, unknown dates and permission-limited actors. ОТиЗ still lacks blocking/unknown readiness cases proving issue-to-object association and unavailable publication.

4. **HIGH — A5–A7 remain only partially witnessed.** There is no overlap/occlusion/clipping detection, labelled-mobile-row or contained-table-scroll assertion, reliable browser 200% reflow witness, Tab/Shift+Tab order, true focus-visible behavior, icon-only accessible-name check or hover-independence case. Motion coverage does not prove actual 120/220/360 ms and easing use, absence of page-load choreography, complete transform/stagger/stacking removal, or retained focus/color feedback.

5. **HIGH — A8 remains partial.** The no-JavaScript SSR/link characterization is useful, but it does not exercise a relevant native workflow form through completion or simulate a failed enhancement script. The contract requires native links and forms to remain working when JavaScript is disabled or fails.

6. **HIGH — A11 still lacks the required semantic/state matrix.** The new checks establish `shlz-button` membership for selected visible form buttons or one reset link. They do not verify command versus navigation-link behavior, primary/secondary/danger roles, shared status/focus semantics, or loading/empty/error/success/disabled/permission states across the named surfaces. Checklist has no explicit vocabulary/state assertion. Additionally, `evaluateAll(...every(...))` passes vacuously when no matching visible controls exist; assert at least one applicable control before checking all of them.

7. **MEDIUM — A9–A10 traceability should be made explicit.** The mapped GREEN suites substantively cover permissions, GET/HEAD fact preservation, CSRF, replay and concurrency, but the package does not identify assertions for every enumerated href/action/method, revision, return-URL, outcome and stale-conflict invariant. Record that mapping so future test changes cannot silently remove preservation coverage.

### Required changes

Correct the false fine-pointer 44×44 expectation first. Then make every A1–A8 outcome reachable and complete the missing edge-state, responsive-table, accessibility, motion, progressive-enhancement and A11 vocabulary/state cases. Capture exact-source evidence and resubmit the regenerated package before Gate 4.

---

## Aggregated UI evidence rereview — 2026-09-18

- Reviewed source: dirty snapshot over base `5bc6a2254bfaa4f5abef283795189d83f98348e3`; candidate source `edc3da3254db340a956beee80c2569a9c2eede072b2fa9cde41060313f5e47cc`; retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T184323Z-3ccfea8649/snapshot/source.patch`, SHA-256 `1bc3a9e6b64cadcbdbb40246c2d75b107f9a70d205d6ea03e136a514b5b9238f`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T184323Z-3ccfea8649/package.json`.
- Independence remains unchanged.
- Verdict: `CHANGES_REQUESTED`.

### Resolved findings

- Both lead suites now accumulate all included UI failures after completing their existing workflows. Exact RED reports object-card 200% reflow, reverse-keyboard and coarse-target failures, plus ОТиЗ 320 px overflow, 200% reflow, reverse-keyboard and coarse-target failures. The former first-assertion reachability defect is resolved for the assertions actually present.
- Coarse-target checks now run in separate authenticated `hasTouch: true`, `isMobile: true` contexts and first prove `matchMedia('(pointer: coarse)').matches`. The former false fine-pointer 44×44 expectation is resolved.
- JavaScript-disabled SSR/link characterization, A9–A10 GREEN preservation evidence, A11 route vocabulary evidence, E2E categorization, expected-signature classification and exact-source integrity remain valid.

### Remaining findings

1. **CRITICAL — the recorded reverse-keyboard RED is based on an invalid oracle.** Each lead suite presses Tab once, then Shift+Tab, and treats `document.activeElement === document.body` as failure (`tests/Yii2/preopening_browser.mjs:49`; `tests/Yii2/otiz_settlement_browser.mjs:127`). Returning backward from the first focusable element to the document/browser boundary is valid keyboard traversal. Both retained RED lists include this false `reverse keyboard order` failure, so a conforming implementation can remain RED. Traverse at least two known focusable elements, record their identities and bounding/DOM order, then assert Shift+Tab returns to the known previous element.

2. **HIGH — A2–A4 still lack material fixtures and assertions.** There is no bounding proof that registration/address/status/engineer and exactly one permitted primary action fit the two specified first viewports, no distinct semantic-region assertions, and no long-name/history, missing/corrupt technical data, unknown-date or permission-limited browser cases. ОТиЗ still lacks blocking/unknown readiness fixtures proving issue-to-object association and publication unavailability.

3. **HIGH — A5–A7 remain incomplete.** Root overflow does not detect overlap or clipping; there is no labelled-mobile-row/contained-table-scroll witness. Authored CSS `zoom: 2` is not a reliable browser/user 200% reflow oracle. A6 lacks true focus-visible, icon-only accessible-name and hover-independence cases. Motion checks do not prove actual 120/220/360 ms and easing use, no page-load choreography, complete stagger/stacking removal or retained focus/color feedback; ОТиЗ has no reduced-motion case.

4. **HIGH — A8 remains partial.** The JavaScript-disabled login/card/link path is useful, but no relevant native workflow form is submitted in that context, ОТиЗ SSR operation is not covered, and failed enhancement-script behavior is not exercised.

5. **HIGH — A11 remains incomplete.** Current checks prove selected controls carry `shlz-button`, but not command-versus-navigation semantics, primary/secondary/danger roles, status/focus consistency, or route-specific loading/empty/error/success/disabled/permission states. Checklist lacks an explicit vocabulary/state assertion. The `evaluateAll(...every(...))` checks must also assert a nonzero applicable-control set to avoid vacuous GREEN.

6. **MEDIUM — A9–A10 traceability remains grouped.** Preserve the existing useful GREEN suite mapping but explicitly identify the assertions covering revisions, return URLs/outcomes and stale-conflict fact preservation, as well as the other enumerated protocol invariants.

### Required changes

Replace the invalid reverse-keyboard oracle and capture fresh exact-source RED. Complete the outstanding A2–A8 and A11 public-seam cases, including non-vacuous semantic assertions, then regenerate and resubmit before Gate 4.

---

## Keyboard-oracle correction rereview — 2026-09-18

- Reviewed source: dirty snapshot over base `5bc6a2254bfaa4f5abef283795189d83f98348e3`; candidate source `bece76eef76202e6d6416270ffa8a1c59a17dda5c8063c454af7d3cc650057e7`; retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T184811Z-bc62737f74/snapshot/source.patch`, SHA-256 `e8dd99def831f6adae69706b5d8412f658de4948e0e81b37530a1790e4d9c5da`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T184811Z-bc62737f74/package.json`.
- Independence remains unchanged.
- Verdict: `CHANGES_REQUESTED`.

### Corrected finding

The reverse-keyboard oracle is now valid. Each lead suite retains the first focused element, advances with Tab, returns with Shift+Tab, and compares exact element identity. Fresh RED no longer reports keyboard-order failures. The current exact RED is valid for the assertions it reports: object-card 200% reflow and coarse sizing, and ОТиЗ 320 px overflow, 200% reflow and coarse sizing. Aggregate reachability, coarse-pointer contexts, expected-signature classification, E2E categorization and snapshot integrity remain sound.

### Remaining findings

1. **HIGH — A2–A4 remain incomplete.** Missing witnesses include first-viewport bounding at both required desktop sizes for registration/address/status/engineer/exactly one allowed action; distinct semantic regions; long-name/history and missing/corrupt/unknown/permission-limited cases; and ОТиЗ blocking/unknown readiness with issue-to-object association and publication unavailable.
2. **HIGH — A5–A7 remain incomplete.** Root overflow does not detect overlap/clipping, no mobile-table strategy is asserted, and authored CSS zoom is not an independent browser/user 200% zoom witness. A6 still lacks DOM-versus-visual order, true focus-visible, icon accessible names and hover independence. A7 lacks exact token/easing application, page-load choreography prohibition, complete reduced-motion semantics and ОТиЗ reduced-motion coverage.
3. **HIGH — A8 remains partial.** JavaScript-disabled SSR and a native link are proven, but no post-login native workflow form is operated, ОТиЗ is not covered, and failed-script behavior is absent.
4. **HIGH — A11 remains incomplete.** Selected `shlz-button` class membership does not establish command/navigation distinction, primary/secondary/danger roles, shared status/focus behavior, or loading/empty/error/success/disabled/permission states on every named surface. Checklist lacks an explicit witness. `evaluateAll(...every(...))` cases must first prove a nonzero applicable control set.
5. **MEDIUM — preserve explicit A9–A10 traceability.** The mapped GREEN suites are substantive, but the review package should identify coverage for every enumerated href/action/method, revision, return URL/outcome and stale-conflict fact invariant.

### Required changes

Complete the missing A2–A8 and A11 public-seam matrix, retain the corrected keyboard and coarse-pointer cases, capture exact-source evidence, and resubmit before Gate 4.

---

## Owner-approved object-card decomposition review — 2026-09-18

- Reviewed source: dirty snapshot over base `5bc6a2254bfaa4f5abef283795189d83f98348e3`; candidate source `c4f40ff8127258fc6b79fd668728b1bb7ae5e70fd867593c4e5d5700d91cc1e9`; retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T185538Z-3ab23c8239/snapshot/source.patch`, SHA-256 `7a161eec2c34bbb8be8b6d658784c0ec57af3440e1e496a56c9c65a6a7cead18`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T185538Z-3ab23c8239/package.json`.
- Scope decision: the owner-approved decomposition is coherent. ОТиЗ and other active screens are explicit non-goals of the revised stable contract and are not blockers for this review.
- Independence remains unchanged.
- Verdict: `CHANGES_REQUESTED`.

### Accepted coverage

- The real authenticated object-card browser journey, aggregate failure reporting, expected-signature classification, E2E category, reversible keyboard identity oracle and authenticated coarse-pointer geometry are sound.
- A5 is adequately supported for this bounded slice by exact-source GREEN object-card HTTP and preopening concurrency suites covering permission, GET/HEAD read-only, fields/CSRF, replay/concurrency, outcomes and fact preservation. The delivery trace should identify the owning assertions, but no new A5 test is required by this review.
- Exact-source RED is deterministic and isolated for the currently asserted 200% reflow and coarse-target failures; snapshot and evidence binding are intact.

### Remaining bounded findings

1. **HIGH — A1 is only partially witnessed.** `tests/Yii2/preopening_browser.mjs:41` proves a heading, status and engineer label are visible, but not the registration number and address, exactly one permitted primary next-action, or distinct semantic regions for composition/documents, deadlines, technical data and history. Add rendered content/count/role/region assertions derived from A1.

2. **HIGH — A2 edge-state readability is not exercised at the browser seam.** `tests/Yii2/yii2_object_card_001_test.php` usefully characterizes unknown dates, missing/corrupt technical data and permission denial in HTTP/body/fact terms, but the browser journey uses only the normal fixture. It does not prove long content or degraded/permission-limited cards remain readable without overflow, nor that forbidden actions are absent in rendered edge states. Add deterministic rendered fixtures and geometry/action assertions.

3. **HIGH — the A3 200% witness is not independent of page styling.** Setting `document.documentElement.style.zoom = '2'` is an authored CSS transformation, not browser/user zoom or an equivalent independently controlled reflow viewport. Use an independent effective viewport/browser zoom strategy and assert the required content/control bounds. The four unzoomed widths, reversible focus identity, visible focus and proven coarse-pointer 44×44 checks are otherwise adequate for the revised A3.

4. **MEDIUM — A4 proves visibility but not operation of the native link.** The JavaScript-disabled context authenticates with native forms and sees SSR identity plus the checklist link, but never follows that link. Click it and assert the expected authenticated navigation/HTTP result to prove the link remains working.

5. **MEDIUM — A1–A4 traceability remains coarse.** The verification input maps all four requirements to one wrapper and one RED signature. Map each requirement to concrete assertion groups/cases so missing A1/A2/A4 obligations cannot disappear behind an otherwise GREEN aggregate.

### Required changes

Add the missing A1 hierarchy/region witnesses, browser edge-state cases for A2, an independent 200% reflow witness, and actual no-JavaScript workflow navigation. Improve A1–A4 assertion traceability, capture fresh exact-source evidence, and resubmit before Gate 4.
