# INSTALLER-UTILIZATION-FORECAST-001 — independent Gate 3 review

- Review date: 2026-09-25 (Europe/Moscow)
- Reviewer: independently tasked agent `/root/gate3_review`; authored none of the reviewed specification, OpenSpec artifacts, tests, RED evidence, or production code.
- Reviewed base: `f9b05a298550f5b33e26fd6ab7328f25865150e6`, with the uncommitted specification/OpenSpec/test files named below. The individual SHA-256 digests were captured during review; there is no retained reconstructible pre-implementation RED snapshot/package.
- Scope: `specs/INSTALLER-UTILIZATION-FORECAST-001.md`; `openspec/changes/add-installer-utilization-forecast/{proposal.md,design.md,tasks.md,verification-input.json,specs/workforce/installer-utilization-forecast/spec.md}`; `tests/Workforce/installer_utilization_forecast_001_test.php`; `tests/Yii2/yii2_installer_utilization_forecast_001_test.php`; `tests/Yii2/yii2_installer_utilization_forecast_browser_001_test.php`; `tests/Yii2/installer_utilization_forecast_browser.mjs`.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **CRITICAL — the acceptance matrix is not implemented by the tests despite the manifest claiming complete A–G–M/B–H–L coverage.** `verification-input.json:5-6` maps the whole projection and dashboard/detail ranges to three tests, but `tests/Workforce/installer_utilization_forecast_001_test.php:7-8` covers only the six Monday labels, an empty-assignment free result, one current interval/release, one busy member, observation non-mutation, and a broad fact snapshot. It has no independently asserted cases for D (open end through every remaining week), E (factual versus planned start and draft exclusion), F (effective deadline certificate versus earlier confirmed PTO), G (per-person UNKNOWN and global unavailable), conflict/one-count overlay, latest native application ownership versus registered fallback, or M (0/50/125/1000 rows). Correct the projection test with explicit fixtures and independent expected values for every listed case; do not treat a label or manifest mapping as coverage.

2. **HIGH — the public HTTP/browser seams do not make the forecast semantics sensitive.** `tests/Yii2/yii2_installer_utilization_forecast_001_test.php:5-8` checks labels, one all-free detail, two names, and statuses; it never proves all six dashboard counts/denominators correspond to the authoritative fixture, every bucket activation reaches the matching week/bucket, detail includes the required bounds/object/interval reasons, members are sorted by `fio,tabId`, or a new application changes forecast/detail while observations remain byte-equivalent. The direct projection call in the Workforce test is useful but cannot substitute for the normative `GET|HEAD` seams in `specs/...:9-10,20`. Add public-seam assertions which would fail if the controller/view showed stale, swapped, fabricated, or reasonless values.

3. **HIGH — authorization, HEAD, rejection, and no-leak coverage is incomplete.** `tests/Yii2/yii2_installer_utilization_forecast_001_test.php:7-8` tests detail HEAD status/body, one guest GET, and one denied GET only. It omits dashboard HEAD, guest/denied HEAD, the required object-scoped actor, full `objects.read + installers.read` combinations, GET/HEAD safe-header equivalence, and assertions that forbidden responses disclose no counts, names, objects, or other PII. The invalid non-Monday 404 is not a substitute, and invalid bucket/bounds behavior is untested. Exercise both routes and both methods, compare safe headers, and assert body non-disclosure and unchanged facts for every rejected path.

4. **HIGH — deterministic/read-only and fail-safe behavior are not adequately witnessed.** Neither PHP test repeats identical reads and compares normalized output, and the facts snapshots are taken only around selected successful paths. There is no malformed identity, missing mandatory start, or systemically incomplete-source fixture, so a regression that reports false `free`/zero instead of UNKNOWN/unavailable would pass. There is also no read-only witness across dashboard/detail HEAD and rejected requests for jobs, observations, assignments, audits, and other domain facts. Add explicit per-person UNKNOWN and global unavailable cases, repeat-output checks, and before/after facts around the complete route/method matrix.

5. **HIGH — acceptance L and the browser task are materially overstated.** `tests/Yii2/installer_utilization_forecast_browser.mjs:1` opens only a 390 px viewport, checks page-level overflow, focuses the first anchor, and presses Enter. It does not cover 1440 px, local chart overflow/containment, readable week bounds/values/legend, accessible names, visible focus styling, non-color distinctions, all group activations, or touch/pointer activation. `tasks.md:11,19` nevertheless marks desktop/390, legend, keyboard/focus, local overflow, and the browser implementation green. Add semantic DOM/accessibility assertions plus desktop and mobile interaction witnesses; checking `activeElement` alone does not prove visible focus.

