# Code review: YII2-SIDEBAR-STATE-ICONS-001

- Reviewer: independent `gpt-5.6-sol / low` Gate 5 reviewer (`/root/sidebar_icons_gate5`); authored neither the specification/tests nor production implementation
- Verdict: `APPROVED`
- Reviewed base: `e67b566d8958faa0df8f8ebb8c09db3f1e0983ce`
- Reviewed exact source SHA-256: `8e96df27b34a32928b0b6d379c85185a3098bc7fdd82296e7591901eb52a4382`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T011326Z-ae3f5ea158/package.json`
- Verification plan SHA-256: `250c218b63d7edae894b274eaa331021e42bd27f172833a6258c061fedcb1746`
- Reconstructible snapshot: base above plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T011326Z-ae3f5ea158/snapshot/source.patch`, patch SHA-256 `8ae9f3c28ca46296759841d7429260454cd28455cabd3367366b963ee13871b3`
- Normative sources: `specs/YII2-SIDEBAR-STATE-ICONS-001.md`, `openspec/changes/fix-sidebar-state-and-adopt-shlz-icons/`
- Earlier review: `reviews/tests/YII2-SIDEBAR-STATE-ICONS-001.md`, including approved Gate 3 source and the approved post-Gate-4 browser timing delta

## Findings

None.

## Standards review

The complete production, test, policy, audit and lifecycle diff was reviewed. The change remains within the declared bounded UI slice and preserves routes, authorization, domain responses, database state and append-only history. No documented-standard violation or material maintainability smell was found.

The pre-head classic script is a small, isolated bootstrap whose sole responsibility is applying the saved compact marker and closing the server-open `details` element before paint. The ordinary module owns hydration cleanup and interactive state. This separation is justified by the first-paint boundary and avoids moving domain or persistence behavior into the view. Asset serving remains an explicit allowlist with `nosniff` and same-origin resource policy; icon names embedded by `MainNavigation::icon()` retain strict validation and HTML escaping.

The production path is compatible with the real Yii CSP (`script-src 'self'`), has no inline-script exception, and preserves the expanded/no-JavaScript fallback. Storage denial and missing values fail open to the existing expanded server state. The synchronous toggle path updates visible text, `aria-label`, directional marker and storage before the intentional geometry transition; decorative SVG/IMG instances are hidden from assistive technology through `aria-hidden` or empty `alt` while actionable controls retain their labels.

## Specification review

All seven normative requirements are implemented at the agreed public seam:

1. Stored `false` applies compact geometry and closes the server-open sidebar before the first observable frame, including the Objects-to-Calendar full navigation.
2. Stored `true`, absent storage and storage-read denial retain the expanded fallback.
3. With JavaScript disabled the expanded sidebar and all permitted server links remain usable.
4. Both toggle directions synchronously update accessible/control state and persistence while retaining the existing 280 ms user-triggered transition.
5. Calendar navigation and calendar action/status compositions use the pinned `calendar-sidebar` and `calendar-interface` public exports; Objects retains its distinct icon.
6. The immutable 29-occurrence baseline manifest covers executable YiiRuntime and active rapid-pilot SVG occurrences. `REPLACE`, `SHLZ` and `RETAIN` decisions are source-bound; selected exports are digest-pinned to clean `shlz-ui origin/main` `45a99e7`; replacement bindings are exact and cardinality-aware; retained fingerprints survive; legacy manual geometry and unclassified current occurrences are rejected.
7. Authenticated repeated capture is asserted read-only, and the diff does not change routes, permissions, application facts, schema or history.

The acceptance tests would catch plausible regressions in boot order, compact first-frame geometry, CSP execution, hydration cleanup, storage fallbacks, toggle accessibility/direction, no-JS navigation, icon identity/provenance, omitted duplicate replacements and unintended audit drift. The post-Gate-4 timing correction does not weaken the no-flash oracle: the initial compact assertion remains immediate, while only the deliberate interactive transition waits for its observable terminal width.

## Exact-source verification evidence

All five mandatory focused records in the reviewer package are `GREEN`, exit `0`, end on exact source `8e96df27…a4382`, and report `source_drift=false`:

- `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php` — record `1789953120499235000-72ee38e6d3c94b45bac3793c748eba71.json`; PASS shared permission-aware Yii navigation.
- `php tests/Yii2/yii2_sidebar_state_icons_001_test.php` — record `1789953177737799000-5e11b1796ba9426b97aadee1a05e23a3.json`; PASS first-frame/state/toggle/no-JS matrix and icon audit.
- `python3 tests/Verification/change_verification_001_test.py` — record `1789953085258134000-2bb267bb60354fcdbf324cee6cf5db18.json`; 18 tests PASS.
- `php tests/Runtime/runtime_storage_001_test.php` — record `1789953085267875000-76dd664b8a2b46bc91b7dd992d48984c.json`; PASS runtime storage contract.
- `python3 tests/Verification/architecture_guard_001_test.py` — record `1789953085264504000-89a7d2da47044a34ae5e56c4f93a4d0f.json`; 59 tests PASS.

