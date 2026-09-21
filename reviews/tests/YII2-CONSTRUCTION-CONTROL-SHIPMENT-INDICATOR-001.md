# YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001 — Gate 3 test review

- Reviewer: independent Codex reviewer `/root/issue16_gate3`; authored neither specification nor tests
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T141746Z-223fc42cfc/package.json`
- Exact reviewed source: base `80130fbb3bb7998a1a0fc6e88429699244715540` plus reconstructible snapshot, harness source `71b04ed78d1cf25cdc06adce74fa28eb5e096f496ce42fdebde077d18935f219`, executable source `3bfd5f51f9701e54bc76d2fa34e9f2050540978ac16e047a8a19dd0bfa7b29d0`
- Snapshot patch SHA-256: `d6f10d9862637316a49b5a34f087319f0cf5a680ecd737111759a6bab9778b33`
- Verification plan SHA-256: `61dd3a6015624cfa82b5120fb4a0a57ec7f2c57cd60c463174ddfc12edd27638`
- Review date: 2026-09-21

## Complete findings

1. **HIGH — the unknown/error matrix is only represented by readiness-only data.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:17`; `tests/Yii2/yii2_construction_control_shipment_indicator_001_test.php:42-45`. The normative contract separately requires three `NULL` values, `never_synced`, `failed_before_success`, and an unavailable projection not to become either a positive shipment fact or an invented negative fact. The test updates one successful current-projection row to leave readiness only. It never arranges an object without a current fact, a failed-before-success sync history, all-three-null current data, or projection unavailability. An implementation that correctly handles readiness but derives a positive/negative label from missing or failed synchronization—or silently treats storage failure as ordinary absence—can pass. Add distinct real HTTP cases for those states, including the declared runtime outcome for an unavailable projection, and prove no shipment claim plus unchanged complete facts.

