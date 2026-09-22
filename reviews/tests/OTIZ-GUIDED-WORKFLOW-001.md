# Gate 3 test review: OTIZ-GUIDED-WORKFLOW-001

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); authored neither specification nor tests and made no production/spec/test/OpenSpec changes.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T181518Z-d409d5f0a7/package.json`.
- Base: `c8bfc42d774bbe52a6528eb44a371c8a9003e6d3`.
- Exact candidate source: `0eb901a756fd5a16f3bc14ff8617e4af2a56b7a908d3afe8c5e9b56325787f6f`.
- Contract: `specs/OTIZ-GUIDED-WORKFLOW-001.md`.
- Reviewed mapped test: `tests/Yii2/yii2_otiz_shlz_ui_001_test.php` with `tests/Yii2/otiz_shlz_ui_browser.mjs`.
- Intended RED: record `1790014492555456000-7629eccaa78748d3a42860bc9c0459a1.json`, exit 255 at `otiz_shlz_ui_browser.mjs:28`; actual navigation has four links and the old label, while the contract requires exactly three modes and `Выполнение расчёта`. The failure reaches the authenticated Yii/Chromium public seam and is an intended missing-behavior failure, not setup failure.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **HIGH — the single A1–A6 mapping does not execute the required successful guided journey.** `openspec/changes/otiz-guided-native-interface/verification-input.json:28-36` maps the complete contract only to this browser test, but `tests/Yii2/otiz_shlz_ui_browser.mjs:17-145` starts from manually inserted snapshots and never performs successful `POST /calculate`, persisted-draft continuation after reload/back, successful warning-only accept, real XLSX response validation, payment-confirmation cancel, successful `payments/complete`, or its replay/no-change outcome. The only accept submission is deliberately rejected as `?error=incomplete` (`:141-142`). Consequently an implementation can render the new shell while breaking every state-changing stage and the real download seam and still pass. Add one isolated end-to-end public-seam journey using the existing commands, with exact redirects/ids/statuses, an XLSX signature/content-disposition assertion, before/after facts for cancel and rejected/replayed cases, and ledger evidence after one successful positive payment registration. Map focused owner regressions as companion evidence rather than treating one presentation test as complete financial-command coverage.

2. **HIGH — payment truthfulness and ledger semantics are only tested by control presence, not by the required states and meanings.** The contract requires separate saved status/saved amounts/current availability/ledger labels; a preview saying FMonitor only records an external payment; neutral zero/no-change/replay/error states; and discipline/reversal rows that retain the source link and original history. At `otiz_shlz_ui_browser.mjs:32-47`, the test checks two header labels and merely counts payment, discipline, reversal, and export controls. The fixture has a zero-paid discipline row, but no assertion proves it is labelled as withholding rather than payment; no `reverses_payment_closure_id` row is seeded or checked; no zero-available accepted snapshot is inspected; and no payment preview/cancel/no-change/error copy is asserted. Add independently seeded positive payment, zero-available, discipline, and linked reversal rows, bind each amount/status/label to its semantic region, assert the original row remains, and exercise preview/cancel/success/replay/no-change without accepting a second “payment completed” claim.

3. **HIGH — historical/detail immutability and interaction acceptance are incomplete.** `otiz_shlz_ui_browser.mjs:48` checks only Enter/Escape for one accepted-object drawer; `:98-99` checks only that same-date ids `#504/#505` coexist. It does not prove row/drawer parity from economy, issues, and archive; saved operands/sources/allocations/trace; distinct same-date snapshot contents; resistance to later source changes; current-vs-saved labelling; close-button/backdrop behavior; focus return for each trigger; or the no-JS fallback detail. A drawer can show latest/global data for every id and still pass. Seed two same-date snapshots with deliberately different object/allocation/trace values, mutate the live source after seeding, and assert exact snapshot-specific values through each entry point and fallback. Exercise close button, backdrop, Escape, keyboard opening, focus restoration, and list query/back context.

4. **MEDIUM — preparation/date/error sensitivity is missing despite explicit contract cases.** The test never distinguishes calculation date, creation time, payment date, and unknown source date; never submits an invalid/stale preparation as an authorized actor; never proves repeated operation id returns the same snapshot; and never checks that an unknown date stays unknown. The current forbidden-malformed check (`otiz_shlz_ui_browser.mjs:135-137`) correctly proves authorization precedence and no facts, but cannot cover authorized validation, idempotency, or date presentation. Add fixed literal fixtures and public requests for valid prepare, same-operation replay, invalid date/operation id, stale refusal, and absent source date, with exact no-fact/idempotency assertions.

## Review assessment

The existing test has strong isolation (private fixture database and artifacts), fixed expected monetary literals, a broad fact fingerprint for read/denial/rejected-repeat paths, and useful deterministic responsive/accessibility assertions. Expected values for the exercised display cases are independent of production computation. The source-bound RED is valid and intentionally early. Those strengths do not close the missing successful workflow, truthful payment-state matrix, or historical/detail immutability required by the canonical verification contract.

