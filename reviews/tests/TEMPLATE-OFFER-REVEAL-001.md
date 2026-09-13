# Test review: TEMPLATE-OFFER-REVEAL-001

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; authored none of the specification, OpenSpec artifacts, fixtures, tests, or production code)
- Test author: root delivery session for issue #53
- Reviewed source: `cadb2627c30e51ce7727e57a11a93eaa4c21174d0ac9138defc999584079f236`; base `8834572877782f4d1ff89174be9fd74cb7aaf463`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T110703Z-56c94f6863/snapshot/source.patch`, SHA-256 `6bcd3c382383227a3225bed6234d779cf6583b9e765c01b4eff9f3c57dfaeecd`
- Agreed review scope: complete Gate 3 review of A1–A6, both new tests, their fixture/browser helper, OpenSpec artifacts, generated plan, and retained RED evidence
- Specification: `specs/TEMPLATE-OFFER-REVEAL-001.md`, SHA-256 `7784ebaebfb766110b7fb4081a0a4b75f539c7718b4dc899c144b8b4b811ed73`
- Public seam claimed by the contract: Yii2 `GET /pilot/objects/{objectId}/assignment-order/selection` HTML/DOM and form events; existing template POST is the negative command boundary
- Red commands: `php tests/Yii2/yii2_template_offer_reveal_001_test.php`; `php tests/Yii2/yii2_template_offer_reveal_browser_001_test.php`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — the tests do not exercise the specified public HTTP seam.** `tests/Support/YiiSelectionViewFixture.php` directly includes `app/YiiRuntime/Views/selection.php`, and both `tests/Yii2/yii2_template_offer_reveal_001_test.php:8-29` and `tests/Yii2/template_offer_reveal_browser.mjs:8-18` consume that handcrafted static rendering. A controller/runtime regression that omits, corrupts, or supplies stale saved-composition input can pass because the fixture constructs the view model itself. This conflicts with the normative seam at `specs/TEMPLATE-OFFER-REVEAL-001.md:7-11`. Add a focused request through the real authenticated Yii2 selection GET (or extend the existing real HTTP fixture) and assert the initial fail-closed markup and exact saved identity there. Static DOM browser interaction may remain complementary.

2. **Blocking — A3's central saved-composition mismatch rule is untested.** The browser test proves only fresh/no action and unchanged saved/action present (`tests/Yii2/template_offer_reveal_browser.mjs:13,16`). It never changes a saved installer set or engineer and verifies that the old order's POST disappears or becomes unavailable until save. Thus an implementation that reveals and leaves the stale template form active whenever readiness is true passes. Add independently constructed saved fixtures with at least two installer/engineer choices and exercise installer add/remove and engineer change relative to the snapshot, including normalization (unique, sorted, positive IDs), then require the stale action to be unavailable and the save-first explanation to be observable. Reconfirm exact-match recovery only after returning to the exact saved identity or after a new server GET, per the chosen contract wording.

3. **Blocking — the A2/A5/A6 acceptance matrix is incomplete and permits plausible regressions.** `tests/Yii2/template_offer_reveal_browser.mjs:11-18` checks installer removal and reduced-motion only. It does not check hide after confirmation is unchecked, the engineer-change path, the required `inert`-before-`hidden` behavior, keyboard tab exclusion of nested actions, normal-motion opacity/translate transition, absence of overlay, or no focus movement on hide. Its "deterministically" assertion performs a different event sequence and observes visibility only; it cannot detect needless DOM mutation on identical input. Add behavioral sensors for every named event path and both motion modes, including focus preservation and keyboard reachability. For repeated identical input, observe stable relevant DOM attributes/state rather than only the final visibility.

4. **Blocking — A3 rejection and A4/A6 no-side-effect requirements are neither mapped nor executed by the prepared focused plan.** The new tests use static HTML and cannot observe database rows, PDF bytes, navigation, form submission, opening, or audit/history. The plan includes the broad preopening browser regression but not the existing negative template HTTP suites, and the review package provides no exact mapping showing which existing assertions cover stale/forged POST with no new template/process facts, reveal without submission/navigation, optional direct upload, and unchanged adjacent authorization. Add the applicable real HTTP/DB regression commands to the verification input/plan and explicitly map their assertions to A3/A4/A6; add a focused assertion where existing coverage does not prove stale composition rejection and zero new facts. Generated-plan regeneration is required after changing the bound verification input.

5. **Non-blocking positive findings.** The literal expected identities (`81`, `7001`, `73`, and the route) are independently fixed in test data rather than copied from production output. Both tests are bounded, use fictional data, clean their temporary browser artifacts, and have no production/remote dependency. The retained evidence matches independent reproduction: the HTML test exits `255` at missing `[data-template-offer]`, and the browser wrapper exits `255` because Playwright reaches the same missing semantic offer; these are intended missing-behavior failures, not setup failures. `git diff --check` is clean. These qualities do not compensate for the blocking seam and sensitivity gaps above.

## Required changes

1. Exercise the real Yii2 selection GET seam and assert the server-composed initial/saved contract.
2. Add saved-composition mismatch coverage for installer and engineer changes, normalization, stale-action unavailability, and the save-first return path.
3. Complete the A2/A5/A6 event, accessibility, motion, focus, and idempotence sensors.
4. Bind and run exact existing/new HTTP/DB regressions for forged/stale POST, zero facts, optional upload, adjacent authorization, and no automatic command/navigation; regenerate the prepared plan and RED evidence.
5. Submit the corrected exact-source package for a fresh independent Gate 3 review before production implementation.

Gate 4 is not authorized for this source.

---

## Correction review — exact source `3104e53ae3997c36f7f897d59edda4943cd9be34584a135b3f8cbfa81cb44855`

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (fresh correction pass; still independent and author of review records only)
- Reviewed source: `3104e53ae3997c36f7f897d59edda4943cd9be34584a135b3f8cbfa81cb44855`; base `8834572877782f4d1ff89174be9fd74cb7aaf463`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T111720Z-62060ea251/snapshot/source.patch`, SHA-256 `34424d3b6f07cd30add261988f1906867cd6bfa823f497b90e6925688fa0f56f`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T111720Z-62060ea251/package.json`
- Regenerated plan: SHA-256 `2c66241c8d945d1bb19a99319885ecfc48d548106b012ba92e0db777d6886ab2`
- Superseding verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Real public seam — resolved.** `tests/Yii2/yii2_template_offer_reveal_001_test.php:6-10` now starts the actual Yii runtime against an isolated MariaDB fixture, authenticates, performs the real selection GET and POST, parses the returned HTML, compares full facts around GET and denial, and confirms no legacy runtime was loaded. The browser test likewise uses the real server and authenticated routes rather than static view inclusion.
2. **Saved-composition mismatch — partially resolved.** `tests/Yii2/template_offer_reveal_browser.mjs:11-13` now proves exact saved action, installer mismatch, engineer mismatch, save-first text, and recovery to the saved identity. The values are fixed independently by the fixture. The remaining normalization and event gaps are listed below.
3. **Event/motion/accessibility/idempotence matrix — partially resolved.** Confirmation show/hide, focus preservation, reduced motion, a basic hidden-tab sensor and narrow overflow are now executable. The remaining missing installer-removal, normal-motion and genuine repeated-event sensors are blocking below.
4. **HTTP/DB side effects and plan — partially resolved.** The real HTML test adds full-facts assertions around GET and a forged absent-order POST. The verification input and plan were regenerated consistently. They still do not observe the A4/A6 no-automatic-command interval exercised by the browser, nor include the applicable adjacent negative HTTP suites in the focused plan.

### Correction findings

1. **Blocking — last-installer removal is no longer exercised.** A2 explicitly requires recalculation after removal and hide when readiness becomes false. The corrected browser script adds `7002` to the already saved `7001`, then removes only `7002` (`tests/Yii2/template_offer_reveal_browser.mjs:12`), leaving the form ready. An implementation with a broken last-installer removal listener can pass. Add a real UI sequence that removes every selected installer, asserts `inert` and hidden state, preserves focus, and then restores readiness. Also exercise ordering/duplicate normalization rather than only set equality for `[7001]` plus one distinct ID.

2. **Blocking — normal-motion sensitivity is too weak for A5.** `tests/Yii2/template_offer_reveal_browser.mjs:14` accepts any non-zero transition duration; a long color-only transition with no opacity or vertical displacement passes. It also has no sensor for the required small translate and non-overlay layout. Assert the relevant computed transition properties and bounded duration/translate behavior in normal mode, plus a layout-position/flow invariant that detects an absolute/fixed overlay. Keep the existing reduced-motion checks.

3. **Blocking — the claimed identical-input idempotence test does not dispatch an identical event.** At `tests/Yii2/template_offer_reveal_browser.mjs:10`, `confirm.check()` is called while the checkbox is already checked; Playwright performs no action and therefore does not invoke the application listener. This cannot detect a handler that toggles or mutates DOM on a repeated same-value event. Dispatch a second same-value `change`/applicable form event and observe stable `hidden`, `inert`, action availability and relevant mutation state. The same sensor should be applied to a repeated installer-apply sequence if that is one of the supported events.

4. **Blocking — A4/A6 no automatic facts remains unobserved across the real browser interaction.** The browser performs reveal/hide/mismatch interactions and then an explicit composition save, but the PHP wrapper never snapshots database/process/PDF state at the boundary before that save. Consequently an implementation could silently call the template POST or another state-changing endpoint during reveal, create an audit/process fact, and still reach the current assertions. Add a browser synchronization checkpoint before any explicit POST and compare the fixture's full facts and private PDF/file inventory with the pre-interaction snapshot; assert URL/navigation stability as well. Then release the browser to perform the deliberate save. Bind the existing authorization/failure regressions (or equivalent focused assertions) in the verification input so the planned commands cover the stated unchanged adjacent POST boundary rather than relying on an unmapped future full run.

### RED and isolation

Both corrected commands were independently reproduced on the reviewed bytes. The HTML command exited `255` after successful authenticated Yii GET/MariaDB setup at `INTENDED_RED semantic offer`. The browser command exited `255` after successful Playwright login and real Yii navigation at `INTENDED_RED offer`. These match the retained exact-source evidence and are intended missing-behavior failures, not setup failures. Fixture cleanup completed, and `git diff --check` passed.

### Required changes

1. Add last-installer removal and stronger normalization sensitivity.
2. Make the normal-motion/flow oracle specific to opacity, bounded translate/duration and non-overlay layout.
3. Dispatch and observe a genuine repeated same-value event for idempotence.
4. Add a pre-command browser/fixture checkpoint proving zero database, history, PDF/file and navigation side effects, and bind applicable adjacent negative HTTP regressions into the regenerated focused plan.
5. Capture new exact-source RED evidence and request another independent Gate 3 correction review.

Gate 4 remains unauthorized for this corrected source.

---

## Rebuilt-matrix review — exact source `c7fc4000dd8ceedc698dc2c44ae46532d63c63aba81aed75b4dedfc544db725e`

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; review-record author only)
- Reviewed source: `c7fc4000dd8ceedc698dc2c44ae46532d63c63aba81aed75b4dedfc544db725e`; base `8834572877782f4d1ff89174be9fd74cb7aaf463`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T112342Z-d538bcd2f6/snapshot/source.patch`, SHA-256 `5739b1caa9f4f77047868be56c4cafd6e0c7ee0158facfce3129ae2327411688`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T112342Z-d538bcd2f6/package.json`
- Regenerated plan: SHA-256 `0c574a9927c19dd6c7ddd1aa5f0e040063b227f598f5879661418c95b7a9daea`
- Superseding verdict: `CHANGES_REQUESTED`

### Complete prior-finding disposition

1. **Public seam: resolved.** Both new tests use the authenticated real Yii runtime and isolated MariaDB fixture; server-rendered identity and browser events are no longer tested through a direct view include.
2. **Last-installer event: resolved.** `tests/Yii2/template_offer_reveal_browser.mjs:12` removes the only installer, requires hidden+inert, checks focus does not enter the offer, and restores readiness through the real picker.
3. **Saved mismatch: resolved except sorted multi-ID normalization.** Installer and engineer mismatch disable the stale action; save-first text and exact recovery are asserted. Duplicate normalization is now exercised.
4. **Genuine repeated event: resolved.** `tests/Yii2/template_offer_reveal_browser.mjs:11` dispatches a bubbling same-value `change` and compares the relevant DOM/action state.
5. **No-side-effect checkpoint: resolved.** The PHP wrapper and browser synchronize before the first explicit POST. The wrapper compares all fixture facts and private files and checks the exact unchanged URL before releasing the browser.
6. **Adjacent negative regressions: resolved.** Both negative HTTP suites are acceptance-mapped in the regenerated input and focused plan, and their exact-source GREEN evidence is retained.
7. **Reduced motion, narrow viewport, keyboard exclusion, focus and non-overlay positioning: resolved.** These now have concrete browser assertions.

### Remaining findings

1. **Blocking — sorted normalization is still not sensitive.** The persisted snapshot contains only installer `7001`. The new test duplicates that one input (`tests/Yii2/template_offer_reveal_browser.mjs:15`), which proves de-duplication but cannot distinguish sorted comparison from order-sensitive comparison: every normalized exact-match set still has one distinct value. The normative A3 rule requires sorted unique IDs, and the prior correction explicitly required ordering sensitivity. Construct/save a composition with at least two distinct installers, reorder its submitted DOM inputs relative to the server snapshot, dispatch the real applicable event, and require the exact saved action to remain enabled. Retain the duplicate case as a separate sensor.

2. **Blocking — the opacity/translate motion oracle still checks declarations, not the specified behavior.** `tests/Yii2/template_offer_reveal_browser.mjs:17` requires `transitionProperty` to mention `opacity` and `transform` and bounds duration, but never observes opacity or a non-zero small vertical transform during a show/hide transition. An implementation declaring both transitions while changing only `hidden`—with opacity always `1` and transform always `none`—passes. Add a deterministic sensor around the transition (or an equivalent CSS-state contract) proving that the ordinary-motion reveal/hide actually traverses opacity and a bounded non-zero vertical translate, while ending in the correct visible/hidden state. The existing reduced-motion and flow assertions should remain.

### Independent evidence reproduction

- `php tests/Yii2/yii2_template_offer_reveal_001_test.php` — exit `255`, intended missing semantic offer after successful real Yii/MariaDB setup.
- `php tests/Yii2/yii2_template_offer_reveal_browser_001_test.php` — exit `255`, intended missing semantic offer after successful Playwright authentication/navigation.
- `php tests/Yii2/yii2_preopening_failures_001_test.php` — GREEN: `PASS: YII2-PREOPENING-JOURNEY-001 denial, resource isolation and atomic rollback`.
- `php tests/AssignmentOrderComposition/selection_http_failures_001_test.php` — GREEN: `PASS domain mapping/retry identity/read failure/template failure`.
- `git diff --check` — clean.

The RED failures match the retained exact-source records and are caused by absent requested behavior, not setup. Expected identities, statuses, event state and database/file inventories remain independently determined and deterministic.

### Required changes

1. Add a two-distinct-installer reversed-order exact-match case, separate from duplicate normalization.
2. Make ordinary-motion sensitivity observe actual opacity and bounded non-zero vertical translate behavior, not only transition property names.
3. Refresh exact-source package/RED evidence and request a further independent Gate 3 review.

Gate 4 remains unauthorized for this source.

---

## Final correction review — exact source `ffb9833867de87f350d35aca8c2f810e7c3c96e62d1140546567c2186caabfd4`

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; authored only the review history)
- Test author: root delivery session for issue #53
- Reviewed source: `ffb9833867de87f350d35aca8c2f810e7c3c96e62d1140546567c2186caabfd4`; base `8834572877782f4d1ff89174be9fd74cb7aaf463`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T112933Z-181ae4c426/snapshot/source.patch`, SHA-256 `0365ba4510f2fe20a08149c418773ff6cb9d044ab277071e32c970895c467294`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T112933Z-181ae4c426/package.json`
- Verification plan: SHA-256 `cd93dc4b522b327d3f0d557d166ccae4bd0f6f57051a1366aec92497e1348d31`
- Specification: `specs/TEMPLATE-OFFER-REVEAL-001.md`, SHA-256 `7784ebaebfb766110b7fb4081a0a4b75f539c7718b4dc899c144b8b4b811ed73`
- Public seam: authenticated Yii2 selection GET/POST HTML, DOM and real browser form events, with the existing template POST as the negative command boundary
- Superseding verdict: `APPROVED`

### Findings and prior disposition

None remain for Gate 3. This section supersedes all earlier `CHANGES_REQUESTED` verdicts for the exact final source above.

- The real Yii HTTP/MariaDB seam supplies both fresh and persisted models; exact independently fixed order/installer/engineer identity is asserted without a private view seam.
- Initial fail-closed `hidden`/`inert`, no fabricated action, saved exact action, forged-target rejection and complete fact preservation are observable.
- Readiness is sensitive to picker apply, last-installer removal, engineer choice and confirmation changes. Show and hide preserve focus; hidden content is excluded from keyboard traversal; a genuine bubbling same-value event leaves relevant DOM/action state stable.
- The persisted composition now contains distinct installers `7001` and `7002`. The test restores both, reverses their DOM order, adds a duplicate input, dispatches the applicable event, and still requires the exact saved action. An order-sensitive or non-deduplicating comparison therefore fails while mismatch remains separately detected.
- Installer and engineer mismatch keep the helper visible but disable the stale template action and expose the save-first return path; returning to exact normalized identity restores the action.
- Normal-motion assertions require opacity and transform declarations, bounded positive durations, ordinary document flow, immediate computed opacity below `1`, a non-zero vertical `translateY` bounded to 16px, and final opacity `1` after settling. Reduced-motion requires zero duration and no transform. These sensors reject declaration-only animation, missing displacement, excessive displacement/duration and overlay implementations.
- The PHP/browser synchronization checkpoint occurs after reveal/hide interactions and before any explicit POST. It compares the full MariaDB fact inventory and private files and requires the unchanged exact URL, catching automatic save/template/PDF/open/navigation behavior.
- Narrow 360px overflow, no-JS fail-closed initial HTML, adjacent authorization/rejection, template failure atomicity and unchanged native composition failure behavior are covered by mapped focused commands. The regenerated plan retains unit, integration, E2E, governance and full exact-source CI obligations for later gates.
- Expected values are literals/fixture facts derived independently from the specification. Inputs are fictional; MariaDB, HTTP server, browser process, files and credentials are isolated and cleaned by bounded fixture ownership. No production or remote system is used.

### Independent verification

```text
php tests/Yii2/yii2_template_offer_reveal_001_test.php
INTENDED_RED semantic offer; exit 255 after successful real Yii/MariaDB setup

