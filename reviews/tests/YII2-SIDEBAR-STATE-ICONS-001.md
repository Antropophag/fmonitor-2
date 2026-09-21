# Test review: YII2-SIDEBAR-STATE-ICONS-001

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 reviewer (`/root/sidebar_icons_gate3`)
- Test author: root delivery agent (owner-authorized autonomous scope/spec/test authorship)
- Reviewed source: base `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T002351Z-fd2ae0cf28/snapshot/source.patch`; candidate source SHA-256 `a61c37f5bcccb144c67807eb1a6527aa2c1c71ec27280fdcf8f4302f21345278`
- Agreed review scope / prior findings disposition (for rereview): initial Gate 3 review; no prior findings
- Specification: `specs/YII2-SIDEBAR-STATE-ICONS-001.md`; OpenSpec change `openspec/changes/fix-sidebar-state-and-adopt-shlz-icons/`
- Public seam: authenticated full Yii `/pilot/*` navigation, sidebar toggle, first rendered geometry, and rendered action/navigation SVG
- Red commands and intended failures:
  - `php tests/Yii2/yii2_sidebar_state_icons_001_test.php`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789950195352382000-17f1decb7bc94eec984de69c6d43e98c.json`; exit `255` at the missing early bootstrap assertion
  - `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789950209351466000-13ff2f3c843042188d35704acb48b477.json`; exit `255` at the missing `calendar-sidebar` icon
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **CRITICAL — the browser acceptance test is disconnected from every registered/evidenced command.** Locations: `tests/Yii2/sidebar_state_icons_browser.mjs:1-23`; `tests/Yii2/yii2_main_navigation_001_test.php`; both retained evidence records. No code in `yii2_main_navigation_001_test.php` invokes `sidebar_state_icons_browser.mjs`. The browser-profile record stops in the PHP DOM assertions at `exact nav icon calendar-sidebar`; it never launches Chromium or observes sidebar geometry. The other record stops at a source-text assertion. Thus neither INTENDED_RED record demonstrates the defining missing behavior—collapsed geometry before the first visible frame—or even establishes that the Playwright fixture can reach the real authenticated page. Register/invoke the browser test through the mapped public acceptance command, fail closed on its exit/output, and retain fresh exact-source INTENDED_RED evidence whose observed failure is the incumbent post-paint expansion/collapse defect after HTTP, authentication, browser, and page setup succeed.

2. **HIGH — the browser candidate does not cover the contract's complete state and toggle matrix.** Locations: `specs/YII2-SIDEBAR-STATE-ICONS-001.md:13-16,21-27`; `tests/Yii2/sidebar_state_icons_browser.mjs:7-21`. It covers stored `false`, storage-read denial, and a partial no-JS geometry check, but not stored `true`, absent storage, or the required full transition from “Объекты монтажа” to “Календарь”. Its toggle assertions check width and localStorage only; they do not assert synchronous visible text, `aria-label`, directed icon, or hydration-marker cleanup. The no-JS assertion does not establish that all permitted links remain visible and keyboard-accessible. Add deterministic assertions for each state/fallback and both toggle directions at the public DOM seam, including the named cross-page transition and accessibility/icon state.

3. **HIGH — the promised icon audit and its leave-in-place decisions have no reviewable acceptance oracle.** Locations: `specs/YII2-SIDEBAR-STATE-ICONS-001.md:17-19`; `openspec/changes/fix-sidebar-state-and-adopt-shlz-icons/design.md:28-29,37-38`; `tests/Yii2/yii2_sidebar_state_icons_001_test.php:16`; `tests/Yii2/yii2_main_navigation_001_test.php:142-145`. The design claims an inventory of 72 baseline SVG occurrences spanning `app/YiiRuntime` and active `rapid-pilot`, classified into replaced and deliberately retained cases, but the candidate contains no auditable inventory/oracle enumerating those occurrences and reasons. Tests cover two new asset digests and main-navigation icons only; they do not prove the selected calendar action/status replacements, active-adapter coverage, or that unmatched cases were deliberately retained rather than missed. Add a deterministic audit artifact or executable inventory bound to the scoped files, with every replacement and every retained occurrence/reason, and test the rendered geometry/action identity of all selected replacements.

4. **HIGH — public-export provenance is environment-dependent for the rendered icon checks.** Locations: `tests/Yii2/yii2_main_navigation_001_test.php:83-88,142-145`; `openspec/changes/fix-sidebar-state-and-adopt-shlz-icons/design.md:28,37`. `$sourceSignature()` reads either an unconstrained `FMONITOR_SHLZ_UI_ROOT` or the sibling `../shlz-ui`, although the contract explicitly excludes the dirty sibling checkout and requires a clean, exact `origin/main` public dist. A caller can point the test at arbitrary matching SVGs, and the default is the prohibited mutable checkout. Bind expected geometry to reviewed immutable digests/fixtures generated from the recorded upstream commit (as the two new asset checks begin to do), and reject or remove mutable sibling/environment fallback as acceptance truth.

5. **MEDIUM — the early-bootstrap regression test is mostly implementation-token matching rather than the specified observable seam.** Locations: `tests/Yii2/yii2_sidebar_state_icons_001_test.php:6-15`. Substring presence/order for `localStorage.getItem`, dataset names, and exact CSS selectors can pass while the script reads the wrong value, mishandles exceptions, applies the wrong geometry, or marks hydration ready too early; conversely it constrains harmless implementation choices absent from the normative contract. Keep narrow source assertions only where they enforce pre-stylesheet order/CSP requirements, but make the connected browser oracle authoritative for state semantics, transition suppression, and cleanup. Add an actual response-level assertion that the early bootstrap is emitted in the page head in the same CSP-compatible form the browser executes.

The normative spec and OpenSpec are mutually coherent, expected calendar digests are independently fixed, the PHP fixture reaches authenticated Yii successfully, and both retained records are exact-source/no-drift failures for the assertions they actually reach. Those strengths do not close the missing browser execution and audit coverage.

## Required changes

1. Connect the Playwright test to a registered mapped command and retain clean exact-source RED showing the real first-frame defect after successful setup.
2. Complete the stored-state, fallback, cross-page, toggle accessibility/icon, hydration cleanup, and no-JS navigation matrix.
3. Supply and verify the bounded 72-occurrence icon audit, including every selected replacement and every retained case with its reason.
4. Make shlz-ui provenance immutable and independent of environment variables or the dirty sibling checkout.
5. Rebuild the verification plan/package after the corrected tests and evidence, then request Gate 3 rereview of that exact source.

---

## Rereview — source `1f78cf184e09999deb045fa9f6f2a06d59c18bd9b2ea267e16d248fef35256d3`

- Reviewer: same independent Gate 3 reviewer; authored neither specification nor tests
- Reviewed source: base `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T003107Z-cd1a88db7d/snapshot/source.patch`; candidate source SHA-256 `1f78cf184e09999deb045fa9f6f2a06d59c18bd9b2ea267e16d248fef35256d3`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T003107Z-cd1a88db7d/package.json`; plan SHA-256 `c3bfa95f8420bc7f606f8b233fcfc1475bc86497b91fcd4a76366b3dbf3f525f`
- Fresh sidebar RED: `php tests/Yii2/yii2_sidebar_state_icons_001_test.php`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789950612831114000-790eec1969aa471ea8d20cbda3f8ea23.json`; exact-source/no-drift exit `255` after successful authenticated Yii capture and host Chromium startup, at `INTENDED_RED stored false must be compact in first rendered response before navigation module`
- Fresh icon RED: `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789950635627664000-41f3f70a5a26415e9a6555e99e2dbfee.json`; exact-source/no-drift exit `255` at missing `calendar-sidebar`
- Rereview verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **FIXED.** `yii2_sidebar_state_icons_001_test.php` now obtains an authenticated, read-only Yii shell response through the browser profile and invokes `sidebar_state_icons_browser.mjs` with fail-closed exit/stderr handling. The retained RED proves fixture/database/login/HTTP capture, host Chromium, synthetic asset serving, and execution reach the exact pre-module geometry assertion. The failure is the incumbent expanded width with stored `false`, not setup or an earlier unrelated assertion.

2. **FIXED.** The browser matrix now covers stored `false`, stored `true`, absent value, storage denial, no-JS fallback, hydration-marker cleanup, both toggle directions, persisted values, visible text, `aria-label`, directed icon state, and an objects/calendar full-page transition. The no-JS branch checks all rendered permitted links remain visible anchors. Expected values remain independently stated by the normative contract.

3. **OPEN (HIGH).** Locations: `docs/operations/sidebar-icon-audit-2026-09-21.md:1-47`; `tests/Yii2/yii2_sidebar_state_icons_001_test.php:15`. The new document is a useful human inventory, but the claimed executable audit only counts 29 Markdown rows and searches three location literals plus seven icon-name literals. It never scans `app/YiiRuntime` and active `rapid-pilot`, never proves that each table row corresponds to an actual baseline SVG occurrence, never detects an omitted/new occurrence, and does not bind each `REPLACE`/`SHLZ` decision to a source declaration or immutable geometry. Apart from the three calendar rows and MainNavigation, the selected replacements—logout, search, row transition, upload, and add—can remain absent or use unrelated geometry while this test passes. This does not satisfy the prior requirement for a deterministic inventory bound to the scoped files or the normative requirement that the audit cover the working contour and replace every unambiguous match. Generate/parse the occurrence inventory from the exact baseline (or maintain a checked manifest with per-occurrence identity/digest), compare it one-to-one with the 29 decisions, and assert the declared public icon plus immutable digest/rendered geometry for every `REPLACE`/`SHLZ` case selected by this slice.

4. **FIXED.** Main-navigation expected geometry now comes only from repository-pinned assets checked against an immutable digest map. The acceptance oracle no longer reads `FMONITOR_SHLZ_UI_ROOT` or the mutable sibling checkout. The two new calendar assets are independently pinned to the recorded `shlz-ui origin/main 45a99e7` digests.

5. **PARTIALLY FIXED (MEDIUM).** The authoritative state semantics are now exercised by Chromium against authenticated Yii response bytes, so source-token matching no longer stands in for observable behavior. However, the capture passes only the response body into a synthetic Node HTTP server; it neither preserves nor asserts the real Yii CSP headers/nonce relationship. The static `strpos` check still establishes source placement but cannot prove that the emitted early bootstrap executes under the real response policy. Since the design identifies CSP compatibility as an explicit risk, capture and apply/assert the relevant real response headers or add an existing response-level CSP oracle proving the bootstrap form is permitted. This remains a correction, though finding 3 independently blocks approval.

### Rereview findings

No new scope findings beyond the two open dispositions above. The corrected RED is deterministic and sensitive to the defining sidebar defect, the state/accessibility matrix is complete for the agreed seam, expected sidebar values are contract-derived, icon digests are immutable, and repeated capture is asserted read-only. Gate 3 cannot advance while the icon-audit acceptance can pass with incomplete or incorrect source coverage.

### Rereview required changes

1. Bind all 29 audit decisions one-to-one to the actual scoped baseline occurrences and enforce every selected replacement's declared public export and immutable geometry/provenance.
2. Add response-policy evidence for the early bootstrap's real CSP-compatible execution, or explicitly close that recorded design risk with an existing authoritative CSP oracle.
3. Rebuild the exact-source package/evidence after correction and return the delta for rereview.

---

## Third rereview — source `1dccc50c9483a2e03815a2ec336711daa6c5925f29e5249de99f1a1e253041bf`

- Reviewer: same independent Gate 3 reviewer; authored neither specification nor tests
- Reviewed source: base `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T003722Z-e1e9031b08/snapshot/source.patch`; candidate source SHA-256 `1dccc50c9483a2e03815a2ec336711daa6c5925f29e5249de99f1a1e253041bf`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T003722Z-e1e9031b08/package.json`; plan SHA-256 `4562afbbb78c86b34439fcecf6d1c9a5cab35d5bb1b08238ae85c56ceb69f530`
- Fresh sidebar RED: `php tests/Yii2/yii2_sidebar_state_icons_001_test.php`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789950955536528000-0ac34401e66e40b792dd072924dd1a1b.json`; exact-source/no-drift exit `255` at the intended pre-module collapsed-width assertion after authenticated capture, real CSP capture, and Chromium startup
- Fresh icon RED: `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789951016137538000-6d44b164674a4d7b9159f086610e8e54.json`; exact-source/no-drift exit `255` at missing `calendar-sidebar`
- Third-rereview verdict: `CHANGES_REQUESTED`

### Remaining findings disposition

1. **Prior finding 3 remains OPEN (HIGH).** Locations: `tests/Yii2/yii2_sidebar_state_icons_001_test.php:14`; `docs/operations/sidebar-icon-audit-2026-09-21.md:1-49`. Stable `file#ordinal` keys and the live `rg` rescan now prove that every currently detected pre-implementation occurrence has exactly one table row and that the table has no extra rows. That closes stale/omitted-row detection for the RED source, but it still does not test the required replacement result:

   - The rescan is performed against the mutable candidate rather than the fixed baseline `e67b566d…`, while the document declares a baseline inventory. Correctly replacing an inline `<svg>` with a helper/public asset removes that occurrence from the scan and changes later ordinals; the hard assertions `count($keys) === 29` and one row per current key can therefore reject the intended implementation.
   - Conversely, leaving all manual SVGs untouched while merely adding the seven digest-correct asset files satisfies the audit assertions. The test checks only that each export name occurs somewhere in the Markdown; it never proves that `rapid-pilot/Shell.php#1`, each logout occurrence, search, row transition, upload, add, or the calendar action/status occurrences actually render or declare the assigned export.
   - One global digest per export proves asset provenance but does not bind an occurrence decision to that asset. The earlier requested one-to-one `occurrence → decision → public export/digest` acceptance remains absent.

   Preserve a fixed baseline manifest separately from the post-change scan. For every `REPLACE`, assert the old baseline occurrence is gone or transformed and the owning composition declares/renders the exact assigned `data-shlz-icon`/public geometry. For every `RETAIN`, assert its stable semantic fingerprint remains classified. Then assert there are no unclassified current executable SVG/action-icon occurrences. The test must pass after the intended replacements and fail if any selected inline/manual icon is left unchanged.