## Required correction

- Close all four findings with root-authored tests and complete acceptance mapping.
- Capture fresh exact-source intended RED and any mapped companion evidence.
- Prepare a new reviewer package before executor implementation.

---

## Correction round 1 — 2026-09-21

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); still independent of specification and test authorship.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T182318Z-7b3dbf6212/package.json`.
- Exact candidate source: `78555db0ec693982bce580bcec44b67a0caec020ea2c8ec48b7065737de8b82c`; executable source `b655a244af90f948550589b2a87e3cf922016479b03eaab146b34820b070fb98`.
- Verification plan: SHA-256 `90b8730983eef974e9d31fce5b5a60e46c8d8d3c44f4de26eba56af1aa745ab6`; lane `CRITICAL`; required reviews `gate3`, `final`; `missing_tests=[]`.
- Exact-source evidence: UI intended RED `1790014960796572000-497529a21ed44481a60045f45598deaf.json`; publication-browser GREEN `1790014923666769000-8ae44c14bde245fab2c5f02e36b23c95.json`; settlement GREEN `1790014923656902000-9295328ee9334d1b8f15a06d0681f38f.json`.
- Current verdict: `CHANGES_REQUESTED`.

### Prior finding disposition

1. **Prior HIGH (successful guided journey): RESOLVED.** The acceptance mapping now includes `yii2_otiz_publication_browser_001_test.php`. Its real Yii/Chromium flow prepares a snapshot, simulates response loss, restores the same operation id/date, replays to the same saved snapshot, accepts it, downloads an XLSX with exact HTTP headers/ZIP signature/content, records discipline and payment, and appends a reversal. The PHP assertions independently verify one snapshot/publication, exact events and exact monetary result. This closes the earlier absence of a successful public-seam journey.

2. **Prior HIGH (payment truth and ledger semantics): PARTIALLY RESOLVED.** The mapped settlement GREEN proves positive completion, durable `no_change`, malformed/forbidden no-fact cases, discipline as a zero-paid row, exact linked reversal, and preservation of the original row. The UI correction also adds an accepted zero-available snapshot with neutral `Нет новой суммы` and no completion form. It still does not test the contract's payment confirmation preview or cancel path, nor bind the new UI's success/replay/error presentation to neutral truthful wording.

3. **Prior HIGH (historical/detail immutability and interaction): PARTIALLY RESOLVED.** The correction now checks drawer content, Escape/backdrop/close-button behavior, focus return, filtered-list URL preservation, archive identity binding, and a no-JS exact-detail link. It still does not prove that two same-date snapshots retain different saved object/allocation/trace values or resist later source changes; snapshot `505` has no object data and only the `504` drawer identity is asserted. Row/drawer parity from the issues queue is also not exercised.

4. **Prior MEDIUM (preparation/date/error sensitivity): PARTIALLY RESOLVED.** Publication-browser GREEN now proves valid preparation, lost-response operation-id replay, retained report date and one resulting snapshot. The mapped tests still do not exercise authorized invalid/stale preparation or unknown source-date presentation, and do not assert calculation date, creation time and payment date as separately labelled values.

### Current complete findings

1. **HIGH — payment confirmation preview and cancel remain untested at the changed UI seam.** `specs/OTIZ-GUIDED-WORKFLOW-001.md:20-21,33-36` requires a preview containing calculation date, scope, current available amount, and an explanation that FMonitor only records an externally completed payment; cancelling it must write nothing, while replay/no-change/error must not look like a new payment. `tests/Otiz/snapshot_publication_browser_001_test.mjs:65-67` submits the completion form directly and never observes or cancels a preview. `tests/Yii2/yii2_otiz_settlement_001_test.php:48-60` proves owner outcomes and retained legacy messages, but not the new preview/cancel or the redesigned success/error presentation. Add browser assertions for the exact preview fields/copy, cancel with a complete fact fingerprint, one confirmed success, replay/no-change/error wording, and absence of a second success claim.

2. **HIGH — historical immutability remains insensitive to cross-snapshot or source substitution.** `tests/Yii2/otiz_shlz_ui_browser.mjs:98-100` proves two ids share a date and that clicking `#504` opens a drawer tagged `snapshot=504/object=8001`; it does not assert saved values in that drawer, gives `#505` no contrasting object/allocation/trace fixture, and never mutates current source data after snapshot creation. An implementation that renders the latest snapshot or live source for every historical id can pass. Seed deliberately different saved object, allocation and trace values for both same-date snapshots, change the live source afterward, and assert exact row/drawer/fallback values for each id plus an issues-queue entry point.

3. **MEDIUM — invalid/stale preparation and date provenance are still outside the mapped evidence.** The successful replay in `snapshot_publication_browser_001_test.mjs:35-54` is good idempotency evidence, but no mapped test sends an authorized invalid date/operation id or stale preparation and proves no new facts. No assertion distinguishes report date, calculated/created time, closure/payment date, and an absent source date in the redesigned HTML. Add fixed public-seam cases for these rejections and labelled date states, including an unknown date that remains unknown rather than becoming today's date.

