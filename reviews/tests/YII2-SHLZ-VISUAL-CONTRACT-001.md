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

---

# Gate 3 correction review — 2026-09-22

- Verdict: **CHANGES_REQUESTED**
- Reviewed candidate source digest: `7a94e6460300941ce5a7d398dbf509ce2136a2d096f7680903138d7d75f29fb5`
- Reviewed commit: `fcdf20ab5db24d312007ce480e616fa048872e6f`
- Reviewer role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T192901Z-bd6dac0de9/package.json`
- Delta: correction of the first Gate 3 return; no production implementation was reviewed or changed.

## Prior-finding disposition

1. **Partially fixed.** Acceptances are now split and real authenticated seams are reached. V02 has an isolated rendered-field RED, V03 has a GREEN 320×568 modal characterization, V04 has an authenticated native-modal RED, and V09 retains its isolated skip-link RED. The autoload guard proves fixture classes come from the exact worktree.
2. **Fixed.** The source test aggregates all ownership failures instead of stopping at the first one, and the remaining browser cases have separate acceptance IDs and records.
3. **Fixed for the reviewed delta.** Private `field()`/`choiceControl()` and JS-token assertions were removed. Interaction behavior moved to rendered browser seams; remaining source checks are limited to explicit public primitive/ownership constraints.
4. **Partially fixed.** Existing user-access history/authorization is GREEN and the OTIZ fixture independently guards several read, denial, and repeat facts. The newly added payment-cancel path asserts only visible ledger-row count and does not reach that assertion in the recorded RED.

## Remaining findings

### 1. Blocker — V07/V08 and the full A–G matrix still have no Gate 3 acceptance mapping

The refreshed `verification-input.json` maps V01/V02/V03/V04/V05/V06/V09, autoload coherence, and one user-access preservation journey. It has no acceptance for audit finding V07 (secondary forms outside the shared field contract) or V08 (local overlays/notifications), despite both remaining `REPRODUCED` in `docs/operations/yii2-shlz-visual-audit-inventory-2026-09-22.md`. It likewise supplies no executable Gate 3 case for C3 field-error association/value retention, D2 Tab/Shift+Tab trapping and background inertness, E1 offline pending/conflict/sent distinctions, or the complete per-view composition/exception inventory required by the normative contract.

Add scenario-level mapped tests or explicitly mapped existing characterization tests for V07/V08 and these remaining material A–G behaviors. The future inventory and final visual sweep may provide final evidence, but they do not replace pre-implementation Gate 3 expectations for known reproduced defects.

### 2. Major — payment cancel/no-new-fact RED is still masked and is not independently observed

The recorded `V04-V06-payment-modal-financial-layout` run stops at `otiz_shlz_ui_browser.mjs:44` when the native open dialog is absent. Therefore the subsequent Escape, focus-return, and `factsBeforeCancel` comparison are not observed as RED. More importantly, `factsBeforeCancel` is only a DOM row count, not the wrapper's independent persisted-fact fingerprint. A visual implementation could submit a command yet leave the current DOM unchanged and still satisfy that assertion after the earlier modal assertion becomes GREEN.

Split or aggregate the V04 checks so open/focus/Escape/focus-return/no-submit expectations are all demonstrated. Add a wrapper/browser synchronization point that compares the independent OTIZ fact fingerprint before and after cancel/Escape. Keep the existing no-JS payload and repeat/idempotency checks mapped as preservation evidence.

## Correction evidence accepted

- Source ownership aggregate RED: record `1790105284708498000-e35ab20521d44869961c18fe579adc1a`.
- V02 rendered installer-field RED: record `1790105284713620000-c0653316c5294c9a8d1fa00c524a58db`.
- V04 native payment-modal RED: record `1790105284728928000-9037189b30834e54aee2747bcaa3a0a5`.
- V09 skip-link RED: record `1790105284732323000-d71247047dda4b56afa98563306b1fa7`.
- Exact-worktree autoload GREEN: record `1790105284734340000-28c065e4c7534048ac913b24f1f2a182`.
- V03 authenticated mobile modal characterization GREEN: record `1790105284743143000-8bc6aec34f824e7999e23e0b2ce79fd6`.
- User-access authorization/history preservation GREEN: record `1790105284757403000-4427ed9de1854e9db26a79429a4936ce`.

The correction establishes deterministic fixture reachability and valid focused evidence for the listed cases, but Gate 3 remains blocked on complete known-finding traceability and independently observed payment cancellation safety.

---

# Gate 3 correction review 2 — 2026-09-22

- Verdict: **APPROVED**
- Reviewed candidate source digest: `72e2e1601edc708e2139ccf403882d87c06cf71ba0991e42e8a4a48d6e82f300`
- Reviewed commit: `495bbac9d25a0806823a982a1b9c4caba63e0468`
- Reviewer role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T193742Z-f0824516ca/package.json`
- Scope: third independent Gate 3 review of the complete corrected spec/test/RED matrix. Tests, specifications, and production code were not changed by the reviewer.

