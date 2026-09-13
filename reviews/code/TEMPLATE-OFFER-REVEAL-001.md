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

## Extracted-architecture Gate 5 — latest controlling verdict

- Independent reviewer: Codex `/root/issue53_gate5`; author of none of the specification, tests, fixtures, production implementation, asset registration, or browser-consumer corrections
- Reviewed source: candidate source `e31a4a9386febe6bfc91c134bf01035853d898e6f8597c6f0b92bc0e0516acbb`; executable source `034232360206b4c80ebd0a2e22aeb459813facb79f99b23db2526c51f8195da2`; base `1aba76a11ac917df98d2873407c2144ee5b3004b` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T135507Z-0242fa5401/snapshot/source.patch` (SHA-256 `0969e82bd60679b159d9f5c0d43e5913a16b7f65b3ef42ebadb760059c3d9084`)
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T135507Z-0242fa5401/package.json`; delta SHA-256 `b720aca2ebc09aa82fa0bbfb75d4637499336501130e4bc01911dc48b997d385`; plan SHA-256 `986508e6fcf06b3ffe3478ed398619c5a8fb394343efdd3ad007e39d1c5fac2e`
- Controlling Gate 3 ownership approval: `Ownership-correction Gate 3 — latest controlling verdict` in `reviews/tests/TEMPLATE-OFFER-REVEAL-001.md`, verdict `APPROVED`
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

The architectural ownership defect is resolved. `app/YiiRuntime/Assets/template-offer.js` is a readable 73-line single owner of readiness, normalized exact identity, copy/action state, inert-first motion, transition cancellation, runtime reduced-motion changes, and reversal. `preopening.js` retains picker/search and original-upload responsibilities and contains none of the reviewed offer-owner markers. The public ownership test rejects duplicated/dummy extraction, while the full browser contract proves the new asset actually owns the behavior.

`template-offer.js` is declared after `preopening.js` by `PreopeningAssetBundle`, admitted by the exact Yii route, served by `PilotAssetController` with the established JavaScript MIME/cache policy, and inventoried by the runtime asset contract. Its event delegation observes picker-created controls without coupling back into the picker owner. No controller/application/domain command or persistence ownership changes.

The exact candidate asset hashes independently reproduce the contract literals:

- `pilot.css`: `e18b92a5426d36f791773010f3e3dffc65fd50f35d19ce9b0e8f5216fe08607a`
- `preopening.js`: `f9db00abfb7cb6a7f27ebf322945de35573dd29c68574f7b4201735863880964`
- `template-offer.js`: `774faee3c0ca2700d63b22343733da61a671dc5ae73a0f1d894ba1eca61ba706`

The adjacent browser corrections remain necessary and preserve their original assertions. No duplicate offer marker/state owner remains. All earlier behavior, accessibility, security, DOM/form, layout, motion/cancellation, no-side-effect, exact-composition, and test-sensitivity findings remain resolved.

### Remaining finding

#### Low / blocking prior-disposition completeness — unrelated picker/upload compaction was not restored after extraction

The actual extraction makes `preopening.js` 108 lines, safely below the 150-line hotspot boundary, but the candidate retains formatting damage introduced by the rejected 186→149 compaction attempt. Against base `1aba76a…`, lines 5-7 still combine unrelated DOM bindings; line 36 compresses the full `renderChecks` callback; lines 38-40 combine element construction and mutations; line 53 combines `fetch` with its error branch; and line 71 joins two independent dialog listeners. The original-upload half similarly retains newly joined statements at lines 77-81, 86, 90-91, and elsewhere.

This no longer games the architecture threshold because responsibility was genuinely extracted, but it leaves the previous Gate 5 requirement to keep ordinary readable statement structure only partially satisfied. None of these compactions is needed to preserve the new seam or remain below the ratchet; they are unrelated formatting churn that makes picker/upload review and future diffs harder. Restore the retained picker/upload code to its pre-compaction readable formatting while preserving only the semantic removal of offer ownership. No specification or test-expectation change should be necessary.