Two earlier parallel executions ended `UNKNOWN` because their fixtures collided on temporary files. They are retained as non-green history and are not used as approval evidence. The five records above are the subsequent sequential exact-source runs and are the only GREEN evidence relied upon here.

The repository-wide `make test` obligation remains for the single exact-source GitHub CI run required by the delivery process; it was correctly not run locally. This Gate 5 approval covers the reviewed candidate and focused evidence, but does not itself convert pending/unknown CI or publication state into GREEN or merge-ready status.

## Decision

`APPROVED`. The candidate conforms to the normative contract, closes all earlier Gate 3 findings, preserves security/accessibility/no-write invariants, and is maintainable at the declared boundary. No code or test correction is requested. Proceed to the exact-source CI/publication steps without changing reviewed production or test bytes; any such delta requires renewed review.

---

## Integrated-source rereview after latest `origin/main`

- Reviewer: same independent Gate 5 reviewer; authored neither the implementation nor the conflict resolutions
- Latest-main comparison base: `c0814f5de3a080f0ea82c77099d5dd593c4ff05c`
- Integrated commit: `0949beb145082755268e6217c887d90ea466165b`
- Reviewed exact source SHA-256: `feb5748c906ff4e20f807cf0e54f74d39e00ea4afc0aa371fd9101733f17f1f9`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T012425Z-50ca619a95/package.json`
- Verification plan SHA-256: `6abbd9a2bb7dcf0621ea2788221976db17d972007f2e92da60f4300fcd3f45dc`
- Snapshot: committed tree at `0949beb145082755268e6217c887d90ea466165b`; package patch is empty with SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Integrated-source verdict: `APPROVED`

### Findings

None.

### Branch-unique and conflict-resolution review

The review compared only the branch-unique delta against latest main and then inspected the integration seams affected by conflict resolution.

- `app/YiiRuntime/Assets/navigation.js` preserves latest-main `enhanceTabs()` alongside `enhanceSelects()` and `enhanceCalendarGrids()`. Moving all three existing enhancements into the same guarded dynamic import lets sidebar hydration execute independently while retaining every upstream behavior; the sidebar's synchronous accessibility/persistence logic remains unchanged.
- `app/YiiRuntime/Views/original.php` preserves latest-main installer tab-number presentation and applies only the reviewed `cloud-upload` and `plus-alt-2` icon substitutions. No upload, composition, correction or history semantics were lost.
- `config/yii/assets.php` now exposes `sidebar-bootstrap.js` and all six new pinned icon files while preserving latest-main `download` and `shlz-file-types` routes. This closes the previously missing real HTTP integration seam; `PilotAssetController` independently retains its explicit file allowlist, content types, `nosniff`, same-origin resource policy and immutable caching.
- The remaining branch-unique source is materially the previously approved sidebar/icon slice. Route order and permissions, first-paint/CSP behavior, no-JS fallback, icon provenance/cardinality, accessibility and read-only GET invariants remain intact.

No conflict marker, unintended upstream reversion, scope expansion or new standards/specification finding was found. `git diff --check` passes for the complete latest-main delta.

### Integrated exact-source evidence

All five fresh mandatory records are `GREEN`, exit `0`, end on source `feb5748c…f1f9`, and report `source_drift=false`:

- `php tests/Yii2/yii2_sidebar_state_icons_001_test.php` — record `1789953592926048000-fdc88f8a348e4e93a147498cbce72ca5.json`; PASS first-frame/state/toggle/no-JS matrix and audit.
- `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php` — record `1789953623672236000-3ca6d48287ba4af78215ef29f0577073.json`; PASS permission-aware navigation through the integrated HTTP/assets seam.
- `python3 tests/Verification/change_verification_001_test.py` — record `1789953719054006000-3140dcff77164dc7bc9e4d567740ce2e.json`; 18 tests PASS.
- `php tests/Runtime/runtime_storage_001_test.php` — sequential record `1789953836566829000-48ddfa720af14ea7a3a5a09d501030d1.json`; PASS runtime storage contract.
- `python3 tests/Verification/architecture_guard_001_test.py` — record `1789953719054045000-71e2298737c84c86a5e6b32317520321.json`; 59 tests PASS.

One earlier parallel runtime record remains `UNKNOWN` and is not counted; the sequential exact-source runtime record above is the relied-upon GREEN evidence. The three tests seen failing during pre-commit inventory fail identically in a clean latest-main comparison, are not branch-unique regressions, and are correctly absent from the refreshed planner-selected local obligations. Those baseline failures are not reclassified as GREEN and do not contribute to this approval.

The mandatory repository-wide `make test` GitHub CI obligation remains pending and fail-closed. This integrated-source approval authorizes publication/CI of the exact reviewed bytes; it does not by itself claim CI GREEN or merge readiness.

### Integrated decision

`APPROVED`. The latest-main integration retains both upstream behavior and the complete `YII2-SIDEBAR-STATE-ICONS-001` contract. No production, test or integration correction is requested. Any subsequent code/test/config delta requires renewed review.

---

## CI-correction code rereview

- Reviewer: same independent Gate 5 reviewer; authored neither the correction nor the Gate 3 test/support delta
- Previous integrated checkpoint: commit `42ccefd65c7f559544101d450c64552db5c82cb4`
- Reviewed exact source SHA-256: `e9ff837281490aacc9540ed0b4b14b1dcfac0971fb8331f273062c94bfdbd2ff`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T015539Z-04a4292208/package.json`
- Verification plan SHA-256: `8ed6102c0214a1bc469359d86811abaaafa34455299e8cf50571ad9a1a5f4c95`
- Snapshot: base commit `42ccefd65c7f559544101d450c64552db5c82cb4` plus `snapshot/source.patch`, patch SHA-256 `193cdfefda5e573f928ad5e734545f3577bb889a271032e5c57c0cb29dcccbd5`
- Gate 3 delta review: `reviews/tests/YII2-SIDEBAR-STATE-ICONS-001.md`, section “CI-correction test/support review”, `APPROVED`
- CI-correction verdict: `APPROVED`

