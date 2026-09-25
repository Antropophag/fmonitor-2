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