php tests/Yii2/yii2_template_offer_reveal_browser_001_test.php
INTENDED_RED offer; exit 255 after successful Playwright authentication/navigation

php tests/Yii2/yii2_preopening_failures_001_test.php
PASS: YII2-PREOPENING-JOURNEY-001 denial, resource isolation and atomic rollback

php tests/AssignmentOrderComposition/selection_http_failures_001_test.php
PASS domain mapping/retry identity/read failure/template failure

git diff --check
PASS (no output)
```

The two RED failures match the retained exact-source evidence and fail at the absent requested semantic offer, not setup. Both mapped adjacent suites are GREEN.

### Required changes

None. Gate 3 is approved for minimal Gate 4 implementation against this exact reviewed source. Any change to the specification, fixtures, test expectations or mapped acceptance set restarts Gate 2/3 review.

---

## Runtime motion-cancellation correction review — exact source `2618763b9a95369c7a4e1d4967ad73cd35ba91d84624ba4d4e2dde0427223bc9`

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; authored only this review section)
- Reviewed source: `2618763b9a95369c7a4e1d4967ad73cd35ba91d84624ba4d4e2dde0427223bc9`; base `8834572877782f4d1ff89174be9fd74cb7aaf463`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T120511Z-4a78a8cc00/snapshot/source.patch`, SHA-256 `09665921096a7e3355cac06cbcd548d6053b8bcb6a994e243a9dbc47895b06ef`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T120511Z-4a78a8cc00/package.json`
- Verification plan: SHA-256 `4f0724e5e7c2a7641f8ee34d89a7eda37f6776c6ba0bb86e73b4363e558ac0e7`
- Expected mapping: unchanged HTML and adjacent controls `GREEN`; runtime motion-cancellation browser expectation `INTENDED_RED`
- Superseding verdict: `CHANGES_REQUESTED`

### Findings

1. **Blocking — the test does not prove that an exit is pending before the runtime preference switch.** At `tests/Yii2/template_offer_reveal_browser.mjs:17`, `check(!await offer.getAttribute('hidden'),'exit pending before preference switch')` passes both when the boolean attribute is absent (`null`) and when it is present with its normal empty-string value (`""`). Therefore an implementation that hides immediately before `page.emulateMedia({reducedMotion:'reduce'})` can pass this checkpoint; the following hidden wait can also succeed immediately without the preference change causing cancellation. Replace this with an unambiguous assertion such as `await offer.evaluate(e => !e.hidden)` or `getAttribute('hidden') === null`, and retain the simultaneous immediate `inert` assertion. The test must establish `inert=true, hidden=false` immediately before changing the media preference, then require the same already-pending exit to become `hidden=true` as a consequence of that switch.

The remaining correction is well targeted. After a proven pending exit, switching to reduced motion and waiting for hidden would detect the current cancellation bug; switching back to `no-preference`, checking confirmation and waiting for visible state proves recovery. The later reduced-motion hide checks immediate hidden+inert. Existing actual bounded entry/exit motion, reversal, saved two-ID order+duplicate normalization, wide two-column adjacency, narrow overflow, focus, idempotence and no-side-effect synchronization remain intact.

Expected mapping is otherwise honest: production from the prior correction makes the HTML contract GREEN while the newly added runtime-switch path is intended RED. The specification already requires inert-before-hidden ordinary exit and motion removal under reduced-motion preference, so this is sensitivity added within A2/A5 rather than new product scope.

### Independent verification

```text
php tests/Yii2/yii2_template_offer_reveal_001_test.php
PASS: TEMPLATE-OFFER-REVEAL-001 real Yii HTML, identity and no-side-effect denial

