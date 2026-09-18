# Gate 5 code review — SHLZ-OPERATIONAL-UI-001

- Reviewer: separately tasked agent `/root/ui_object_card_final_review`; authored none of the reviewed specification, tests, or implementation.
- Review date: 2026-09-18.
- Current reviewed source: reconstructible dirty snapshot over base `5bc6a2254bfaa4f5abef283795189d83f98348e3`; exact candidate `288fae19e2ac4e1e647c66ebe4db33d0bb10fc1e78fdc532c8be134e2d36be7a` from reviewer package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T200853Z-5343453cc9/package.json`. Earlier candidates and returns are retained below.
- Contract: `specs/SHLZ-OPERATIONAL-UI-001.md`, A1–A5.
- Gate 3 status: `DEFERRED_BY_OWNER` under the recorded 2026-09-18 exception. It is not `APPROVED`; its historical findings and subsequent resolutions are retained below.
- Current verdict: `APPROVED` (see latest asset-contract correction rereview below).

## Findings

1. **HIGH — the mobile hierarchy does not keep the next action in the immediate object-card context required by A1.** In `app/YiiRuntime/Views/object-card.php`, `.fm2-static-passport` precedes `.fm2-object-workspace` in DOM order. At the captured 390×844 mobile rendering, the full engineer/deadline context card consumes the first viewport and the primary workflow panel starts below it; the fixed bottom navigation intersects that transition. Identity, status, engineer, and the next action therefore are not available together as the card's immediate hierarchy. Preserve logical keyboard order, but move or duplicate only the semantic summary needed for the next action ahead of the long context details on narrow layouts; do not use CSS visual reordering that diverges from DOM order. Add explicit first-viewport bounding assertions at the required desktop and narrow/effective-zoom sizes.

2. **HIGH — A2 has no rendered edge-state verification, so readability and permission-limited action suppression are not established.** `tests/Yii2/preopening_browser.mjs` exercises only the normal opened fixture. The HTTP characterization in `tests/Yii2/yii2_object_card_001_test.php` checks unknown dates, missing/corrupt technical data and permissions as response/body facts, but it does not measure rendered long content, overflow/occlusion, or the absence of forbidden controls in those states. Add deterministic browser fixtures for long address/name/history, unknown dates, missing and corrupt technical data, and a permission-limited actor; assert readable geometry, no page overflow/occlusion, and exact permitted primary-action count.

3. **HIGH — the A3 200% zoom witness remains implementation-controlled and can be made GREEN by special-case CSS rather than accessible reflow.** `tests/Yii2/preopening_browser.mjs` sets `document.documentElement.style.zoom='2'`, while `app/YiiRuntime/Assets/pilot.css` explicitly detects `html[style*="zoom"]` and overrides every card descendant. The test and implementation therefore share the same artificial marker and do not independently model user/browser 200% zoom. Use an independently controlled effective viewport/browser zoom strategy, remove the test-specific selector, and assert important content and control bounds in addition to root `scrollWidth`.

4. **MEDIUM — A4 proves JavaScript-disabled visibility but not a working native workflow link.** The no-JavaScript branch in `tests/Yii2/preopening_browser.mjs` authenticates and checks that the checklist link is visible, then closes the context without following it. Click the link with JavaScript disabled and assert the expected authenticated destination/HTTP result. This is the observable operation required by “остаётся ... рабочими,” not merely link presence.

5. **MEDIUM — the prepared final-review evidence was incomplete at dispatch.** The package contains exact-source GREEN records for only three of eight local obligations: the aggregate UI wrapper, object-card HTTP suite, and preopening concurrency suite. It contains no retained records for the selected browser, deployment, governance, runtime, or architecture obligations. During this review I independently ran and observed GREEN for `php tests/Yii2/yii2_preopening_browser_001_test.php`, `python3 tests/Deployment/pilot_jobs_compose_001_test.py`, `python3 tests/Verification/change_verification_001_test.py`, `php tests/Runtime/runtime_storage_001_test.php`, `python3 tests/Verification/architecture_guard_001_test.py`, and `make architecture-check`; these checks support the implementation but are not bound records in the prepared package. Re-prepare the exact corrected candidate with the complete selected focused evidence before rereview.

## Conformance and risk assessment

- The production diff is bounded to the object-card view and shared presentation CSS. No controller, persistence, schema, route, or domain-command owner changed.
- Existing POST actions, methods, CSRF field, request/revision/order fields, permission guards, return links, GET/HEAD behavior, replay/concurrency, and append-only facts are preserved by inspection and by the exact-source GREEN object-card/concurrency evidence. No A5 defect was found.
- The view uses escaped dynamic text, semantic headings/sections, visible focus styling, coarse-pointer sizing, reduced-motion rules, and SSR-native links/forms. The desktop and mobile screenshots are visually coherent with the existing `shlz-ui` vocabulary apart from the hierarchy issue above.
- `git diff --check`, PHP syntax, the real browser journey, all remaining selected bounded checks, and `make architecture-check` passed. Architecture advisories were pre-existing hotspots outside this diff.
- Exact-source CI is `UNKNOWN` and must remain so until the required GitHub run completes; this review does not imply publication readiness.

## Required correction

Correct the narrow-layout hierarchy, add the missing A1/A2 rendered witnesses, replace the coupled zoom oracle, operate the JavaScript-disabled native link, and submit a freshly prepared exact-source package with complete focused evidence. No domain-layer change is requested.

---

## Correction rereview — 2026-09-18

- Reviewed source: exact candidate `7edaf2e5f3729698bb8686c5f679b54890d47650e1f16d7c54c1cd3ee79d0298` from package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T192838Z-c6c5f3d4bf/package.json`.
- Gate 3 remains `DEFERRED_BY_OWNER`, not `APPROVED`.
- Verdict: `CHANGES_REQUESTED`.