2. **Prior finding 5 is FIXED.** The authenticated capture now requires and transports the real Yii `Content-Security-Policy` header. The synthetic first-frame server replays that exact policy, Chromium confirms the expected `script-src 'self'` policy, and the hydrated reload records and rejects CSP console violations. This makes the browser oracle sensitive to an early bootstrap that the production response policy would block.

### Third-rereview findings

No new independent findings. The sidebar/state/CSP acceptance remains valid and its RED is for the exact missing behavior. All seven selected public exports now have immutable `origin/main 45a99e7` digests. Gate 3 remains blocked solely because the icon audit can both accept an implementation that does not perform most promised replacements and reject a correct implementation that removes the baseline inline SVG occurrences.

### Third-rereview required changes

1. Split immutable baseline inventory from current-source verification and make the current oracle validate each `REPLACE` at its owning composition against the assigned export/digest, each `RETAIN` against a stable fingerprint, and absence of unclassified current occurrences.
2. Demonstrate that the corrected audit test is GREEN only when all selected replacements are applied and retains a focused INTENDED_RED for at least one currently missing selected replacement without being blocked by the expected baseline-to-current occurrence-count change.
3. Rebuild the package/evidence and return the bounded audit delta for rereview.

---

## Fourth rereview — source `74e9eac7034f28bfd4586ffe00420f0d3ab74aeb4c21dba5eb5afbfd10ca8f6d`

- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T004129Z-98f989a1f4/package.json`
- Reviewed snapshot: base `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce` plus the package snapshot; candidate source SHA-256 `74e9eac7034f28bfd4586ffe00420f0d3ab74aeb4c21dba5eb5afbfd10ca8f6d`; plan SHA-256 `d0ff3bbe2bc34a24a763b1d831e56dce4130c539a6b78a9d7c48a95adb3a84e9`
- Fresh sidebar RED: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789951194959822000-99d3cc9b5f8d4c698e40c87b5ce75dcc.json`, exact-source/no-drift `INTENDED_RED` at the same valid first-frame assertion
- Fresh icon RED: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789951263446552000-cac0dc9db2d84608a751dc360ea09a04.json`, exact-source/no-drift `INTENDED_RED` at missing `calendar-sidebar`
- Fourth-rereview verdict: `CHANGES_REQUESTED`

### Prior blocker disposition

**PARTIALLY FIXED; one HIGH false-GREEN path remains.** The immutable JSON manifest correctly separates the 29-entry baseline from the post-change scan. Baseline fingerprints, decisions and assigned exports are explicit; `RETAIN`/`SHLZ` fingerprints must survive; `REPLACE` fingerprints and known manual geometries must disappear; all currently scanned occurrences must classify; and all seven exports are digest-bound. This resolves the previous fixed-count/ordinal rejection of a correct implementation.

The per-owner replacement assertion is still not semantically bound to an icon render seam:

```php
str_contains(file_get_contents($entry['file']), $entry['assigned_export'])
```

For every `logout` entry, the unchanged form action `/pilot/logout` already contains the assigned string. For the `search` entry, unchanged `type="search"`, `data-control-search`, class names and labels already contain `search`. An implementation can delete the legacy SVG without inserting any replacement icon and satisfy both “old fingerprint removed” and “assigned export rendered by owner”. The current-occurrence classifier will not notice the missing icon because a helper call or absence of an SVG produces no scanned occurrence. Thus the exact false-GREEN described in the previous finding remains for multiple selected replacements.

### Required correction

Bind every `REPLACE` entry to an unambiguous icon declaration or rendered DOM geometry, not a free substring in the owner file. Examples include parsing a canonical helper call with its exact argument, requiring `data-shlz-icon="<export>"` at the owning action, or rendering the composition and comparing its SVG signature with the pinned digest. Add a negative mutation/proof that removing the old `logout`/`search` SVG without adding the assigned icon fails. The existing immutable baseline, RETAIN checks, current classification, CSP oracle and RED records otherwise satisfy Gate 3.

---

## Fifth rereview — source `488c89fc6a555fb150a1853272957d5bb152853bbb8d2c44ec728c4646193c4d`

- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T004448Z-1ed9bcab1f/package.json`
- Reviewed snapshot: base `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce` plus the package snapshot; candidate source SHA-256 `488c89fc6a555fb150a1853272957d5bb152853bbb8d2c44ec728c4646193c4d`; plan SHA-256 `68daffc48e5b573369a4747a1d352a1aa21ef2837232fe9810ea8fd72ee78b45`
- Fresh sidebar RED: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789951401964733000-6048ddb5000c47a395245109987ed02c.json`, exact-source `INTENDED_RED`
- Fresh icon RED: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789951463741843000-0c0d33af52e14b9d80747c5afe077a3d.json`, exact-source `INTENDED_RED`
- Fifth-rereview verdict: `CHANGES_REQUESTED`