php tests/Yii2/yii2_template_offer_reveal_browser_001_test.php
INTENDED_RED: page.waitForFunction timeout waiting for pending exit to finalize after runtime reduced-motion switch; exit 255

php tests/Yii2/yii2_preopening_failures_001_test.php
PASS: YII2-PREOPENING-JOURNEY-001 denial, resource isolation and atomic rollback

php tests/AssignmentOrderComposition/selection_http_failures_001_test.php
PASS domain mapping/retry identity/read failure/template failure

git diff --check
PASS (no output)
```

The browser reaches the new runtime-switch branch after the previously approved motion and composition matrix, and its timeout matches the retained intended-RED evidence. Setup is valid and deterministic, but the ambiguous precondition prevents Gate 3 approval because the test could pass for the wrong hidden state after an implementation change.

### Required changes

1. Assert pending exit unambiguously as `inert=true` and `hidden=false` immediately before `emulateMedia(reducedMotion: 'reduce')`.
2. Preserve the causal post-switch hidden assertion and no-preference recovery, capture fresh exact-source RED evidence, and request independent Gate 3 rereview.

Gate 4/correction implementation is not authorized against this test source.

---

## Post-Gate-5 test correction review — exact source `bd8e4613d6ebdb35cec95cd3a9c5360eacd662b3cffc461af548ac063b3d90b8`

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; did not author specification, tests, correction candidate, or production code)
- Reviewed source: `bd8e4613d6ebdb35cec95cd3a9c5360eacd662b3cffc461af548ac063b3d90b8`; base `8834572877782f4d1ff89174be9fd74cb7aaf463`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T115044Z-58525fe520/snapshot/source.patch`, SHA-256 `83451555446acc631868600917ff519e12e027e8a7895f8434e74ea82e408508`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T115044Z-58525fe520/package.json`
- Verification plan: SHA-256 `1875300c7ec0a12f393db7e14d78e6ac2e89cd5e2563d12e7ec1f64b2837a071`
- Specification: unchanged `specs/TEMPLATE-OFFER-REVEAL-001.md`, SHA-256 `7784ebaebfb766110b7fb4081a0a4b75f539c7718b4dc899c144b8b4b811ed73`
- Expected mapping: HTML and adjacent negative controls `GREEN`; corrected real-browser exit-motion expectation `INTENDED_RED`
- Superseding verdict: `APPROVED`

### Findings

None.

The expectation change is honest and remains within A2/A5 rather than changing product scope. A2 already requires `inert` before hidden on the ready-to-not-ready transition; A5 requires a short opacity/vertical-motion transition and reduced-motion removal. The former browser oracle proved entry motion but permitted immediate `hidden` on exit. The corrected test makes this previously missed direction observable without weakening any approved readiness, exact-composition, side-effect, focus, narrow-layout or adjacent-route expectation.

The exit sequence is sensitive and deterministic at the public browser seam:

- a genuine confirmation change immediately requires `inert` while `hidden` is still false;
- the same immediate computed sample requires opacity in `[0,1)` and a non-zero absolute `translateY` no greater than 16px;
- the test waits for eventual `hidden`, then reverses the control and waits for visible state;
- last-installer removal independently repeats inert-first and eventual-hidden behavior;
- ordinary entry motion still requires declared opacity+transform, bounded positive duration, actual intermediate opacity/translate, settled opacity `1`, and non-overlay flow;
- after `prefers-reduced-motion: reduce`, confirmation loss must produce inert and hidden immediately, with zero transition duration and no transform;
- the 1440px geometry now requires the helper to remain horizontally adjacent to the order surface with aligned tops, while the existing 360px no-overflow assertion remains intact.

The persisted two-installer normalization sensor remains unchanged and sensitive: saved IDs are `7001`/`7002`; the DOM order is reversed, a duplicate is inserted, a real bubbling change is dispatched, and the exact saved action must remain enabled. Installer/engineer mismatch, same-value idempotence, focus preservation, keyboard exclusion and the synchronized no-database/file/navigation checkpoint are retained.

Expected values are independently fixed from the contract and fixture. The browser does not call private production helpers. Timing is bounded by the declared maximum transition and explicit eventual-state waits; reduced motion has no timing dependency. The isolated MariaDB/browser fixture and temporary coordination files retain bounded cleanup.

### Independent verification

```text
php tests/Yii2/yii2_template_offer_reveal_001_test.php
PASS: TEMPLATE-OFFER-REVEAL-001 real Yii HTML, identity and no-side-effect denial