6. **HIGH — query-scale and runtime-closure acceptance M has no executable oracle.** No reviewed test counts SQL at 0/50/125/1000 identities, asserts a fixed/bounded ceiling, measures all six weeks, or detects runtime loading of `rapid-pilot`/`app/PilotHttp`. This contradicts `specs/...:23,42`, `design.md:45,50`, and checked task `2.4`. Add a deterministic query observer with a stated constant ceiling at each scale and a runtime closure check that fails on either forbidden dependency.

7. **HIGH — the described RED establishes only feature absence and is not retained as exact-source evidence.** The reported pre-implementation commands produced: projection `INTENDED_RED forecast public read seam absent` followed by `TestFailure: INSTALLER-UTILIZATION-FORECAST-001 forecast missing`; HTTP an assertion failure on missing `Прогноз загрузки на 6 недель`; and, after correcting an earlier import setup error, browser absence of `[data-dashboard-chart="installer-forecast"]`. Those are plausible intended REDs for the narrow initial assertions, but no record file, exit status/output excerpt, reviewed-file digest, or reconstructible pre-implementation source was retained. They cannot demonstrate RED sensitivity for the missing cases above, and the current worktree already contains implementation. After completing the matrix, restore/capture a production-without-feature exact source, run every bounded test, and retain commands, exits, relevant first failures, source identity, and the browser setup correction history. UNKNOWN evidence must not be promoted to GREEN or approval.

## What is sound

The normative contract identifies the actor, six Moscow weeks, base partition versus overlays, conservative open-end behavior, read-only intent, authorization, responsive/accessibility expectations, and non-goals. The existing fixtures use fixed dates and isolated test infrastructure; the tested Monday ranges and simple free/current/releasing expected values are independently legible. All three PHP files pass `php -l`, and the browser module passes `node --check`. These foundations do not resolve the missing sensitivity above.

## Gate 3 verdict

`CHANGES_REQUESTED`

Return to Gate 2 and rebuild the tests as one complete A–M matrix, reconcile the falsely checked OpenSpec tasks, capture fresh exact-source intended-RED evidence, and submit the corrected specification/test package for a new independent Gate 3 review before relying on any Gate 4 implementation. No production source was inspected or approved by this review; CI, deployment, live adapters, and server-side enforcement remain `UNKNOWN`.

---

## Gate 3 correction rereview — 2026-09-25

- Reviewer independence is unchanged; this reviewer authored none of the corrected contract, tests, implementation, snapshot, or evidence.
- Reconstructible feature-absent source: base `f9b05a298550f5b33e26fd6ab7328f25865150e6` plus `/tmp/fmonitor-forecast-tests-v2.patch`, verified SHA-256 `a63d0146500e04d4cba7bcd302600482767e83559598e4af5d16e4d8ae4b55ad`.
- The current spec and four test artifacts match the Git blobs in that patch (`3714e952`, `71242b04`, `c4050909`, `8fa39c23`, `def74a0c`). `tasks.md` has changed after the snapshot and is not part of the approved test-source identity.
- Retained RED records share feature-absent candidate source `072f54819bac1c696d75af11f75f322475c957f0c3225e4bb7cd38e349c8bacb`: projection record `1790362011284150000-e11fd7a608bd443785f0d78beba1d9c1.json`, HTTP record `1790362011284192000-7861613153584ff9b7d3fbdb2a02ca18.json`, and browser record `1790362011287979000-5c7a9e0159e74b619bb0c9993e33a6f8.json`. Each exited `255` for the intended missing forecast seam/chart after reaching its fixture.
- Fresh bounded current-source runs by this reviewer: projection PASS, HTTP/auth/query-scale PASS, browser PASS. These runs are supporting evidence only and do not repair missing assertions.
- Rereview verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Matrix completeness — improved, still open.** The correction adds sequential/overlap, open end, deadline certificate/PTO, conservative UNKNOWN/unavailable, and a native applied-order path. Acceptance E is still incomplete: `tests/Workforce/installer_utilization_forecast_001_test.php:14` observes only the state after native application. It never reads forecast while the composition is merely a draft selection to prove it does not occupy the installer, never proves a future applied-but-unopened assignment begins at effective planned start, and never sets factual start different from planned start to prove factual precedence/no duplicate interval. Add all three independently asserted states.

