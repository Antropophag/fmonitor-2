# Delivery — YII2-SIDEBAR-STATE-ICONS-001

## Authorization and authors

- Owner authorization: 2026-09-21, autonomous one-slice delivery through merge-ready PR; specification pre-approved.
- Root author: OpenSpec artifacts, normative specification, verification input and RED tests.
- Executor: Codex agent `/root/sidebar_icons_executor` (production implementation only).
- Independent Gate 3 reviewer: `/root/sidebar_icons_gate3`; initial suite and narrow post-review timing correction both approved.
- Independent final reviewer: `/root/sidebar_icons_gate5`; exact source approved with no findings.
- Baseline: `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce` (`origin/main`).

## Diagnosis and audit baseline

- Correct cause: server renders `<details open>`, while `navigation.js` reads `fmonitor.sidebar.expanded` only from an end-of-body module, after first layout.
- Icon inventory: 29 executable SVG occurrences plus the shared `MainNavigation` semantic declarations across active Yii/pilot source before classification. Main navigation was already pinned to public `shlz-ui` exports except Calendar, which reused `circle-grid-interface-sidebar`. Calendar action/status compositions still used manual SVG geometry.
- Clean upstream icon source: `shlz-ui origin/main` `45a99e7518fab5ea33b4fd1e3723cfeafcffd719`; public dist reproduced from its normalized manifest. `calendar-sidebar.svg` SHA-256 `e48dfea86a132c25b44344ee52b4244853280ddbbf7794d1552ca26e0194f54c`; `calendar-interface.svg` SHA-256 `f916a796bdc803cbf0ef8496c7a297a8fb5fa53f278ccc8e18c00f0c9d001d7a`.
- The dirty/conflicted sibling checkout was not used as source evidence.

## Gate 2 RED

- `php tests/Yii2/yii2_sidebar_state_icons_001_test.php` → INTENDED_RED: missing pre-head saved-state bootstrap.
- `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php` → INTENDED_RED: Calendar still lacks exact `calendar-sidebar` icon.
- A local containerized Playwright attempt was setup-blocked by missing browser system libraries and is not claimed as RED/GREEN. The deterministic boot-order test is the executable regression seam; final visual verification uses the host browser pass.

## Gate 4 implementation and focused verification

- Added the blocking pre-head `sidebar-bootstrap.js`, which applies stored compact state under the real Yii CSP, closes the server-open `<details>` before paint, and retains expanded behavior when storage is absent or denied. `navigation.js` owns hydration cleanup and synchronously updates the accessible toggle state while preserving the 280 ms manual transition.
- Replaced every audit `REPLACE` occurrence with its assigned public export and pinned the seven exact `shlz-ui` `45a99e7` files. SHA-256 output matched the immutable audit for all seven exports.
- `impeccable detect --json` over all changed UI targets was run once and returned `[]`.
- One bounded authenticated visual observation used the existing capture/browser infrastructure with stored compact state. Desktop `1440×900` rendered the expected 72 px compact rail with intact navigation and calendar action icon; mobile `390×844` rendered the expected bottom navigation, readable responsive object card, and intact calendar action icon. No sidebar/icon regression was found and no production correction was needed. Artifacts: `/private/tmp/fmonitor-sidebar-desktop-20260921.png`, `/private/tmp/fmonitor-sidebar-mobile-20260921.png`; one-off script: `/private/tmp/sidebar_visual_observation.mjs`.
- `php tests/Yii2/yii2_sidebar_state_icons_001_test.php` — PASS: first-frame/state/toggle/no-JS matrix and complete icon provenance/cardinality audit.
- `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php` — PASS; focused source digest `1acf3c90042ee799ef4d66f105d08695ac4e99db3760bbccc8163cf4e3629e5e`.
- `python3 tests/Verification/change_verification_001_test.py` — PASS, 18 tests.
- `php tests/Runtime/runtime_storage_001_test.php` — PASS.
- `python3 tests/Verification/architecture_guard_001_test.py` — PASS, 59 tests.
- `git diff --check` and focused PHP syntax checks — PASS.
- The owner ban on local full `make test` / `make verify` was observed.

## Current state

Gate 4 behavior, bounded desktop/mobile observation, detector, and five planner-focused checks are GREEN. Two earlier parallel UI runs remain `UNKNOWN` fixture-temp collisions and were not counted; sequential exact-source reruns passed. Gate 5 approved source `8e96df27b34a32928b0b6d379c85185a3098bc7fdd82296e7591901eb52a4382` with no findings. Next: commit/push, PR and the single required exact-source CI consumer.