php tests/Yii2/yii2_template_offer_reveal_browser_001_test.php
INTENDED_RED: exit is inert before hidden
Actual sample: {"hidden":true,"inert":true,"opacity":1,"translateY":0}
exit 255 after successful real Yii/MariaDB/Playwright setup

php tests/Yii2/yii2_preopening_failures_001_test.php
PASS: YII2-PREOPENING-JOURNEY-001 denial, resource isolation and atomic rollback

php tests/AssignmentOrderComposition/selection_http_failures_001_test.php
PASS domain mapping/retry identity/read failure/template failure

git diff --check
PASS (no output)
```

This matches the retained exact-source evidence: HTML and both mapped adjacent controls are GREEN, while browser RED fails solely because current production hides the offer immediately and never exposes the required exit transition. It is not a setup or environmental failure.

### Required changes

None. The corrected test expectation is approved for minimal implementation of the exit-motion behavior. Because a test changed after Gate 5, the corrected executable source requires a new independent Gate 5 review after GREEN; the earlier code-review approval cannot cover this delta.

## Latest controlling verdict

The later exact source is `2618763b9a95369c7a4e1d4967ad73cd35ba91d84624ba4d4e2dde0427223bc9`, reviewed in **Runtime motion-cancellation correction review** above. Its controlling verdict is `CHANGES_REQUESTED`; the older `bd8e4613...` approval does not apply to the new cancellation-test delta. Gate 4/correction implementation remains unauthorized until the ambiguous pending-exit assertion is corrected and independently rereviewed.

## Cancellation-sensor rereview — latest controlling verdict

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; test-delta review only)
- Reviewed source: `317a9e1b1e4daebb9e06afd3b9dbea3eed76936b771ca2ff49c5293863015195`; base `8834572877782f4d1ff89174be9fd74cb7aaf463`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T121004Z-25e176d1cb/snapshot/source.patch`, SHA-256 `43ab2f9c76524dff0da27941e9f01802a9a97bf2aaa0f02569811ec543566f0b`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T121004Z-25e176d1cb/package.json`
- Verification plan: SHA-256 `476095ad1c17df1f1849a4473170a9d7350a874753f8c4060b17366870feaca6`
- Verdict: `APPROVED`

The sole prior blocker is resolved. `tests/Yii2/template_offer_reveal_browser.mjs:17` now evaluates `!e.hidden && e.hasAttribute('inert')` on the offer immediately before changing the media preference. A prematurely hidden implementation therefore fails at the precondition instead of satisfying it through the empty-string behavior of a boolean HTML attribute.

After that exact pending state is proven, the same DOM node must become hidden after `page.emulateMedia({reducedMotion:'reduce'})`. The test then restores `no-preference`, checks confirmation, and waits for visible state, proving recovery rather than one-way cancellation. The later reduced-motion case still requires immediate hidden+inert. This distinguishes all relevant faults: immediate ordinary hide, missing inert, a stranded pending exit, a media-change handler that does not finalize, and a state machine that cannot re-enter after cancellation.

No expected result, fixture, production file, public seam, acceptance mapping or adjacent control changed beyond the corrected boolean assertion. The retained HTML and adjacent commands are GREEN on the exact package source. Independent reproduction of the browser command reached the corrected precondition and then exited `255` at the 30-second `waitForFunction` for post-switch hidden state, matching the intended missing cancellation behavior rather than setup failure.

No blocking traceability, seam, independence, determinism, isolation or sensitivity finding remains for this delta. Gate 3 is approved for minimal correction implementation against exact source `317a9e1b...`; browser expectation changes require a new independent Gate 5 after GREEN.

## Post-CI complete-correction Gate 3 — latest controlling verdict

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; authored no test, fixture, production change, or CI correction)
- Reviewed source: `9113e3856e65ce444deebd5ca74834ace8ae54c3603bd39bc7894c92371808a9`; base `1aba76a11ac917df98d2873407c2144ee5b3004b`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T125055Z-828fd34b70/snapshot/source.patch`, SHA-256 `379e38cc24712e0e1372fb77a8303e58a68fbee10a0345c70779da619f810ca4`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T125055Z-828fd34b70/package.json`
- Verification plan: SHA-256 `2a1685caaf20eb04a8a269cd7c8067eb39f3867d8b8d64fc8329ee3fa466d4c8`
- Scope: complete CI-attempt-2 failure inventory and correction expectations for existing browser consumers, production asset digest consumer, and the public architecture-check seam
- Verdict: `APPROVED`

### Findings

None blocking for this exact correction input.

The two existing browser-oracle changes are legitimate compatibility corrections, not weakened expectations. `tests/Support/pilot_current_flow_browser.cjs` and `tests/Yii2/preopening_browser.mjs` now explicitly check `controlEngineerConfirmed` after the saved selection GET before invoking the template action. This follows A1's confirmed-engineer readiness requirement and preserves the original real POST/PDF/audit/navigation assertions. They do not bypass the helper or force-enable the action. Both corrected end-to-end consumers are GREEN on the exact source.

The production cutover RED is sensitive and narrowly classified. `tests/Runtime/yii2_production_web_cutover_001_test.php` still serves the real public assets and compares status, exact SHA-256, cache policy, MIME type, `nosniff`, and same-origin policy. Independent reproduction reports exactly two failures: old `pilot.css` digest versus current `e18b92a...`, and old `preopening.js` digest versus current `1e868902...`; all transport/security fields match. Thus the RED is the expected immutable-asset consumer update, not runtime setup or a broad cutover regression. Because the architecture correction can still change final JS bytes, the digest expectation must be updated only to the final reviewed asset hashes; that resulting test delta must be included in the next exact-source Gate 3/5 package rather than silently treated as GREEN.

The new architecture wrapper is a valid public verification-seam test. `tests/Verification/template_offer_architecture_001_test.py` contains no duplicate line-count oracle or alternate threshold: it literally runs `make architecture-check`, forwards complete captured output on failure, and preserves the real exit code. Independent reproduction returns exit `2` with the exact sole architecture finding `hotspot_ratchet: new hotspot app/YiiRuntime/Assets/preopening.js (186 lines)` after the existing HTTP global-call qualification passes. A fake local pass, lower-level-only invocation, suppressed finding, or wrong make target cannot satisfy this wrapper. The separately run lower-level `architecture_guard_001_test.py` completes all 59 tests GREEN, proving the intended RED is the higher public make seam and not broken Python/setup.

The regenerated verification input honestly maps the full known CI inventory: original HTML/browser and selection failure controls; both corrected existing browser journeys; production runtime browser; cutover digest consumer; lower architecture unit; and public architecture wrapper. It retains E2E, governance, integration and eventual full exact-source CI obligations. No UNKNOWN is treated as approval.

Expected values remain independent where behavioral: confirmation readiness comes from A1, browser actions retain their prior outcomes, and the hotspot is selected by repository architecture policy rather than a test-local invented limit. The asset hashes are explicit immutable delivery-contract values; their pending final-byte update is mechanical but still must be reviewed on the exact final source.

### Independent verification and retained inventory

```text
python3 tests/Verification/template_offer_architecture_001_test.py
INTENDED_RED; exit 2; hotspot_ratchet: new hotspot app/YiiRuntime/Assets/preopening.js (186 lines)