2. **Public HTTP/detail sensitivity — improved, still open.** The correction establishes six-by-five link structure, free-member ordering, and one PTO reason end date at the direct read owner. It still does not assert rendered dashboard numeric values/denominators against fixture expectations, detail week bounds/denominator/object/full reasons, or that each of the five bucket link classes preserves its exact week and bucket. A plausible controller/view regression that swaps numeric bucket values or routes every non-free link to `free` would pass. Acceptance I also remains incomplete: source facts are mutated and projection changes, but historical observations are not captured and compared byte-for-byte across that mutation as required by `specs/...:38`. Add public response or DOM oracles for these values/coordinates/reasons and the explicit observations invariance witness.

3. **Authorization/HEAD/no-leak — mostly fixed, one public method gap remains.** Guest, denied, and scoped GET/HEAD on both routes now check status, empty rejected HEAD bodies, no leaked fixture values, invalid coordinates, and read-only facts. Authorized HEAD parity is asserted only for detail (`tests/Yii2/yii2_installer_utilization_forecast_001_test.php:7`); the normative public seams explicitly include `GET|HEAD /pilot/dashboard`, but no authorized dashboard HEAD status, empty body, or safe-header parity is asserted. Add the corresponding authorized dashboard HEAD oracle. The existing denied actor also does not isolate each permission independently, but the object-scoped case plus full-access success sufficiently witnesses the material policy for this bounded correction.

4. **Determinism/fail-safe/read-only — fixed.** Repeated dashboard output is normalized only for CSRF and compared, direct projection is compared exactly, per-person UNKNOWN and systemic unavailable are explicit, and the complete HTTP rejection/success matrix is surrounded by the fact snapshot. The chosen `missing_from_delivery` fixture is a deterministic incomplete-identity/source witness and cannot silently become `free`.

5. **Responsive/accessibility — fixed for the specified bounded surface.** The browser test now covers 1440 and 390, the six-by-five grid and labels, accessible names, page containment plus local mobile overflow, computed visible focus, keyboard activation, touch activation, exact navigation, and detail content. Text labels provide the required non-color distinction.

6. **Query scale/runtime closure — improved, still open.** The test establishes a constant observed query count and ceiling between the two-person fixture and the expanded 1000-person fixture, and checks forbidden runtime files. The normative matrix is explicit: `0/50/125/1000 rows` (`specs/...:42`), while `tests/Yii2/yii2_installer_utilization_forecast_001_test.php:10` exercises only 2 and 1000. An implementation with threshold-triggered N+1 behavior at 50 or 125, or an invalid empty-catalog response, would pass. Exercise each specified cardinality in isolated complete-source fixtures (or revise the accepted contract before review), asserting denominator, six weeks, query ceiling, and runtime closure at each.

7. **RED retention/source identity — fixed.** The patch digest is correct, its reviewed test blobs match the current files, and the retained records include argv, exact candidate source, exit status, duration, stdout/stderr locations, and intended first failure. The feature-absence guards mean later assertions do not each fail independently on the blank base, but the completed assertions remain necessary regression oracles once the public seam exists.

### Correction verdict

`CHANGES_REQUESTED`

Gate 3 remains blocked on the four narrow open areas above: complete acceptance E, public numeric/detail/I sensitivity, authorized dashboard HEAD parity, and the exact 0/50/125/1000 M matrix. Preserve the resolved witnesses and retained RED source, correct these together, capture fresh RED only if the test-source bytes change, and submit the complete package for independent rereview. No production behavior, CI, deployment, live adapter, or admission enforcement is approved here; those remain outside this Gate 3 decision or `UNKNOWN`.

---

## Gate 3 third review — v3 corrected matrix — 2026-09-25

- Independence remains unchanged; this reviewer authored none of the reviewed artifacts or evidence.
- Reconstructible feature-absent source: base `f9b05a298550f5b33e26fd6ab7328f25865150e6` plus `/tmp/fmonitor-forecast-tests-v3.patch`, verified SHA-256 `f27e498150df293cd418921d90d8ed1dfe309b6c574df4008438345b3a62da21`.
- Reviewed current blobs match the v3 patch: normative spec `3714e952`, projection test `4831444b`, HTTP test `341adbe9`, browser wrapper `8fa39c23`, browser module `def74a0c`.
- Fresh retained RED records share feature-absent candidate source `7de32c4efd4ea5cdfa37467092e553b790b249186361e70a62ee73fec8b3a6d2`: projection `1790362420037970000-eb25500baddf4ec18bbd973afc8156c8`, HTTP `1790362420037932000-1bd95809f8e24c3eb0afb157399477e8`, browser `1790362420041359000-df1393d25c914b279701bb8902b13991`. All reached their fixtures and exited `255` on the intended absent forecast seam/chart.
- Fresh bounded current-source runs by this reviewer all passed: projection `PASS: ... A-G/I/K/M projection`, HTTP `PASS: ... HTTP/auth/query-scale`, browser `PASS: ... browser`.
- Verdict: `CHANGES_REQUESTED`

