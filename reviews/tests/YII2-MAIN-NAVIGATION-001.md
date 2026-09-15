# Test review: YII2-MAIN-NAVIGATION-001

- Reviewer: Codex independent sol/low reviewer `/root/gate3_review`; authored neither specification nor test
- Test author: root agent for issue #150, as assigned by `docs/operations/current-delivery-goal.md`
- Reviewed source: base commit `25aee5524f790292d350175ba278bc47e282ed4c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T113244Z-9da15cef84/snapshot/source.patch`, patch SHA-256 `3432eb46d158de997e876186bf0604dfdde18db3c0dc9a25ae4c2f89eefb6b81`, candidate source `b928cf043d9babae106213bc2d0d53caa52ca90bc91fe5a8d83cfc9ab6e5cfdf`
- Agreed review scope / prior findings disposition (for rereview): first whole-candidate Gate 3 review; no prior findings
- Specification: `specs/YII2-MAIN-NAVIGATION-001.md`; OpenSpec delta `openspec/changes/unify-yii-main-navigation/specs/runtime/yii-main-navigation/spec.md`
- Public seam: real Yii HTTP GET responses and semantic DOM on the five issue #150 routes
- Verification plan: prepared package `20260915T113244Z-9da15cef84`, plan SHA-256 `f1dfe43fef4b05c4a55cfaab90990413df9c5f794b3f1e3fd1d8bffbfe965147`; lane `CRITICAL`; required reviews `gate3`, `final`; four planner obligations represented by the mapped HTTP test and governance/unit/full-CI commands
- Red command and intended failure: `php tests/Yii2/yii2_main_navigation_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789471937388715000-12dcd72e140b4802bbb6fa3b963bb9e8.json` exits `255` after successful fixture startup/login at the intended missing-links assertion on `/pilot/objects` (expected construction-control and OTIZ links are absent)
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — the main-navigation oracle discards required links and labels before comparing the DOM.** `tests/Yii2/yii2_main_navigation_001_test.php:28-52` admits only the five section hrefs, so it cannot detect a missing, duplicated, inconsistent, or wrong-return-path «Обратная связь» link required by `specs/YII2-MAIN-NAVIGATION-001.md:22`. It records link labels but never asserts them, leaving the normative permission-to-label mapping at lines 16-20 insensitive. The same filter means an extra non-section link carrying `aria-current="page"` is invisible, despite the exactly-one rule at line 24. Parse every direct MAIN-navigation link, assert the exact ordered section href/label pairs plus one feedback link whose decoded `from` value equals the current route on every surface, and calculate `aria-current` across all MAIN-navigation anchors.

2. **High — the effective-permission matrix does not prove all four normative mappings.** `tests/Yii2/yii2_main_navigation_001_test.php:75-88` removes only `otiz.manage` and `construction_control.read`; every asserted state retains `objects.read` and `access.administer`. An implementation that always renders Objects or both admin links, ignores either permission, or couples admin visibility to the current view would pass. This leaves `specs/YII2-MAIN-NAVIGATION-001.md:16-21,25` and the OpenSpec limited-administrative-combination scenario only partially covered. Add accessible-page cases without `objects.read` and without `access.administer`, assert both admin links appear and disappear together solely with `access.administer`, and retain at least one changed combination while opening each route that remains authorized.

3. **Medium — read-only and concurrency coverage is narrower than the complete contract.** The only facts comparison at `tests/Yii2/yii2_main_navigation_001_test.php:65-68` surrounds the initial full-permission sequential reads; later permission-filtered reads and direct denials are outside that oracle. The test repeats requests serially but never overlaps them, although `openspec/changes/unify-yii-main-navigation/specs/runtime/yii-main-navigation/spec.md:38-40` requires repeated or parallel responses to independently reflect current permissions without persistence/audit operations. Add before/after fact assertions around each read/denial phase and a bounded distinct-request concurrency case (or an equivalent deterministic barrier) that checks independent DOM results and zero facts.

4. **Medium — the OTIZ preservation assertion does not prove that internal navigation remains separate from MAIN navigation.** `tests/Yii2/yii2_main_navigation_001_test.php:70-73` searches the entire response body for three href substrings. It would pass if those links were moved into the shared MAIN nav, duplicated in unrelated markup, or supplied only in script text, contrary to `specs/YII2-MAIN-NAVIGATION-001.md:27` and the OpenSpec presentation-boundary requirement. Locate the internal OTIZ navigation semantically outside `nav[aria-label="Основная навигация"]` and assert its exact three href/label pairs there.

