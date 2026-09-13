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