### Review assessment

The correction materially improves complete mapping and closes the largest gap. The three records are exact-source bound; the two GREENs are successful and the UI RED still fails deterministically at the first absent three-mode navigation requirement rather than setup. Expected monetary values are independently derived, command fixtures are isolated, and rejection/no-change owner semantics are strong. Gate 3 remains blocked only by the three explicit UI/history/date sensitivities above.

### Required correction

- Close the three current findings with root-authored assertions.
- Capture fresh exact-source evidence and prepare another complete reviewer package before executor implementation.

---

## Correction round 2 — rebuilt complete matrix — 2026-09-21

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); authored none of the specification or tests.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T183659Z-2fc0ea241b/package.json`.
- Exact candidate source: `dc53cc6f9af8e61b3f17478d0033dd9c6afe86decff146a85b78e07d0110eeb8`; executable source `7d3b571ab16ba81b0b6b1e81c65a8612022aa652d1dd660f045ff2f3331b25b0`.
- Verification plan: SHA-256 `104ee7d5b531a97a60384d4433001e3c79f648d23c39fd25f3f218998f9c7fb2`; lane `CRITICAL`; required reviews `gate3`, `final`; `missing_tests=[]`.
- Exact-source evidence: UI intended RED `1790015787839396000-ea60374db0524af5884faa6ef7ab37a4`; publication journey GREEN `1790015762422419000-aecb049d99934ed7999d37e21b013c8a`; settlement GREEN `1790015762435335000-11e8beabb0c6468181f0e0d3927472cc`; snapshot validation/idempotency GREEN `1790015762422898000-265b3802d07e4dda90ea4894af7fc7d1`; Yii command invalid/stale/no-fact GREEN `1790015762421385000-17b241fd89684d26a91c31de0945f31f`.
- Current verdict: `CHANGES_REQUESTED`.

### Prior finding disposition

1. **Prior HIGH (payment preview/cancel and truthful results): MOSTLY RESOLVED.** The UI test now opens the payment dialog, asserts the external-action boundary, cancels it, and later synchronizes the complete eight-table fact fingerprint against the pre-navigation state. It also proves that `?paid=1` cannot invent success without a persisted fact, that error is neutral, and that zero availability/duplicate state does not claim a new payment. Publication and settlement GREENs independently exercise one successful completion and durable no-change/reversal semantics. The sole remaining issue is that the preview's required date/scope/amount are not actually asserted.

2. **Prior HIGH (historical immutability): RESOLVED.** Snapshots `501` and `505` now share report date and object identity but contain deliberately different saved address, amount, issue and fact-date values. The archive opens each exact id/object drawer and asserts those distinct values, the `501` drawer excludes the `505` issue, and the issues queue reaches the same exact detail seam. The mapped snapshot-publication test additionally proves a consistent saved input cut across a concurrent source update. This is sensitive to latest-snapshot and live-source substitution.

3. **Prior MEDIUM (invalid/stale preparation and dates): RESOLVED.** `snapshot_publication_001_test.php` covers invalid date, invalid operation id, replay identity and rollback/no-fact behavior. `yii2_otiz_commands_001_test.php` covers authorized invalid preparation, conflict/stale acceptance, immutable/blocker/incomplete cases and a full fact fingerprint for all rejections. The UI now distinguishes calculation, creation and payment dates and preserves an absent source value as `Нет данных`/`Не указан`.

### Current complete finding

1. **MEDIUM — the payment-preview field assertion is nominal rather than value-sensitive.** `specs/OTIZ-GUIDED-WORKFLOW-001.md:20-21` requires the complete-payment preview to contain the calculation date, scope and current available amount. `tests/Yii2/otiz_shlz_ui_browser.mjs:46` only asserts that one `[data-confirm-summary]` element exists and then checks the external-payment explanation on the enclosing form. An empty summary, or one showing a saved/gross amount instead of the current `900,00 ₽`, passes. Bind the preview/dialog to the independently seeded literals: snapshot `#501`, calculation date `30.09.2026`, object scope/count (or the exact agreed scope label), and current available `900,00 ₽`; also assert that the dialog exposes the same values after opening. No additional command test is needed.

### Review assessment

The rebuilt matrix otherwise provides complete and independent coverage of the public journey, authorization and validation refusals, idempotency, real XLSX, append-only settlement/reversal, truthful zero/no-change states, historical id binding, accessibility/responsiveness and no-write reads. Fixtures are isolated and deterministic; expected monetary/date values are literal or independently derived. The UI RED remains an intended first missing-behavior failure at the authenticated Yii/Chromium seam. Approval is withheld only for the bounded preview-value sensitivity above.

### Required correction

- Add the exact payment-preview value assertions, capture a fresh source-bound UI RED/package, and return for final Gate 3 rereview.