python3 tests/Verification/architecture_guard_001_test.py
Ran 59 tests ... OK

php tests/Runtime/yii2_production_web_cutover_001_test.php
INTENDED_RED; only pilot.css and preopening.js SHA-256 differ; headers/status match
```

The exact-source package additionally retains GREEN evidence for:

- `yii2_template_offer_reveal_001_test.php`
- `yii2_template_offer_reveal_browser_001_test.php`
- `yii2_preopening_failures_001_test.php`
- `selection_http_failures_001_test.php`
- `production_runtime_browser_001_test.php`
- corrected `yii2_preopening_browser_001_test.php`

`git diff --check` is clean. The two reproduced RED causes and all retained outcomes match the package mapping.

### Required changes

None before minimal correction implementation for the 186-line architecture hotspot and final asset-contract synchronization. Any final digest edit or other test expectation delta must be included in a fresh exact-source review package; after all mapped checks are GREEN, a new independent Gate 5 is required before CI rerun.

## Final exact-source test-delta review — controlling verdict

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; authored no production, test, fixture, digest or compaction change)
- Reviewed source: `527136227346e80a8e82d853e3cc2ccc9e096e06ad711bfbfdd433d7bf252659`; executable source `dd46bc763b16132580811f21d5becec5a396b70ae97ec2804b6492cf70d05988`; base `1aba76a11ac917df98d2873407c2144ee5b3004b`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T131349Z-fc552ab46a/snapshot/source.patch`, SHA-256 `754528668b46e875deedbeac9f80494d430c49021ac18a5696ae9bac2bec26fb`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T131349Z-fc552ab46a/package.json`
- Verification plan: SHA-256 `d1e1955d3359df3563b82b85dbce5dee10782471e75632c86405c467f253dfd7`
- Verdict: `APPROVED`

### Delta and findings

No blocking finding remains.

The delta from the prior reviewed snapshot preserves the already approved browser-consumer confirmation changes and behavioral oracles. Production `preopening.js` is mechanically compacted from the 186-line hotspot to 149 lines while retaining the reviewed public behavior; the full real browser contract is GREEN. The architecture wrapper itself is unchanged and still literally delegates to `make architecture-check`; it now returns `TEMPLATE_OFFER_ARCHITECTURE_OK`, so the former exact hotspot RED is closed through the public policy seam rather than by weakening or bypassing the guard.

The only final expected-value update is the immutable production asset digest consumer in `tests/Support/yii2_production_web_cutover_contract.php`. I independently computed the candidate bytes:

```text
e18b92a5426d36f791773010f3e3dffc65fd50f35d19ce9b0e8f5216fe08607a  app/YiiRuntime/Assets/pilot.css
4175ae4a39c8657866f6077b2e24e72b5976705cd03e4981be75094df8c6b72b  app/YiiRuntime/Assets/preopening.js
```

Those values exactly equal the two updated contract literals. All other asset digests, status, cache, MIME and security-header expectations are unchanged. Independent execution of `yii2_production_web_cutover_001_test.php` is GREEN, proving the public runtime serves those exact final bytes. The values were not copied from a production test response during this review; they were calculated directly from the exact candidate files and cross-checked against the retained contract.

The regenerated input honestly changes the cutover and architecture expectations from their reviewed correction REDs to GREEN and adds the digest contract file to the planned/effective boundary inventory. It does not reclassify any unresolved UNKNOWN. The package contains 12 focused GREEN records; every record names the same candidate source `527136...` and executable source `dd46bc...`, and together they cover the nine acceptance-mapped commands plus E2E, governance and integration obligations. `git diff --check` is clean.

### Independent verification

```text
sha256sum app/YiiRuntime/Assets/pilot.css app/YiiRuntime/Assets/preopening.js
# exact matches shown above

