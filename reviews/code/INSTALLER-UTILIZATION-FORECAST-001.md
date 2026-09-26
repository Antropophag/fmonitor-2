# Gate 5 review — INSTALLER-UTILIZATION-FORECAST-001

Date: 2026-09-25
Reviewer: independent `gpt-5.6-sol/low` final reviewer (`/root/final_review`)
Initial verdict: **CHANGES_REQUESTED** (superseded by the correction rereview below)

## Reviewed source and authorization

Reviewed the exact dirty working tree at `/private/tmp/fmonitor-258-forecast`: `HEAD` and base are both `f9b05a298550f5b33e26fd6ab7328f25865150e6`; the candidate consists of the 7 modified and 11 untracked paths reported by `git status --porcelain` at review time. Harness state identified current admission candidate/source `3011996d4d642061d5df5957088571db2e82207127f36554fd54c16e6eb38085`; the executor role package is `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T174529Z-62e34c09f4/package.json` (prepared input source `5f2a2f0891232bb8b1c28d2ad9f68f46664210003c9cef3a42cea69f42b8df6a`). The working-tree implementation therefore post-dates the executor package snapshot, as expected for that role handoff.

The current goal explicitly authorizes autonomous delivery of issue #258 stage 3 with root-authored scope/spec/tests, a separate executor, and independent Gate 3/5 reviewers. Actual production authorship is consistent with the executor role. Scope contains no schema migration, writer, backfill, auto-allocation, capture-job correction, or unrelated WIP change. This review modified only this review record.

Lifecycle is not ready for admission: planner state selects `CRITICAL`, while no Gate 3 record exists under `reviews/tests/` and harness state reports both required reviews missing. OpenSpec task 2.5/3.4 are checked despite that missing evidence and despite the coverage gaps below.

## Findings

### 1. HIGH — conflict and releasing overlays do not implement the required interval semantics

`app/Workforce/MariaDbInstallerUtilization.php:64` declares `conflict` whenever more than one assignment merely intersects the same week (`count($hits)>1`). The contract requires the assignments themselves to overlap on at least one calendar day. Sequential assignments in one week are therefore falsely reported as a conflict.

The same line declares `releasing` when any hit ends during the week, without checking whether another assignment intersects after that end. The contract requires no later/continuing assignment after the ending interval. An installer who transitions directly to another assignment is therefore falsely reported as releasing.

These violate normative clauses 3 and acceptance C in `specs/INSTALLER-UTILIZATION-FORECAST-001.md` and the more explicit OpenSpec requirement in `openspec/changes/add-installer-utilization-forecast/specs/workforce/installer-utilization-forecast/spec.md:19`.

### 2. HIGH — forecast does not read effective planned dates and mishandles deadline extensions

`app/Workforce/MariaDbInstallerUtilization.php:55-56` reads order snapshot planned dates directly and does not use the existing effective-object-details owner required by the design. `forecastInterval()` at line 71 then takes the minimum of explicit end, PTO, current deadline, and the stale order snapshot. A current deadline certificate that extends the planned finish can never extend the interval because the earlier snapshot always wins. Likewise an effective planned-start edit is invisible.

This violates normative clauses 4–5 and acceptance E/F, which require effective planned start/finish, factual start, current deadline certificate, and earlier confirmed PTO precedence. It can publish false `free` and false `releasing` results.

### 3. HIGH — the declared acceptance suite is materially insensitive

`tests/Workforce/installer_utilization_forecast_001_test.php:7-8` checks only empty assignments and one legacy interval. It has no native application, overlapping or sequential pair, continuing assignment after a release, open end, draft exclusion, effective edit/deadline extension, PTO precedence, malformed identity/source gap, duplicate tabId, 50/125/1000-row query count, or deterministic repeated-read scenario. The HTTP test at `tests/Yii2/yii2_installer_utilization_forecast_001_test.php:5-8` does not test scoped denial, unavailable/UNKNOWN, PII non-leakage, stable ordering, GET/HEAD header equality, or writes beyond its narrow fixture. The browser test only checks page-level overflow and DOM focus/Enter; it does not assert local overflow, visible focus, legend differentiation, desktop layout, or touch behavior.

