# Gate 5 code review — YII2-SIDEBAR-NAVIGATION-002

- **Reviewer:** separately tasked Codex agent `/root/sidebar_gate5`; authored none of the specification, tests, or production implementation under review.
- **Reviewed scope:** current working-tree candidate over base `d0eaf5c58b38f79fcc3de41af6a17cf590055dc1`, including all modified views and all untracked vendored SVG assets.
- **Contract:** `specs/YII2-SIDEBAR-NAVIGATION-002.md` A1–A5 and the owner-split `refine-sidebar-navigation` OpenSpec change. Calendar is excluded and tracked by GitHub issue #203.
- **Gate 3:** final rereview in `reviews/tests/YII2-SIDEBAR-NAVIGATION-002.md` approved the corrected test design and retained pre-implementation HTTP/browser RED.
- **Initial verdict:** `CHANGES_REQUESTED`.

## Findings

1. **HIGH — the prepared review package does not bind the current candidate.** `python3 tools/delivery/harness.py state` reports the active prepared package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T185304Z-6418a917dc/package.json`, prepared source `b8bc4c241199fd34ce190ae91229fc209c4969b7f19052f666c8162126f77a52`, while the live candidate/source is now `9dc01246261b223d5347b8145d2da18937a421a2d96eb61438e6fffde78aaf60` and executable source `ee44d3ec460d6d9e44db2eedbaba0d8690def2807eb0d76df21988309b297d43`. The live candidate additionally contains the patched standalone views and the untracked `app/YiiRuntime/Assets/shlz-icons/` inventory. Gate 5 requires an exact commit or reconstructible source snapshot; regenerate/capture the complete candidate and attach the review/verification evidence to that exact source before approval.

2. **MEDIUM — the runtime changes pinned shlz-ui root geometry.** `app/YiiRuntime/MainNavigation.php:60` replaces every source `<svg>` root with a hard-coded `viewBox="0 0 24 24"`. The exact pinned `setting-tool-circle.svg` bytes use `width="24" height="25" viewBox="0 0 24 25"`; although the vendored file is byte-identical to `../shlz-ui/packages/icons/dist/icons/setting-tool-circle.svg`, the rendered icon is vertically rescaled and is no longer the pinned geometry promised by A2/design. The PHP oracle compares only `<path>` attributes, so it cannot catch this regression. Preserve the source viewBox/root geometry while adding the safe runtime attributes, and extend the provenance assertion to cover the root geometry.

3. **MEDIUM — the collapse control still has four independently maintained renderers.** The same accessibility-sensitive `<details>/<summary>` markup is duplicated in `app/YiiRuntime/ViewSupport.php:49`, `app/YiiRuntime/Views/objects.php:17`, `app/YiiRuntime/Views/users.php:12`, and `app/YiiRuntime/Views/roles.php:12`. This candidate correctly removes the installer-specific shell, but leaves the same drift class for three other views: future label, icon, or keyboard corrections can silently diverge. Use one shared collapse-control renderer (or migrate the remaining views to the shared shell) and keep the current route/current-section behavior unchanged.

4. **BLOCKING VERIFICATION GAP — browser-only A3/A4 behavior has no post-implementation GREEN.** The supplied real-HTTP navigation run is GREEN (`tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php`, source digest `ee44d3ec…`), and PHP/Node syntax plus `git diff --check` are GREEN. Exact vendored SVG byte comparisons against `../shlz-ui` are also GREEN. However, the container Chromium wrapper cannot launch because required Linux shared libraries are absent, and no host post-implementation browser smoke was retained. Therefore collapse visibility/keyboard/persistence and desktop/mobile feedback geometry, hit target, safe-area and non-overlap remain `UNKNOWN`, not GREEN. Run the focused browser acceptance in a capable environment against the corrected exact source before requesting rereview.

## Standards assessment

No authorization, state mutation, append-only history, route-permission, session, logout, or OTIZ internal-navigation regression is visible in the production diff. Icon names are allowlisted by syntax before filesystem access, labels/hrefs remain encoded, empty permission groups are omitted, and feedback is outside the `nav`. PHP lint, Node syntax, `git diff --check`, and the reported Impeccable detector (`[]`) are clean. Finding 3 is a Fowler **Duplicated Code / Shotgun Surgery** judgement call; findings 1, 2 and 4 are hard delivery/contract blockers.

## Spec assessment

A1 grouping/order, A4 removal of feedback from MAIN, A5 current-section behavior, and the intended desktop/mobile CSS structure are implemented in the inspected source. Calendar is consistently absent from production paths and remains deferred to #203. A2 is partial because one rendered SVG loses its pinned viewBox. A3/A4 rendered behavior is plausible by inspection but cannot be accepted without the required post-implementation browser evidence.

## Required correction

Preserve the pinned icon root geometry and test it, consolidate the duplicated collapse renderer, prepare/capture the resulting exact candidate, then retain focused HTTP and browser GREEN for that same source and request one complete Gate 5 rereview. Do not run the locally prohibited full suite.

---

## Corrected-candidate rereview — 2026-09-19

- **Package:** `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T192555Z-77de5db890/package.json`.
- **Candidate source:** `72ea92f156ee4378fbc0dfa40f4c662e74ca2b0802f2bdc7de0b6aba5d8464b4`.
- **Executable source:** `90f32e9768d525423b62c706d0e2d5bf99f9f0835da3ea1234ad4c4e582ece73`.
- **Final verdict:** `APPROVED`.

### Prior findings disposition

1. **Fixed.** The refreshed package binds the complete current candidate, including every standalone view and all nine vendored SVG files. `harness.py state` reports the same candidate source and executable source as the rereview handoff. This review-file update is the expected additive independent-review artifact; no production, test, or specification bytes were changed by the reviewer.

2. **Fixed.** `MainNavigation::icon()` now injects only class, accessibility and provenance attributes into the original root and preserves its supplied `width`, `height`, and `viewBox`. In particular, `setting-tool-circle` remains `24×25` with `viewBox="0 0 24 25"`. The corrected HTTP oracle compares width, height, DOM-normalized `viewBox`/`viewbox`, and normalized path attributes against the pinned export. Every vendored asset remains byte-identical to its corresponding `../shlz-ui/packages/icons/dist/icons/` file.

3. **Fixed.** `MainNavigation::collapseControl()` is now the single renderer. `ViewSupport` and the objects, users, and roles standalone shells all call it; no duplicate `fm2-nav-state` markup remains in those consumers.

4. **Fixed.** The corrected candidate has focused semantic HTTP GREEN and rendered host-Chromium GREEN. The HTTP run passed against source digest `54400855…`, including exact groups/children/order, permission variants, one current item, feedback exclusion/presence, and pinned SVG root/path geometry. A disposable exact-candidate runtime on port 8094 passed desktop 1280 and mobile 360 browser assertions for the ≥44px control, state-specific left/right icon and accessible name, Enter activation, reload persistence, mobile control absence, and the fixed feedback action's placement, hit target, edge clearance and non-overlap. The timing correction waits for the native `<details>` toggle state before asserting; it does not relax behavior. The disposable runtime and volumes were removed after the read-only smoke.

### Final assessment

The corrected source implements A1–A5 without changing permissions, routes, domain facts, session/logout behavior, or OTIZ internal navigation. Calendar remains explicitly excluded to #203. The implementation uses exact pinned shlz-ui icon assets, exposes a discoverable accessible desktop collapse/expand affordance, keeps mobile navigation free of that control, places Монтажники and ОТиЗ in their required groups, and renders feedback as a single accessible floating action outside MAIN. The focused tests would catch the plausible regressions identified in the initial review.

PHP/Node syntax and `git diff --check` are clean; the prior Impeccable detector result is `[]`. No full local suite was run, in accordance with the owner prohibition. Exact-source CI/publication/deployment remain separate later delivery states and are not implied by this Gate 5 approval.
