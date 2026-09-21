# Gate 3 review — YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001

- Reviewer: Codex, independent `gpt-5.6-sol`, reasoning `low`
- Test author: root
- Reviewed exact source: `49f650a2f9424fe3bfbbca46eb51cc490b5ad894e4ca5d7e809ee4c34f4438f2`
- Reconstructible source: base commit `ea010d94b98e0ddc65254d67733daa078c185593` plus empty retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T081816Z-03914f213b/snapshot/source.patch`, patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T081816Z-03914f213b/package.json`
- Specification: `specs/YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001.md`
- Reviewed tests: `tests/Yii2/yii2_operational_dashboard_bar_charts_001_test.php`, `tests/Support/operational_dashboard_bar_charts_browser.cjs`
- Public seams under review: `YiiOperationalDashboard::read(actorId, cutoff)`, real Yii `GET|HEAD /pilot/dashboard`, `GET /pilot/objects?chart=<kind>&bucket=<key>`, and rendered browser behavior at 1440/390
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789978682984133000-2a1670110c5b4d62b3fc1758dc955192.json`
- RED command: `php tests/Yii2/yii2_operational_dashboard_bar_charts_001_test.php`
- RED evidence: `INTENDED_RED`, exit `255`; first failure is `INTENDED_RED fixed chart DTO` at test line 23 because the predecessor public read DTO has no charts. This is an intended missing-behavior failure, not a setup/environment failure.
- Verdict: **CHANGES_REQUESTED**

## Findings

1. **[BLOCKING] The stage expectation contradicts itself across the two public seams.** At `tests/Yii2/yii2_operational_dashboard_bar_charts_001_test.php:24`, the `installation` stage value is `3`; at line 32 the corresponding `chart=stage&bucket=installation` drill-down total is expected to be `4`. Acceptance B and E require the bar value to equal the registry `filters.total` for the same mutually exclusive canonical bucket. Both assertions cannot be correct. Independently derive the intended fixture membership once, correct the wrong expectation, and assert parity for every one of the six stage bars and drill-downs.

2. **[BLOCKING] Traceability A–L is claimed but the executable matrix omits the central bounded-read contract.** The comment at line 6 and final PASS text claim complete A–L coverage, while no 30,000-object fixture, fixed query-count witness, DTO-size/materialization check, response/DOM bound, or hidden-dataset exclusion exists. This leaves Acceptance F and K's highest-risk requirement wholly untested. Add a measured 30k public data/HTTP witness proving fixed query count and DTO shape, bounded memory/materialization, 23 marks only, and no serialized object corpus.

3. **[BLOCKING] Determinism, repeat, and concurrent-read behavior from Acceptance A are absent.** The test performs one owner read, one dashboard GET, and one HEAD, but does not compare repeated results or issue simultaneous reads. It therefore cannot detect request-local cutoff drift, temporary-state leakage, or read-side races. Add sequential equality and bounded concurrent GET/HEAD or owner-read probes around fresh before/after fact fingerprints, with the HTTP clock fixed to one Moscow cutoff.

4. **[BLOCKING] Drill-down coverage is not sensitive to the allowlisted bucket contract.** Lines 29–33 prove only a generic `/pilot/objects?chart=` prefix and four selected totals out of 23 bars. They do not verify exact hrefs or totals for all six stage, twelve week-series, and five activity buckets; search/page composition and the displayed registry cutoff are also absent. A wrong bucket key, swapped series/week, duplicated href, client-supplied boundary, or lost pagination/search could pass. Assert all 23 exact server-derived links and bucket totals, plus permitted search/page composition and current-cutoff disclosure.

5. **[BLOCKING] Rejection and authorization coverage is partial and not proven read-only.** Line 33 exercises four malformed/conflicting URLs but omits missing chart/bucket pairs, malformed duplicate/extra dimensions beyond `from`, and the full allowlist edges. Line 34 checks forbidden dashboard access, but there is no guest safe-return-path assertion, no forbidden chart-filter request, and no proof that rejected/forbidden reads preserve facts. Add guest `303` with preserved `/pilot/dashboard` return, forbidden dashboard and drill-down no-disclosure cases, a complete malformed/conflict table, and fresh fact fingerprints around all rejection groups.

6. **[BLOCKING] The activity-age test does not cover the specified boundaries or all accepted evidence semantics.** Lines 13–17 exercise ages 0, 7, 15, 31 and `never`, but not 8, 14, or 30; there is no revoked-photo exclusion, future-after-cutoff exclusion, correction/root maximum, or explicit locally-unaccepted operation case. A classifier with incorrect inclusivity at three boundaries or with the wrong evidence source can pass. Add independently calculated cases for `7/8/14/15/30/31`, accepted versus local-only/device time, current non-revoked versus revoked photo, root/correction facts, after-cutoff exclusion, and maximum selection.

7. **[BLOCKING] Browser evidence covers only the populated success state and does not exercise keyboard activation.** `tests/Support/operational_dashboard_bar_charts_browser.cjs:3` captures populated pages at 1440/390 and checks focus styling, but never activates a link with the keyboard, proves the resulting filtered registry, or renders zero and atomic-error states. Acceptance G/K explicitly requires populated and zero/error screenshots and keyboard activation. Add state-specific browser runs at both viewports, retained screenshot paths/hashes, semantic state assertions, Enter activation to the expected bucket, and overflow/clipping checks for each state.

8. **[MAJOR] Presentation/accessibility assertions are too weak for the stated contract.** PHP line 30 accepts any `aria-label` containing `:` and any href with the generic prefix; the browser helper validates only the first bar and merely records all other labels without asserting them. Visible category/value text, chart/series/category/value names for every mark, legends that work without color, zero-value honesty, full-width versus paired layout, and document-flow stacking are not verified. Assert each of the 23 visible labels/values and complete accessible names, all legends/series text, zero values, and the required desktop/mobile widget geometry.

9. **[MAJOR] Atomic error and empty behavior are incomplete.** Line 38 tests one renamed activity table and safe `503`, but not a malformed DTO/sum invariant or failures in stage/week sources; it also does not fingerprint the failed read. No successful empty dataset is tested at the data, HTTP, or browser seam. Add representative mandatory-source and DTO-shape/sum failures with no partial values/identities and no writes, plus an accessible empty `200` with all 23 zero values and the honest explanation.

10. **[MAJOR] Canonical status ownership/parity is not established.** The fixture happens to expect six stage values, but does not compare each stage aggregate with the existing queue status projection. The four sampled drill-downs do not protect all classification branches, and finding 1 demonstrates an existing disagreement in the expectations. Add all-six queue parity assertions and exclusivity/sum checks using the ordinary queue public seam so duplicated or divergent classification logic fails.

11. **[MAJOR] The weekly contract is only partly characterized.** Line 26 usefully covers Monday boundaries, an out-of-window start, a transfer, and six rows, but it does not test Sunday/Monday transitions for later weeks, unknown/invalid dates, one-count-per-object behavior, or the full starts/finishes drill-down matrix. Add boundary fixtures and exact link/total assertions for all twelve series buckets, including original finish replaced (not supplemented) by the latest confirmed transfer.

12. **[MAJOR] Predecessor and adjacent-flow preservation from Acceptance H is not covered.** The candidate does not assert the four existing metrics, two top-five lists, dashboard navigation, ordinary queue filters, object/card authorization, or correctness-bearing table fingerprints across every dashboard/queue read. A chart implementation that regresses the predecessor page or ordinary registry behavior could still turn this test green. Add focused characterization of these material adjacent flows or map and execute existing tests that provide the exact witnesses.

13. **[MAJOR] The `shlz-ui` public-export claim is not substantiated.** Line 40 checks only two class-name substrings and absence of several runtime names in the local view/CSS. It cannot distinguish pinned public contracts from copied/private assets, and does not cover the required status/control/link/button/empty-state contracts. Add provenance/hash or other repository-standard public-export witnesses for every used contract and reject private imports/copies and alternative chart runtimes across all changed presentation sources.

14. **[MINOR] The retained browser evidence schema is not self-validating.** PHP line 36 checks only that screenshot `sha256` and viewport entries exist; it does not require path, byte size, file existence, non-empty bytes, or recompute the digest. Require a complete per-state/per-viewport evidence record and verify the retained file bytes against the recorded digest so later review can independently inspect the exact artifacts.

## Gate decision

The normative specification is coherent, bounded, read-only, explicit about authorization and fail-closed behavior, and uses appropriate public seams. The captured RED is correctly caused by absent production chart behavior. The submitted executable contract, however, contains one directly contradictory expected value and leaves major A–L requirements unexercised, especially boundedness, determinism/concurrency, complete bucket parity, authorization/rejections, activity boundaries, and browser zero/error evidence. Implementation must not begin from this Gate 2 candidate.

## Required changes

Correct the contradictory stage/drill-down expectation and revise the RED candidate to close every blocking and major gap above. Re-run the focused command to retain a fresh intended-RED record, regenerate the prepared reviewer package for the new exact source, and obtain a new independent Gate 3 review before executor handoff.

---

# Gate 3 re-review — 2026-09-21

- Reviewer: Codex, independent `gpt-5.6-sol`, reasoning `low`
- Reviewed exact source: `48cfed52384158524b4070bed302c9d25da4a64087eba0222a5ee53200b3aaa7`
- Reconstructible source: base commit `120fd2749f241c2e3dd14e42e1594ab14e199848` plus empty retained patch `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T082505Z-7cf1e91140/snapshot/source.patch`, patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T082505Z-7cf1e91140/package.json`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789979090704971000-630f3c2b47794b098d863e3f73690fe0.json`
- RED test blob: `44bac639491b3f9ad49276a6e4b6a37216d4e5e56132a7028caf0f7eb1fb948b`
- RED evidence: `INTENDED_RED`, exit `255`; first failure is now line 27, `INTENDED_RED fixed chart DTO`, because the predecessor owner does not return the chart DTO. The correction did not introduce a setup/environment failure before the missing behavior.
- Prior review disposition: all 14 findings were rechecked against the complete corrected candidate; dispositions are recorded below.
- Verdict: **CHANGES_REQUESTED**

## Current findings

1. **[BLOCKING] Concurrent determinism can still false-GREEN.** `tests/Yii2/yii2_operational_dashboard_bar_charts_001_test.php:45` starts concurrent GET and HEAD requests and asserts only `curl` exit `0` plus an unchanged fact fingerprint. It never reads the retained GET/HEAD output files, never proves the concurrent GET body equals the already asserted deterministic dashboard response, and never proves HEAD has the expected headers with an empty body. A race that returns a different but successful dashboard, a partial chart body, or a shifted cutoff will pass. Read the two outputs and assert the same stable GET semantics/body and exact HEAD semantics required by Acceptance A; retain the existing no-write fingerprint.

2. **[MAJOR] The all-bar accessible-name oracle remains too weak for Acceptance G.** At PHP line 36 every name is accepted if it matches only `/\:\s*\d+$/`; the browser helper checks only the first bar with similarly generic conditions. Therefore all 23 links could expose names such as `Столбец: 1`, omitting chart, series, or category, and the test would pass even though the contract requires chart/series/category/value. The exact href matrix and visible-text check do not prove the accessible name contains those semantic fields. Build independently expected accessible names (or independently expected required tokens) for all six stage, twelve weekly-series, and five activity bars, assert all 23 in PHP, and have the browser witness validate the rendered names rather than only recording 22 of them.

3. **[MAJOR] Atomic DTO shape/sum failure remains untested.** The correction materially improves Acceptance F/I with a missing mandatory activity table, but line 50 is still the only atomic failure injection. The normative contract separately requires a unified `503` when the owner/controller receives an invalid fixed shape or violated stage/activity sum invariant. An implementation that safely handles SQL failure but renders a malformed/partial DTO can pass. Add a public HTTP-seam fixture/factory injection or other repository-supported public oracle that supplies malformed shape and sum mismatch and proves the same atomic safe response with no chart/object disclosure or writes.

4. **[MINOR] Verification-input traceability still hides the browser executable dependency.** `openspec/changes/add-operational-dashboard-bar-charts/verification-input.json` continues to map all A–L to one acceptance and lists only the PHP wrapper in `tests`, although populated/empty/error 1440/390 evidence materially depends on `tests/Support/operational_dashboard_bar_charts_browser.cjs`. The package does include the helper as a source, so this is not independently blocking, but listing it in the acceptance mapping would make planner/reviewer traceability honest.

## Prior finding dispositions

1. **Resolved — contradictory stage count.** Lines 28, 32, 35–36 use one `stageExpected` for DTO, displayed-row projection, all six chart drill-downs, and total. The specification now correctly clarifies that chart stages are mutually exclusive displayed-status buckets, while ordinary `status=installation` retains the predecessor active-queue meaning. Line 37 checks that ordinary filter remains accepted without treating it as stage parity.

2. **Resolved — 30k boundedness.** Line 47 creates 30,000 additional objects and checks exact aggregate, fixed `6/6/5` DTO shape, bounded query count, bounded memory delta, 23 DOM marks, and absence of a scale-row sentinel from HTML.

3. **Partially resolved, still blocking — repeat/concurrent determinism.** Lines 38 and 45 now prove sequential owner equality, GET/HEAD no-write behavior, and concurrent process completion/no writes. Finding 1 above records the remaining missing concurrent-result comparison.

4. **Resolved — complete drill-down allowlist.** Lines 35–37 derive and exercise all 23 exact hrefs/totals, require unique rendered links, verify registry cutoff disclosure, and cover search/page composition.

5. **Resolved — rejection and authorization/read-only.** Lines 39–41 cover missing, unknown, conflicting, duplicate, and extra dimensions; guest safe return; forbidden dashboard and chart-filter requests; no disclosure; and fact fingerprints.

6. **Resolved — activity boundaries and evidence selection.** Lines 13–21 and 31 independently cover ages `7/8/14/15/30/31`, future accepted evidence exclusion, untrusted device time, revoked photo exclusion, correction/root maximum behavior, `never`, and completed exclusion through the expected totals.

7. **Resolved — browser states and activation.** Lines 43, 49–50 and the browser helper cover populated/empty/error at 1440/390, Enter activation, exact resulting href, focus visibility, clipping/overflow, state semantics, and retained screenshots.

8. **Partially resolved, still major — presentation/accessibility.** Desktop full-width/paired geometry, mobile flow, visible values, exact unique links, empty zeros, focus, and text/state semantics are now covered. Finding 2 above records the remaining all-bar accessible-name false-GREEN.

9. **Partially resolved, still major — atomic error/empty behavior.** Empty HTTP/browser behavior, all 23 zeros, safe SQL-source failure, no partial data, and read-only fingerprints are covered. Finding 3 above records the remaining explicit invalid-shape/sum path.

10. **Resolved — canonical stage ownership/parity.** Line 32 counts the canonical displayed labels across the full ordinary registry fixture and compares all six to the DTO. Lines 35–36 compare all six mutually exclusive chart drill-downs. This is the correct normative seam and does not redefine historical `status=installation`.

11. **Resolved — weekly characterization.** The fixture covers Monday/Sunday/Monday edges (`09-21`, `09-27`, `09-28`), an unknown date, out-of-window data, original-finish replacement by a confirmed transfer, six fixed week records, and every start/finish drill-down.

12. **Resolved — adjacent flows.** The focused test preserves ordinary `status=installation`, and the prepared plan explicitly selects the existing object-card and object-queue focused consumers plus the predecessor minimal-dashboard regression in CI. The current candidate does not replace those established oracles.

13. **Resolved — public `shlz-ui` provenance.** Line 52 pins hashes and exact public bytes for dashboard/chart widget, status, link, button, and empty-state styles, while rejecting private/dependency markers and alternate chart runtimes.

14. **Resolved — self-validating browser evidence.** Line 43 requires path, digest, byte size, viewport data and an existing non-empty file, then recomputes each screenshot SHA-256 for every mode and viewport.

## Re-review decision

The correction is substantial and closes the stage semantic inconsistency, boundedness, complete 23-bucket parity, rejection/RBAC/read-only coverage, activity boundaries, responsive state matrix, adjacent-flow mapping, public-export provenance, and evidence integrity. The fresh RED remains an intended missing-behavior failure. Gate 3 cannot advance while concurrent determinism and the mandated all-bar accessible-name semantics can false-GREEN, and malformed DTO/sum atomicity remains without an executable witness.

## Required changes after re-review

Assert the actual concurrent GET/HEAD results against the stable expected response, independently verify chart/series/category/value semantics in every rendered accessible name, and add a public-seam malformed DTO/sum-invariant atomic-error witness. Update the acceptance mapping to name the browser helper. Then retain a fresh intended-RED record and request another independent rereview of the corrected exact source.
