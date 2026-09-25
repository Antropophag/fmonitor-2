# YII2-COMPLETION-DOCUMENT-REGISTER-001 — independent Gate 3 review

- Date: 2026-09-25
- Reviewer: `/root/gate3_review` (independent; authored none of the reviewed specification, tests, or implementation)
- Reviewed head: `5f35e293cd3342ff8983654e54a1fe56b7beabc2`
- Reviewed candidate source: `54ded09e6b48f69c079ee39ae8380ee90f5edb6d6074fdf82c4f5041a7a876e4`
- Base: `99bd0974150617a01e195cec28f7d886f1ede761`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T235253Z-146fa66ac4/package.json`
- Normative sources: issue #268, `specs/YII2-COMPLETION-DOCUMENT-REGISTER-001.md`, and the bound OpenSpec change
- Verdict: **REJECT**

## Findings

1. **BLOCKING — the executable acceptance does not cover the specified document-state matrix or correction semantics.** `tests/Yii2/yii2_completion_document_register_001_test.php:23-45` seeds only one PTO root, performs one queue GET and one HEAD, then checks a capability denial. It never creates the required unopened/no-fact, opened/no-PTO, PTO-only, both-documents, completed, same-address/multiple-lift, or invalid declaration-without-PTO cases. It also never creates a correction. Consequently it cannot detect loss of inherited declaration details after a date-only correction, wrong effective dates, broken ordering after PTO correction, aggregation by address, or an invalid fact combination being presented as success. Add independently asserted HTTP scenarios for the complete issue matrix, including history bytes remaining unchanged.

2. **BLOCKING — search, period, count, pagination, and URL semantics are almost entirely untested.** The sole query uses `q=TEST`, but merely finds the fixture's default object and does not distinguish effective address, effective registration number, declaration details, or stale imported values. There are no assertions for explicit PTO/declaration periods, exclusion of rows missing the selected date, all four modes, default old-PTO-first ordering, stable tie-breaking, more than 50 rows, global counters versus page size, page bounds, escaped wildcard search, filter-submit page reset, reload, pagination links, or Back preservation. Source-token checks for `LIMIT` and `COUNT` at lines 16-17 do not prove server-side behavior and can pass with incorrect SQL. Exercise these behaviors through the HTTP seam on a representative multi-page fixture.

3. **BLOCKING — authorization scope is asserted only as a global capability, so the test permits cross-object disclosure.** Deleting `objects.read` at lines 42-43 proves all-or-nothing denial only. Neither the test nor the adapter supplies an actor/object scope to the register read; `MariaDbYiiCompletionRegister::read()` queries every matching installation case. This does not substantiate the contract that the same accessible-card scope governs rows, search, and every counter, and a limited actor's foreign object would currently be returned if such a scope exists. Define the canonical existing scope in the specification, pass the actor/scope into the read seam, and test that foreign identities, matching search text, and counter deltas remain undisclosed. If the product truly has no narrower current scope, the contract and issue's limited-scope acceptance need an explicit owner resolution rather than an unexercised promise.

4. **BLOCKING — corruption and source-failure behavior is not exercised, and the implementation has an uncovered invalid-state success path.** The issue requires empty results, unavailable source, and damaged history to remain distinct. The test covers none of them. In particular, the SQL accepts a declaration root without a PTO root and classifies it under `without_pto`; issue #268 requires data outside the current rules to surface as a separate limitation/error, not a successful document state. Add missing/drifted-table and malformed lineage cases, duplicate/invalid document-state cases allowed by the fixture, exact `503` evidence, and a genuine successful empty state whose copy does not claim physical document absence. Assert zero facts/jobs/outbox mutations for each GET/HEAD failure and success.

5. **BLOCKING — the public workflow is not tested.** The acceptance requirement is queue → filtered direct `#completion` link → existing declaration command → return/reload → moved row and corrected counters. The test only searches the returned HTML for a link. It does not prove that the target section is visible, that existing command permissions and 85%/PTO prerequisites remain enforced, that the existing recovery/history path is preserved, or that the refreshed register observes the new fact without any register-owned writer. Add one end-to-end HTTP/browser journey using the existing form command and explicit before/after counters and immutable history.

