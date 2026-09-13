# Code review: TEMPLATE-OFFER-REVEAL-001

- Reviewer: separately tasked Codex agent `/root/issue53_gate5` (independent Gate 5 reviewer; authored none of the specification, OpenSpec artifacts, tests, fixtures, or implementation)
- Reviewed source: candidate source `f9aafe3072738ca86c9a5c5e109052a1861d0cd30398f5a08d0848b529c6185b`; executable source `998b902817c110ed2813e5027d899b965daecdad46be83a1310a9514484f6175`; base `8834572877782f4d1ff89174be9fd74cb7aaf463` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T114441Z-e0a360fcce/snapshot/source.patch` (SHA-256 `37cf6e5af6da8751971c6b33025b616feae619e242e6a4f4323acf49c77578de`)
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T114441Z-e0a360fcce/package.json`; delta SHA-256 `4fbd73cf4468964776b0e4bc01ece79649a38a6a2fb36e93bca7dc8e8fac047c`; plan SHA-256 `1e23bfe5d22eccb59966c0757f7bc5417e18dd045df003e8453db0dec0a6e61b`
- Specification: `specs/TEMPLATE-OFFER-REVEAL-001.md`
- Approved Gate 3 review: `reviews/tests/TEMPLATE-OFFER-REVEAL-001.md`, superseding verdict `APPROVED` on its recorded exact test source
- Review scope: A1-A6 conformance, every changed entry point, security, accessibility and motion, DOM/forms validity, domain side effects, maintainability, adjacent regressions, and test sensitivity
- Verdict: `CHANGES_REQUESTED`

## Findings

### 1. Medium / blocking — hide motion is not implemented, and the approved browser test cannot detect it

`app/YiiRuntime/Assets/preopening.js:48` applies `inert` and `hidden` in the same synchronous branch. `app/YiiRuntime/Assets/pilot.css:922` maps `[hidden]` to `display: none`, so the helper disappears immediately and cannot traverse the declared opacity/vertical transition. Reveal animates, but hide does not.

This is partial conformance to A5 and to the OpenSpec ordinary-motion scenario, which require the helper's show/hide behavior to use the short opacity/small vertical transition. Immediate `inert` is correct for accessibility, but visual removal must complete through a bounded exit state before `hidden` is applied; interrupted/reversed transitions and reduced-motion behavior must remain deterministic.

The test is insensitive to this plausible regression. `tests/Yii2/template_offer_reveal_browser.mjs:11-12` checks only the final hidden/inert result after unconfirm/removal. Its motion sensor at line 17 performs a synchronous false/true pair and observes only the subsequent reveal. The current implementation therefore passes while one required direction is absent. Correcting this needs a new browser assertion that observes a non-zero bounded hide transition and eventual `hidden`, with immediate no-motion completion under `prefers-reduced-motion: reduce`. Because that changes the approved acceptance sensor, it restarts Gate 2/3 under the delivery process.

### 2. Medium — unrelated desktop layout regression is outside the bounded contract and untested

`app/YiiRuntime/Assets/pilot.css:891` changes the established desktop grid from `minmax(0, 920px) minmax(240px, 300px)` to a single `minmax(0, 920px)` column. The new valid sibling form/helper DOM at `app/YiiRuntime/Views/selection.php:28-45` does not require dropping the second desktop column, and A1-A6 authorize conditional reveal and narrow-layout safety rather than a wide-screen redesign. This moves the helper below the form and leaves unused horizontal space where it previously appeared beside it (also at odds with the plain-language “рядом”).

The browser coverage checks only non-overlay flow and 360px overflow, so it would not catch the wide-layout regression. Preserve the existing two-column desktop layout, retaining the pre-existing responsive one-column rule at `pilot.css:1167`, or explicitly amend/reapprove the scope and add a wide-viewport geometry assertion.

### 3. Low — reduced-motion ownership is duplicated

