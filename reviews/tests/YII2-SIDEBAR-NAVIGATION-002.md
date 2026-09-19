# Gate 3 test review — YII2-SIDEBAR-NAVIGATION-002

- Reviewer: independent agent `/root/sidebar_gate3`; authored none of the specification, OpenSpec artifacts, production code, or tests under review.
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T184317Z-3ec85bef56/package.json`; candidate source `f4633c948ad22b905541ab233b2c54be2c6defdec07a6fb9bdd182a0b5ba123f`; executable source `542413726916841f220e550c9898bef635c2d4d404741e17797040736b42e71c`; verification-plan SHA-256 `b8eb612a1838403d8c487a582a936a023eebf3f1890280b7142738a6f407b0ed`.
- Reviewed contract: `specs/YII2-SIDEBAR-NAVIGATION-002.md` A1-A6 and the complete `refine-sidebar-navigation` OpenSpec change.
- Reviewed tests: `tests/Yii2/yii2_main_navigation_001_test.php` and `tests/Yii2/feedback_browser.mjs`.
- Retained RED cited by the delivery record: focused real-HTTP navigation run exits 255 at the membership assertion on `/pilot/objects`, because the incumbent output lacks Calendar and retains feedback in MAIN. No retained browser RED or independent direct-calendar 404 RED is supplied.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **BLOCKING — A1's exact group hierarchy and order are not tested.** `tests/Yii2/yii2_main_navigation_001_test.php:73-82` sorts expected and actual links for membership, then treats the first rendered order as the oracle and only requires later pages to repeat it. Any stable but contract-violating order passes. Lines 88-96 collect group-label spans independently of links, so all links may remain ungrouped or be placed under the wrong heading while the test stays GREEN. This misses the core requirements that Calendar and Стройконтроль are children of Монтаж, Монтажники is under Справочники, ОТиЗ is under its own section, and the four groups/children have the normative order. Assert a semantic DOM structure that binds each direct group heading to its exact ordered children, while still omitting permission-empty groups.

2. **BLOCKING — A3 collapse behavior has no executable oracle.** Neither test locates or operates the desktop collapse-control. `tests/Yii2/feedback_browser.mjs:9-28` exercises only the feedback flow. An implementation with a hidden control, wrong labels, the same chevron in both states, a sub-44px target, broken keyboard activation, no persisted state, or an erroneous mobile collapse control would pass. Add desktop expanded and collapsed checks for visibility/hit target, exact state-specific `aria-label`, distinct left/right shlz-ui chevrons, keyboard activation and reload persistence, plus a mobile assertion that the bottom navigation has no collapse control.

3. **BLOCKING — A2 shlz-ui provenance can be faked by a class name and does not cover collapse icons.** The sole icon assertion at `tests/Yii2/yii2_main_navigation_001_test.php:97` accepts any inline SVG/path carrying `fm2-nav-icon--shlz`; arbitrary placeholder paths therefore pass the exact regression this slice is meant to prevent. It also covers only MAIN links, although A2 explicitly includes both collapse states, and does not check 24×24/viewBox or the pinned asset bytes/symbol identity. Bind each expected icon (including chat and both chevrons) to a pinned `shlz-ui` export or exact vendored byte/path identity; if a local icon is introduced, test the documented allowed geometry instead of trusting a marker class.

4. **BLOCKING — A4 proves route status and no facts, but not a calendar projection.** The PHP matrix checks `200`, current navigation, permission denial, and unchanged facts (`tests/Yii2/yii2_main_navigation_001_test.php:69-107,157-164`), but it never seeds scheduled inspections or asserts independently expected dates/objects in the returned calendar. A blank page (or any page with the shared shell) passes as a “read-only calendar projection.” Add a worked fixture with scheduled and irrelevant inspections and assert the exact projected values/order. Also retain a focused pre-implementation probe that reaches `/pilot/calendar` and demonstrates the incumbent 404: the supplied RED stops earlier on `/pilot/objects` membership, so it does not establish that the new controller/route expectation is red for its intended reason.

5. **BLOCKING — A5 overlap/safe-area coverage is incomplete and one PHP expectation is stricter than the contract.** On the originating work screen, `feedback_browser.mjs:11-13` checks only fixed position, size, and center-point hit testing. It does not assert non-overlap with mobile bottom navigation, safe-area allowance, or a main submit/action. The later `geometry()` checks run only after navigation to feedback/admin forms and do not compare those controls with the floating action, which is no longer the tested locator on those pages. Add explicit bounding-box/non-overlap checks on representative desktop and mobile work screens, including bottom navigation and a primary action, and safe-area-aware bottom placement. Conversely, `yii2_main_navigation_001_test.php:84-87` requires the FAB on `/pilot/feedback`, while A5 explicitly permits hiding it there; either exclude that route from the one-FAB expectation or tighten the contract deliberately.

6. **BLOCKING — complete intended-RED evidence is missing for the two-test acceptance mapping.** `verification-input.json` declares both the PHP and browser tests `INTENDED_RED`, but the delivery record supplies only one early PHP failure. That failure occurs before the calendar request, structural grouping, icons, current-state, permission phases, and the browser-only geometry path; there is no retained run showing the browser candidate fails for the absent floating action (or, after correction, collapse behavior). After correcting findings 1-5, regenerate the source-bound plan/package and retain deterministic RED evidence that reaches the independently meaningful HTTP/calendar and browser branches rather than allowing the first membership failure to stand in for all six requirements.

## Assessment

The tests use appropriate real authenticated HTTP and Chromium seams, have isolated fixture ownership, and already provide useful permission, denial, `aria-current`, OTIZ-internal-navigation, feedback return-path, and no-facts coverage. The current candidate is nevertheless insensitive to several central owner-visible regressions and overconstrains one allowed feedback-page variant. Gate 4 must not proceed from this test candidate.

## Required correction

Correct the complete matrix above as one test/spec reconciliation, regenerate the prepared package because test/source bindings will change, capture fresh independently reachable intended RED (including the incumbent calendar 404 and browser behavior), and request independent Gate 3 rereview before implementation.

---

## Owner-split correction rereview — 2026-09-19

- Reviewer independence: unchanged; the reviewer authored none of the corrected specification or test artifacts.
- Scope change: Calendar is explicitly removed from this contract and deferred to GitHub issue #203. The current contract is A1-A5 for grouping, icons, collapse, floating feedback and compatibility.
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T185304Z-6418a917dc/package.json`; candidate source `b8bc4c241199fd34ce190ae91229fc209c4969b7f19052f666c8162126f77a52`; executable source `7c792f864cb9de2a4ec09f0cc66181d8b39b8c96dabacdfc29586e81d89eda4f`; verification-plan SHA-256 `51e52d8d91f41a6d2bce75b1d9ffd4c9fd83ff1b308b5d31d2ca1a856198926f`.
- Fresh reviewer HTTP RED: `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php`; exit `255`, source digest `7c792f864cb9de2a4ec09f0cc66181d8b39b8c96dabacdfc29586e81d89eda4f`. Fixture startup/login succeeded and `/pilot/objects` failed at the intended exact membership assertion because incumbent MAIN still contains feedback.
- Browser evidence: the current environment fails before script execution because host Chromium shared libraries are missing. This is setup failure and is not treated as behavior RED.
- Rereview verdict: `CHANGES_REQUESTED`.