The fixture is isolated, the public HTTP seam is appropriate, expectations for the five canonical hrefs are independently stated, and the retained RED is an intended missing-behavior failure rather than a setup failure. Those strengths do not close the sensitivity and matrix gaps above.

## Required changes

Resolve findings 1-4, regenerate the verification plan/package for the changed test source, and retain fresh intended RED evidence. Gate 4 implementation remains blocked pending independent approval of the corrected complete candidate.

## Rereview 1 — corrected whole candidate

- Reviewer: Codex independent sol/low reviewer `/root/gate3_review`; authored neither specification nor test
- Reviewed source: base commit `25aee5524f790292d350175ba278bc47e282ed4c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T113719Z-5fca1abfd3/snapshot/source.patch`, patch SHA-256 `57e6929f86918e09c4c6015609a00b54dedc1b1f8c29f288f3192608d71741bd`, candidate source `8e5e132d10d7362199624cac329d78294e60e558550b12d1dc86da1bbea0696c`
- Prior findings disposition: findings 2 and 4 are resolved; finding 1 is substantially corrected but exposes one remaining independent-expectation defect; finding 3's read-only coverage is resolved and its concurrency request is withdrawn because the corrected OpenSpec delta explicitly narrows the scenario to repeated reads, matching the stable normative contract
- Verification plan: prepared package `20260915T113719Z-5fca1abfd3`, plan SHA-256 `2cb66d3fd38fcb8aa4caf099ae116381cdd04f00c5a1721ccdf8430050f4c1d4`; lane `CRITICAL`; required reviews `gate3`, `final`; mapping now names labels, feedback return path, all four permission exclusions, distinct OTIZ navigation, authorization and repeated read-only requests
- Fresh RED evidence: `php tests/Yii2/yii2_main_navigation_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789472198942769000-5b8e72b0a82c41cdba8010cfc235a361.json` exits `255` after successful fixture startup/login at the intended exact MAIN-link assertion on `/pilot/objects`; actual output is the pre-change menu and lacks the expected construction-control/OTIZ links
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **Medium — the exact ordered expectation invents a feedback-link position not owned by the normative contract.** `tests/Yii2/yii2_main_navigation_001_test.php:66-74` always inserts «Обратная связь» immediately before `/pilot/admin/users` when that permission is present. `specs/YII2-MAIN-NAVIGATION-001.md:16-23` defines the section href/label mappings, requires feedback with the current return path, and requires the ordered set to be identical across surfaces, but it never says feedback precedes the administrative links (the prose actually lists all section mappings before introducing feedback). Consequently, a conforming renderer with one stable order such as all five section links followed by feedback would fail, while the expected position is derived from neither a normative example nor an independently stated product decision. Either specify the complete canonical order in the normative contract and keep an exact-order oracle, or assert the exact required href/label membership and feedback return path independently while deriving the order from one response and requiring that same order on the other four surfaces and permission-filtered subsets.

The correction otherwise makes all MAIN anchors observable, asserts labels and route-specific feedback URLs, computes `aria-current` across the complete MAIN nav, exercises absence of each of the four permissions (including paired admin links), checks authenticated direct denial for all five routes, brackets every permission/denial phase with a no-facts oracle, repeats the five-route reads, and verifies the exact three-link OTIZ internal navigation in a distinct semantic `nav`. The fresh RED remains setup-independent and fails for missing behavior.

### Required changes

Resolve the remaining ordering ambiguity at Gate 1 or make the Gate 2 oracle test only the order actually required by the current contract. Rebuild the exact package and retain fresh intended RED evidence before implementation.

## Rereview 2 — complete corrected candidate