---

## Correction round 3 — bounded payment-preview rereview — 2026-09-21

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); authored neither specification nor tests.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T184123Z-d1c32b7cf7/package.json`.
- Exact candidate source: `a0eaa07c9b98043acbccb7525690297c6e7f08b6cae00b1daeae7647aa895c53`; executable source `a72bc9d2b5cdc244a15ce7e341b714ca8491bba5381f9634b92d739eee804b32`.
- Verification plan: SHA-256 `1c1126865d70faea7ab3efdbb3cc2d90598365eaa465381778e26eb0ed0caf4e`; `missing_tests=[]`.
- Fresh exact-source evidence: snapshot publication GREEN `1790016029221356000-48ecbfdadbc842f787825ec4e37ddf58`; Yii commands GREEN `1790016029238756000-1531e42e7bda4910b2aefa61587e4e6f`; publication journey GREEN `1790016029245873000-0800ac7a2db342aa9bb800be38974241`; settlement GREEN `1790016029266358000-908ffba996c14095a78597510b176514`; UI intended RED `1790016053970403000-e5eb071421dc48c3bcd34b3b76773187`.
- Verdict: `APPROVED`.

### Remaining finding disposition

1. **Prior MEDIUM (nominal payment-preview assertion): RESOLVED.** `tests/Yii2/otiz_shlz_ui_browser.mjs:46` now binds the inline preview to the independently seeded exact values `#501`, `30.09.2026`, `1 объект` and current available `900,00 ₽`; after opening, the confirmation dialog must repeat the same four values. The existing assertion separately preserves the external-payment explanation, cancel closes the dialog, and the later synchronized full fact fingerprint proves the cancel path adds or rewrites no OTIZ facts. An empty summary or a saved/gross `1 000,00 ₽` substitution now fails.

### Complete Gate 3 result

No findings remain. The complete mapped matrix covers the authorized prepare/replay/accept/XLSX/payment/reversal journey; blocker, warning, immutable, stale, invalid, forbidden and no-change outcomes; exact no-fact behavior; truthful zero/payment/discipline/reversal presentation; same-date historical snapshot identity and saved detail; query/list context; JavaScript-off fallback; keyboard/focus/dialog behavior; responsive layouts; and exact date/scope/current-amount confirmation.

The five evidence records all bind candidate `a0eaa07c9b98043acbccb7525690297c6e7f08b6cae00b1daeae7647aa895c53` and executable source `a72bc9d2b5cdc244a15ce7e341b714ca8491bba5381f9634b92d739eee804b32`. The four companion checks are GREEN. The UI record reaches the authenticated Yii/Chromium seam and fails deterministically at the first absent three-mode navigation requirement, so it is valid intended RED rather than setup failure. Expected values are independently derived from fixed fixtures, and isolation/cleanup are adequate.

Gate 5, owner stand review, CI, PR, merge and deployment remain separate and are not implied by this Gate 3 approval.

### Required changes

None.

---

## CI correction test-delta review — 2026-09-22

- Reviewer: independent `/root/otiz_reviewer`; authored neither correction tests nor production.
- Prior approved package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T004659Z-b9bd681f5e/package.json`.
- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T015854Z-0f73f3c4c7/package.json`.
- Exact source: `1636023072f1a1af9437813378ddd33ff4b9464a7039a31c0511af3c7d138653`; executable source `596003f3a01b1b7ddc6fe058083f1f123f0f0f228f421ac303d203e8bae998ea`.
- Base commit: `faddb58393a22e4e61a8c7476d24898cece08753`; snapshot patch SHA-256 `6f2565518d4bbd51f1b1cb52154f85b40103e7c251d52fe357766287c39d5c00`.
- Verdict: `APPROVED`.

### Test-delta assessment

No findings. The adjacent CI corrections update stale expectations to the already approved UI rather than weakening behavior: main navigation requires «Выполнение расчёта»; DatePicker interaction uses the public localized visible field; settlement assertions enter the exact object drawer and retain trace/worker/KTU/issue/finance checks; asset hashes bind exact bytes.

The new replay regression is sensitive to the production defect. It captures the first operation and snapshot, returns through a `?created=1` snapshot page, then requires a distinct operation id and distinct immutable snapshot on the next preparation. With cleanup scoped inside the absent calculate form, that sequence reuses the old key and fails; moving cleanup to every OTIZ page carrying `created=1` closes exactly that gap while retaining response-loss replay before success.

The reversal path requires exact `?reversed=1`, the status text «Предыдущая отметка о выплате отменена.», the appended linked reversal row, preserved history and restored availability. Dropping the flash fails the test even when ledger mutation succeeds.

All nine selected local evidence records are GREEN on the matching source and `missing_tests=[]`. The first compose attempt failed only while resolving the pinned Node manifest from Docker Hub (`context deadline exceeded`) and the same-source retry passed. The first governance attempt completed 17 of 18 tests and timed out in its nested harness command; the same-source retry passed all 18. These retained transient failures and reasoned retries do not conceal a behavior failure.

