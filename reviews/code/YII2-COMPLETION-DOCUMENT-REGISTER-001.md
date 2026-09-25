# YII2-COMPLETION-DOCUMENT-REGISTER-001 — independent Gate 5 review

- Date: 2026-09-25
- Reviewer: `/root/final_review` (independent; authored none of the candidate implementation, specification, or acceptance tests)
- Reviewed head: `b2fcf0a44902a93d861f6c8002d3ca024dfb5a84`
- Reviewed candidate source: `6a817b9a3d09bd4961c08a967ecc77cecfa084f8b0ff3a466077bab44e012f66`
- Base: `99bd0974150617a01e195cec28f7d886f1ede761`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T001131Z-6e91a7f341/package.json`
- Normative sources: issue #268, `specs/YII2-COMPLETION-DOCUMENT-REGISTER-001.md`, and the bound OpenSpec change
- Verdict: **REJECT**

## Spec findings

1. **BLOCKING — the retained acceptance still does not establish the required search and correction matrix.** `tests/Yii2/yii2_completion_document_register_001_test.php:65-76` proves a PTO date period and declaration-details/date inheritance for one object, but never proves search by an edited effective address or edited effective registration number rather than stale imported text. It also does not prove that a corrected PTO date changes the period result, queue order, displayed age, and stable tie-break. These are explicit issue #268 acceptance requirements, and a defect in any of the SQL expressions at `app/InstallationProcess/MariaDbYiiCompletionRegister.php:33-36,59-63` would escape the current suite.

2. **BLOCKING — the required object/document-state coverage and append-only-history witness remain incomplete.** The HTTP fixture covers unopened/no-facts, PTO-only, declaration-without-PTO, and then both facts, but it does not distinguish an opened case below 85%, a completed case, or multiple lifts at one address. It creates corrections directly in storage and never records or compares the pre/post history through the existing correction workflow. Therefore it does not prove that rows remain case/object identities rather than address aggregates, that completed history remains searchable, that target-form constraints survive, or that the existing history is preserved after corrections.

3. **BLOCKING — bounded-query behavior is asserted by source tokens and row counts, not measured.** The implementation performs a global history-integrity query, four `COUNT` queries, and a page query with correlated correction subqueries (`MariaDbYiiCompletionRegister.php:29-37,46-63`). The 55-row fixture proves `COUNT` before `LIMIT`, but no retained query-count/shape/EXPLAIN witness establishes the issue's representative bounded-query requirement or rules out growth from history depth. No index change is requested here; the missing deliverable is the required measurement/evidence.

4. **BLOCKING — exact-source CI is not available.** The bound package and harness state report GitHub/CI as `UNKNOWN` for the reviewed source, while the CRITICAL plan contains 331 CI obligations. Focused local GREEN cannot substitute for the required one exact-source CI matrix, and `UNKNOWN` is not GREEN under the repository constitution.

## Standards findings

No separate hard architecture/security violation was found in the reviewed diff: the change uses a dedicated read adapter, exact `objects.read` admission, GET/HEAD-only controller behavior, the existing completion route/forms, parameter binding, output escaping, and no new writer or schema. The current production card scope is globally capability-based, matching the explicit owner resolution in the OpenSpec; a future narrower per-object scope remains an acknowledged follow-up rather than a claim of present enforcement.

As maintainability concerns, `MariaDbYiiCompletionRegister.php:21-70` combines filter validation, authorization, history validation, five aggregate/page reads, projection, ordering, and mapping in densely compressed statements, while `Views/completion-register.php:8-19` compresses the complete page into long lines. This makes audit-sensitive SQL and escaping harder to review, but it is not the basis of this rejection. A suggested concern about nullable correction dates is not applicable: the canonical correction schema and writer require `fact_date`; only declaration `details` is sparse, and that inheritance is implemented and exercised.

## Evidence reviewed

- `php tests/Yii2/yii2_completion_document_register_001_test.php` — GREEN on the reviewed checkout.
- `php tests/Yii2/yii2_completion_document_register_browser_001_test.php` — GREEN on the reviewed checkout.
- PHP syntax checks for the adapter, controller, and view — GREEN.
- Retained desktop and 390 px screenshots were inspected. They show the Yii shell, filters, counters, direct `#completion` journey, post-write queue removal, and searchable saved declaration. The browser test does not exercise correction history, error presentation, keyboard/focus behavior, multiple pages, or explicit before/after counter assertions.
- The implementation distinguishes empty results (200 copy), source/history failure (503), and an inconsistent declaration-without-PTO row. GET/HEAD no-write is checked against completion facts, but jobs/outbox and correction/history bytes are not included in that witness.

## Required return

Add focused public-seam acceptance for effective edited address/regnumber, completed and same-address multi-lift cases, below-threshold target-form enforcement, PTO correction effects, and immutable history. Retain a deterministic query-budget/shape measurement on representative page/history depth. Then refresh the exact-source plan/package, run the selected exact-source CI once, inventory every failure if any, and request a new independent Gate 5 review. Full local `make test` / `make verify` remains prohibited.