- Reviewer: Codex independent sol/low reviewer `/root/gate3_review`; authored neither specification nor test
- Reviewed source: base commit `25aee5524f790292d350175ba278bc47e282ed4c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T113954Z-0e33905d57/snapshot/source.patch`, patch SHA-256 `ce6f41364ebc3054696d649c2b59071c7cf3529f338d553f0d61d404f998d28d`, candidate source `6c4aeeefee3b6201af29befc37a0512ec9999d986f13c03cfd203847d68df160`
- Prior findings disposition: all first-review findings 1-4 and rereview-1 finding 1 are resolved for the agreed issue #150 slice; the concurrency portion of original finding 3 remains withdrawn under the reviewed repeated-read OpenSpec clarification
- Verification plan: prepared package `20260915T113954Z-0e33905d57`, plan SHA-256 `adec9d976445c63aa89bc02e2a17434aca67cdb749db3100984ff1a018831afb`; lane `CRITICAL`; required reviews `gate3`, `final`; no missing tests reported
- Fresh RED evidence: `php tests/Yii2/yii2_main_navigation_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789472371208681000-a337318686f9475287197b0e0f60fea0.json` exits `255` after successful fixture startup/login at the intended membership assertion on `/pilot/objects`; expected construction-control and OTIZ links remain absent in the unimplemented source
- Syntax evidence: `php -l tests/Yii2/yii2_main_navigation_001_test.php` reports no syntax errors
- Verdict: `APPROVED`

### Findings

None.

### Disposition

The final correction separates exact membership, labels and route-specific feedback return paths from ordering. It derives order from the first accessible surface only and compares that normalized order across permission-equivalent surfaces, so it detects the required cross-surface inconsistency without inventing an unspecified canonical position. Together with the prior correction, the complete test now covers all four permission mappings, both admin links as one permission result, all five authenticated direct-route denials, guest login redirect, exactly one `aria-current` across every MAIN anchor, repeated read-only responses with phase-local no-facts assertions, and exact OTIZ internal navigation in a distinct semantic `nav`.

Gate 3 is approved for implementation from the exact retained snapshot above. Any subsequent specification or test change requires review of the changed delta under the recomputed plan.

## Post-approval setup-delta review

- Reviewer: Codex independent sol/low reviewer `/root/gate3_review`; authored neither the test nor production implementation
- Reviewed delta: `tests/Yii2/yii2_main_navigation_001_test.php:14` adds only `ALTER TABLE fm_maintable ADD responsstroicontrol VARCHAR(80) NULL` immediately after isolated `ObjectQueueFixture` construction; no expectation, acceptance mapping, permission matrix, or DOM oracle changed
- Reviewed exact GREEN source: candidate source `948b50caa432ffba8bf7ca36ba53786f89a4278a3744189ffdd8fcb1cfc07316`, executable source `885c339397ac46c3a471fa8d3f56d1afde96c38accddd35adbd94fe17ee3b17e`
- GREEN evidence: `php tests/Yii2/yii2_main_navigation_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789472998692766000-696122f49260429a8e6a9e864924fba1.json`, exit `0`, duration `12.663075708s`, output `PASS: YII2-MAIN-NAVIGATION-001 shared permission-aware Yii navigation`
- Verdict: `APPROVED`; prior Gate 3 approval remains valid

### Findings

None.

### Disposition

The added nullable legacy column is bounded fixture schema needed for the existing construction-control read projection to reach the already approved navigation assertions. It neither supplies the missing navigation behavior nor weakens the test's sensitivity, and the exact-source GREEN demonstrates that the full five-route matrix now executes beyond setup. The prior Gate 3 expectations remain unchanged and approved.

## Gate 3 restart after Gate 5 findings

- Reviewer: Codex independent sol/low reviewer `/root/gate3_review`; authored neither the specification, strengthened test nor production corrections
- Test/spec author: root agent for issue #150; the normative specification is unchanged
- Reviewed source: base commit `25aee5524f790292d350175ba278bc47e282ed4c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T115937Z-ecd4543e41/snapshot/source.patch`, patch SHA-256 `cda51844799ce4084e238336c771916dad6c51630d578b9a44dc8ba47c5d4e73`, candidate source `529801a65023d9e4bbc79e09662eb1557c75945e1a9a5abfc17f837126d35baf`, executable source `964226cdd2c933b5df9e66af6b720c5f622e71b01ddd77af3bb9780536085671`
- Trigger and prior-RED witness: Gate 5 review `reviews/code/YII2-MAIN-NAVIGATION-001.md` found that prior snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T115235Z-61296b55fb/snapshot/source.patch` (SHA-256 `a6f71622141f4f80acb55aaeb2d27ff01e56acd6b48c1b9f36473ab4c9e3fa22`, candidate `cc3f2468a9885a7ddaaa693c04940b100e3bb254abf478f38593712e65b38081`) emitted text-only MAIN anchors without group spans/icons and called `MainNavigation::render()` unconditionally from OTIZ `objects()`/`overview()`. That exact source is executable RED evidence for the new assertions at `tests/Yii2/yii2_main_navigation_001_test.php:81-88,117-126`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T115937Z-ecd4543e41/verification-plan.json`, SHA-256 `2755a88dc78cc6a42a381d40f95997a342dbbd0522a908414ba88425f225b7bb`; lane `CRITICAL`; required reviews `gate3`, `final`; no missing tests reported
- Corrected GREEN evidence: `php tests/Yii2/yii2_main_navigation_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789473578481796000-93132d04c9b54596a31151b8cf147c3e.json`, exit `0`, duration `14.282827291s`, output `PASS: YII2-MAIN-NAVIGATION-001 shared permission-aware Yii navigation`, bound to the reviewed candidate and executable source above
- Verdict: `APPROVED`