This contradicts the checked claims in OpenSpec tasks 2.1–2.4 and leaves acceptance E–G and M, plus substantial parts of H–L, unverified. The focused tests pass despite findings 1–2, demonstrating the sensitivity failure.

### 4. MEDIUM — source completeness and unique denominator are not established

`forecast()` queries rows whose current catalog status is `employed`, increments the denominator once per returned row, and never validates source-level completeness/freshness or uniqueness of `installer_tab_id` (`app/Workforce/MariaDbInstallerUtilization.php:54,64`). A duplicate tabId is counted twice, contrary to the unique-installer requirement; an empty or systemically incomplete catalog can be published as six genuine zero weeks instead of unavailable. Per-row malformed identity becomes UNKNOWN, which is safe, but global source completeness is not proven before zeros/free values are emitted.

This violates normative clauses 2 and 5 and acceptance G.

### 5. MEDIUM — maintainability/cohesion guardrail

`app/Workforce/MariaDbInstallerUtilization.php:51-66` compresses four bulk reads, snapshot validation, interval construction, classification, overlap logic, and response shaping into one method. This triggers the review advisory in `docs/architecture/guardrails.md` section 4 and is a possible Fowler Divergent Change hotspot. The density contributed directly to the unauditable overlay errors above. Extract named loading and interval-classification helpers while keeping SQL ownership in the adapter.

### 6. LOW — malformed calendar input can escape the intended HTTP boundary

`app/YiiRuntime/Controllers/DashboardController.php:76` constructs `DateTimeImmutable` before the guarded call. The route regex accepts syntactically shaped but calendar-invalid dates, so malformed input can escape as an internal error rather than the intended fail-closed 404/503 response.

## Confirmed properties

- No production write/DDL/schema/backfill seam was added; focused before/after fact assertions passed.
- Query statement count is fixed rather than per identity, although the required scale/query-count test is absent.
- Forecast is independent of saved observations and runtime production code does not load `rapid-pilot` or `app/PilotHttp`.
- Dashboard/detail enforce `objects.read` + `installers.read` and reject the construction-control scoped role; route verbs are GET/HEAD. Output values are escaped.
- Base `busy/free/unknown` partition is structurally exclusive for each returned catalog row, and open-ended intervals remain busy through the horizon.
- UI separates forecast from history, has text labels, keyboard links, and localized horizontal overflow. Accessibility/responsive acceptance remains under-tested as described above.

## Evidence

Focused commands run against the reviewed working tree (no full suite):

- `php tests/Workforce/installer_utilization_forecast_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_forecast_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_forecast_browser_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_browser_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_stage_one_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_surfaces_001_test.php` — PASS
- `git diff --check` — PASS

Independent standards-axis review additionally reported PHP lint PASS. Its bounded `tools/architecture/check` attempt was terminated after 30 seconds and is explicitly **not GREEN**. No exact-source CI was run at Gate 5; CI remains a later step after approval.

## Required correction before re-review

Implement pairwise day-overlap conflict detection; suppress releasing when any assignment continues/intersects after the ending interval; source planned boundaries through the canonical effective-details/deadline semantics; establish global source completeness and unique tabId handling; and add sensitive projection/public/browser/query-count tests for every claimed acceptance branch. Restore the required Gate 3 evidence and reconcile OpenSpec task state before presenting a new exact-source candidate.

---

## Correction rereview — 2026-09-25

Final verdict: **APPROVED**

### Exact reviewed source

The rereview covered the exact dirty candidate in `/private/tmp/fmonitor-258-forecast` immediately before this review-only record update: base and `HEAD` `f9b05a298550f5b33e26fd6ab7328f25865150e6`, harness admission candidate/source `ca7499911179c7dbe3d656b6125c381ca698386d1c6675cdaab6d717dadf0e5e`. The only reviewer-authored mutation after that identity is this appended Gate 5 record; production, specification, OpenSpec, and test files were not edited by the reviewer.