### Prior findings disposition

1. **Resolved for the owner-split scope.** The corrected PHP oracle now requires exact canonical link order and an exact direct-child token stream binding every nonempty heading to its ordered children across permission phases. Calendar-specific hierarchy is correctly absent after the explicit split.
2. **Structurally corrected, RED evidence still open.** The browser source now checks both desktop labels, 44×44 geometry, distinct left/right markers, Enter activation, reload persistence and absence of a visible mobile control. No behavior run has reached these assertions.
3. **Partially resolved; exact icon provenance remains open.** Chat and both chevron states now have named markers, but markers are not pinned SVG identity; arbitrary path placeholders can still carry the expected class/data attribute and pass.
4. **Not applicable after owner split.** Calendar behavior and its 404/projection RED belong to #203 and are no longer acceptance criteria or planned paths here.
5. **Mostly corrected; safe-area sensitivity remains open.** Feedback-page rendering is one permitted A4 choice, and the browser source now checks fixed placement, hit target and bounding-box overlap. Its viewport assertion does not establish clearance from a nonzero mobile safe-area inset.
6. **Open for browser-only behavior.** Fresh exact-source HTTP RED now exists, but Chromium setup failure leaves collapse, persistence and rendered overlay geometry without executable RED.

### Remaining findings

1. **BLOCKING — A2 still trusts self-asserted marker attributes instead of pinned `shlz-ui` SVG identity.** `tests/Yii2/yii2_main_navigation_001_test.php:114` accepts any SVG with `fm2-nav-icon--shlz`; `tests/Yii2/feedback_browser.mjs:15,18,22` similarly accepts `data-shlz-icon` strings. A renderer can keep the incumbent arbitrary paths, add these markers, and pass. The design promises exact vendored pinned bytes/path data. Add an implementation-independent oracle for the expected exported SVG identity/geometry for every navigation symbol, chat and both chevrons (for example exact normalized SVG/viewBox/path fingerprints derived from the pinned exports). Do not infer provenance from a class or data attribute controlled by the implementation.