wc -l app/YiiRuntime/Assets/preopening.js
149

php tests/Runtime/yii2_production_web_cutover_001_test.php
PASS: YII2-PRODUCTION-WEB-CUTOVER-001 single runtime

python3 tests/Verification/template_offer_architecture_001_test.py
TEMPLATE_OFFER_ARCHITECTURE_OK

git diff --check
PASS (no output)
```

The retained 12/12 GREEN evidence additionally confirms both original/new browser flows, HTML/no-side-effect behavior, adjacent Yii/native failures, runtime browser, lower architecture unit, compose, verification governance and runtime storage on the exact executable source.

### Harness limitation

The harness package remains `approval: NOT_REVIEWED` because its v1 plan cannot encode typed lineage from the older manually captured RED records once the current exact plan is all GREEN. This is a tooling/metadata limitation, not inferred approval and not a fabricated historical RED. This explicit independent review supplies the final Gate 3 test-delta decision for the exact source; the harness label itself remains truthfully unchanged.

### Required changes

None. Gate 3 is `APPROVED` for the exact test delta and final asset-digest expectations above. This does not replace the required independent Gate 5 decision or exact-source full CI result.

## Architecture-decomposition Gate 3 — latest controlling verdict

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; authored no design, test, asset, runtime registration, controller or production implementation)
- Reviewed source: `148d3a3218f21db8db0de53313f76f41f1fece9b7c45c6d095bd4b02b7b1b58e`; executable source `ed07d98bdacc1ccae7870dac6502cae21c973b743adf5f23072b2950d620f667`; base `1aba76a11ac917df98d2873407c2144ee5b3004b`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132518Z-ef1c9afd5f/snapshot/source.patch`, SHA-256 `b2c7a9d08965565574a258c19f1d55c5a9e8b38807b05b3e11772ab52f762262`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T132518Z-ef1c9afd5f/package.json`
- Verification plan: SHA-256 `87bf9dd47261bdb9436acd0e4f4618589c48f5b9e6dd3a0e184cbf964ea8cfc8`
- Verdict: `CHANGES_REQUESTED`

### Finding

1. **Blocking — the new oracle proves presence of a second asset, but not transfer of ownership out of the hotspot.** The design now explicitly says that bounded `template-offer.js` computes readiness while `preopening.js` retains installer selection and upload behavior. `tests/Yii2/yii2_template_offer_asset_001_test.php:8-10` requires the selection script tag, exact public transport, and three tokens in the new asset. It never observes that `preopening.js` no longer contains or executes the template-offer owner. An implementation can copy the existing offer state machine into `template-offer.js`, keep the old implementation in `preopening.js`, and satisfy the new test; the existing browser behavior can continue to be supplied by the old asset. If physical lines remain below the ratchet, the architecture wrapper also passes despite two owners and no actual responsibility decomposition. This is precisely the Gate-5 architectural defect the new slice is intended to correct.

Add a sensitive ownership assertion at the public asset boundary: fetch `/pilot/assets/preopening.js` and require absence of the semantic template-offer owner markers/state machine (at minimum `data-template-offer`, reduced-motion/transition-cancellation ownership and saved-composition reconciliation identifiers), while requiring them in `template-offer.js`. Prefer also a browser case that blocks the new asset and proves offer behavior is absent while installer selection/upload ownership remains functional, or an equivalent deterministic single-owner sensor. The expectation must reject both duplicated ownership and a dummy token-only new asset.

### Conforming areas

The proposed module seam is otherwise coherent and remains within `app/YiiRuntime`; it creates no new domain/application owner and does not alter persistence. The real Yii HTTP test authenticates through the existing fixture, loads the actual selection page, requires an exact `/pilot/assets/template-offer.js` declaration, then requests the real asset route and checks `200`, exact JavaScript MIME, exact cache policy, and semantic motion/offer tokens. It calls no private production methods, uses fictional isolated MariaDB state, confirms no legacy layer, and cleans its fixture.

The RED is intended and independently reproduced: the authenticated selection GET succeeds, then the test exits `255` because the page lacks the new asset declaration. This is missing behavior, not setup. The core HTML acceptance independently remains GREEN. Package evidence records all preceding full-plan commands GREEN on exact candidate/executable source, including public architecture-check, cutover/runtime assets, existing browser consumers, and negative seams; the two core template tests are separately GREEN.

The generated boundaries are complete for the proposed implementation: `template-offer.js`, `preopening.js`, `PreopeningAssetBundle.php`, `PilotAssetController.php`, `config/yii/assets.php`, selection view, immutable asset contract, browser consumers and architecture checks are all effective/planned. No `rapid-pilot`, production-image, Compose, deployment or other #76-owned path is changed. Shared Yii asset infrastructure is touched only to register and serve the bounded issue-53 asset, so no behavioral #76 scope conflict is apparent from this exact package.

Expected transport values are independently derived from existing Yii asset contracts, and the semantic tokens follow the approved A1–A5 state machine. The remaining flaw is sensitivity to exclusivity/real ownership, not expected-value provenance or determinism.

### Independent verification

```text
php tests/Yii2/yii2_template_offer_asset_001_test.php
INTENDED_RED selection declares bounded template offer asset; exit 255 after successful authenticated Yii GET