### Resolved findings

- The source order is now identity, next action, workspace content, then object context. The desktop grid uses workspace/context columns and the narrow layout follows the same DOM order; no CSS visual reordering was introduced.
- The coupled inline `zoom` and `html[style*="zoom"]` implementation hook are removed. The browser suite now uses an independently controlled 640 px effective reflow viewport and the production CSS relies on ordinary shrinkable grid/flex descendants.
- The JavaScript-disabled journey now clicks the native checklist link and asserts the authenticated destination.
- The package contains eight exact-source GREEN records, one for every planner-selected local obligation. The separately reported `make architecture-check` result is GREEN.
- Inspection of the correction found no route, method, CSRF, form-field, permission, domain-command, persistence, replay/concurrency, or history change.

### Remaining finding

1. **HIGH — required A1/A2 rendered-state coverage is still absent.** `tests/Yii2/preopening_browser.mjs` still checks only a heading, the first status, an engineer label, root overflow on the normal opened fixture, and input/motion basics. It does not assert exactly one permitted primary next action or the distinct composition/documents, deadlines, technical-data and history regions required by A1. It also does not render the A2 long-content, unknown-date, missing/corrupt-technical-data, or permission-limited states and assert their readable geometry and forbidden-action absence. `tests/Yii2/yii2_object_card_001_test.php` provides useful HTTP/body characterization for several degraded states, but cannot catch overlap, clipping, unreadable wrapping, or unauthorized rendered controls. A CSS or template regression in these required states can therefore pass every retained GREEN check. Add deterministic rendered cases and non-vacuous action/region/geometry assertions, then retain fresh exact-source evidence.

All implementation defects and evidence omissions from the first review other than this public-seam coverage gap are resolved. Exact-source CI remains `UNKNOWN` and cannot be inferred from this rereview.

---

## Final correction rereview — 2026-09-18