### Verification evidence

The package records 13/13 focused commands GREEN, each on exact candidate `e31a4a93…` and executable `03423236…`. Coverage includes native selection failures, production runtime browser, Yii cutover/assets, lower and public architecture checks, existing preopening browser/failure flows, the new real asset ownership/transport check, core HTML and complete offer browser flows, Compose, verification governance, and runtime storage. `git diff --check` is clean.

These results establish behavior and integration correctness but do not erase the narrow maintainability disposition above. Full post-correction exact-source CI, merge state, and deployment remain outside this Gate 5 approval.

### Required changes

1. Restore readable pre-compaction formatting in the retained picker and original-upload code, keeping `template-offer.js` as the sole extracted offer owner.
2. Refresh exact asset hashes/package and rerun the affected asset, browser, architecture, and mapped focused checks.
3. Return the formatting-only exact source for final Gate 5 equivalence review; a Gate 3 restart is needed only if expectations or behavior change.

---

## Readable extracted architecture Gate 5 — latest controlling verdict

- Independent reviewer: Codex `/root/issue53_gate5`; author of none of the specification, tests, fixtures, production implementation, asset registration, timeout correction, or browser-consumer corrections
- Reviewed source: candidate source `bf83ecaf2e897f2e1aceb1bc125cfcdc509c1cf2772ffcc7c62310df3ec94603`; executable source `b09e8149152a644751338353fcbbe6bba07bb4dea02112c8b2808dc4cab2d430`; base `1aba76a11ac917df98d2873407c2144ee5b3004b` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T143003Z-d18687e27c/snapshot/source.patch` (SHA-256 `61c0368d84fda763f75178a38834bcefc0efce1f128fd2d06bed0c585127bbd6`)
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T143003Z-d18687e27c/package.json`; delta SHA-256 `932a64b40455ad63c20091e172880dd8e8dbbbfbd064325efbfef757776e8357`; plan SHA-256 `c1ddad25d5e29cf0c51800078ce512ceaf677b3bfca8201883f2adfecf43d4c3`
- Controlling Gate 3 approvals: `Ownership-correction Gate 3 — latest controlling verdict` and `OTIZ browser timeout test-delta review — latest controlling verdict` in `reviews/tests/TEMPLATE-OFFER-REVEAL-001.md`, both `APPROVED`
- Verdict: `APPROVED`

### Findings and complete prior disposition

None remain.

The final maintainability finding is resolved. `app/YiiRuntime/Assets/preopening.js` is restored to the base-readable picker/upload formatting and is 129 lines. Its diff against base consists of removal of the template-offer owner and calls plus the already necessary document-level dialog lookup; it contains no offer marker, saved-identity reconciliation, motion preference, or cancellation ownership. The prior unrelated statement-joining/minification churn is gone.

`app/YiiRuntime/Assets/template-offer.js` is the sole, readable 73-line owner of A1-A6 client behavior. It retains normalized sorted/unique identity comparison; exact action/copy state; inert-first bounded enter/exit motion; idempotent `transitionend`/`transitioncancel` finalization; runtime reduced-motion completion; and stale-event-safe reversal. Document-level change/click delegation correctly observes controls and picker-rendered installer inputs without importing picker implementation state.

The asset seam is complete and single-owned: `PreopeningAssetBundle` loads `template-offer.js` after the picker/upload asset; the Yii route and controller serve it with the established JavaScript MIME/cache policy; the immutable runtime contract inventories its exact bytes; and the public ownership test rejects both missing/dummy behavior and retained duplicate owner markers in `preopening.js`. The corrected existing browser consumers explicitly reconfirm the engineer after the saved GET and otherwise retain their prior template/original/OTIZ assertions.

The OTIZ helper delta changes only Playwright's bounded default timeout from 5 to 15 seconds. It adds no retry, skip, catch-and-ignore behavior, relaxed locator, alternate result, or removed assertion. Its independent Gate 3 approval records why the bound matches observed local Compose latency, and the exact-source production runtime browser command is GREEN.