2. **HIGH — accessibility, disclosure interaction, and row-navigation isolation are not executable.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:18-19`; `openspec/changes/show-equipment-shipment-in-control-queue/specs/shipment-indicator/spec.md:19-26`; `tests/Yii2/yii2_construction_control_shipment_indicator_001_test.php:33-40`. The test searches row substrings for two filenames, two visible labels, and `<details`; it does not assert the indicator's accessible name or bind label/date/icon within the disclosure, does not activate the control by keyboard and touch/click, and does not prove that opening it avoids the row's checklist navigation. An implementation with decorative icons only, an inaccessible or non-operable summary, text elsewhere in the row, or event propagation that navigates away can pass. Add a bounded browser/DOM acceptance that checks distinct accessible names and icon forms, focuses and activates both disclosures by keyboard and pointer/touch-equivalent input, observes the corresponding title/date, and proves the checklist row link is triggered only when the row navigation target itself is activated.

3. **MEDIUM — compact responsive presentation and public SHLZ provenance are asserted only by filenames.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:20`; `tests/Yii2/yii2_construction_control_shipment_indicator_001_test.php:34,37`. No test observes desktop/mobile layout, clipping/overflow, indicator footprint, or disclosure reachability at either viewport. Likewise, the presence of `delivery-box.svg` and `delivery-4.svg` in HTML does not prove that the served/copied assets are the public `shlz-ui` exports; arbitrary local files with those names pass. Add bounded viewport assertions for row/indicator containment and usable disclosure at representative desktop/mobile widths, plus an executable asset provenance/content check against the declared public `../shlz-ui` exports (or the repository's established SHLZ asset-copy verifier).

## RED evidence assessment

Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790000226583483000-f1caffc5335040a7ae37f1e602f776de.json` is exact-source bound with `source_drift=false`. The integration profile starts MariaDB, completes the existing fixture/opening/authentication contour, reaches the real Yii queue with HTTP 200, and fails at the first absent `data-shipment-state="first"` assertion. This is a valid intended RED for the missing positive indicator, not an environment/bootstrap failure.

Because the PHP test is fail-fast at line 34, the retained run provides no execution evidence for later priority, unknown, read-only, HEAD, or denied-read assertions. More importantly, the three sensitivity gaps above are not asserted by the test at all, so the clean first failure cannot compensate for them.

No local full `make test` or `make verify` was run. PR, CI, and deployment remain `UNKNOWN`; none is treated as GREEN or authorization.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 2 for the bounded unknown/error-state, accessibility/navigation, responsive-layout, and asset-provenance corrections; capture fresh exact-source intended RED evidence and regenerate the package before independent Gate 3 rereview.

---

## Correction rereview — 2026-09-21

- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T142236Z-990a7c2afc/package.json`
- Exact corrected source: base `80130fbb3bb7998a1a0fc6e88429699244715540` plus reconstructible snapshot, harness source `f1949b99ed7f67b545379a3f15bb8b250f85327797750974fe347e2bda9caa88`, executable source `013f9c4ec65cba3ab9e24698a3b0d641ffce9f4a0ea5d80720c437eee5bbabe9`
- Snapshot patch SHA-256: `5b29dba991474ff72a83282bb231142b72e5d1fdfbe9a69535e86b00c52b3855`
- Verification plan SHA-256: `68c4917dc28f3f6a9de90bbf99e22da5d86dc01578228a3b1f73ea850ec38897`

### Prior findings disposition

1. **Unknown/error matrix — resolved.** The corrected test separately arranges readiness-only, all-three-null, no current row, failed-before-success, and dropped/unavailable current projection states. It requires no positive or invented negative shipment claim, and the unavailable projection must fail explicitly with HTTP 503. GET, repeat GET, HEAD, and denied read remain bracketed by the complete database inventory before the deliberate availability fault.
2. **Accessibility/disclosure/navigation — partially resolved.** A launched Chromium check now exercises keyboard and pointer activation, exact distinct `aria-label` values, and observes that disclosure activation does not increment intercepted row-link navigation. The remaining positive-navigation and disclosure-content gaps are finding 1 below.
3. **Responsive/provenance — partially resolved.** The test pins SHA-256 values that independently match `../shlz-ui/packages/icons/dist/icons/delivery-box.svg` and `delivery-4.svg`, resolving provenance. Mobile containment is observed in Chromium. Desktop compactness and opened-disclosure mobile usability remain finding 2 below.

### Remaining findings

1. **HIGH — disclosure content binding and row-navigation isolation are still only half-observed.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:19`; `tests/Support/construction_control_shipment_indicator_browser.cjs:4`; `tests/Yii2/yii2_construction_control_shipment_indicator_001_test.php:53-55`. The browser reads `aria-label`, opens each `<details>`, and proves the intercepted row-link count stays zero, but it never inspects the opened disclosure's visible title/date. PHP substring searches do not bind those strings to the `<details>` content, so an empty disclosure with the label/date elsewhere in the row passes. It also never clicks the actual checklist navigation target to prove that the interception observer and row affordance work; a missing/broken row link produces the same `[0,0]` result. Assert the visible expanded content for each state/date, then activate the actual row/checklist link and require exactly one navigation observation while disclosure activation remains zero.

2. **MEDIUM — “compact on desktop and mobile” remains under-observed.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:20`; `tests/Support/construction_control_shipment_indicator_browser.cjs:4`. The browser creates a 1280px viewport but records no desktop geometry. At 390px it only requires the closed indicator rectangle to fit horizontally inside the row. It does not bound the indicator footprint/row overflow on desktop, nor verify that an opened disclosure is visible, contained, and reachable on mobile. A desktop indicator occupying the full row or an expanded mobile panel clipped outside the row/page passes. Record bounded desktop geometry and page/row overflow, and check the opened mobile disclosure's visibility/containment (or deliberate non-clipped overlay behavior) at the declared viewport.

### Corrected RED evidence assessment

Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790000525612556000-0b55f0fb95184ff4b51c4d16869fae32.json` is exact-source and drift-free. It again completes MariaDB/bootstrap, fixture opening/authentication, and a real HTTP 200 queue request, then fails on the expected absent first-shipment marker. That remains a valid intended RED for the missing production indicator.

The test is still fail-fast at line 35, before the new asset hash, Chromium, unknown/error, read-only, and projection-unavailable assertions. Thus the record does not demonstrate that the newly added browser launcher or later corrected cases execute successfully up to their intended missing-production boundaries. After completing the two bounded corrections above, retain aggregate or otherwise independently reachable exact-source RED evidence so every corrected acceptance group is exercised rather than hidden behind the first failure.

CI and deployment remain `UNKNOWN`; neither is treated as GREEN or authorization.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked only on the bounded disclosure-content/positive-navigation and complete responsive-geometry assertions, plus fresh evidence that reaches those corrected groups.

---

## Third Gate 3 rereview — 2026-09-21

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T142522Z-dbb654d3a8/package.json`
- Exact source: base `80130fbb3bb7998a1a0fc6e88429699244715540` plus reconstructible snapshot, harness source `e87cf059d1d09060694b9459fab5dd2bc7c227a101b3c0085a6e721b51899a45`, executable source `10e9733f5bb7df51ab37540a15c41c8bd05a3672a97838c250f24091a929048a`
- Snapshot patch SHA-256: `98fa2d8cf22927f4a2eac161f05bc18b3b01202265e8b3bd7ba081d9836cfc74`
- Verification plan SHA-256: `fe21ca9a299f21a11930be91784c7d80878fc8615a07240ad19f4def42d3ed3c`

### Remaining test findings disposition

Both remaining test-design findings are resolved.

- The browser now reads normalized expanded text after keyboard and pointer activation and the PHP acceptance binds each disclosure to its exact title and date. It separately clicks an actual row anchor and requires the navigation observer to advance from zero to one, while both disclosure activations retain zero. This is sensitive to empty/misbound details, broken positive navigation, and propagation from the indicator.
- Desktop geometry now bounds the indicator to 220px and its owning row at 1280px. The mobile observation keeps the full opened `<details>` horizontally contained in the row and requires a 44-by-44 minimum summary target at 390px. Together with actual opening and expanded-text checks, this covers compact desktop presentation and reachable opened mobile disclosure for the bounded contract.
- The unknown/error/read-only matrix and exact public SHLZ hashes remain complete as assessed in the correction rereview. No specification or executable-test sensitivity finding remains.

### Remaining evidence finding

1. **HIGH — the fresh intended-RED record still does not execute any corrected acceptance group.** Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790000692863626000-81461a95930d44ccafb2df06118998fc.json` is exact-source and drift-free, and it is a valid RED for the absent first positive indicator. However, it fails at `yii2_construction_control_shipment_indicator_001_test.php:35`, before asset provenance at lines 42-45, Chromium launch at lines 46-58, every unknown/error case at lines 60-72, the read-only matrix at lines 73-83, and unavailable-projection handling at lines 84-87. Its output contains only the first missing marker, exactly as the prior two records did. The prior rereview explicitly required aggregate or independently reachable evidence after the corrections; a fresh digest alone does not establish that the browser launcher works in the selected integration environment or that the later assertions reach their intended boundaries. Aggregate the independent acceptance groups (or split them into separately selected commands) so one missing production behavior does not mask all others, retain an exact-source record showing the expected failures from each group without setup/environment failure, and rebuild the package. No expectation change is needed.

CI and deployment remain `UNKNOWN`; neither is treated as GREEN or authorization.

### Third rereview verdict

`CHANGES_REQUESTED`

The specification and test matrix are approved in substance, but Gate 3 remains blocked on executable RED reachability. Once one exact-source evidence set exercises the browser, provenance, unknown/error, read-only, and unavailable-projection groups rather than stopping at the first marker, no further test-design correction is presently required.

---

## Fourth Gate 3 rereview — 2026-09-21

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T143020Z-c2713b7784/package.json`
- Exact source: base `80130fbb3bb7998a1a0fc6e88429699244715540` plus reconstructible snapshot, harness source `6f585da22db9581819280a94902609fca908f2b788b4a3aa86c6eac70e3e73b0`, executable source `4407a4fbd9a5325ca40f460a302953192136664e351b998d9a412799a72ae97e`
- Snapshot patch SHA-256: `2828b773436ad4a9f31b109540447f7cbe5d67dcd344d28ecfe4be32b29ed1fc`
- Verification plan SHA-256: `8933115f10c3da0c2ca4116e1403d9b658722b358103e4d2059d1c518dff2281`

### Aggregation assessment

The PHP acceptance now accumulates independent failures rather than stopping at the first missing marker. This resolves reachability for the HTTP/source groups: the exact-source record reports all first/full/full-without-first markers, both absent SHLZ assets, and the unavailable-projection status mismatch. The unknown/error and read-only groups execute without findings, which is appropriate against the pre-implementation source because absence of shipment presentation already satisfies those negative requirements. The fixture completes and the real HTTP seam remains available throughout the planned observations.

### Remaining evidence finding

1. **HIGH — the browser evidence is a setup failure, not intended product RED.** Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790000955484355000-93049f91acb84be1bc7b118f896538d4.json` runs the selected `browser` profile, but Chromium does not launch. Playwright reports: `Host system is missing dependencies to run browsers` and lists missing native libraries. Consequently the following accessible-name, interaction, expanded-content, navigation, desktop, and mobile failures all contain `null` values and merely cascade from the launcher failure; they do not reach missing product locators or any browser assertion boundary. This directly contradicts the package handoff claim that the browser profile loads Chromium. Environment/setup failure cannot be classified as `INTENDED_RED` and does not close the prior executable-reachability requirement. Fix the bounded browser profile/image so Chromium launches, or use an existing working repository browser runner/profile, then retain fresh exact-source aggregate evidence whose browser failure is at the absent shipment locator/product assertion rather than process startup. The HTTP/source aggregation can remain unchanged.

The record is source-bound and drift-free, but that does not convert an environment-only browser failure into acceptable RED. CI and deployment remain `UNKNOWN`; neither is treated as GREEN or authorization.

### Fourth rereview verdict

`CHANGES_REQUESTED`

Gate 3 remains blocked only on a clean browser-capable exact-source RED run. The specification, test assertions, aggregation design, HTTP/source RED groups, and negative-state/read-only coverage otherwise require no further correction at this time.

---

## Fifth and final Gate 3 rereview — 2026-09-21

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T143329Z-658160ba2b/package.json`
- Exact source: base `80130fbb3bb7998a1a0fc6e88429699244715540` plus reconstructible snapshot, harness source `c1d5ca682c910e39c48c7d1184871f8906ea5ab2aac2bb572f68678c757280e5`, executable source `2edb24ea51e03f4c253b57884783e7c83e78748d713bfed54f5935f50830a5ae`
- Snapshot patch SHA-256: `406cd6a60877622262e1e207d4d24d16e2bc1883ba202cade970f6a0168ebf2e`
- Verification plan SHA-256: `e2b51653a45bb37234afcbf4082c5d36f8a5d53b48d08ec70b2d526910a6e2ca`

### Final finding disposition

The sole remaining evidence finding is resolved without weakening the bounded contract.

The replacement helper is dependency-free and deterministic over the actual authenticated HTTP response plus the production CSS and queue client sources. It requires native, enabled `<details>/<summary>` disclosure semantics for keyboard and pointer operation; exact distinct accessible names; title/date content within each matching disclosure; the existing checklist-link positive control; an explicit shipment-disclosure guard in the row-click owner; a bounded 220px desktop indicator; and the declared mobile full-width/44px target rules. These observations preserve the approved sensitivity while removing the unavailable Chromium runtime from Gate 2 evidence. Exact SHLZ asset hashes, positive-state priority, unknown/error states, read-only behavior, and explicit projection-unavailable failure remain independently asserted in the same aggregate acceptance.

Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790001177305278000-6c841808d7004fd080ef86bf8d1883af.json` is exact-source and drift-free. It completes MariaDB/bootstrap, real fixture opening and authentication, all HTTP state transitions, the dependency-free DOM/CSS/client contract, and cleanup. Its 21 findings are exclusively expected missing-product gaps: positive markup/content/icons, absent public assets, accessible/native disclosure contract, responsive CSS, row-navigation guard, and unavailable-projection handling. There is no process-launch, dependency, parser, fixture, or environment failure. The existing checklist link is positively recognized, while the negative-state and read-only groups correctly pass against the pre-implementation source.

No specification, test-sensitivity, or intended-RED finding remains. CI and deployment remain `UNKNOWN`; neither is treated as GREEN or authorization.

### Final Gate 3 verdict

`APPROVED`

Gate 4 may proceed against exact source `c1d5ca682c910e39c48c7d1184871f8906ea5ab2aac2bb572f68678c757280e5`. Implementation must make the complete focused acceptance GREEN without changing expectations; any normative or executable-test change requires fresh Gate 2 evidence and independent Gate 3 review.

---

## Owner-directed UI replacement Gate 3 review — 2026-09-21

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T164138Z-d351faf8e9/package.json`
- Exact source: base `557166d918b16933538510c489ce5c99c2abaee0` plus reconstructible snapshot, harness source `d13779437be406f08a353866a8974ced5094a8ba10738d760fd302a3a9c951a8`, executable source `57ec4fa8ea11197fd273e0c891e37927f2c6c44f8f18ce74bebc97ef9a075661`
- Snapshot patch SHA-256: `d8ee555e2d87a035eaadff0c02e0e8012d35ddb966cbadd3e8b9d57c38ba984d`
- Verification plan SHA-256: `b97a888b4f5f6453013a3902ee1164d0787e88f47d7841a099c56065fb81ccce`
- Scope: specification/tests/INTENDED_RED only; production implementation not reviewed

### Complete findings

1. **HIGH — the executable contract and OpenSpec delta prescribe mutually incompatible UIs.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:15-22`; `openspec/changes/show-equipment-shipment-in-control-queue/specs/shipment-indicator/spec.md:5-26`. The owner-directed contract requires an always-visible three-state «Отгрузка» column, visible «Не известно», no disclosure, and the ready-to-open activity replacement. The still-bound delta instead requires no positive indicator for readiness and normatively requires a keyboard/touch disclosure that opens to reveal title/date. An implementation cannot both remove disclosure and satisfy the delta's disclosure scenario, and the delta contains none of the separate-column, visible-unknown, composed full icon, no-row-height, mobile-label, or ready-activity requirements. Replace the obsolete delta requirements/scenarios with the new owner decision before Gate 4; OpenSpec cannot remain normative for the rejected design.

2. **HIGH — the full composed icon and decorative-icon accessibility contract are not structurally sensitive.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:19-20`; `tests/Yii2/yii2_construction_control_shipment_indicator_001_test.php:38-49`; `tests/Support/construction_control_shipment_indicator_browser.cjs:3-5`. The test only searches the full row for `delivery-box.svg` and `checkmark.svg`; it does not bind the checkmark as a separate corner overlay on the box, so two unrelated sequential icons or a checkmark elsewhere pass. It also never requires empty `alt`/`aria-hidden` treatment for all three decorative icon forms or binds accessible status text to the shipment cell. Add a structural source/DOM assertion for one icon stack containing the box plus separately positioned corner checkmark, exact unknown/partial icon structure, decorative semantics, and visible status text as the accessible name. Bind the corner positioning/no-colored-container CSS rather than only checking for filenames and the absence of one `background` declaration on two selectors.

3. **MEDIUM — the ready-to-open relocation is asserted globally rather than on the qualifying row.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:22`; `tests/Yii2/yii2_construction_control_shipment_indicator_001_test.php:74-75`. The positive assertion accepts `data-activity-state="ready"` anywhere on the page, while the negative regular expression starts at the first `fm2-control-link` and scans an arbitrary 500 characters. It does not prove that the same fixture object is ready, has no inspection activity, shows «Готов к открытию» in its activity cell, omits «Инспекций ещё не было», and lacks a duplicate label in its identity cell. Extract the known qualifying row and its object/activity cells separately, then assert the complete replacement and absence rules within that row; retain a non-ready/no-inspection row proving the ordinary empty-activity copy remains unchanged.

### RED evidence assessment

Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790008858977164000-274d3829a31e4facb5e253e8d8540f31.json` is exact-source and drift-free. It completes MariaDB/bootstrap and the real authenticated Yii queue, then aggregates 21 expected missing-product gaps across the separate column, new labels, public assets, source-layout contract, removal of `delivery-4`, all unknown states, and ready activity. There is no setup or environment failure. This is valid intended RED for the assertions that exist, but it cannot compensate for the normative conflict and structural sensitivity gaps above.

CI and deployment retain their existing external dispositions and are not treated as Gate 3 evidence or authorization.

### Replacement-design verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Reconcile the OpenSpec delta with the owner-directed replacement, add bounded structural icon/accessibility and row-local ready-activity assertions, retain fresh aggregate exact-source RED, and resubmit for independent Gate 3 review.

---

## Replacement-design correction rereview — 2026-09-21

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T164713Z-eaf19c73ce/package.json`
- Exact source: base `557166d918b16933538510c489ce5c99c2abaee0` plus reconstructible snapshot, harness source `bcef6256c959ee2635f56f2fc101f9ec06b0d42c0efc7ddebd1cc1294c5fade7`, executable source `152a0a60b86effdadee76a30dadb37ac324e064f735a61b7429577fac9270753`
- Snapshot patch SHA-256: `673cac8b04f092a23963c79171586ac0ffe91a7c3978a0d84e186ba60add15fc`
- Verification plan SHA-256: `e4746735a246edb547fe7a81bbef4cefd33e85ecd73c3d735b78481310f54f43`

### Prior findings disposition

1. **Normative coherence — resolved.** The OpenSpec delta now requires the separate three-state column, full-over-partial priority, visible neutral unknown, no disclosure, box-plus-corner-checkmark composition, decorative icons, neutral compact layout, and the ready activity replacement. Its readiness scenario's “positive indicator absent” is compatible with rendering the explicitly required neutral unknown state.
2. **Icon/accessibility structure — partially resolved.** The test adds the intended icon-stack/icon/marker class names and `alt=""`, but the remaining binding defect is described below.
3. **Ready activity — resolved.** Object 4512 is explicitly moved to the ready process state; assertions extract its row and bind the ready copy to a `data-activity-state="ready"` cell while excluding it from that row's identity cell. Object 4513 independently proves the ordinary no-inspection copy remains.

### Remaining finding

1. **HIGH — the purported exact full-icon markup assertion remains an unordered row-wide substring check.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-SHIPMENT-INDICATOR-001.md:19-20`; `tests/Yii2/yii2_construction_control_shipment_indicator_001_test.php:38-50`; `tests/Support/construction_control_shipment_indicator_browser.cjs:3-5`. The loop only proves that the full row contains the strings `fm2-shipment-icon-stack`, `fm2-shipment-icon`, `delivery-box.svg`, `fm2-shipment-marker`, `checkmark.svg`, and one occurrence of `alt=""`. It does not prove that the box and marker are nested in the same stack, that both images are decorative, or that the marker is positioned in the corner. The helper still has no icon-structure observation and its CSS check only covers fixed height/inline display/no direct background. An implementation with the checkmark elsewhere in the cell, two sequential uncomposed icons, non-empty alt on one image, and an unpositioned marker passes. Parse or structurally match the shipment cell so the full state requires one stack containing exactly the decorative box image and decorative marker image; require unknown/partial images to be decorative too; and assert the marker selector has corner positioning relative to the stack. Retain the visible text labels as the accessible content.

### Corrected RED evidence

Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790009190501607000-9e9b9bf68ce04c20a33853ed6fe79bf4.json` is exact-source, drift-free, and aggregates 26 expected missing-product findings. It reaches the corrected row-local ready and ordinary controls and all three-state/layout groups without setup failure. Fresh RED remains required after the final structural icon assertions are added.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked only on the bounded composed-icon/decorative-semantics structural assertion. The normative artifacts and ready-activity coverage need no further correction at this time.

---

## Final replacement-design Gate 3 rereview — 2026-09-21

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T165048Z-a2a2abd8ed/package.json`
- Exact source: base `557166d918b16933538510c489ce5c99c2abaee0` plus reconstructible snapshot, harness source `3a4fae1757711f895f7c35df87bffdc91edc9011837b3de5179a7a6ef1350c99`, executable source `df60c88cc939745226218eb951acbc98e53f2e4174cc1960e0b49e3210f9360c`
- Snapshot patch SHA-256: `c0ca3e73f40b6f0588b152680d53c14d7149d04f58736077467820ea90b60151`
- Verification plan SHA-256: `4e662c023e7db491e9c790fcf70441c02a92495eb111ecd48c57e3b7308ca57f`

### Final finding disposition

The sole remaining finding is resolved.

- Full state is now bound by one exact structural expression to a single `fm2-shipment-icon-stack` with `aria-hidden="true"`, containing the decorative `delivery-box.svg` base immediately followed by the decorative `checkmark.svg` marker. Partial is independently bound to an exact decorative box-only stack, and unknown to an exact decorative `info-circle.svg` image.
- The source-layout helper requires the icon stack to be `position: relative` and the marker to be `position: absolute` with an inset rule, making the separately nested marker a corner overlay rather than an unrelated sequential icon.
- Visible cell text remains the accessible status content. The exact public hashes, three-state labels, separate fixed-height column, neutral styling, mobile label, no `delivery-4`, full priority, unknown matrix, row-local ready activity replacement, ordinary no-inspection control, read-only envelope, and unavailable-projection behavior remain covered.

Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790009409744760000-8e6fdf457d0449628a97a4bf7fafa533.json` is exact-source and drift-free. It aggregates 23 expected missing-product findings, including the three exact icon structures and relative/absolute corner positioning, without setup or environment failure.

No specification, test-sensitivity, or intended-RED finding remains for the owner-directed replacement design.

### Final replacement verdict

`APPROVED`

Gate 4 may proceed against exact source `3a4fae1757711f895f7c35df87bffdc91edc9011837b3de5179a7a6ef1350c99`. Implementation must make the complete replacement acceptance GREEN without changing expectations; any normative or executable-test change requires fresh Gate 2 evidence and independent Gate 3 review.