### Prior blocker disposition

1. **Acceptance E and observation invariance — fixed.** The projection test now separately proves draft selection and accepted-but-unapplied original remain free, future applied work uses planned `2026-10-01`, factual opening overrides it with `2026-09-30`, and the observation rows remain byte-equivalent.

2. **Authorized dashboard HEAD — fixed.** The HTTP test now proves status `200`, empty body, and safe-header parity with authorized dashboard GET.

3. **Exact M cardinalities — fixed.** The direct owner is exercised in isolated fixtures at `0/50/125/1000`; zero fails unavailable, positive denominators are exact, and 50/125/1000 have one fixed query count under the ceiling. The existing HTTP include inventory continues to reject runtime `rapid-pilot`/`app/PilotHttp` loading.

4. **Public detail and I sensitivity — mostly fixed, one H/dashboard gap remains.** The HTTP test now asserts detail bounds, count/denominator, stable free-member order, native object, planned-start reason, and observation invariance after application. However, `tests/Yii2/yii2_installer_utilization_forecast_001_test.php:6` still asserts only that the dashboard contains six week containers and 30 anchors; it never asserts the numeric `busy/free/releasing/conflict/unknown` values or denominator against independently expected values. The browser test validates only the first `free` link's navigation on both viewports. No test validates that all 30 anchors encode their exact week plus bucket. A plausible regression that swaps displayed busy/free counts, or routes every non-free anchor to `/free`, passes every reviewed assertion while violating normative contract items 6/H and the OpenSpec rule that every indicator leads to its matching detail. Add one independently expected dashboard value vector for all five buckets and denominator (before and after the native fact change), and inspect the 30 hrefs as the Cartesian product of the six exact week starts and five exact bucket names. One representative activation remains sufficient once that structural coordinate oracle exists.

### Third-review verdict

`CHANGES_REQUESTED`

Gate 3 is blocked only on the remaining public dashboard value/link-coordinate sensitivity above. Preserve all resolved tests and v3 evidence; make this single bounded correction, retain a new reconstructible test-source snapshot and intended RED record because the HTTP test bytes will change, then request the next independent rereview. No production, CI, deployment, or admission state is approved by this Gate 3 record.

---

## Final narrow Gate 3 rereview — 2026-09-25

- Reviewer independence remains unchanged.
- Unchanged reviewed identities: normative spec `3714e952`, projection test `4831444b`, browser wrapper `8fa39c23`, browser module `def74a0c`.
- Final corrected HTTP test identity: Git blob `c44e4a4623742444a33e50c6037fa38418b1dd62` (supersedes v3 blob `341adbe9`). The only material delta is additive dashboard expected-value/link-coordinate sensitivity after the same feature-presence boundary.
- Feature-absent source/evidence remains the verified v3 snapshot (base `f9b05a298550f5b33e26fd6ab7328f25865150e6` plus `/tmp/fmonitor-forecast-tests-v3.patch`, SHA-256 `f27e498150df293cd418921d90d8ed1dfe309b6c574df4008438345b3a62da21`) and RED candidate `7de32c4efd4ea5cdfa37467092e553b790b249186361e70a62ee73fec8b3a6d2`. The HTTP RED reaches the valid fixture and fails at the absent forecast dashboard before either the v3 or final additive semantic assertions; the final additions do not alter setup or the public missing-feature boundary.
- Fresh reviewer run: `php tests/Yii2/yii2_installer_utilization_forecast_001_test.php` exited `0` with `PASS: INSTALLER-UTILIZATION-FORECAST-001 HTTP/auth/query-scale`.

### Remaining finding disposition

**Fixed.** `tests/Yii2/yii2_installer_utilization_forecast_001_test.php:7` now independently asserts the six exact denominators, all 30 members of the six-week × five-bucket Cartesian href set, and the exact baseline numeric vector (`busy=0`, `free=2`, `releasing=0`, `conflict=0`, `unknown=0`) for every week. Line 9 then asserts the first-week live vector changes to `busy=1`, `free=1`, and zero overlays/unknown after native application before checking the exact busy detail. This catches swapped counts, stale dashboard values, duplicate/missing coordinates, and misrouted non-free links while preserving the previously approved representative keyboard/touch activation.

### Final Gate 3 verdict

`APPROVED`

Gate 3 passes for the exact reviewed blobs named above, with the retained v3 feature-absent snapshot and RED records. Gate 4/5 may rely on these expectations without changing them. Any subsequent normative spec or test-byte change requires planner-selected review again. This verdict approves test adequacy only; production conformance, CI, deployment, live adapters, and admission enforcement remain for their own gates or `UNKNOWN`.