### Required changes

None.

---

## Refreshed exact-source Gate 3 — archive link and no-JS delta — 2026-09-22

- Reviewer: independent `/root/otiz_reviewer`; authored neither specification, tests nor production.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T002058Z-2b9117df14/package.json`.
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`.
- Exact candidate source: `d0381212115f669ace6adf7b491c7f58336c7c81161cd6a714fd621a5d2495c6`; executable source `538759dcdc664c8a606b288858c2e6fde13f40ede02d03d8a0c550a72ecf4306`.
- Snapshot patch SHA-256: `faae709a0f553171f0fa58a91e10f9262b2c0265e75f4ba11c1d0f50f08d5ba8`.
- Verdict: `CHANGES_REQUIRED`.

### Complete findings

1. **HIGH — canonical spec and the refreshed owner-directed test require different economy columns.** The owner-approved visual decision combines paid and withheld values in one visible «Выплачено» column, and `tests/Yii2/otiz_shlz_ui_browser.mjs:82-86` now requires exactly that nine-column table and reads the combined `data-label="Выплачено / удержано"` cell. However, `specs/OTIZ-GUIDED-WORKFLOW-001.md:22` still normatively requires separate «Выплачено» and «Удержано» columns. Thus the current GREEN proves behavior that contradicts the canonical contract, and a conforming separate-column implementation would fail the test. Amend requirement 8 to record the owner decision explicitly (one visible «Выплачено» column with the paid amount and separately labelled withheld amount in the same cell), then keep the per-cell assertions aligned with that wording.

### Delta assessment

The requested archive/no-JS correction itself is sound. `_otiz-snapshot-list.php` renders one accessible icon-only anchor per archive row with an exact `/pilot/otiz/snapshots/<id>` href; the refreshed browser test checks each row's single-control cardinality, accessible exact-snapshot name, non-eye icon and empty visible text, then follows both same-date snapshot links. Section 5 correctly requires this server link to remain usable without behavior JavaScript and no longer requires the removed textual archive fallback. No finding remains against that bounded delta.

### Required correction

- Reconcile requirement 8 with the recorded owner-approved combined paid/withheld presentation and prepare a fresh exact-source package for bounded Gate 3 rereview.

---

## Refreshed exact-source Gate 3 — correction round 1 — 2026-09-22

- Reviewer: independent `/root/otiz_reviewer`; still authored neither specification, tests nor production.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T004659Z-b9bd681f5e/package.json`.
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`.
- Exact candidate source: `13c9fa4b98f93bb8a06baf69a481edd5df3853f6bd6944c83eff8b03f2f8ad00`; executable source `b57ccb1eacf43ffe4e13c0008a6d4acec98701fb6ba9f873545beb2a8d3be09c`.
- Snapshot patch SHA-256: `8a635d0ad1f77c2dd2e0b5f6570fa40ccfa3aa06f202924588b1dcc7e0e3df48`.
- Verdict: `APPROVED`.

### Prior finding disposition

1. **Prior HIGH (canonical columns contradicted owner-approved test): RESOLVED.** `specs/OTIZ-GUIDED-WORKFLOW-001.md:22` now normatively lists the nine visible data/action columns and explicitly states that «Выплачено» contains separately labelled paid and withheld values in one cell, with no separate «Удержано» column required. This matches the owner decision and the existing per-row assertions at `tests/Yii2/otiz_shlz_ui_browser.mjs:82-86`; no UI behavior was changed.

### Complete Gate 3 result

No findings remain. The archive action remains one accessible icon-only exact-snapshot anchor per row and works without behavior JavaScript; section 5 and the browser assertions agree. The corrected economy wording now agrees with the owner-approved presentation and test oracle. All eight selected local records are GREEN on the exact source, `missing_tests=[]`.

### Required changes

None.

---