All earlier findings remain resolved: initial fail-closed HTML, valid sibling forms, exact saved composition, stale-action disablement, forged POST failure atomicity, absence of automatic domain facts, focus/tab behavior, same-value determinism, 1440px adjacent and 360px overflow-safe layout, actual bidirectional motion, reduced-motion behavior, cancellation/media-switch recovery, single reduced-motion CSS owner, and readable architecture decomposition. No security, authorization, persistence, audit/history, application-command, upload/original/opening, or `rapid-pilot` ownership regression was found.

### Exact assets and verification

Independent SHA-256 calculation matches the final asset-contract literals:

- `pilot.css`: `e18b92a5426d36f791773010f3e3dffc65fd50f35d19ce9b0e8f5216fe08607a`
- `preopening.js`: `d0746e990b2b3a1756ab4122b955a859097319397c937ce98a68d2733e630fd6`
- `template-offer.js`: `774faee3c0ca2700d63b22343733da61a671dc5ae73a0f1d894ba1eca61ba706`

The package records 13/13 focused commands GREEN. Every record names candidate source `bf83ecaf2e897f2e1aceb1bc125cfcdc509c1cf2772ffcc7c62310df3ec94603` and executable source `b09e8149152a644751338353fcbbe6bba07bb4dea02112c8b2808dc4cab2d430`. The plan covers native selection failures, production runtime browser, Yii cutover and exact assets, lower/public architecture checks, prior preopening browser/failures, public offer-asset ownership/transport, core HTML and full offer browser behavior, Compose adjacency, verification governance, and runtime storage. `git diff --check` is clean.

The live harness still reports PR #114 at head `1aba76a…` with CI `FAILURE`, head/base mismatch, and `merge_ready: false`. This Gate 5 approval covers only the retained exact working-tree source above; it does not reclassify the old CI, approve publication, or imply merge/deployment readiness. A new exact committed candidate and authoritative full CI remain required.

### Required changes

None. Gate 5 is approved for exact candidate source `bf83ecaf2e897f2e1aceb1bc125cfcdc509c1cf2772ffcc7c62310df3ec94603` and executable source `b09e8149152a644751338353fcbbe6bba07bb4dea02112c8b2808dc4cab2d430`.

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

---

## Post-CI compaction Gate 5 — latest controlling verdict

- Independent reviewer: Codex `/root/issue53_gate5`; author of none of the specification, tests, fixtures, production code, browser-consumer corrections, or asset-digest updates
- Reviewed source: candidate source `527136227346e80a8e82d853e3cc2ccc9e096e06ad711bfbfdd433d7bf252659`; executable source `dd46bc763b16132580811f21d5becec5a396b70ae97ec2804b6492cf70d05988`; base `1aba76a11ac917df98d2873407c2144ee5b3004b` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T131349Z-fc552ab46a/snapshot/source.patch` (SHA-256 `754528668b46e875deedbeac9f80494d430c49021ac18a5696ae9bac2bec26fb`)
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T131349Z-fc552ab46a/package.json`; delta SHA-256 `a8ddea38895e722814e6210a4214091b4dd2f3fffb9eed5eb1409e653656e007`; plan SHA-256 `d1e1955d3359df3563b82b85dbce5dee10782471e75632c86405c467f253dfd7`
- Final test-delta approval: `Final exact-source test-delta review — controlling verdict` in `reviews/tests/TEMPLATE-OFFER-REVEAL-001.md`, verdict `APPROVED`
- Verdict: `CHANGES_REQUESTED`

### Finding

#### Medium / blocking — the hotspot ratchet is satisfied by source minification rather than a maintainable boundary