The executor package remains `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T174529Z-62e34c09f4/package.json`, bound to predecessor `f9b05a298550f5b33e26fd6ab7328f25865150e6` and normative contract `specs/INSTALLER-UTILIZATION-FORECAST-001.md`. The current goal authorizes root specification/test authorship, separate executor implementation, and independent Gate 3/5 review. The appended `reviews/tests/INSTALLER-UTILIZATION-FORECAST-001.md` ends with an independent Gate 3 **APPROVED** verdict for the unchanged normative spec and exact corrected test blobs (`3714e952`, `4831444b`, `c44e4a4623742444a33e50c6037fa38418b1dd62`, `8fa39c23`, `def74a0c`) plus retained reconstructible RED evidence.

### Prior findings disposition

1. **Interval overlays — fixed.** `app/Workforce/MariaDbInstallerUtilization.php:64` now calculates pairwise inclusive day overlap for `conflict`, counts each installer once, and suppresses `releasing` when another interval covers later time in that week. Tests independently distinguish sequential assignments, same-day overlap, and continuing/open-ended work.

2. **Effective boundaries — fixed.** The forecast queries use the canonical planned-start expression, effective object labels, the current deadline revision in preference to stale finish, factual start for opened cases, and earlier confirmed PTO as the end cap. Native application rows no longer require a legacy registered-order join. Tests prove future applied planned start, factual override, deadline extension, PTO precedence, draft/original exclusion, and native ownership without registered fallback.

3. **UNKNOWN/unavailable safety — fixed.** Missing-delivery identity becomes the mutually exclusive `unknown` base bucket and never `free`; empty catalog and detected authoritative assignment gaps fail the whole projection unavailable rather than publishing zeros. The dashboard renders an explicit unavailable state. Exact 0/50/125/1000 tests prove zero-source rejection, denominators, and a fixed bounded query count.

4. **Public seam/auth/read-only — fixed.** Corrected tests cover all six exact week bounds, all five bucket values and the 30 week/bucket link coordinates, live detail reasons/order/count/denominator, forecast changes with observation byte-equivalence, authorized and rejected GET/HEAD behavior, safe-header parity, object-scoped denial, no PII leakage, invalid coordinates, deterministic reads, and fact non-mutation. Invalid calendar dates now fail closed before `DateTimeImmutable` construction can escape.

5. **Responsive/accessibility/runtime closure — fixed.** Browser coverage exercises 1440 and 390 px, local rather than page overflow, text labels/non-color distinctions, accessible names, visible focus, keyboard and touch activation, exact navigation, and detail content. Runtime evidence rejects `rapid-pilot`/`app/PilotHttp` loading.

6. **Maintainability advisory — accepted as non-blocking.** The forecast projection remains unusually dense in the existing MariaDB adapter, but it preserves the repository's SQL ownership boundary, uses a constant number of bulk queries, adds no writer, and is now protected by sensitive A–M tests. This is technical-debt advice, not a remaining spec or safety failure for this bounded candidate.

### Fresh Gate 5 evidence

Run against source `ca7499911179c7dbe3d656b6125c381ca698386d1c6675cdaab6d717dadf0e5e`; no full local suite was run:

- `php tests/Workforce/installer_utilization_forecast_001_test.php` — PASS (`A-G/I/K/M projection`)
- `php tests/Yii2/yii2_installer_utilization_forecast_001_test.php` — PASS (`HTTP/auth/query-scale`)
- `php tests/Yii2/yii2_installer_utilization_forecast_browser_001_test.php` — PASS (`desktop/mobile keyboard/touch`)
- `php tests/Yii2/yii2_installer_utilization_browser_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_stage_one_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_surfaces_001_test.php` — PASS
- `git diff --check` — PASS
- PHP lint for the changed projection, controller, and detail view — PASS

No remaining Gate 5 correctness, authorization, read-only, interval, UNKNOWN, query-shape, UI/accessibility, or test-sensitivity finding was identified. Exact-source CI remains the next separate gate; merge/deploy remain unauthorized.

---

## Post-main-merge Gate 5 rereview — 2026-09-25

Verdict: **APPROVED**

### Exact reviewed commit and authority