### Findings

None.

### Failure inventory and correction review

The complete first-run inventory was reviewed: unit failed in `yii2_shlz_select_001_test.php` and the Playwright-dependent sidebar test; integration failed in `yii2_production_web_cutover_001_test.php`; e2e failed in `ObjectRegisterPagingBrowserFixture.php` on six calendar/icon 404 responses; aggregate verify and Quality Graph failed because mandatory jobs were not green. No `REGRESSION_FAILURE` entries were reported. The corrections map to that inventory without suppressing failed requests, broadening filesystem access or weakening behavioral assertions.

- `app/YiiRuntime/Assets/navigation.js` restores the official static imports for `enhanceCalendarGrids`, `enhanceSelects` and `enhanceTabs`, preserving the public SHLZ module contract checked by the existing select test. The pre-head `sidebar-bootstrap.js` remains independent and still owns saved compact state before paint; the restored imports do not move or delay that responsibility.
- `rapid-pilot/router.php` exposes only seven explicitly named, repository-pinned SVG assets through an anchored regex. The requested name is reduced to the captured allowlisted basename before path construction, missing bytes return 404, and responses set exact SVG MIME, length, `public, max-age=31536000, immutable`, and `X-Content-Type-Options: nosniff`. There is no traversal, arbitrary-extension or sibling-workspace read path.
- `tests/Support/OtizOracleRouter.php` mirrors the production allowlist and headers so the active browser oracle exercises real icon requests. Its consumer continues to reject console errors, page errors, failed requests and non-success responses; the former 404 is corrected, not ignored.
- The sidebar synthetic server supplies no-op exports only for the unrelated SHLZ behavior dependency so the focused first-paint oracle can evaluate the production navigation module. The sidebar state, CSP, storage, accessibility and geometry code is not stubbed. Gate 3 independently approved this support delta.
- Moving `yii2_sidebar_state_icons_001_test.php` from `unit` to `e2e` accurately declares its existing Playwright/Chromium dependency. It remains mandatory and its test body and historical sensitivity are unchanged.
- Production cutover hashes were updated to the exact reviewed `pilot.css` and restored-static-import `navigation.js` bytes. MIME/cache expectations remain strict. Verification input now includes every correction boundary, and the refreshed planner adds the applicable deployment/e2e obligation.

The rapid adapter remains a bounded oracle/temporary adapter and does not receive new domain logic. Routes, rights, facts, database/history semantics, CSP and the approved icon provenance/cardinality contract are unchanged. `git diff --check` passes.

### Exact-source focused evidence

All six package records are `GREEN`, exit `0`, end on exact source `e9ff8372…d2ff`, and report `source_drift=false`:

- `php tests/Yii2/yii2_sidebar_state_icons_001_test.php` — record `1789955502401510000-163a6bf15c6140a392c67d223968499b.json`; PASS first-frame/state/toggle/no-JS matrix.
- `tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php` — record `1789955566146256000-fb4a22f6c3ac4789a668f35276ca22db.json`; PASS shared permission-aware Yii navigation.
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` — record `1789955588756612000-c973b8c84d3641218222da1dfb42f69c.json`; PASS isolated root Compose delivery/restart.
- `python3 tests/Verification/change_verification_001_test.py` — record `1789955642722394000-739a81f7595d437d9e15cf1e47ccff5d.json`; 18 tests PASS.
- `php tests/Runtime/runtime_storage_001_test.php` — record `1789955673283313000-5ca78fd39cd24132bd492d57394f4574.json`; PASS runtime storage contract.
- `python3 tests/Verification/architecture_guard_001_test.py` — record `1789955695446311000-3804ed50573b428887514690b740752f.json`; 59 tests PASS.

The prior GitHub run remains `FAILURE`; its failed-job inventory is historical diagnostic evidence, not GREEN evidence. This review approves the correction source for a new exact-source CI run and does not claim that mandatory full CI has passed. Publication and merge readiness remain fail-closed until that run is GREEN.

### CI-correction decision

`APPROVED`. The production correction is narrow, secure and consistent with the previously approved specification. The independently approved test/support changes remain sensitive and mandatory, and the six refreshed focused obligations are green on the exact reviewed source. No further code or test correction is requested before exact-source CI.