### Fourth-review correction disposition

**The free-substring false GREEN is FIXED.** `rapid-pilot/Shell.php#1` is correctly reclassified as existing `SHLZ`. A `REPLACE` now accepts only an exact `MainNavigation::icon('<name>')`, `data-shlz-icon='<name>'`, sprite `#shlz-icon-<name>`, or `/shlz-icons/<name>.svg` form. Bare route, input type, class and label strings no longer satisfy `logout` or `search` provenance.

### Remaining finding

1. **HIGH — repeated assignments in one owner are not cardinality-bound.** Locations: `docs/operations/sidebar-icon-audit-2026-09-21.json` entries `rapid-pilot/InspectionSchedule.php#1` and `#2`; `tests/Yii2/yii2_sidebar_state_icons_001_test.php:14`. Both baseline occurrences are separate required compositions and both are assigned `calendar-interface`, but `$exactBinding()` returns only a boolean for the whole file. The loop evaluates that same single match as `true` for both entries. An implementation can remove both legacy fingerprints/geometries and add `calendar-interface` to only the planning button or only the scheduled-status composition; every current assertion then passes. This violates the normative scenario requiring both compositions and leaves the audit's one-to-one claim incomplete.

### Required correction

Make replacement binding occurrence-aware or cardinality-aware. At minimum, group manifest entries by `file + assigned_export` and require at least that many distinct exact bindings; preferably bind each entry to a stable surrounding action/composition selector and assert the rendered SVG in both the planning button and scheduled-status output. Add a negative proof/mutation where one of the two `InspectionSchedule` bindings is absent and require failure. All other earlier Gate 3 findings are now closed.

