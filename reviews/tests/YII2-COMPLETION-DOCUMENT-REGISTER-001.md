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