## Owner-directed recomposition test/spec delta — Gate 3 — 2026-09-22

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); reviewed the root-authored recomposition specification/test delta and compatibility with the previously approved financial matrix.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T210032Z-6028c4493e/package.json`.
- Exact candidate source: `4fd7eff1fa72a3ad5efc8ec90163f3ac4c16b19a0d9c7131d03a4fb6fcd27633`; executable source `88ac1928d460013b640ef7c81a24ba8277934d3e2b09d0fc2f571789c46b9bf3`.
- Verification plan: SHA-256 `f11812d3812fdf84e1f15f315227cbad606ec737f656b034be8dc52fa1b60455`; `missing_tests=[]`.
- Fresh evidence: UI intended RED `1790023127471020000-cafae7f1b05d469b9537f8d001dae6cc`; snapshot publication GREEN `1790024045719428000-c31cb713b2c14803a720d42a348a5ebe`; Yii commands GREEN `1790024052947215000-17590bba4a774fdbb620e0b23f121f9d`; publication journey GREEN `1790024387649670000-519460cdfe484c6d9ebcf99e481c76f4`; settlement GREEN `1790024404615971000-a438b43b805242908b0f17fb37b7babc`.
- Verdict: `CHANGES_REQUESTED`.

### Findings

1. **HIGH — the canvas assertions are mutually contradictory, so a conforming implementation cannot pass.** `tests/Yii2/otiz_shlz_ui_browser.mjs:80` requires `.fm2-otiz-page` to have `rgb(244, 246, 249)` as the gray canvas and separately proves three or more padded white `.fm2-otiz-surface` children. Three lines later, `:83` recomputes the same `.fm2-otiz-page` background and requires it to equal `rgb(255, 255, 255)` together with the table wrapper. Both predicates cannot be true in one render. Remove the stale page-white assertion; retain a white assertion scoped to the table surface/wrapper and the gray canvas assertion on the page root.

2. **HIGH — the DatePicker expected value is tied to yesterday's wall clock and is not deterministic.** `tests/Yii2/otiz_shlz_ui_browser.mjs:112` requires hidden `reportDate` to equal literal `2026-09-21`, while `OtizSettlementController::payments()` obtains the current Europe/Moscow date from `DateTimeImmutable('now')` and the fixture does not inject a fixed clock for this controller. The current review date is 2026-09-22; after implementation reaches this assertion, correct current-date behavior will fail. Either inject a fixed clock/`FMONITOR_NOW` seam that this controller actually consumes, or derive and assert the Europe/Moscow date immediately around the request with a midnight-safe bound. Keep the ISO hidden value, visible formatted value, keyboard calendar and submit payload checks independent of wall-clock drift.

3. **MEDIUM — the removal of text detail/open actions is checked by legacy representation rather than observable semantics.** `tests/Yii2/otiz_shlz_ui_browser.mjs:42` only requires zero `.fm2-otiz-detail-link` elements, so a separate detail link with another class passes. At `:100`, only links with accessible name exactly `Открыть` are rejected, so a button named `Открыть`, a longer `Открыть расчёт`, or other text action passes despite the contract's icon-only action column. Assert that the relevant snapshot/archive action cells contain exactly one icon-only accessible control, have no visible `Открыть`/`Открыть отдельную детализацию` text, and contain no additional link/button action beside that control.

### Review assessment

The remaining recomposition assertions are appropriately sensitive: step strip and loose empty-issue copy must disappear; snapshot objects become a six-column mass-work table; worker/KTU/issues move into the exact drawer; gray canvas and separated padded surfaces are specified; public drawer structure and behavior remain covered; object-register and archive action columns, non-eye affordance, `file-xlsx` icon, readable ledger headings, public DatePicker keyboard opening, and explicit zero-available language are all reached through the authenticated Yii/Chromium seam. The four adjacent source-bound GREENs preserve financial and command compatibility.

The UI RED is valid and fails at the intended obsolete four-step strip after successful setup. That valid early RED does not reveal the contradictory and time-dependent later assertions, which must be corrected before executor dispatch.

### Required correction

- Resolve all three findings, capture a fresh exact-source package, and return the bounded recomposition delta for independent Gate 3 rereview.

---

## Owner-directed recomposition — correction round 1 — 2026-09-22

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); reviewed only the correction of the three prior findings and compatibility with the retained matrix.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T210659Z-ec406ec5e3/package.json`.
- Exact candidate source: `5089c43380e893965c6f0b38a3f1cf9149c50a3fad4c0ecaed0d87a836dae687`; executable source `29d00e9eb4c4ef6eadec735b71cdd3e321aff2b8b0f450c86c8e7305b0374f07`.
- Verification plan: SHA-256 `c2fdaed5cbb6e7592384f7ce5cbdd97096c7e738dbb429873279bef390fa4a47`; `missing_tests=[]`.
- Fresh evidence: UI intended RED `1790024739172223000-3fb0dc5220254a33954780c3915e4b2a`; snapshot publication GREEN `1790024764711395000-8fca21ef05074807adee6eca31533a28`; Yii commands GREEN `1790024773026672000-cfb1c27dcb0140e3a6b6feea0830c034`; publication journey GREEN `1790024779280858000-edfa3da10f9946aa8502e3ba0b06f93b`; settlement GREEN `1790024790666121000-51e2cebb1e334e3eaff76fb5e0f0e79e`.
- Verdict: `CHANGES_REQUESTED`.

### Prior finding disposition

1. **Prior HIGH (contradictory canvas colors): RESOLVED.** The page root is consistently required to be gray, while the table wrapper and all separated surfaces are white, padded and rounded.

2. **Prior HIGH (wall-clock DatePicker literal): MOSTLY RESOLVED.** The stale literal date is gone and the hidden field is constrained to ISO syntax. The remaining visible-value assertion is too weak to prove localization or correspondence to the ISO value.