---

## Sixth rereview — source `d0277762a97ffe5df6bb7a15d3832686ae8bd1c53b6adc795170e9f0bac3bc31`

- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T004748Z-fa5f31f49e/package.json`
- Reviewed snapshot: base `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce` plus the package snapshot; candidate source SHA-256 `d0277762a97ffe5df6bb7a15d3832686ae8bd1c53b6adc795170e9f0bac3bc31`; plan SHA-256 `60f6db369ee5e24c96c186f6d0294981fcd8e038ed759525930ec61e88105fa7`
- Fresh sidebar RED: `php tests/Yii2/yii2_sidebar_state_icons_001_test.php`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789951586300590000-b5d4dc675c4d4f4e8de48d4c6e299023.json`; exact-source/no-drift `INTENDED_RED` at the incumbent pre-module expanded geometry after valid authenticated Yii/CSP/Chromium setup
- Fresh icon RED: `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789951645342992000-9ee152ae9102438882a4c9e31d19f0ff.json`; exact-source/no-drift `INTENDED_RED` at the incumbent missing `calendar-sidebar`
- Sixth-rereview verdict: `APPROVED`

### Final prior-finding disposition

**FIXED.** Replacement requirements are grouped by `file|assigned_export`; `preg_match_all()` must find at least as many exact icon bindings as the manifest contains separate `REPLACE` entries. Consequently `rapid-pilot/InspectionSchedule.php|calendar-interface` requires two distinct bindings, so one calendar icon can no longer stand in for both the planning-button and scheduled-status compositions.