Reviewed the clean committed tree `f5262de0e85ca87f4cf4af72ffc3133ec0a61549` in `/private/tmp/fmonitor-258-forecast`, a merge of feature parent `d88f0bd2bd44804296a6d6ccaaa7fca9f663e823` with `origin/main` parent `9c28aa790f7bc0441c13ca83e012acc7f5d762b7`. The conflicting paths were `DashboardController.php`, `dashboard.php`, and the current delivery goal. This appended record is the reviewer's only post-commit working-tree change; no production, specification, OpenSpec, or test file was edited.

The current goal now explicitly authorizes PR creation and merge after GREEN CI and required approvals. It still does not authorize deploy. The feature contract, independent Gate 3 approval, separate executor authorship, and earlier Gate 5 approval remain preserved in the merged history.

### Merge-resolution review

- **Main dashboard contract preserved.** `DashboardController::actionIndex()` remains available to every authenticated role through the main dashboard read owner. Restricted roles are not globally rejected by the forecast feature.
- **Forecast confidentiality preserved.** Forecast computation requires both `objects.read` and `installers.read` and rejects the object-scoped construction-control role. On the shared dashboard, denied/scoped actors receive only the sanitized “forecast unavailable” widget with no counts, identities, or object data. Direct `GET|HEAD /pilot/dashboard/installers/forecast/...` remains `403` for those actors. Guests retain the canonical authentication redirect.
- **Latest main history behavior preserved.** The merged controller calls `currentSummary(0)` and bounded `historyBetween($utilizationFrom, $utilizationTo)`; the view retains the six-calendar-week history chart and its observation links. Forecast remains a separate live projection and does not replace or mutate observations.
- **Five-widget layout reconciled.** Main's primary pairing of stages + utilization and secondary weeks + start-risk grid is unchanged; forecast is added as the fifth independent widget. The operational dashboard browser expectation was updated from four to five widgets while retaining full-width forecast and paired chart geometry on desktop/mobile.
- **Feature semantics preserved.** The merge does not alter authoritative interval construction, effective dates, UNKNOWN/unavailable behavior, read-only operation, bounded query shape, detail routing, or responsive/accessibility behavior approved in the prior Gate 5 review.
- **Scope preserved.** No forecast writer, schema migration, backfill, auto-allocation, capture-job correction, or deploy action was introduced by the resolution.

### Findings

No blocking or non-blocking merge-resolution finding was identified. The controller intentionally treats unavailable/unauthorized forecast data as a sanitized optional widget on the universal dashboard while retaining hard authorization at the PII-bearing detail seam; this is consistent with the merged all-role dashboard contract and the amended forecast contract.

### Fresh evidence on exact commit `f5262de0`

No full local suite was run. The reviewer ran:

- `php tests/Workforce/installer_utilization_forecast_001_test.php` — PASS (`A-G/I/K/M projection`)
- `php tests/Yii2/yii2_installer_utilization_forecast_001_test.php` — PASS (`HTTP/auth/query-scale`, including sanitized restricted dashboards and detail 403)
- `php tests/Yii2/yii2_installer_utilization_forecast_browser_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_observations_001_test.php` — PASS
- `php tests/Yii2/yii2_operational_dashboard_bar_charts_001_test.php` — PASS (`complete A-L matrix`, including five-widget geometry)
- `git diff --check 9c28aa79..f5262de0` — PASS

Commit `f5262de0e85ca87f4cf4af72ffc3133ec0a61549` is approved for the next authorized delivery step. Deployment remains unauthorized.

---

## Test-only delta Gate 5 rereview — commit `00fa3e85` — 2026-09-25

Verdict: **APPROVED**

Reviewed exact `HEAD` `00fa3e85b6f03a520b48f6b40aa34431eb0631bb`, parent `f5262de0e85ca87f4cf4af72ffc3133ec0a61549`. The committed delta contains no production or normative-spec change: it changes only `tests/Yii2/yii2_installer_utilization_forecast_001_test.php` and appends the originating Gate 3 delta finding to `reviews/tests/INSTALLER-UTILIZATION-FORECAST-001.md`. The pre-existing local modification to this Gate 5 record is reviewer history and is not part of commit `00fa3e85`.