`app/YiiRuntime/Assets/pilot.css:1099-1102` defines the complete helper reduced-motion behavior, then `pilot.css:1528` repeats part of it with `!important`. This is duplicated code and creates two cascade owners for one behavior. Consolidate the helper rule in one reduced-motion block; keep the `@starting-style` override alongside it.

## Conforming areas

A1-A4 and A6 otherwise conform in the reviewed delta. Server-rendered `hidden`/`inert` is fail-closed; the DOM contains no nested forms; the saved snapshot contains only normalized numeric identities; current inputs are normalized, deduplicated, and sorted before comparison; exact mismatch disables the stale template action; and fresh state does not fabricate an order/template POST. No string from the snapshot is used as executable HTML or URL content.

The implementation does not alter controller/application/domain entry points, server authorization, template generation, persistence, audit/history, upload/original/opening routes, or `rapid-pilot`. Existing forged/stale template rejection remains the authority and the mapped checks preserve failure atomicity. Show/hide code performs no submit, navigation, `focus()`, fetch, file write, or domain mutation. Focus exclusion through `inert`/`hidden`, narrow-width overflow, no-JS fail-closed behavior, same-value determinism, exact-order normalization, and reduced-motion final CSS state are covered and GREEN, subject to finding 1's missing hide-motion sensor.

## Verification evidence

The prepared package records four GREEN focused commands on candidate source `f9aafe30…` / executable source `998b9028…`:

- `php tests/Yii2/yii2_template_offer_reveal_001_test.php`
- `php tests/Yii2/yii2_template_offer_reveal_browser_001_test.php`
- `php tests/Yii2/yii2_preopening_failures_001_test.php`
- `php tests/AssignmentOrderComposition/selection_http_failures_001_test.php`

I independently reproduced the browser command through the delivery harness. Record `1789299972635480000-ca8e8048dc9943f7a94f1eee8c494fa0` is GREEN in 31.51 seconds, with unchanged start/end candidate source `f9aafe3072738ca86c9a5c5e109052a1861d0cd30398f5a08d0848b529c6185b` and executable source `998b902817c110ed2813e5027d899b965daecdad46be83a1310a9514484f6175`. `git diff --check` is also clean.

These GREEN results are not approval because finding 1 is outside the current test's sensitivity. Full exact-source CI, PR, merge, and deployment remain `UNKNOWN` and are not inferred as GREEN.

## Required changes

1. Implement a bounded opacity/vertical exit transition while applying `inert` immediately; apply `hidden` after exit completion, with safe cancellation/reversal and immediate reduced-motion completion.
2. Add a browser acceptance sensor for motion during hide and eventual hidden state, including reduced-motion behavior; obtain a new independent Gate 3 approval for the changed test.
3. Restore the existing two-column desktop layout, or explicitly rescope and test the new wide-screen geometry.
4. Consolidate the duplicate reduced-motion helper rules.
5. Rebuild an exact-source reviewer package, rerun the mapped focused checks, and return the complete corrected candidate to independent Gate 5 review.

---

## Correction rereview — exact source `bb821283926abd4045c8e2ae4a5dcbf862403a4464edd16b285ea7def62cdb36`