2. **BLOCKING — source inspection plus HTTP RED does not satisfy Gate 2 for browser-only A3/A4 behavior.** The exact-source HTTP failure proves only that feedback remains in MAIN; it cannot demonstrate collapse visibility/keyboard/persistence or rendered hit-target/overlap behavior. The attempted browser run did not execute the script, so it is setup failure rather than intended RED. Run `php tests/Yii2/yii2_feedback_browser_001_test.php` in an environment containing the required Chromium libraries and retain a source-bound failure at the missing behavior. While correcting that oracle, assert safe-area clearance rather than only `a.bottom <= innerHeight`; otherwise a FAB occupying the device inset remains GREEN despite A4.

### Rereview assessment

The owner split is coherent, the current package no longer carries Calendar production paths, and the exact group/child/order and permission matrix is now sensitive to findings 3 and 4 from the owner request. The collapse and overlay assertions are materially improved and deterministic by inspection. Two blockers remain: SVG provenance is still self-certified, and the browser-only contract has no demonstrable behavior RED because Chromium never launched. Correct these together, prepare fresh source-bound evidence/package, and request rereview before Gate 4.

---

## Final Gate 3 rereview — 2026-09-19

- Reviewer independence: unchanged; the reviewer authored none of the specification or corrected tests.
- Reviewed artifact: current worktree test/spec bytes directly. A replacement package was not created because the harness rejected the evidence-metadata update after the source-bound run; this administrative rejection is not represented as test failure or approval evidence.
- Exact HTTP RED: harness record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789844400459898000-a25128cefe5a4208b96df2dc160c9fe0.json`, outcome `INTENDED_RED`, command `acceptance:yii2_main_navigation_001_test`, acceptance `A1-A5-sidebar-feedback`, exit `255`. Its retained `RUN_IN_PROFILE_RESULT` binds source digest `9534cd00ab831b453d33f50333fecf6383fc22b3aade94dee6eb7dfeb88f5cb7`; fixture/database startup succeeded and the incumbent fails at exact MAIN membership because feedback remains in navigation.
- Browser RED: current browser script was executed with host Chromium against the incumbent stand at desktop and mobile viewports. It observed the intended missing behavior: feedback remains in MAIN, no floating action exists, and the collapse toggle has null accessible label/icon state. This is behavior RED, distinct from the earlier discarded missing-shared-library setup failure.
- Static validity: `php -l tests/Yii2/yii2_main_navigation_001_test.php`, `node --check tests/Yii2/feedback_browser.mjs`, and `php -l tests/Yii2/yii2_feedback_browser_001_test.php` all succeed.
- Final verdict: `APPROVED`.

### Remaining findings disposition

1. **Resolved — pinned SVG provenance is independently compared.** The PHP oracle loads each named SVG from the pinned `shlz-ui` export root, parses it, normalizes every path's complete attribute set (excluding only non-geometric `id`), and compares that signature with rendered output. The mapping covers all six navigation icons, feedback chat, and both collapse chevrons. Marker strings now select the expected symbol but cannot make arbitrary placeholder geometry pass.

2. **Resolved — browser-only behavior has executable RED and safe-edge sensitivity.** The host Chromium run reaches the current assertions and fails on the incumbent UI behavior rather than environment setup. The test covers desktop expanded/collapsed labels, distinct icon states, 44px target, Enter activation, reload persistence, mobile absence, floating action placement/hit target and non-overlap. The final geometry oracle also requires computed right and bottom clearance of at least 8px in addition to viewport containment, closing the prior edge/safe-area-insensitive pass.

### Final assessment

No Gate 3 findings remain for the owner-split A1-A5 scope. The corrected suite is traceable to the normative contract, exercises the real authenticated HTTP and rendered browser seams, derives exact hierarchy/icon expectations independently, preserves permission and no-facts checks, and is deterministic across repeated/role-restricted phases. Calendar is consistently excluded and remains tracked by GitHub issue #203.

Gate 4 may proceed from the exact reviewed test bytes represented by source digest `9534cd00ab831b453d33f50333fecf6383fc22b3aade94dee6eb7dfeb88f5cb7`. Any later specification, test, verification-input or evidence-binding change requires applicable independent delta review.

---

## Post-CI fixture delta review — 2026-09-19

- Reviewer independence: unchanged; the reviewer authored neither production changes nor these two fixture corrections.
- Reviewed delta: only `tests/Support/yii2_production_web_cutover_contract.php` asset hashes and the submit locator in `tests/Yii2/installer_directory_browser.mjs`.
- CI failure inventory supplied for review: Integration 1 failed only the two changed asset hashes; e2e failed only Playwright strict-mode ambiguity after the shared shell added the logout form; the aggregate verify job reflected those failures; all other job outcomes were known.
- Focused evidence: `php tests/Runtime/yii2_production_web_cutover_001_test.php` exits `0` with `PASS: YII2-PRODUCTION-WEB-CUTOVER-001 single runtime`. `node --check tests/Yii2/installer_directory_browser.mjs` succeeds. The local browser profile still stops before script execution on missing Linux browser libraries and is not counted as behavioral GREEN.
- Delta verdict: `APPROVED`.

### Delta findings

None.

### Sensitivity assessment

1. The new `pilot.css` and `navigation.js` SHA-256 values exactly equal the current production asset bytes (`fc8cc00971efc3fb3dc6a1588a1ba66879345b0e12fcc3aa2cfc093969c46d8c` and `da1bc22a4f9c28991580457a0a0e04ec498d0b9321ba5686d1eb368bb4ae8572`). The contract continues to compare immutable expected digests, MIME type and cache policy; updating the two expected values does not relax or bypass the cutover oracle and the focused test reaches GREEN.

2. Replacing global `form button[type=submit]` with `getByRole('button', {name: 'Показать'})` narrows the action to the filter form's accessible submit button. It removes only the ambiguity introduced by the legitimate shared-shell `Выйти` form. Sensitivity is preserved by the preceding exact status/availability selections, awaited navigation, the independently expected `40 сотрудников` result, and reload-persistence assertion. A no-op, logout click, wrong form submission or lost filters still fails.

The earlier Gate 3 approval remains valid. These fixture-only corrections may proceed to exact-source CI and final review; the local browser setup failure is neither approval nor GREEN.