---

## Remediation rereview — final code decision

- Date: 2026-09-25
- Reviewed head: `ff4276c2e919ac848fe36bdd39d8214363a8f8c1`
- Reviewed candidate source: `a1086a9f67e7e1db64c6fcc30bc09a379b4ace264cbc3b0609c91c7724c3fa92`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T002730Z-4c151f2e44/package.json`
- Superseding verdict: **APPROVE (CODE); EXACT-SOURCE CI PENDING**

### Prior findings disposition

1. **RESOLVED.** Public HTTP acceptance now distinguishes effective edited address and registration number from stale imported values, checks declaration details, corrected PTO date/period/age, effective-PTO ordering with stable object-identity tie-breaking, literal wildcard escaping, retained pagination filters, and filter-submit page reset.

2. **RESOLVED.** The representative fixture covers unopened/no-facts, PTO-only, both facts, inconsistent declaration-without-PTO, completed work, and separate lifts at one address. Existing documentary and form-recovery suites remain the authoritative executable coverage for the 85% gate, existing commands, append-only history and recovery. The register consumes those facts without adding a writer.

3. **RESOLVED.** The 55-row, multi-page acceptance now asserts a constant eight-command adapter budget: authorization, duplicate-root and lineage integrity checks, four global mode counts, and one limited page query. SQL inspection confirms server-side predicates/counts before `LIMIT` and no per-row card/history reader.

4. **RESOLVED FOR CODE REVIEW.** A disposable constraint-disabled fixture now injects malformed correction lineage and proves the public GET returns `503` without facts/history mutation. Honest empty state and inconsistent fact state remain separately covered. The source-unavailable case was covered by the earlier test revision; replacing it with the more discriminating lineage case does not alter the controller's inspected catch-to-`503` behavior.

5. **RESOLVED.** Updated desktop/narrow browser evidence proves the direct `#completion` journey through the existing declaration form, Back-preserved URL, refreshed search/counters, local table scrolling, reachable row action above fixed navigation, and a keyboard-focus target. The page uses a local asset and does not modify shared CSS.

### Rerun evidence

- `php tests/Yii2/yii2_completion_document_register_001_test.php` — GREEN at exact `ff4276c2`.
- `php tests/Yii2/yii2_completion_document_register_browser_001_test.php` — GREEN during this rereview sequence after the browser/CSS remediation.
- `php tests/Yii2/yii2_documentary_http_001_test.php` — GREEN.
- `php tests/Yii2/yii2_completion_form_recovery_001_test.php` — GREEN.
- `git diff --check` and changed PHP syntax checks — GREEN.
- Updated desktop and narrow screenshots inspected; the narrow action and focus ring are visible without fixed-navigation overlap.

### Remaining limitation

The implementation/specification/test candidate is approved for Gate 5 code review. This is **not yet publication or merge approval**: exact-source GitHub CI for source `a1086a9f67e7e1db64c6fcc30bc09a379b4ace264cbc3b0609c91c7724c3fa92` remains `UNKNOWN` in the bound state. The selected CI run must complete GREEN on this exact source, or every failure must be inventoried and resolved before PR-ready may be claimed. No merge or deployment is authorized by this review.

---

## CI-regression correction rereview

- Date: 2026-09-25
- Reviewed head: `148ad755284505bf617b0edac19c7df27f070423`
- Reviewed candidate source: `db7df7688cb681f74f63159306ce2c73342fb1d9dff8bf9f196860941e17a293`
- Correction under review: `tests/Yii2/yii2_main_navigation_001_test.php` plus its verification-input registration; production implementation is unchanged from the approved candidate
- Verdict: **APPROVE**

The correction preserves the navigation test's exact membership, order, group hierarchy, active state, permission filtering, pinned icon geometry, repeated-read and no-write assertions. It adds `/pilot/completion-register` only where the production navigation already exposes it under `objects.read`, and pins the existing `folder-file-open` asset digest. It does not weaken or remove any previous route, label, permission, icon, or mutation assertion.

The exact selected profile command
`tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php`
completed GREEN on head `148ad755`; its structured result reports exit `0` and exact git SHA. Direct host execution returned the expected environment-only `503` because it does not own the isolated MariaDB profile; that is not a product/test regression and is why the planner selects the profile wrapper. `git diff --check` and PHP syntax validation are GREEN. The reported registered-focused-bootstrap rerun is also GREEN after the navigation inventory correction.

This test-only correction is approved and the prior Gate 5 code approval remains valid. A new exact-source CI result for `db7df7688cb681f74f63159306ce2c73342fb1d9dff8bf9f196860941e17a293` is still required before publication/merge readiness; the earlier failed run cannot be relabelled GREEN.