- Independent reviewer: Codex `/root/issue53_gate5`; author of none of the specification, tests, fixtures, or implementation
- Reviewed source: candidate source `bb821283926abd4045c8e2ae4a5dcbf862403a4464edd16b285ea7def62cdb36`; executable source `24c166be10f8594a056d07442f19e15b2b6d2fcbfd753d81c6d6c85b752523f2`; base `8834572877782f4d1ff89174be9fd74cb7aaf463` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T120046Z-a730de636e/snapshot/source.patch` (SHA-256 `0bd11e321ce10d4f397f9bce7345180f3fa420827605e8d972dfe8dc54c69b39`)
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T120046Z-a730de636e/package.json`; delta SHA-256 `2fe254ea792395d09460b3efc1647d07c134c3c378e2ee7134df4a9572a47b2d`; plan SHA-256 `9bd30afddf117091f7bb9843acedaa49dce4af66b564e28780e262159dd569c1`
- Corrected-test approval: append-only `Post-Gate-5 test correction review` in `reviews/tests/TEMPLATE-OFFER-REVEAL-001.md`, verdict `APPROVED`
- Superseding verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Ordinary hide motion and test sensitivity: resolved on the normal completion path.** The helper becomes inert immediately, traverses opacity/translate exit motion, and becomes hidden on the opacity `transitionend`. The corrected browser test observes inert-before-hidden, actual bounded motion, eventual hidden state, last-installer removal, subsequent reveal, and immediate reduced-motion hide.
2. **Desktop layout: resolved.** The original two-column grid is restored. The valid outer `div` owns layout, the selection and template POST forms remain siblings, and `display: contents` exposes the selection fieldset as the first grid item without nesting forms. The existing responsive rule returns the grid to one column. The corrected browser test checks adjacent aligned geometry at 1440px and no overflow at 360px.
3. **Duplicated reduced-motion CSS: resolved.** Helper animation, transform, transition, and `@starting-style` behavior now have one owner in the existing reduced-motion block.

The exact-snapshot normalization, mismatch disablement, security boundary, DOM validity, focus behavior, no-JS state, absence of automatic commands/domain side effects, and unchanged adjacent HTTP/application seams remain conforming as described in the initial review.

### Remaining finding

#### Medium / blocking — a cancelled exit transition can leave a permanently unhidden inert layout item

`app/YiiRuntime/Assets/preopening.js:30-36` finalizes pending hide only on opacity `transitionend`. There is no `transitioncancel` handler or bounded fallback. The `MediaQueryList` created at line 29 also has no `change` handler. If the opacity transition is cancelled rather than ended—for example when `prefers-reduced-motion` changes to `reduce` during the 240ms exit and the media rule switches `transition` to `none`—the browser emits `transitioncancel`, not the awaited `transitionend`.

In that path `hidePending` remains true, the helper retains `fm2-order-helper--exiting`, `hidden` remains false, and subsequent not-ready reconciliation returns early at line 45. The helper is inert and transparent but continues to occupy its 240–300px grid cell. That violates A2's eventual hidden state, A5's reduced-motion behavior/layout stability, and the correction requirement for safe cancellation. The current test covers reversal and reduced motion selected before hide, but not cancellation or preference change while hide is pending, so all focused commands can remain GREEN with this defect.

Use one idempotent hide-finalization function from `transitionend`, `transitioncancel`, and a bounded fallback. A reduced-motion media-query change to `matches=true` should invoke it immediately when `hidePending`; reversal must cancel/ignore the pending completion without a stale event hiding the newly visible helper. Add a public-browser sensor for cancelled/pending exit (preferably the real `page.emulateMedia` change during exit) and obtain renewed independent Gate 3 approval because this expands the acceptance sensor.

### Verification evidence

The package records all four focused commands GREEN on candidate `bb821283…` / executable `24c166be…`: the Yii HTML contract, corrected real-browser contract, Yii preopening failure suite, and native selection/template failure suite. Their evidence files all record environment `70f8214e58ad8381fe5fe7e1d46f4fc253908c6c4b3b0f5224593a8878c6ab3f`. `git diff --check` is clean.

GREEN is not approval because the cancellation path is outside the corrected browser test's sensitivity. CI, PR, merge, and deployment remain `UNKNOWN` in harness state.

### Required changes

1. Make pending-hide completion idempotent and safe for `transitionend`, `transitioncancel`, runtime reduced-motion changes, and a bounded no-event fallback; preserve reversal safety.
2. Add a browser acceptance sensor for cancellation/preference change during an exit and reacquire independent Gate 3 approval.
3. Rebuild the exact-source package, rerun the four mapped focused checks, and return the complete candidate to Gate 5.

---

## Final cancellation correction rereview — latest controlling verdict