---

## Post-main-merge Gate 3 delta review — commit `f5262de0` — 2026-09-25

- Exact reviewed source: clean commit `f5262de0e85ca87f4cf4af72ffc3133ec0a61549`.
- Delta scope: the revised authorization clauses in `specs/INSTALLER-UTILIZATION-FORECAST-001.md` and the forecast OpenSpec delta; the corresponding forecast HTTP matrix; and the inherited operational-dashboard browser oracle changed from three to five widgets with updated responsive geometry.
- Reviewer independence remains unchanged.
- Reviewer verification: `php tests/Yii2/yii2_installer_utilization_forecast_001_test.php` exited `0` with `PASS: INSTALLER-UTILIZATION-FORECAST-001 HTTP/auth/query-scale` at the exact commit. The other focused PASS results reported in the handoff were not promoted to independent evidence by this review.
- Verdict: `CHANGES_REQUESTED`

### Finding

1. **HIGH — the sanitized denied/scoped dashboard test is not sensitive to the new “no forecast counts” contract.** The amended contract is internally coherent: guest redirects; authenticated denied/scoped dashboard remains `200` with sanitized unavailable forecast; direct forecast detail remains `403`; HEAD is bodyless. The HTTP matrix exercises all those statuses and methods and requires the unavailable message. But `tests/Yii2/yii2_installer_utilization_forecast_001_test.php:8` treats only `Монтажник 7001` and the exact phrase `2 монтажников` as secrets. A regression that renders the live forecast grid or links with bucket counts—for example `Свободны 0`, `Заняты 1`, or the 30 forecast detail hrefs—while also rendering `Прогноз временно недоступен` passes every denied/scoped assertion. That directly violates normative item 7 and OpenSpec's “sanitized unavailable forecast without counts/PII.” Parse each denied/scoped dashboard GET and assert the unavailable state contains no forecast chart/value/member/reason/detail-link markers (at minimum no `[data-dashboard-chart="installer-forecast"]`, `.fm2-forecast-value`, `/pilot/dashboard/installers/forecast/`, denominator, tabId/FIO/object/reason data), while preserving the unrelated universal dashboard widgets now intentionally available. Keep the direct-detail no-body/PII checks.

### Sound delta

The specification and OpenSpec authorization prose agree on the superseding universal-dashboard policy. Guest redirect, denied/scoped dashboard `200`, direct-detail `403`, GET/HEAD status behavior, unavailable copy, read-only facts, and the fully authorized path remain covered. The operational-dashboard browser update explicitly expects five widgets and checks wide/intermediate/narrow geometry for all five; it does not weaken the forecast-specific authorization boundary.

### Delta verdict

`CHANGES_REQUESTED`

The previously approved forecast matrix remains valid except for this changed authorization outcome. Correct the single sanitized-dashboard absence oracle and submit the exact test delta for independent rereview. Production conformance, the reported focused runs not independently executed here, CI, deployment, and admission enforcement remain separate or `UNKNOWN`.

---

## Final post-main authorization delta rereview — commit `00fa3e85` — 2026-09-25

- Exact reviewed source: `00fa3e85b6f03a520b48f6b40aa34431eb0631bb`; corrected forecast HTTP test blob `52831fc9`.
- Reviewer independence remains unchanged.
- Fresh reviewer verification: `php tests/Yii2/yii2_installer_utilization_forecast_001_test.php` exited `0` with `PASS: INSTALLER-UTILIZATION-FORECAST-001 HTTP/auth/query-scale`.

### Prior finding disposition

**Fixed.** For both authenticated denied and scoped dashboard GET, the test still requires the sanitized unavailable message and now rejects every material forecast disclosure surface: forecast value markup, every forecast detail-link prefix, member markup, and reason markup. Together with the existing explicit FIO/denominator exclusions, direct-detail `403`, guest redirect, empty HEAD bodies, status matrix, and fact snapshot, this makes the changed authorization outcome sensitive to leaked counts, navigable forecast coordinates, people, and reasons while leaving the unrelated universal dashboard widgets available.

### Final delta verdict

`APPROVED`

Gate 3 approves the authorization spec/test delta at exact commit `00fa3e85b6f03a520b48f6b40aa34431eb0631bb`. The earlier full forecast Gate 3 approval remains in force with this superseding authorization outcome. Any later spec or test-byte change requires planner-selected review again. Production conformance, CI, deployment, and admission enforcement remain for their separate gates or `UNKNOWN`.
