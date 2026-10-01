# PR #286 continuation — superseding independent Gate 3 review

Verdict: **APPROVED**

Reviewer: `/root/pr286_gate3` (independent; authored no reviewed specifications, tests, or implementation)

This verdict supersedes the withdrawn/open review in `gate3-review.md` and covers the expanded runtime-CSP scope.

## Binding

- Full candidate base: `145cebd2b5a2691338554a30e45c60a623e02c5e` (`origin/main` at preparation)
- Published PR head / snapshot base: `97884878631303d46cb4cf31ab83231517e266e9`
- Exact source: `ec614317a1fe1d92dc8b104df3e317dff8c9862217b8a5d1cc7e26770b89eb93`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261001T063254Z-7cc564648a/package.json`
- Verification plan SHA-256: `3ee5f58be130961e30f1a857bf39526f910f74dc36eda4d1c9755f539ad68147`
- Canonical contract SHA-256: `21af975f52ae98c11bb9c2add6fced5f71500f1a13be846091f47f86ccbefab4`
- Restored private snapshot: `/Users/antropophag/.local/share/fmonitor-2/pr286-20261001/gate3-csp-worktree.bib39V`
- Snapshot patch SHA-256: `67df8049e4975eb492c9fe3bd3811523f2ef1c60864aa7609b15efd4ef57ddb4` (manifest, source patch, and restored staged diff agree)

## Findings

No findings.

The only delta since the prior complete review is the calculation-date locator correction and the appended CSP contract. The locator targets the same visible business input by textbox role and exact accessible name, avoiding the disabled native date input while preserving fill, blur, navigation, and all downstream assertions.

The CSP contract identifies the exact defect and a bounded correction: remove the 11 redundant inline `col` width declarations while retaining the table structure and the already-present external scoped selectors. `pilot.css` contains all required widths under `.fm2-otiz-v2-group-table--objects` and `--employees` (30/16/14/17/19/4 and 42/9/17/27/5). The contract explicitly preserves table order, grouping, totals, responsive behavior, and strict `style-src 'self'`; it forbids `unsafe-inline`, exceptions, and console filtering. No CSS change is required by the observed defect.

The existing production-runtime regression is an adequate executable seam. It uses the real disposable production HTTP/browser path, creates a nonempty calculation, downloads XLSX, accepts the calculation, and fails on any console error, page error, failed request, or HTTP error response. The exact RED reaches all business steps (`objectRows=1`, XLSX 41,247 bytes, accepted-payment action present) and fails only because the inline widths emit CSP console errors. Thus it cannot become GREEN by weakening the workflow or silently suppressing the violation without contradicting the contract and existing assertions.

The earlier review conclusions remain valid: authorization is still fail closed; money/source/no-write/audit assertions are retained; the workbook RED changes only the distribution-pool presentation and proves no mutation; and the architecture RED retains literal, concatenated, same-line, selector-contained, PHP, and DML negative SQL coverage without baseline or policy exemptions.

## Exact combined matrix

All seven harness records bind source `ec614317a1fe1d92dc8b104df3e317dff8c9862217b8a5d1cc7e26770b89eb93`:

- GREEN: `tests/Otiz/native_admission_001_test.php`
- INTENDED_RED: `tests/Otiz/settlement_v2_workbook_001_test.php` at the exact 8,500-vs-9,000 pool mapping
- INTENDED_RED: `tests/Runtime/production_runtime_browser_001_test.php` after successful create/download/accept, with only strict-CSP inline-style console errors
- INTENDED_RED: `tests/Verification/architecture_js_select_tokens_286_test.py` only for the three DOM/select false positives; negative SQL/DML cases pass
- GREEN: `tests/Yii2/yii2_calendar_003_test.php`
- GREEN: `tests/Yii2/yii2_inspection_planning_ui_255_test.php`
- GREEN: `tests/Yii2/yii2_main_navigation_001_test.php`

Snapshot restore, patch digest verification, `git diff --cached --check`, and JavaScript syntax checking are GREEN. No full local suite was run.

Gate 3 approves the expanded specification and regression boundary for the separate executor. It does not claim implementation, post-implementation GREEN, exact-source CI, final review, or merge readiness.


---

# PR #286 scanner amendment — superseding independent Gate 3 review

Verdict: **APPROVED**

Reviewer: `/root/pr286_gate3` (independent; authored no reviewed specifications, tests, or implementation)

This verdict supersedes `gate3-csp-review.md` for the amended scanner regression boundary. The previously reviewed candidate remains unchanged except for the root-authored scanner cases described here.

## Binding

- Full candidate base: `145cebd2b5a2691338554a30e45c60a623e02c5e` (`origin/main` at preparation)
- Published PR head / snapshot base: `97884878631303d46cb4cf31ab83231517e266e9`
- Exact source: `10e9cbeca5b301bd8808b9b53ab30efaeb23a1bcb73f55486bd1f5d8a6e4300d`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261001T064330Z-976475401c/package.json`
- Verification plan SHA-256: `27f22b2a7ae9d8f7e3346bfb21e2c7f27f71ce0eae81afc8abbc65e16d5e8abd`
- Canonical contract SHA-256: `21af975f52ae98c11bb9c2add6fced5f71500f1a13be846091f47f86ccbefab4`
- Scanner regression SHA-256: `56b36a2d981ebaf8c1ee7ad1154d6ea5eb065c17dba92dc497e67b57d20de329`
- Restored private snapshot: `/Users/antropophag/.local/share/fmonitor-2/pr286-20261001/gate3-scanner-worktree.MSj1vV`
- Snapshot patch SHA-256: `4d5cdd4b7b3c62ca9e386b65d0680ef07a9b63611e0898a83b928846e74f626f` (manifest, source patch, and restored staged diff agree)

