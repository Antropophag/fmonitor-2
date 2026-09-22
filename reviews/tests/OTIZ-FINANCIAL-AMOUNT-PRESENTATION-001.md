# Gate 3 test review: OTIZ-FINANCIAL-AMOUNT-PRESENTATION-001

- Reviewer: independent `/root/gate3_otiz_labels`; authored neither the specification nor the tests and made no production/spec/test/OpenSpec changes.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T154921Z-136ce6d7c0/package.json`.
- Base: `0504d2589835f2583dc9afdbc47e4694e2573365`.
- Exact candidate source: `db1d7451603cca5b2486bc8ddb56690cfac6f4f0cff7abeb3611dbd09a9fcb42`.
- Contract: `specs/OTIZ-FINANCIAL-AMOUNT-PRESENTATION-001.md`.
- Public seam: authenticated Yii2 `/pilot/otiz/snapshots/<id>` rendered by Chromium at 1440 px and 320 px.
- Reviewed tests: `tests/Yii2/yii2_otiz_shlz_ui_001_test.php` and `tests/Yii2/otiz_shlz_ui_browser.mjs`.
- Intended RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790092137799467000-0ed8e9cb80e5412a82e35c797301604f.json`; the source-bound run exits at the exact financial `dt`/`dd` pair because the new saved-payment label is absent. Fixture setup, authentication and the target drawer are reached, so this is an intended missing-presentation failure rather than an environment/setup failure.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **HIGH — the reversal fixture links to the wrong operation and therefore does not independently model the stated signed/reversal case.** `tests/Yii2/yii2_otiz_shlz_ui_001_test.php:38-40` inserts three closure rows in one multi-row statement and then uses `mysqli::insert_id` as the id reversed by the negative row. For a multi-row insert, that value identifies the first generated row, here the 80,000.00 ₽ payment, not the third 5,000.00 ₽ withholding named `Reversed withholding source`. The displayed 100,000.00 ₽ still arises from summing the four signed literals, so the assertion at `tests/Yii2/otiz_shlz_ui_browser.mjs:155-156` can pass while the reversal relationship is semantically false. Insert the source row separately (or resolve its exact id), link the reversal to that row, and assert the source/reversal relationship or both identifiable ledger rows so the fixture proves the promised cross-snapshot linked-storno case rather than only a negative number.

2. **HIGH — the required unchanged-numeric-results oracle is incomplete.** The contract's “Проверка неизменности” requires all displayed monetary values and the computed available remainder to match before and after the presentation-only change. The new browser block at `tests/Yii2/otiz_shlz_ui_browser.mjs:148-166` checks the two renamed values for two objects, one ledger row, and neutral zero copy, while the PHP fingerprint at `tests/Yii2/yii2_otiz_shlz_ui_001_test.php:44-51` proves only that GET/HEAD did not mutate persisted tables. It neither captures a pre-change rendered baseline nor independently asserts the remaining displayed amounts/available 50,000.00 ₽ for snapshot 507. An implementation could alter `remaining_cents`, pool/distributed values, or another rendered money value while satisfying these assertions and preserving the database fingerprint. Add an explicit independently derived expected money-value inventory for the affected snapshot/drawers (including available remainder), or a retained pre-change rendered-value baseline compared with the post-change result; keep label/value assertions separate so a copy-only change is demonstrated rather than inferred.

## Review assessment

The remaining coverage is well scoped. Assertions bind each new label to its adjacent value inside the exact `Финансы` section, cover payment-plus-withholding and withholding-only examples, reject calling the live total “paid now,” preserve the ledger's separate paid/withholding presentation, exercise neutral zero without inventing a cause, and repeat the financial block at desktop and narrow widths with a no-overflow check. Fixtures use the suite's isolated database and deterministic literal dates/amounts, and the exact-source RED is sensitive to the first missing label. No production implementation is authorized by this review.

## Required changes

- Correct the reversal fixture/link and assert the intended linked source/reversal evidence.
- Add executable evidence that all affected rendered monetary values, especially available remainder, are unchanged across the presentation correction.
- Prepare a fresh exact-source package and RED record for bounded Gate 3 rereview.

---

## Gate 3 rereview — correction round 1 — 2026-09-22

- Reviewer: independent `/root/gate3_otiz_labels`; still authored neither specification nor tests and made no production/spec/test/OpenSpec changes.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T155547Z-9c4fa0acf7/package.json`.
- Exact candidate source: `821f5fc2c0071945ac32c9edc18f845cef132e8ae63be6b8d3ab6ed7213efd18`; executable source `4ce5960d342a18be87be76f579e7616dada7875b52cd2378219c9266e0a164aa`.
- Verification plan SHA-256: `cc2d8ff599c547592c6280554a8ba090aed6c7e7c570ace4c5f9b8b22295f7fb`; `missing_tests=[]`.
- Refreshed intended RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790092510248272000-821c13f0d7da45db9d0c1c36c61293ef.json`, source-bound exit 255 at the absent new saved-payment label after fixture setup, authentication and exact drawer reachability.
- Verdict: `APPROVED`.

### Prior findings disposition