### Final findings

None. All prior findings are closed. The reviewed tests now provide:

- a real authenticated Yii response and host-Chromium first-frame seam with real CSP replay;
- complete stored-state, fallback, toggle, accessibility, hydration and no-JS coverage;
- deterministic exact-source RED for the defining sidebar defect and calendar navigation icon;
- immutable public-export digests independent of sibling/environment state;
- an immutable 29-entry baseline inventory separated from current-source validation;
- preservation checks for `RETAIN`/`SHLZ`, removal checks for manual `REPLACE` fingerprints/geometries, exact and cardinality-aware assigned-export bindings, and rejection of unclassified current occurrences;
- read-only/fact-preservation evidence at the authenticated capture seam.

The tests are traceable to `YII2-SIDEBAR-STATE-ICONS-001`, use the agreed public seams, derive expected behavior independently from the normative contract, fail for missing production behavior rather than setup, and are deterministic within the prepared browser/service profile. Gate 3 is approved for implementation against this exact reviewed source.

---

## Post-Gate-4 narrow test-delta review — source `dc4a94af9302ceafdbe3cd6453c4211204afcec0db196748117190961020d66c`

- Reviewed scope: root-authored delta in `tests/Yii2/sidebar_state_icons_browser.mjs` only; production code was not reviewed or changed here
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T010656Z-33ecae42e8/package.json`; candidate source SHA-256 `dc4a94af9302ceafdbe3cd6453c4211204afcec0db196748117190961020d66c`; plan SHA-256 `c4ff457dc7aad8a5bdac883f7f5768f177a7d4b003283f90ca547836f145020e`
- Historical sensitivity lineage: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789951586300590000-b5d4dc675c4d4f4e8de48d4c6e299023.json`, exact-source/no-drift `INTENDED_RED` at the pre-module first-frame collapsed-geometry assertion
- Current sidebar GREEN: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789952817690844000-f1c6975534774bfaacba2c679d7dfc3d.json`, source/end source `dc4a94af…`, no drift, exit `0`, `PASS first-frame/state/toggle/no-js matrix`
- Current main-navigation GREEN: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789952880402971000-ce39ad19ada1440dbd354acee3120020.json`, source/end source `dc4a94af…`, no drift, exit `0`
- Verdict: `APPROVED`

### Findings

None. Immediately after each summary activation, before any geometry wait, the test still requires the contractually synchronous visible text, `aria-label`, directed-icon marker, and persisted `localStorage` value. It then waits on the observable terminal width before asserting completion of the existing interactive CSS transition. This distinguishes synchronous accessible/control state from intentionally animated geometry and does not weaken the no-flash requirement: the pre-module first-frame width assertion remains immediate and unchanged.

The correction removes a false expectation of zero-duration manual-toggle geometry, matches the OpenSpec decision to preserve ordinary interactive animation after hydration, retains the independently reviewed expected values, and remains deterministic without a hard-coded sleep. The historical exact-source RED continues to demonstrate sensitivity to the original first-frame defect, while both affected focused commands are GREEN on the corrected exact source.