## Findings

No findings in the test amendment.

Root defect R1 remains **OPEN pending implementation**: the current line-oriented JavaScript normalization can erase or split evidence so real lowercase SQL in multiline strings returns success. The amended regression correctly fails closed under the existing contract that all real SQL strings remain rejected; it creates no new exception or policy relaxation.

`test_multiline_sql_literals_remain_detected` covers four distinct false-negative shapes: a multiline template containing a complete query, a `select` template fragment concatenated with the remainder, an escaped multiline double-quoted literal, and `select.col FROM users` inside a template. These force detection across physical lines and prevent the identifier exemption from hiding quoted SQL-like content.

`test_comment_and_regex_quotes_do_not_hide_sql` covers lexer state around an apostrophe in a line comment, a quote in a block comment, and both quote characters inside a regex literal before a real lowercase SQL literal. The first two are already GREEN and constrain repair behavior; the regex case reproduces an additional current false negative. Together they require source-aware quote/comment/regex handling without weakening the existing DOM-selector allowances.

All prior negative coverage remains intact: uppercase literal SQL, concatenated `SELECT`, selector plus SQL on one line, SQL passed to a selector API, unquoted `select` adjacent to SQL, `UPDATE`/`INSERT`/`DELETE`, and PHP SQL still fail as required. The three legitimate DOM/select cases remain GREEN.

## Exact combined matrix

All seven harness records bind source `10e9cbeca5b301bd8808b9b53ab30efaeb23a1bcb73f55486bd1f5d8a6e4300d`:

- INTENDED_RED: `tests/Verification/architecture_js_select_tokens_286_test.py`; exactly five subcases fail (four multiline/fragment cases and one regex-quote case), while all established cases and both comment cases pass.
- GREEN: `tests/Otiz/native_admission_001_test.php`
- GREEN: `tests/Otiz/settlement_v2_workbook_001_test.php`
- GREEN: `tests/Runtime/production_runtime_browser_001_test.php`
- GREEN: `tests/Yii2/yii2_calendar_003_test.php`
- GREEN: `tests/Yii2/yii2_inspection_planning_ui_255_test.php`
- GREEN: `tests/Yii2/yii2_main_navigation_001_test.php`

Independent rerun of the scanner test reproduced the same five failures. Snapshot restore, patch digest verification, `git diff --cached --check`, and Python compilation are GREEN. No full local suite was run.

Gate 3 approves the root-authored scanner amendment for separate executor repair. It does not close R1 or claim post-repair GREEN, final review, exact-source CI, or merge readiness.