3. **Prior MEDIUM (text/icon action semantics): MOSTLY RESOLVED.** Economy and archive cells now reject anchors and visible text and require public affordances. Aggregate counts do not prove exactly one control in every row, and archive does not enforce the required non-eye icon.

### Current complete findings

1. **MEDIUM — the DatePicker visible value can be arbitrary while the test passes.** `tests/Yii2/otiz_shlz_ui_browser.mjs:112` proves the hidden value matches `YYYY-MM-DD`, but the visible field only has to be nonempty/different from that raw ISO string. `banana`, the wrong localized date, or a value unrelated to the submitted ISO date passes. Parse the hidden ISO components and require the visible value to be the corresponding `DD.MM.YYYY` (or the exact public DatePicker locale format), then retain the keyboard calendar and hidden submit-field checks.

2. **MEDIUM — action-cell cardinality and archive non-eye icon are not per-row sensitive.** At `tests/Yii2/otiz_shlz_ui_browser.mjs:82`, 50 cells plus 50 matching affordances is an aggregate: one row may have two matching controls and another none; additional icon-only buttons also pass because only anchors and visible text are rejected. The archive assertion at `:100` has the same aggregate 6/6 weakness and never rejects `eye.svg`. Evaluate each action cell and require exactly one interactive child, exactly one public affordance with the expected accessible name, no anchor/extra button, and a non-eye image source. Apply the same per-cell predicate to all economy and archive rows.

### Review assessment

The correction removes both blockers that would have made a correct implementation impossible or date-dependent. The intended RED remains valid at the obsolete step strip, and all four adjacent source-bound checks are GREEN. The remaining findings are bounded assertion-strength issues; the broader recomposition and financial matrices remain intact.

### Required correction

- Strengthen the visible-date equivalence and per-row action-cell predicates, capture fresh exact-source evidence, and return for bounded rereview.

---

## Owner-directed recomposition — correction round 2 — 2026-09-22

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); reviewed only the two bounded assertion-strength corrections and retained compatibility.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T211211Z-c3db4c9df4/package.json`.
- Exact candidate source: `b1fc8d4f4047689b77d2619e34d75011d8f7933fc1e853fd7a863c536708f103`; executable source `7c99aa787ec3c4d96f941fa07833d468239be1e6ae45a12ca74b149e25af3302`.
- Verification plan: SHA-256 `63e007f05ac3f2b687e76aaded4df021156d2a03b3f26020c4bd2dedf657137c`; `missing_tests=[]`.
- Fresh evidence: UI intended RED `1790025054362530000-140aac9a6657454ab1127d3d15a65eed`; snapshot publication GREEN `1790025075816376000-f9c42ada4839444ebeb4c84973976e2b`; Yii commands GREEN `1790025082390429000-ccd770a27a564be19c9d01978d1dfa24`; publication journey GREEN `1790025089487367000-871e0d48721e42eb97609ba5f8ecb2ab`; settlement GREEN `1790025101621480000-53a074705a2d4f20951e97e1bc500b83`.
- Verdict: `APPROVED`.

### Remaining finding disposition

1. **Prior MEDIUM (visible DatePicker equivalence): RESOLVED.** The test splits the hidden ISO value and requires the visible Russian field to equal the exact corresponding `DD.MM.YYYY`; arbitrary or mismatched localized text now fails. The wall clock remains unpinned without making the assertion date-dependent, and keyboard calendar behavior is retained.

2. **Prior MEDIUM (per-row action cardinality/non-eye): RESOLVED.** Every economy and archive action cell is independently evaluated. Each must contain exactly one interactive `a,button`, exactly one direct public button affordance with the expected accessible-name prefix, no `eye.svg`, and empty visible text. Extra controls, missing controls, redistributed aggregate counts, text actions and the construction-control eye now fail per row.

### Complete recomposition Gate 3 result

No findings remain. The owner-directed recomposition matrix is deterministic and sensitive to the specified gray canvas/separate surfaces, public DatePicker/calendar, compact snapshot mass table, removal of the step strip and loose detail/empty-issue elements, public drawer detail, non-eye icon-only economy/archive actions, public XLSX icon, readable ledger table and explained zero availability. The previously approved financial and command behavior remains covered by the four exact-source GREEN companions.

The UI RED reaches the authenticated Yii/Chromium seam and fails on the first obsolete step strip, not setup. All five records bind candidate `b1fc8d4f4047689b77d2619e34d75011d8f7933fc1e853fd7a863c536708f103` and executable source `7c99aa787ec3c4d96f941fa07833d468239be1e6ae45a12ca74b149e25af3302`.

Approval covers this root-authored test/spec delta only. Production implementation, Gate 5, owner stand acceptance, CI and publication remain separate.

### Required changes

None.

---

## Owner-rejected stand layout visual delta — Gate 3 — 2026-09-21

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); reviewed only the root-authored visual test delta and compatibility with the previously approved matrix.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T194203Z-50276d756b/package.json`.
- Exact candidate source: `b04719ec1789758667b4e33a82dd613ee170114557b2e60922c67dd0f75f9c9e`; executable source `92bafc78ff14d8571a3f62c09926d19878c8d460ab7486f8c458571ed8b8076e`.
- Verification plan: SHA-256 `5a685b106b1a0a3486e0523258eea2aae84bbfc52d1d635ff8ecb55847d684c3`; `missing_tests=[]`.
- Fresh evidence: UI intended RED `1790019638595774000-ff33f6049f254001b0846a0c20f0e719`; snapshot publication GREEN `1790019661585561000-3007032751214de4b3fee25f4eaf1084`; Yii commands GREEN `1790019668681165000-5ad4c26e080f45f3866df0f10c87ff7f`; publication journey GREEN `1790019676176628000-d3eb5ed12fd3426abbee8245483592ae`; settlement GREEN `1790019690101125000-b29a238080ec4cb3ae6f3ce4a8f0236c`.
- Verdict: `CHANGES_REQUESTED`.