### Findings

None.

### Sensitivity and disposition

The new structural oracle is sensitive to the concrete Gate 5 regression: it requires direct `fm2-nav-group` children with «Монтаж» and permission-applicable «Администрирование», and requires exactly one direct `fm2-nav-icon` SVG with `aria-hidden="true"` for every MAIN anchor on every available target route and permission phase. The prior text-only renderer therefore fails both shape checks rather than receiving another same-source GREEN.

The new scope oracle performs real authenticated GETs to `/pilot/otiz/objects`, `/pilot/otiz/payments`, and `/pilot/otiz/history`, preserves their `200` outcome, parses each response, and requires zero `nav[aria-label="Основная навигация"]`. The prior unconditional controller helper necessarily fails this assertion on all three routes. The corrected helper gates MAIN rendering to the exact `/pilot/otiz` path, and the exact-source GREEN proves both new assertions execute together with the previously approved complete permission, label, feedback, current-section, authorization, OTIZ-internal-navigation and no-facts matrix.

Gate 3 is re-approved for the strengthened test and corrected exact source. The planner-required independent final review must now evaluate this candidate; later test/spec changes require another applicable review.

## Gate 3 restart — feedback-final assertion

- Reviewer: Codex independent sol/low reviewer `/root/gate3_review`; authored neither the specification, new assertion nor production implementation
- Reviewed delta: `tests/Yii2/yii2_main_navigation_001_test.php:81` adds one assertion that the normalized final MAIN href is `/pilot/feedback`; no other test or normative-spec expectation changed
- Reviewed source: base commit `25aee5524f790292d350175ba278bc47e282ed4c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T120608Z-c6c12a1f15/snapshot/source.patch`, patch SHA-256 `246f2741ea603a980df93ed9fee604f8d324c59756e511d3867acef9cd1ee442`, candidate source `ec59204075d240565e0e1d3db8f6e2984fe27da6ecf26d2fbaa78a60f8981a78`, executable source `880ac7564606fc54262012809af5cb91bf95771f1de8d3d72fa1fdf8363e3afe`
- Trigger: Gate 5 rereview finding 1 in `reviews/code/YII2-MAIN-NAVIGATION-001.md` identifies the established final feedback position on all four pre-change styled sidebars as part of the unchanged-sidebar invariant in `specs/YII2-MAIN-NAVIGATION-001.md:5,38-40`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T120608Z-c6c12a1f15/verification-plan.json`, SHA-256 `1ed329855715886c9686355010f58e9652fc3105477a58ad486a563a232040a0`; lane `CRITICAL`; required reviews `gate3`, `final`; no missing tests reported
- Exact RED evidence: `php tests/Yii2/yii2_main_navigation_001_test.php`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789473944561504000-9f9f8ca909de474aa18250fd90d9edb7.json`, exit `255`, duration `12.778670042s`; after successful setup and prior navigation assertions, `/pilot/objects` reports expected final `/pilot/feedback` but actual final `/pilot/admin/roles`
- Verdict: `APPROVED`

### Findings

None.

### Sensitivity and disposition

The assertion is a bounded, implementation-independent semantic DOM check applied by the existing matrix to every available target route and permission phase. It fails the exact current renderer for the concrete Gate 5 regression, while continuing to allow any otherwise consistent ordering whose final MAIN anchor preserves feedback. It does not weaken or replace membership, labels, return path, permission filtering, cross-surface ordering, groups/icons, `aria-current`, authorization, OTIZ scope, or no-facts coverage.

Gate 3 approves this one-assertion test strengthening. Production must remain RED until the separate executor correction; the corrected exact source then requires focused GREEN evidence and the planner-required independent final rereview.
