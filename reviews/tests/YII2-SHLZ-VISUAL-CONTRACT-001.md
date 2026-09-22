# Gate 3 review — YII2-SHLZ-VISUAL-CONTRACT-001

- Verdict: **CHANGES_REQUESTED**
- Reviewed candidate source digest: `3357f4da7b7e93593102198396ad88eba1e897772e03bd9093a77c47f30fc335`
- Reviewed commit: `92c6790d8a748949e3e5950fb91a25929953e0d4`
- Reviewer role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T190157Z-bc3fa138a6/package.json`
- Scope: independent Gate 3 review of specification, tests, and recorded RED only. Production code, tests, and specifications were not changed.

## Findings

### 1. Blocker — the executable acceptance does not cover the claimed A–G public seam

`tests/Support/yii2_shlz_visual_contract_browser.cjs:18-50` visits only unauthenticated `/pilot/login`. It never logs in despite receiving credentials, never visits the representative authenticated routes or the 25-view inventory, and never exercises a table, field error, selection dialog, payment dialog, object card, checklist/offline state, pagination, sidebar state, permission boundary, or state-changing/no-JS fallback. Consequently it cannot detect the required V03/V04/V06 behavior or establish B–F preservation. It also omits 760, 761, and 769 breakpoint probes, 200% browser zoom/reflow, touch input, expanded/collapsed sidebar, short-height overlay behavior, long Russian data, focus trap/Shift+Tab/Escape/cancel/focus return, background inertness, local table overflow, and complete accessible money/action values required by the contract.

The source test (`tests/Yii2/yii2_shlz_visual_contract_001_test.php:8-56`) proves that 25 files exist, then samples structural strings in nine table views and a few assets. File existence is not an executable inventory of the compositions, exceptions, and evidence required for every view. Add focused browser/HTTP/characterization cases at the actual public routes for every normative group A–G, including the explicitly required V02/V03/V04/V06/V09 cases and complete inventory mapping. The pre-existing preopening and OTIZ journeys that fail earlier on `origin/main` need not be claimed as RED, but they do not replace these new isolated cases.

### 2. Blocker — recorded RED demonstrates only two early failures, not the acceptance matrix

Both tests are fail-fast. The source RED stops at `yii2_shlz_visual_contract_001_test.php:25` (`full Field owns one explicit API`), so none of the later field, modal, table, financial-value, shell-ownership, or touch-target assertions were observed. The browser RED stops at `yii2_shlz_visual_contract_browser.cjs:47` (`one shared skip link`) after the width loop; it does not demonstrate any overlay or authenticated-flow failure. Labeling both records with the single acceptance `A-G-complete-visual-contract` overstates the evidence.

Split the acceptance into independently runnable cases (or aggregate failures without stopping at the first assertion), map each to a requirement/scenario, and retain distinct intended RED proving each missing behavior before implementation. Gate 3 requires traceable RED for the complete bounded slice, not one sentinel failure per large wrapper.

### 3. Major — several expectations are implementation-coupled rather than independently derived

`yii2_shlz_visual_contract_001_test.php:25-26` requires PHP methods literally named `field` and `choiceControl`; lines 35-36 require one exact dialog class/markup order and forbid one exact selector; lines 41-43 infer a complete modal lifecycle from the mere presence of `showModal()`, `.close()`, `keydown`, and `Escape`; lines 47-50 require literal class strings in each PHP source file. The normative contract specifies observable field, table, modal, and focus behavior, not these private method names or token placement. These checks can reject a conforming implementation and can pass a broken implementation containing dead/unrelated strings.

Move primary expectations to rendered HTML and browser interaction at the public seam. Keep source checks only for explicit ownership/rejected-pattern rules, and make each such architectural constraint normative before binding to a private identifier.

### 4. Major — preservation and rejected cases lack executable guards

The contract rejects changes to routes, query/filter/page state, roles, permissions, payloads, CSRF, idempotency/concurrency, formulas, dates, statuses, snapshots, append-only histories, offline storage/protocol, and no-JS fallbacks. Apart from comparing the fixture-wide fact snapshot around read-only login screenshots, the candidate contains no characterization assertions for these boundaries. It also does not prove cancel/Escape produce no fact, repeat confirmation produces at most one fact, server denial remains enforced, or the existing payment payload/no-JS form remains intact.

Add bounded characterization coverage before production edits, with independently constructed expected requests/results and explicit no-new-fact assertions for rejected/cancel/repeat cases. This is especially necessary for D4 and F1–F4; a read-only fixture snapshot cannot protect write-path semantics that are never invoked.

## Evidence assessment

- Traceability: incomplete; one aggregate acceptance maps two wrappers to all A–G without scenario-level coverage.
- Public seam: only login rendering/keyboard navigation is exercised; authenticated routes and interactions are absent.
- Sensitivity: financial confirmation, permissions, offline/replay, and append-only preservation are specified but unguarded.
- Expected-value independence: insufficient in structural checks due to exact private names/tokens; browser width expectations are independently enumerated but incomplete.
- Rejected cases: mostly prose only; no executable cancel, duplicate, denial, no-JS, or preservation evidence.
- Determinism: fixed fixture and viewport widths are positive, but coverage is too narrow; screenshots are generated from the candidate as `shlz-before-*` and no recorded human review establishes an independent baseline.
- A–G completeness: not achieved.

## RED evidence reviewed

- Source contract record `1790103685479585000-162bba69b2b24118bf967dcc278d4a5b`: exact-source `INTENDED_RED`, exit 255, first failure at source-test line 25.
- Browser contract record `1790103697641622000-71ceaffa0d4f4f4caaf814e4760c21ee`: exact-source `INTENDED_RED`, exit 255, failure at browser-script line 47 on the login skip link.

These are valid RED observations for those two narrow defects. They are not sufficient RED evidence for `A-G-complete-visual-contract`.