### Findings

1. **MEDIUM — the promised desktop select geometry is not exercised.** `tests/Yii2/otiz_shlz_ui_browser.mjs:80` sets the page viewport to 320 px for the register matrix, and no later call restores a desktop width before the new select measurement at `:113`. Therefore `selectGeometry` proves three public roots, exact 58 px trigger height, width equality and containment only at 320 px. The earlier 320/768/1024/1440 loop (`:62-72`) visits the snapshot page, not `/pilot/otiz/objects`, and does not inspect selects. A desktop-only width/overlap regression can pass despite the stated 320/desktop requirement. Run the same three-root/height/width/containment observation at one representative desktop viewport (the accepted 1440 px oracle is suitable) as well as 320 px, and retain independent assertions per viewport.

### Review assessment

The remaining visual delta is sound. The test requires an exact public `data-shlz-drawer-trigger`, native `dialog.shlz-drawer[data-shlz-drawer]`, public surface/header/body composition, four or more readable content sections, focus entry/return, native Escape, backdrop and close-button behavior. The register requires a dedicated tenth action column and one accessible compact public affordance per row while retaining exact numeric-column alignment and row economics. The RED fails deterministically on the first absent public drawer trigger after authenticated setup, and the four adjacent mapped tests remain exact-source GREEN. Previous behavioral coverage is preserved.

### Required correction

- Add the desktop object-register select geometry observation and capture a fresh exact-source package before implementation.

---

## Owner-rejected stand layout visual delta — correction round 1 — 2026-09-21

- Reviewer: independent `/root/gate3_review` (`gpt-5.6-sol / low`); reviewed only the bounded geometry correction and compatibility with the previously reviewed visual delta.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T194543Z-dbe9fd302f/package.json`.
- Exact candidate source: `2e538e7166c238ae2a7955f7405d60c7b70c6604aece01351dc906c3d7622f44`; executable source `58cdb895cc99822f830a8fabdee640219105197cf0ecf2010903d3e885e51459`.
- Verification plan: SHA-256 `bb20a15d5fdb75955d3800a3b483680dd8fc99297d3ff714087be1ba4799a2e9`; `missing_tests=[]`.
- Fresh evidence: UI intended RED `1790019867276148000-a4ce63ca7bcb40388d65211c65cbb5bc`; snapshot publication GREEN `1790019888478568000-ec069bf130234c1295be888bff1c8a0f`; Yii commands GREEN `1790019894564189000-998ed24a972247748b669c39c47c67d3`; publication journey GREEN `1790019901708435000-cdd2d21efcfb4a87921b47f7c7c40a24`; settlement GREEN `1790019914766702000-ce12b27f5283414daff449812e47f37d`.
- Verdict: `APPROVED`.

### Remaining finding disposition

1. **Prior MEDIUM (desktop select geometry absent): RESOLVED.** `tests/Yii2/otiz_shlz_ui_browser.mjs:113` now runs the same observation at 320 and 1440 px. For each width it navigates to the actual object register, requires exactly three public `.shlz-select-root` fields, and independently asserts exact 58 px trigger height, trigger/root width equality and containment without overflow. A mobile-only or desktop-only geometry regression now fails.

### Complete visual-delta result

No findings remain. The visual assertions sensitively require the public native dialog drawer trigger and composition, four or more formatted sections, focus/keyboard/backdrop/close behavior, a dedicated action column with accessible compact affordances, and stable public select geometry at both accepted mobile and desktop widths. The previously approved behavioral matrix remains intact, and the four adjacent exact-source regressions are GREEN.

The UI RED reaches the authenticated browser seam and fails at the first missing public drawer trigger, not setup. All five records bind candidate `2e538e7166c238ae2a7955f7405d60c7b70c6604aece01351dc906c3d7622f44` and executable source `58cdb895cc99822f830a8fabdee640219105197cf0ecf2010903d3e885e51459`.

This approval covers only the root-authored visual test delta. Production implementation, Gate 5, owner stand acceptance, CI and publication remain separate.

### Required changes

None.