- Independent reviewer: Codex `/root/issue53_gate5`; author of none of the specification, tests, fixtures, or implementation
- Reviewed source: candidate source `62751c6b6d20e72a5f63102271227c06edc620121ae83dbee000a91549a84beb`; executable source `e2742abada1759d5fe26ceb5f9a3cbe46cee6a1d37752601f0f35a8915be738e`; base `8834572877782f4d1ff89174be9fd74cb7aaf463` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T121641Z-8b1270a459/snapshot/source.patch` (SHA-256 `1ace3e5e7ccf42b4babf180febbd533b4dfa3e1bdcb34f1853e8cda4d6706417`)
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T121641Z-8b1270a459/package.json`; delta SHA-256 `eda4f8b64a157fa5a0195c13f48dc4793cbaca07fca137cbc60c9ab5e69e0c23`; plan SHA-256 `2c3396121a62dbbeedbe22fb9c75784fff8cf65d032c67b321af57ea775ef7ed`
- Corrected-test approval: `Cancellation-sensor rereview — latest controlling verdict` in `reviews/tests/TEMPLATE-OFFER-REVEAL-001.md`, verdict `APPROVED`
- Verdict: `APPROVED`

### Findings and prior disposition

None remain.

The cancellation finding is resolved. `finalizeHide()` is an idempotent completion seam guarded by `hidePending`; both opacity `transitionend` and `transitioncancel` invoke it, and a runtime reduced-motion change invokes it immediately when the query begins matching. The finalizer clears pending state before applying `hidden` and removes every transient motion class.

Reversal is safe: the ready branch clears `hidePending` before removing the exit class and starting entry. Any resulting delayed `transitioncancel` is therefore ignored and cannot hide the newly visible helper. A subsequent not-ready event starts a fresh exit. With reduced motion already active, the same finalizer is invoked synchronously. No timer, asynchronous network work, focus movement, form submission, or domain side effect is added.

The independently approved browser sensor unambiguously establishes `inert=true` and `hidden=false` during an ordinary pending exit, switches the same live page to reduced motion, requires eventual `hidden`, returns to no-preference, and requires successful reveal recovery. It separately retains normal transition completion, actual bounded opacity/translate motion, rapid reversal, last-installer removal, immediate reduced-motion hide, focus and tab exclusion, 1440px two-column adjacency, 360px no-overflow, exact two-installer sorted/deduplicated identity, mismatch disablement, same-value stability, and the no-facts/files/navigation checkpoint.

All earlier findings remain resolved: the helper has an observable enter and exit transition; the valid sibling-form DOM preserves the original desktop two-column layout through `display: contents` and the existing responsive one-column rule; reduced-motion CSS has a single owner. A1-A6 otherwise remain conforming, and controller/application/domain, authorization, template, persistence, audit/history, upload/original/opening, and `rapid-pilot` boundaries remain unchanged.

### Verification evidence

The final package records all four mapped focused commands GREEN on exact candidate `62751c6b…` / executable `e2742aba…`:

- `php tests/Yii2/yii2_template_offer_reveal_001_test.php`
- `php tests/Yii2/yii2_template_offer_reveal_browser_001_test.php`
- `php tests/Yii2/yii2_preopening_failures_001_test.php`
- `php tests/AssignmentOrderComposition/selection_http_failures_001_test.php`

I independently reproduced the complete browser command through the delivery harness. Record `1789301843761500000-4856be20c56d448aad1effbb10579567` is GREEN on unchanged exact candidate/executable source. `git diff --check` is clean.

This approval covers the retained exact working-tree source only. Full exact-source CI, PR, merge, and deployment remain `UNKNOWN`; they are not inferred from focused GREEN evidence and remain subject to the delivery harness and owner authorization.

### Required changes

None. Gate 5 is approved for exact candidate source `62751c6b6d20e72a5f63102271227c06edc620121ae83dbee000a91549a84beb` and executable source `e2742abada1759d5fe26ceb5f9a3cbe46cee6a1d37752601f0f35a8915be738e`.