## Prior-blocker disposition

1. **Fixed.** V07/V08 now have an explicit acceptance mapping. `yii2_shlz_forms_overlays_inventory_001_test.php` aggregates independent RED findings for secondary Fields and checklist/inspection overlay ownership; the existing SHLZ Select inventory and authenticated inspection journey supply GREEN characterization of the retained public control and checklist interaction seams.
2. **Fixed.** The V04 browser case now aggregates native-modal, focus-entry, Escape-close, context, and focus-return observations instead of aborting at the first failure. It safely closes the legacy overlay after observation. The PHP fixture wrapper synchronizes with the browser and compares the full independent OTIZ persisted-fact fingerprint before and after Escape/cancel. The exact-source RED reports the three actual missing behaviors while focus return, context, synchronization, and no-new-fact checks complete successfully.

## Gate 3 assessment

- Traceability: V01–V09 and baseline/preservation controls are split into named acceptances with exact commands and expected RED/GREEN outcomes.
- Public seam: rendered authenticated installers, preopening selection, OTIZ payment, inspection/checklist, user access, and login shell seams are reached; source inventories are limited to explicit ownership constraints.
- Sensitivity and preservation: exact-worktree autoload, authorization/history, OTIZ denial/repeat behavior, and independently observed payment-cancel no-new-fact behavior are guarded.
- Expected-value independence: browser expectations use roles, visible content, geometry, focus, persisted facts, and fixed fixture values; source assertions target the contract's declared public primitive/ownership boundaries.
- Rejected cases: nested Fields, local common overlays/toast, ARIA-only payment modal, subject-owned shell geometry, irreversible financial ellipsis, and page-local table contracts have executable RED coverage.
- Determinism: all reviewed records bind exact candidate source `72e2e1601edc708e2139ccf403882d87c06cf71ba0991e42e8a4a48d6e82f300` and executable source `1c8c5ad760764facf49d9808056b1937e1c8d97142ba576c3fd00a6677787497`.
- A–G coverage: sufficient for Gate 3. The complete 25-view migration inventory, full responsive/zoom sweep, human screenshot comparison, focused GREEN matrix, final review, and exact-source CI remain later-gate obligations and are not claimed here.

## Evidence reviewed

- V01/V05/V06 aggregate source RED: `1790105791096560000-7c3648b424354902a16ff3b8b89079f2`.
- V02 authenticated rendered-field RED: `1790105791094092000-e539ffccb0cd40f3aa5e52cab88cd392`.
- V03 authenticated mobile selection characterization GREEN: `1790105791222513000-41daa0fcfbcc48548752d44a91dcb2d5`.
- V04/V06 authenticated payment/financial RED with completed no-fact handshake: `1790105791062962000-41f93bee17da443f8cfc59bf1f6bd0c5`.
- V07/V08 aggregate inventory RED: `1790105791115281000-a97bd7703eaf4d2699d73c760173fbbc`.
- V07 shared Select characterization GREEN: `1790105791105982000-4dccc380c0b9492c897670e94e6d1fff`.
- V08 authenticated inspection/checklist characterization GREEN: `1790105791062691000-9166ea7ee1504c53bf2ba194cf465db2`.
- V09 shell/skip-link RED: `1790105791109548000-3684192beb394f9882e192650b6d391c`.
- Exact-worktree autoload GREEN: `1790105791127707000-7cb80e9da3fe43888866af79621fc78e`.
- Authorization/history preservation GREEN: `1790105791127166000-b05daf6329e4451d96d497523861041e`.

No open Gate 3 findings remain for this exact source. Any change to specification, tests, expected outcomes, or bound scope requires refreshed exact-source review.