php tests/Yii2/yii2_template_offer_reveal_001_test.php
PASS: TEMPLATE-OFFER-REVEAL-001 real Yii HTML, identity and no-side-effect denial

git diff --check
PASS (no output)
```

### Required changes

1. Make the asset test reject retained/duplicated template-offer ownership in `preopening.js` and reject a token-only inert `template-offer.js`.
2. Capture new exact-source intended RED, regenerate the package if bound inputs change, and request independent Gate 3 rereview.

Architecture-decomposition implementation is not authorized against this test source.

## Ownership-correction Gate 3 — latest controlling verdict

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; test-delta review only)
- Reviewed source: `f7187e0255fb454ebc3be24c31c603860845a15e0ac463dde7aa068323d6e945`; executable source `48ab199d1360f928873625694777b592b4a07289fb0628b2f8603c28c8b0dd1b`; base `1aba76a11ac917df98d2873407c2144ee5b3004b`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T133427Z-ec56ceb13b/snapshot/source.patch`, SHA-256 `843446f0e6e62a48c5d88f8e85dd73713374ff8c3238c85c5778b600f744a45f`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T133427Z-ec56ceb13b/package.json`
- Verification plan: SHA-256 `43e5f5f5087b3eace5a8f5ebac392f94df46c274687c26e27e57a7c7e2820c7e`
- Verdict: `APPROVED`

### Prior finding disposition

Resolved. The real Yii asset test now fetches both public production assets. It requires `template-offer.js` to contain the semantic owner markers `data-template-offer`, `prefers-reduced-motion`, and `transitioncancel`, while `preopening.js` must contain none of those and must additionally release `savedInstallerIds`. A copied second implementation that leaves the old owner intact therefore fails.

The ownership oracle is not relied upon alone. The unchanged full browser acceptance remains mapped and GREEN on the exact source; after extraction it must still pass every readiness, exact-composition, motion/cancellation, focus, accessibility, layout, idempotence and no-side-effect behavior. Consequently a token-only inert new asset plus removal from the old asset cannot complete Gate 4: it may satisfy marker placement, but it fails the real browser behavior. Together, the public asset exclusivity check and behavioral browser test are sensitive to both duplicated ownership and dummy extraction.

The test retains the real authenticated Yii selection GET, exact script declaration, real asset routes, `200`, exact JavaScript MIME/cache policy, and no-legacy include trace. Expected markers are independently taken from the approved state-machine/design responsibilities rather than production implementation output. Inputs and runtime remain isolated and deterministic.

### Exact evidence

Independent reproduction:

```text
php tests/Yii2/yii2_template_offer_asset_001_test.php
INTENDED_RED selection declares bounded template offer asset; exit 255 after successful authenticated Yii GET