1. **Prior HIGH (reversal linked to the wrong operation): RESOLVED.** `tests/Yii2/yii2_otiz_shlz_ui_001_test.php:38-42` now inserts the 5,000.00 ₽ source withholding in its own statement, captures that exact generated id immediately, links the −5,000.00 ₽ reversal to it, and verifies the stored foreign-key value. The browser assertions at `tests/Yii2/otiz_shlz_ui_browser.mjs:170` independently require both identifiable ledger rows, withholding semantics, the linked-storno label and the signed negative amount. Together with the literal 80,000.00 ₽ earlier-snapshot payment and 20,000.00 ₽ current withholding, the expected 100,000.00 ₽ total is sensitive to cross-snapshot inclusion and signed reversal arithmetic.

2. **Prior HIGH (unchanged numeric-results oracle incomplete): RESOLVED.** `tests/Yii2/otiz_shlz_ui_browser.mjs:152-168` now checks the snapshot total, each object's accrued/available pair, and the complete seven-entry financial `dt`/`dd` inventory for both fixtures at 1440 px and 320 px. Expected values are independent literals derived from the fixture: the first object includes 150,000.00 ₽ fund/base/accrued, 80,000.00 ₽ saved payment, 100,000.00 ₽ live signed total, 70,000.00 ₽ remaining fund and 50,000.00 ₽ current availability; the withholding-only object similarly fixes every value including 30,000.00 ₽ availability. The existing financial-table fingerprint continues to prove the authenticated reads do not mutate persisted facts.

### Complete rereview result

No findings remain. The canonical specification and complete mapped test agree on the authenticated public seam, exact label/value ownership, object-wide and cross-snapshot signed total, linked reversal, withholding-only case, neutral zero, ledger separation, responsive behavior and numeric preservation. Expectations are literal and independent of the production presentation implementation; dates and amounts are deterministic; the suite retains its isolated database and cleanup. The refreshed RED remains an intended missing-behavior failure and the corrected fixture assertions execute before it, so neither prior defect is masked by the first presentation failure.

This Gate 3 approval authorizes implementation against the reviewed tests. It does not approve production code, final review, CI, merge or deployment.

### Required changes

None.

---

## Post-Gate-4 test delta review — locator mechanics — 2026-09-22

- Reviewer: independent `/root/gate3_otiz_labels`; authored neither the reviewed test delta nor production.
- Gate 3-approved source: `821f5fc2c0071945ac32c9edc18f845cef132e8ae63be6b8d3ab6ed7213efd18`.
- Current exact source: `c8b2b096170942e332c83978a0f6e09572786c6c6f29db6d29047e40a5f13d78`; executable source `79d0357f1afd89a3344791d669929674462688ee7ac69753f3fc1731d366c226`.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T160540Z-1952b5134c/package.json`.
- Reviewed delta: locator mechanics in `tests/Yii2/otiz_shlz_ui_browser.mjs:149-175`; the PHP fixture/test wrapper is byte-identical to the Gate 3-approved package.
- Verdict: `APPROVED`.

### Delta assessment

1. **DOM pair lookup remains strict.** `financialValue` now searches each direct `dl > div` row and reads that row's own `dd`, avoiding an invalid cross-sibling XPath from a locator rooted at `dt`. Its short label probes (`Выплачено ранее`, `Учтено выплатами`) do not weaken the contract because the immediately following complete `Object.fromEntries` equality still requires exactly the seven canonical full labels and their exact formatted values for each object. A missing, renamed, duplicated-with-different-key, swapped or numerically changed pair continues to fail.

2. **Opening drawers before assertions restores the real public interaction seam.** Each viewport pass now clicks the exact object's `Подробнее` button before reading its financial section, checks the first drawer's visible geometry only while open, closes it through the exact accessible `Закрыть` control, then opens and verifies the second object's drawer. This strengthens reachability and prevents hidden `<dialog>` geometry from producing a false failure; it does not remove any label, numeric inventory, explanatory-copy, withholding-only or responsive assertion. A drawer that cannot open, opens the wrong object, loses the financial section, overflows at 320 px or cannot close remains observable.

3. **The zero-message scope is correctly narrowed without changing semantics.** Reading `[data-otiz-next-action] p` instead of the whole wrapper isolates the normative sentence from adjacent action markup. The assertion still requires the exact complete text `К регистрации выплаты сейчас доступно 0,00 ₽.` and still rejects invented payment/withholding causes. Plausible regressions in amount, wording or causal explanation continue to fail.

### Sensitivity and verdict

No expectation was removed or relaxed. The complete exact financial dictionaries at both 1440 px and 320 px remain the primary label/value and numeric-preservation oracle; the signed ledger/source/reversal assertions and neutral-zero rejection remain unchanged. The current GREEN therefore remains sensitive to the plausible regressions in scope: swapped saved/live values, old or misleading labels, unsigned reversal, collapsed paid/withholding history, altered available amounts, hidden/overflowing drawer content and invented zero causes.

No findings remain. This delta approval is limited to the locator changes and does not substitute for independent production/final review or exact-source CI.

### Required changes

None.