6. **BLOCKING — no browser or bounded-query evidence exists for the required UI and performance acceptance.** The test is registered as `e2e` but starts only the PHP fixture and performs textual HTTP assertions. It does not inspect desktop/narrow layout, focus, usable controls, contained table scrolling, empty/error presentation, Back navigation, or screenshots. It also neither measures query count/shape nor proves that one page does not invoke the full card reader or materialize all histories. Add the required real-browser desktop/narrow sweep and a deterministic query-budget/shape witness on a representative multi-page dataset.

## Evidence and assessment

`php tests/Yii2/yii2_completion_document_register_001_test.php` is GREEN at the reviewed head. That result proves the narrow smoke path only; it is not sufficient evidence for the normative acceptance matrix and, because of the gaps above, would allow plausible regressions and current SQL defects to pass.

The OpenSpec requirements state the main intended behaviors coherently at a high level, but the five-line stable contract delegates all detail and the executable owner does not make those requirements observable. No retained complete-matrix RED evidence was provided in the bound package. Gate 3 therefore cannot authorize Gate 4 or publication from this source.

## Required return

Root must expand the specification where object-scope and invalid fact combinations remain ambiguous, replace source-token assertions with complete public-seam acceptance, retain fresh intended RED evidence, refresh the verification plan/package, and request a new independent Gate 3 review before implementation corrections proceed. Full local `make test`/`make verify` remains prohibited.

---

## Gate 3 rereview — `b2fcf0a44902a93d861f6c8002d3ca024dfb5a84`

- Date: 2026-09-25
- Reviewer independence: unchanged
- Reviewed correction range: `61168843420f32a0c55259125260054dc9013071..b2fcf0a44902a93d861f6c8002d3ca024dfb5a84`
- Reported candidate source: `6a817b9a...`
- Bounded reruns: `php tests/Yii2/yii2_completion_document_register_001_test.php` — GREEN; `php tests/Yii2/yii2_completion_document_register_browser_001_test.php` — exit 0
- Browser artifacts inspected: `/Users/antropophag/.local/share/fmonitor-2/issue-268/browser-evidence/completion-register-desktop.png` and `completion-register-narrow.png`
- Verdict: **REJECT**

### Prior findings disposition

1. **PARTIALLY RESOLVED.** The corrected HTTP fixture now distinguishes unopened/no-fact, PTO-only, both-documents, declaration-without-PTO and a multi-page set; it exercises a two-version sparse declaration correction and confirms inherited current details/date. The implementation now explicitly labels declaration-without-PTO as inconsistent. However, the promised fixture matrix still has no completed case or two lifts at one address, and the correction assertions do not prove prior correction/history rows remain byte-for-byte intact. These omissions overlap the still-open search/identity and corruption findings below.

2. **PARTIALLY RESOLVED — BLOCKING.** The test now proves a 57-row global total, 50/7 pagination split, retained page link, one PTO-date period, effective declaration details/date search and queue removal. It still does not test effective corrected address or registration-number search against stale imported text, default old-PTO-first ordering or stable identity ties, declaration-period exclusion of missing dates, filtered per-mode counter values, escaped `%`/`_` search, or form-submit page reset. These are explicit issue #268 acceptance points and are especially important because a syntactically valid SQL implementation can pass all current assertions while ordering or counting against the wrong effective source. Add discriminating public-seam assertions rather than source tokens.

3. **RESOLVED.** The contract now records the current platform fact that ordinary card scope is globally granted by exact `objects.read` and that no narrower per-object seam exists. The adapter repeats active-user/active-role/exact-capability admission internally, while the controller retains its own check. The HTTP test proves denied access. A future narrower scope is correctly treated as a separate security change rather than invented in this slice.

4. **PARTIALLY RESOLVED — BLOCKING.** Empty results, missing correction table, and declaration-without-PTO are now distinct; missing source returns `503`. Production also adds duplicate-root detection. But no executable test constructs malformed correction linkage/gaps and proves `503` rather than a successful effective document. The missing-table case cannot catch a regression that stops validating `previous_correction_id` / `previous_version_no`, and the duplicate-root branch is unexecuted. Add a fixture that deliberately disables the relevant constraint only within the disposable database, creates malformed lineage (and, if the production branch remains, duplicate roots), asserts unavailable response, and proves no writes.

