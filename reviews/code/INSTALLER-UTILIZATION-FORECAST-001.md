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