php tests/Yii2/yii2_template_offer_reveal_browser_001_test.php
PASS: TEMPLATE-OFFER-REVEAL-001 browser

git diff --check
PASS (no output)
```

The package records the seven earlier full-plan commands GREEN on exact candidate `f7187e02...` / executable `48ab199d...`, followed by this intended asset RED; separately captured core HTML and browser controls are GREEN on the same candidate source. The RED cause is the missing new selection asset declaration, not setup or an existing behavior failure.

Planned/effective boundaries remain complete for the extraction: new/old assets, asset bundle, asset controller, Yii asset config, selection view, immutable asset contract, browser consumers and architecture checks. No #76 production-image, `rapid-pilot`, Compose or deployment path is added.

### Required changes

None. Gate 3 is approved for the bounded ownership extraction against exact source `f7187e0255fb454ebc3be24c31c603860845a15e0ac463dde7aa068323d6e945`. Any expectation or fixture change restarts Gate 2/3; the implemented extraction requires fresh exact-source GREEN and independent Gate 5.

## OTIZ browser timeout test-delta review — latest controlling verdict

- Reviewer: separately tasked Codex agent `/root/issue53_gate3` (independent; narrow test-delta review only)
- Reviewed source: `0363a243c2c38569887cb3e0b0dc1778b2289a3534d97aa8301afba599e8fc85`; executable source `b09e8149152a644751338353fcbbe6bba07bb4dea02112c8b2808dc4cab2d430`; base `1aba76a11ac917df98d2873407c2144ee5b3004b`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141127Z-7d7da51f8e/snapshot/source.patch`, SHA-256 `0fac81c62fa10eb887db58cf3cf8b943ce21b3c8f00236caff6e5178a206e208`
- Prepared root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T141127Z-7d7da51f8e/package.json`
- Verification plan: SHA-256 `2ff0f21ab88fde02ed1c3d0b0b1a4c7162bb722c882fc03409de11143489c567`
- Verdict: `APPROVED`

### Findings

None.

The executable test delta in `tests/Otiz/snapshot_publication_browser_001_test.mjs` changes only Playwright's bounded default action/navigation timeout from 5,000ms to 15,000ms. It adds no retry, sleep before assertion, catch-and-ignore path, conditional skip, relaxed locator, alternate URL, reduced result predicate, or suppressed request/page/console failure. Every original OTIZ assertion remains byte-for-byte present: two-stage login and exact navigation, preserved operation UUID/date after the intentionally lost response, created snapshot URL, non-empty object rows, acceptance, XLSX download metadata/bytes, closure/payment/reversal completion, and empty unexpected console/page/request/HTTP-failure inventories.

The 15-second bound is proportionate to the observed local Compose server latency of 9–14 seconds and still fails a hang deterministically. It addresses setup scheduling/response variance rather than changing business behavior or expected results. The two repeat failures at the old fixed 5-second navigation budget are therefore legitimately classified as environment timing, unrelated to issue #53's template-offer behavior.

The helper is now explicitly listed in `verification-input.json` planned paths, so the regenerated plan/source digest cannot omit this cross-flow test change. This binding is conservative: the OTIZ helper is not added as an A1–A6 behavioral oracle and does not broaden production scope; it is included because `production_runtime_browser_001_test.php` consumes it in the full verification path.

The package is a root role package and contains no new evidence records, so this review does not invent a GREEN result or infer approval from absent evidence. It approves only the expectation-preserving timeout delta. Exact-source execution of the consuming runtime browser/full plan remains required, and any failure beyond the bounded timeout must be diagnosed normally.

### Required changes

None. The 5s→15s bounded timeout correction is approved without weakening OTIZ assertions. This test delta still requires inclusion in the next exact-source Gate 5 and verification package.