- Reviewed source: exact candidate `6b110b65afe3415abdaae0289e27a52ada2ebe86562608624fe9ca8a4a2e86b3` from package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T193641Z-1001cc984d/package.json`.
- Gate 3 remains `DEFERRED_BY_OWNER`, not `APPROVED`.
- Verdict: `CHANGES_REQUESTED`.

### Resolved finding

The candidate now supplies non-vacuous rendered witnesses for the long address, registration identity, status, engineer, exactly one primary action, required semantic-region headings, corrupt technical state, unknown dates, narrow overflow and a separate permission-limited actor. All eight planner-selected local obligations have exact-source GREEN records. This resolves the previous general A1/A2 browser-state coverage finding.

### Remaining finding

1. **HIGH — permission-limited action suppression is incomplete and the new guard does not match the checklist route's admission policy.** `app/YiiRuntime/Views/object-card.php:44-45` defines `$canReadChecklist` only as `checklist.read || checklist.edit` and uses it only for the primary panel at lines 71–72. However, `app/YiiRuntime/Views/completion.php:30`, included for every opened card, still renders another `/pilot/objects/{id}/checklist` action unconditionally. The new browser assertion at `tests/Yii2/preopening_browser.mjs:48` searches only `.fm2-next-action .shlz-button--primary`, so it is GREEN while the permission-limited card still exposes the forbidden checklist action. Conversely, checklist admission in `app/InspectionEvidence/MariaDbYiiChecklistRead.php:14-15` also permits role-based access and `inspection.item.complete`; the view-local `checklist.read || checklist.edit` check can hide the primary action from an actor whom the route permits. This violates A2's forbidden-action rule and A5's permission preservation in both directions. Derive visibility from the same public checklist-access decision used by `ChecklistController` (or pass that decision into the view), apply it consistently to both checklist links, and make the browser test assert that no checklist action is present for the denied actor plus that an actor admitted through each supported route policy retains the action.

No other regression or scope issue was found. Exact-source CI remains `UNKNOWN`; the eight local GREEN records do not replace it.

---

## Permission-correction final rereview — 2026-09-18

- Reviewed source: exact candidate `f5fde2ea7255eb4134436f566ec16654d3abbe8cc73b48431f7d301200ef033a` from package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T194518Z-fb651446e2/package.json`.
- Gate 3 remains `DEFERRED_BY_OWNER`, not `APPROVED`.
- Verdict: `APPROVED`.

### Resolution

- `ObjectCardController` now obtains `canReadChecklist` from `MariaDbYiiChecklist::access()['read']`, the same public admission decision consumed by `ChecklistController`; it no longer reconstructs a narrower permission rule in the view.
- Both checklist actions—the primary next-action in `object-card.php` and the secondary completion link in `completion.php`—are guarded by that single decision.
- The browser fixture uses the assigned engineer as an admitted JavaScript-disabled navigation witness and a separate reader as the denied witness. The retained journey also covers long content, unknown dates, corrupt technical data, semantic regions, exact primary-action count, narrow/effective-zoom overflow, keyboard focus/order, coarse targets, and reduced motion.
- All eight planner-selected local obligations have GREEN records bound to exact source `f5fde2ea7255eb4134436f566ec16654d3abbe8cc73b48431f7d301200ef033a`; `git diff --check` is clean. No routes, methods, CSRF fields, command payloads, persisted facts, replay/concurrency behavior, or append-only history ownership changed.

No findings remain for the bounded object-card slice. This approval is the Gate 5 code-review decision only: exact-source GitHub CI is still `UNKNOWN` and remains required before publication/completion.

---

## Asset-contract correction rereview — 2026-09-18

- Reviewed source: exact candidate `288fae19e2ac4e1e647c66ebe4db33d0bb10fc1e78fdc532c8be134e2d36be7a` from package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T200853Z-5343453cc9/package.json`.
- Gate 3 remains `DEFERRED_BY_OWNER`, not `APPROVED`.
- Verdict: `APPROVED`.

The approved object-card implementation, exact checklist access seam, dual-link gating, admitted engineer JavaScript-disabled navigation, denied-reader witness, and A1–A4 rendered assertions are unchanged from the preceding approved candidate. The correction updates `tests/Support/yii2_production_web_cutover_contract.php` so the expected `pilot.css` SHA-256 is `b5fcd68e8af456609c456fd0bc96e52ef8978a11095a8452ef517ed7a3705eb1`, which exactly matches the candidate asset, and binds that contract in the verification input.

The package retains nine exact-source GREEN records, including `php tests/Runtime/yii2_production_web_cutover_001_test.php`, the previously failing inventory consumer, plus the eight earlier planner obligations. `git diff --check` is clean. No new finding or behavioral drift was found.

This remains a Gate 5 approval only. The package reports exact-source GitHub CI as `UNKNOWN`; publication/completion still requires the repository-prescribed CI result.