The new HTTP assertion directly closes the identified sanitized-dashboard sensitivity gap. For denied and scoped authenticated actors it now requires the universal dashboard's unavailable forecast state while rejecting all forecast value classes, forecast detail URLs, member markers, and reason markers. This prevents a regression from rendering a live count/link grid alongside the unavailable message, while leaving unrelated universal dashboard widgets intentionally visible. Existing status, HEAD-body, explicit secret, direct-detail `403`, and read-only assertions remain intact.

Evidence:

- `git diff f5262de0..00fa3e85` — exactly the test assertion plus append-only Gate 3 review record; no production/spec delta.
- `git diff --check f5262de0..00fa3e85` — PASS.
- `php tests/Yii2/yii2_installer_utilization_forecast_001_test.php` — PASS (`INSTALLER-UTILIZATION-FORECAST-001 HTTP/auth/query-scale`).
- Working-tree `git diff --check` — PASS.

No Gate 5 finding remains for this bounded test-only delta. This approval does not itself replace the independent Gate 3 review status recorded in `reviews/tests/INSTALLER-UTILIZATION-FORECAST-001.md`; that gate's exact-test-source verdict must be updated independently before admission. Production behavior remains approved from the `f5262de0` rereview, and deployment remains unauthorized.

---

## CI-correction Gate 5 rereview — commit `fdc31e64` — 2026-09-25

Verdict: **APPROVED**

Reviewed exact `HEAD` `fdc31e64dfd9ed5489d473afccf7048c782170a6`, parent `c67eb1afe0fe7434c24322d3110c539a12f68c61`. The committed correction changes exactly two test-support literals; it does not change production code, specification semantics, authorization, persistence, or runtime behavior.

The complete reported failure inventory for GitHub run `36178445438` contains exactly:

1. `Runtime/yii2_production_web_cutover` — immutable `pilot.css` digest mismatch.
2. `Yii2/yii2_minimal_operational_dashboard` — the generic mobile clipping detector treated forecast links inside the intentional local horizontal scroller as page clipping.

Forecast-specific tests were GREEN in that run. No other failure is hidden or reclassified by this correction.

### Correctness and masking review

- `tests/Support/yii2_production_web_cutover_contract.php` changes only the expected `pilot.css` SHA-256. The new value `36af48af303c55809892d3e8e8eedddd7173ba12b71adb90f3285c8b8f9fe2b8` exactly matches the committed asset bytes; MIME and cache contracts remain unchanged. The immutable-byte check is updated, not weakened.
- `tests/Support/minimal_operational_dashboard_browser.cjs` excludes only `.fm2-forecast-value` anchors from its generic viewport-bound element scan. It still rejects document-level horizontal overflow and clipping of every other main link, metric, object, and empty state. The excluded links live in `.fm2-forecast-scroll`, whose intentional local overflow and page containment are independently asserted at 390 px by the forecast browser test, together with visible focus, keyboard/touch activation, exact navigation, and detail content. The delta removes a false positive without masking page overflow or an untested interaction surface.
- Widget counts, dashboard semantics, screenshots/evidence, desktop checks, and populated/empty/error modes remain unchanged.

### Fresh exact-source evidence

No full local suite was run:

- `shasum -a 256 app/YiiRuntime/Assets/pilot.css` — exact contract digest match.
- `php tests/Runtime/yii2_production_web_cutover_001_test.php` — PASS (`single runtime`).
- `php tests/Yii2/yii2_minimal_operational_dashboard_001_test.php` — PASS (`complete A-N matrix`).
- `git diff --check 00fa3e85..fdc31e64` — PASS for the delivery delta.
- Working-tree `git diff --check` — PASS.

No finding remains for this CI correction. Commit `fdc31e64dfd9ed5489d473afccf7048c782170a6` is approved for rerunning the required CI consumer. This does not convert the prior CI run to GREEN; deploy remains unauthorized.

---

## Production-regression hotfix Gate 5 review — 2026-09-26

Verdict: **APPROVED**

### Exact source, scope, and authorship