The production change in `app/YiiRuntime/Assets/preopening.js` is behaviorally equivalent under the reviewed browser contracts, but its reduction from 186 to exactly 149 physical lines is achieved almost entirely by joining independent declarations and statements onto single lines. It does not reduce responsibilities, control-flow complexity, state, or coupling. It also mechanically compacts the unrelated original-upload workflow at lines 117-149 even though issue #53 changes the selection/template offer.

This is not a maintainable resolution of the architecture finding. The documented hotspot policy in `docs/architecture/guardrails.md:23-25` says that moving behavior behind a seam may require more changed files and is preferred to adding behavior to a hotspot. The candidate instead encodes the same large module just below the scanner's physical-line threshold. The clearest symptom is line 123, which is 1,211 characters and contains the entire error-message map; multiple control-flow lines now combine assignments, conditionals, DOM updates, and returns. This materially impairs reviewability, debugging, future diffs, and ownership discovery while making `architecture-check` GREEN only through formatting.

Treat this as a possible **Divergent Change** smell and a hard maintainability failure for Gate 5: one asset still owns installer search/picker state, template-offer motion/exact identity, and original-PDF upload, while the correction hides that fact from the hotspot inventory. Extract a coherent behavior behind a named asset/module seam (the template offer or original upload are natural candidates), keep ordinary readable statement structure, and register/load/hash/test the resulting production assets through the existing Yii runtime boundary. Do not satisfy the threshold by minification or unrelated whitespace compaction.

### Conforming areas and prior findings

No behavioral, security, accessibility, DOM/form, persistence, or test-sensitivity regression was found in the compaction bytes. The template-offer state machine retains idempotent end/cancel/media-query finalization, stale-event-safe reversal, normal/reduced motion, exact normalized identity, fail-closed initial state, valid sibling forms, responsive two-column/one-column layout, focus preservation, and no automatic domain effects. All prior Gate 5 findings remain behaviorally resolved.

The two browser-consumer corrections are necessary compatibility updates rather than weakened tests: after the post-save GET they explicitly re-confirm the engineer before attempting the now-conditionally available template action. The template POST, upload, and continuing current-flow assertions remain intact.

The production asset contract is exact. Independently calculated SHA-256 values are `e18b92a5426d36f791773010f3e3dffc65fd50f35d19ce9b0e8f5216fe08607a` for `pilot.css` and `4175ae4a39c8657866f6077b2e24e72b5976705cd03e4981be75094df8c6b72b` for `preopening.js`; they equal the updated literals in `tests/Support/yii2_production_web_cutover_contract.php`. Other runtime asset expectations are unchanged. The architecture wrapper remains a transparent invocation of public `make architecture-check`; it does not bypass the canonical checker, although the production formatting described above defeats the intent of its physical-line heuristic.

### Verification evidence

All 12 package records are GREEN and source-stable: each records candidate source `527136227346e80a8e82d853e3cc2ccc9e096e06ad711bfbfdd433d7bf252659` and executable source `dd46bc763b16132580811f21d5becec5a396b70ae97ec2804b6492cf70d05988`. They cover the native selection failure boundary, production runtime browser, Yii production cutover, lower architecture guard, public architecture wrapper, both preopening browser/failure flows, template-offer HTML/browser acceptance, Compose adjacency, verification governance, and runtime storage. `git diff --check` is clean.

These GREEN records establish behavior equivalence but cannot make source readability or a genuine module boundary observable. Harness state also reports the current PR CI as `FAILURE` and `merge_ready: false`; this review does not reinterpret that state.

### Required changes

1. Replace the physical-line compaction with a coherent extraction that leaves both resulting production modules readable and below the hotspot frontier by responsibility, not formatting.
2. Update the Yii runtime asset registration/serving and immutable hash contract for the extracted asset without weakening existing asset, CSP/cache/MIME, browser, or architecture expectations.
3. Restore conventional one-statement/readable formatting in the untouched selection and original-upload behavior.
4. Obtain independent Gate 3 approval for any resulting test/asset-contract delta, rebuild the exact package, rerun the mapped checks, and return the full candidate to Gate 5.