5. **RESOLVED.** The browser test now follows the direct `#completion` link, verifies the target and existing declaration form are visible, submits through the existing writer, navigates Back to the exact filtered URL, observes the object leave the queue, and verifies exactly one declaration root was appended. No register writer was introduced.

6. **PARTIALLY RESOLVED — BLOCKING.** A 55-row fixture exercises page bounding without per-row card reads, and the browser test covers desktop/narrow routes plus local table overflow. The submitted narrow screenshot is not acceptable evidence of a working narrow UI: the fixed bottom navigation visibly overlays the lower form controls (the primary blue action is reduced to a strip behind it), while table row text is densely overprinted/clipped. The test only checks document-wide overflow and `overflow-x`; it never verifies that the submit/reset controls and row action can be scrolled into an unobscured clickable region, keyboard focus visibility, or that columns do not overlap. The desktop screenshot is an empty post-return state, so it also does not visually evidence a populated desktop table. Add assertions based on bounding boxes/hit targets and focus, capture populated desktop and usable narrow states, and correct the local view/layout consequence without rewriting shared CSS.

### Rereview decision

The correction materially improves the candidate and fully closes access ownership and the core existing-form round trip. Findings 2, 4, and 6 remain blocking, with concrete untested SQL semantics and a visible narrow-layout failure. Gate 3 remains **REJECTED**; exact-source CI and publication cannot substitute for the missing acceptance barriers.

---

## Gate 3 second remediation rereview — `f44f4193f29aea35e7610ad4f9b63074dc2c8af5`

- Date: 2026-09-25
- Reviewer independence: unchanged
- Reviewed correction range: `74fc45fcfcd5dfd132e264d2889543e9636502ca..f44f4193f29aea35e7610ad4f9b63074dc2c8af5`
- Focused rerun: `php tests/Yii2/yii2_completion_document_register_001_test.php` — GREEN
- Updated browser artifacts inspected at `/Users/antropophag/.local/share/fmonitor-2/issue-268/browser-evidence/`
- Verdict: **REJECT**

### Remaining-findings disposition

1. **RESOLVED.** The fixture now includes a completed case and two distinct lift identities at one address. Sparse declaration details and corrected effective dates are exercised. The existing documentary acceptance remains the writer/history owner and proves the 85% gate plus append-only correction lineage; the register test correctly remains a consumer rather than duplicating those commands.

2. **RESOLVED.** Current HTTP acceptance discriminates effective edited address and registration number from stale imported text, checks corrected PTO date/age/period, default effective-PTO plus object-identity ordering, literal wildcard escaping, multi-page totals, retained pagination filters and filter-submit page reset. Together with effective declaration details/date search and empty/date filtering from the prior correction, this is now sensitive to the required server-side SQL semantics.

4. **PARTIALLY RESOLVED — SOLE BLOCKER.** Missing source, honest empty state, inconsistent declaration-without-PTO and duplicate-root production detection are present. The required damaged correction-history outcome is still not executable. No test creates a gap or wrong `previous_correction_id` / `previous_version_no` and requires the register HTTP seam to return `503`. Dropping the whole corrections table only tests schema/source unavailability; it would remain GREEN if `assertHistoryIsConsistent()` stopped checking lineage entirely. Add one disposable-fixture corruption case by temporarily removing/disabling the relevant constraint, insert malformed lineage, restore cleanup in `finally`, assert exact unavailable response and zero mutations. If duplicate-root detection remains production behavior, either exercise it similarly or remove the unreachable branch; the normative lineage case is mandatory.

6. **RESOLVED.** The adapter is held to a constant eight-command budget over the representative 55-row dataset. The local asset gives the narrow page bottom clearance and a bounded horizontally scrollable table. Updated browser acceptance scrolls the table to the row action, checks its bounding box remains above fixed navigation, focuses the search control, and the inspected narrow screenshot shows an unobscured action and visible focus ring. The view remains usable without shared CSS changes.

### Decision

All prior blockers except corrupted-lineage sensitivity are closed. Because issue #268 explicitly requires damaged history to differ from empty/source-success and the implementation owns nontrivial lineage SQL, that missing public-seam barrier remains Gate 3-blocking. Once the single malformed-lineage fixture is added and GREEN (with fresh bound package/source), no other Gate 3 finding from this review remains.