Reviewed the exact dirty candidate in `/private/tmp/fmonitor-forecast-hotfix` immediately before this review-only append: clean base/`HEAD` `708e0a6db6cf7075e392ddc3814e6234672123da`, harness admission candidate/source `7134b511dc1d45e1e2e4aaeb39f1e531cb30eac590efb9b6d87cb357392d1f95`. Production changes are limited to `app/YiiRuntime/Controllers/DashboardController.php` and `app/YiiRuntime/Views/dashboard.php`. Root-authored contract/tests and the separate executor implementation follow the current-goal authorization; the independent hotfix Gate 3 correction rereview is **APPROVED** for the exact listed spec/test blobs and retained RED source. This reviewer authored none of those artifacts and changed only this review record.

The owner authorizes PR/merge after approvals and exact-source GREEN CI. Deploy and manual production-data edits remain unauthorized.

### Conformance review

- **One widget, no duplication.** The prior separate `installer-forecast` section is removed. The existing primary-pair `data-dashboard-chart="utilization"` widget and title «Загрузка монтажников и динамика» now render the six-week, five-bucket live forecast. There is exactly one utilization widget, no second forecast widget, and the surrounding four-widget dashboard geometry remains coherent.
- **No dashboard history query/visualization.** `actionIndex()` no longer constructs history bounds, opens the observation adapter, calls `currentSummary()`/`historyBetween()`, or passes historical DTOs. The replaced widget contains no observation-date/history markup. This removes the stale dashboard visualization without deleting historical facts.
- **Observation preservation.** The observation read owner, storage/capture implementation, `actionObservation()`, routes, and saved observation detail view remain unchanged. Focused tests prove stored history stays byte-equivalent and direct saved-detail GET|HEAD remains available.
- **Authenticated dashboard inheritance.** Once the existing dashboard read owner admits an authenticated active user, forecast/dashboard and direct detail no longer impose unrelated installer capability or construction-control scope checks. They call the actor-neutral read projection (`actorId=0`), matching the corrected contract. Permissionless and scoped actors receive the same six weeks, denominator, links, and detail data; guests retain the canonical redirect. This directly corrects the production diagnosis where the owner returned six weeks/denominator 937 but the old controller produced `forecast_allowed=0` for active-user roles.
- **No false unavailable.** Authorization/scope is no longer converted into the forecast catch path. The unavailable widget is now source/error fail-safe only. Empty or incomplete authoritative source still produces explicit unavailable text and never fabricated zero/free values.
- **No behavior masking.** Tests independently assert the single reused widget, absence of historical dashboard markers, six weeks/30 values, exact live values and coordinates, permissionless/scoped GET|HEAD `200`, real detail content, guest redirect, repeated-read/no-write behavior, source-empty fail-safe, retained observation storage/detail, and desktop/mobile keyboard/touch behavior. The operational dashboard oracle now expects four widgets because the historical utilization widget is replaced in place rather than accompanied by a fifth forecast widget.
- **No scope creep.** No workforce projection, database/schema, writer, capture, observation-storage, assignment/PTO, or deployment code changed.

### Findings

No correctness, authorization, source-safety, composition, regression-masking, performance, accessibility, or authorship finding remains. The production diagnosis is consistent with the removed controller gate and does not require a production-data mutation.

### Fresh bounded evidence

No full local suite was run:

- `php tests/Workforce/installer_utilization_forecast_001_test.php` — PASS (`A-G/I/K/M projection`)
- `php tests/Yii2/yii2_installer_utilization_forecast_001_test.php` — PASS (`HTTP/auth/query-scale`)
- `php tests/Yii2/yii2_installer_utilization_forecast_browser_001_test.php` — PASS
- `php tests/Yii2/yii2_installer_utilization_observations_001_test.php` — PASS (`observation storage/detail preserved; dashboard history removed`)
- `php tests/Yii2/yii2_operational_dashboard_bar_charts_001_test.php` — PASS (`complete A-L matrix`)
- `php -l app/YiiRuntime/Controllers/DashboardController.php` — PASS
- `php -l app/YiiRuntime/Views/dashboard.php` — PASS
- `git diff --check` — PASS

Reported executor deployment/verification/architecture checks are consistent with this review; the one transient Docker diagnostic was rerun GREEN and is not promoted here beyond the independent focused evidence above. Exact-source CI remains required before merge. Deploy remains unauthorized.
