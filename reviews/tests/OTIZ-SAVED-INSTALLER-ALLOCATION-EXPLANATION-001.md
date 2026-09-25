# Test review: OTIZ-SAVED-INSTALLER-ALLOCATION-EXPLANATION-001

- Reviewer: independent `gpt-5.6-sol / low` agent `/root/issue29_gate3`
- Test author: root delivery agent (specification, verification mapping and tests)
- Reviewed source: exact source `bb57ff1b80421b7600bf9ce5fa42c6ab132233a6ab604ecb6f2af26f398f8631`; base `99bd0974150617a01e195cec28f7d886f1ede761`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T233737Z-7bedd971de/snapshot/source.patch`, SHA-256 `7717151a89a16012bf7cb48c55d63ac90d14ea4dbf5acfd1be7a093adf33927f`
- Agreed review scope / prior findings disposition: first independent Gate 3 review; canonical specification, OpenSpec delta, verification mapping, PHP/browser tests and prepared RED/GREEN evidence; no prior findings
- Specification: `specs/OTIZ-SAVED-INSTALLER-ALLOCATION-EXPLANATION-001.md` (SHA-256 `d19735f8217d080bee24cbf1804095a5498f3b2a6868b1008d18e9704d857d69`)
- Public seam: guest/denied and authorized `GET /pilot/otiz/snapshots/{snapshotId}`, existing object drawer, Chromium desktop/narrow interaction, and database no-write inventory
- Planner: package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T233737Z-7bedd971de/package.json`; plan SHA-256 `bd84c6ced601440af7f2a71a6d12cdf0d34738437360b29eff5b0909eae92ca2`; lane `CRITICAL`; required reviews `gate3`, `final`
- Verdict: `CHANGES_REQUESTED`

## Evidence reviewed

- `php tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php` — `INTENDED_RED`, exit 255, 9.165 s. Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293008069293000-13b313f98c6b47c0b625da1de3165035.json`; exact start/end source `bb57ff1b80421b7600bf9ce5fa42c6ab132233a6ab604ecb6f2af26f398f8631`. Failure is the missing presentation behavior at test line 58: `Сумма к распределению` is absent. This is an intended product RED, not a fixture/setup failure.
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php` — `GREEN`, exit 0, 11.355 s. Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293028543838000-c9a965631df34a05a10b788185ce72a1.json`; exact start/end source matches the reviewed source.
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php` — `GREEN`, exit 0, 16.059 s. Record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293028541534000-231169050cec4bf5b662397457008bf3.json`; exact start/end source matches the reviewed source.
- Bound test bytes match the prepared plan: PHP SHA-256 `ce4a76a1852d1a0ee05230cd4d3df7524b6b20cb3198a27828a38bcfe704e82e`; browser SHA-256 `0fb45759d7b8270bf75b349ea5090d640b043f111881d4a94722233f330f5229`.

## Findings

1. **BLOCKING — A02 is seeded but never exercised through the public seam.** `tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php:33-46` creates snapshots 821 and 822 with different dates, object amounts, identities and allocations, but all authorized assertions at lines 55-64 request snapshot 821. Consequently a candidate that ignores the selected snapshot ID, mixes publications, or renders later values for the first publication can pass. Add authorized GET/assertions for both snapshot IDs and bind each response to its own date, distributed amount, identities, shares, amounts and absence of the other snapshot's values.

2. **BLOCKING — A03 does not test a changed current composition.** The purported later/current participant at `tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php:46` is another saved allocation in snapshot 822, not a row in any current workforce/assignment/composition source. The only related assertion at line 59 proves that snapshot 821 does not display a row from snapshot 822; it does not catch an implementation that falls back to today's composition. Seed the actual current-composition boundary with a replacement participant and assert through the old snapshot GET that the old saved participant remains, the current replacement is absent, and no current source value is used as fallback.

3. **BLOCKING — the worked values are not independent enough to detect forbidden field substitution or recalculation.** In `tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php:43-46`, every allocation has `contribution_bp == share_bp`; for the principal 100.00-ruble object, `amount_cents` also numerically matches the percentage share. The page-wide token assertions at line 58 and browser assertions at `tests/Yii2/otiz_saved_installer_allocation_browser.mjs:13-14` therefore pass if contribution is rendered from share (or vice versa), and can pass if amount is recomputed as distributed amount times share—the exact regressions prohibited by sections 2/A01 of the canonical spec. Use deliberately asymmetric saved values (including an amount that is not derivable from the displayed object amount/share/KTU), then scope label/value assertions to each worker's `<details>` so each field is proven to come from its own saved column.

4. **BLOCKING — A10 verification mapping covers only form recovery, while the mapped acceptance claims the full A01-A10 slice.** `openspec/changes/explain-saved-installer-allocation/verification-input.json` maps two #263 recovery tests, but canonical A10 also requires unchanged settlement form actions/fields, ledger, XLSX and POST behavior. The new PHP/browser test does not assert those remaining boundaries, and no corresponding existing regression is mapped. Either add the existing focused regressions for each retained A10 boundary to the acceptance/plan, or narrow/split the normative A10 claim with an explicit, justified boundary that the selected tests actually verify. Gate 3 cannot approve an acceptance mapping that overstates coverage.

## Review checklist

- Traceability A01-A10: incomplete; A02/A03 are not executable and A10 is only partially mapped.
- Public seam: appropriate existing Yii HTTP/drawer/browser seam; no private production method is used.
- Expected-value independence: insufficient for contribution/share/amount because fixture values alias one another.
- Sensitivity to missing/wrong behavior: RED is genuinely sensitive to the missing explanation, but the candidate test is insensitive to snapshot selection/current-source fallback and key field substitutions.
- Rejected/denied cases: guest redirect and near-match permission 403 are asserted with sensitive-token absence and no-write comparison.
- Read-only/no-write: relevant tables are inventoried before/after repeated authorized GET, denial and browser interaction; this is adequate for the declared table set.
- Escaping and responsive interaction: identity/basis escaping, native details, multiple workers, long basis and narrow page overflow are covered.
- Determinism and isolation: unique prefixed MariaDB fixture, loopback server, local Chromium and temporary artifacts are isolated from production; no external systems are contacted.
- RED evidence: exact-source, intended product failure is valid; the test stops at the first absent presentation label, as expected before implementation.
- #263 regression: both prepared PHP and browser recovery checks are GREEN, but they do not alone discharge all broad A10 retained-boundary claims.

## Required changes

1. Exercise and distinguish both saved publications through authorized GETs (A02).
2. Seed the real current-composition source and prove the old snapshot ignores it (A03).
3. Replace aliased numeric examples with asymmetric independently fixed values and worker-scoped label/value assertions (A01 sensitivity and expected-value independence).
4. Complete or explicitly narrow the A10 verification mapping for settlement actions/fields, ledger, XLSX and POST behavior.
5. Capture a fresh exact-source `INTENDED_RED` plus the retained GREEN regressions, refresh the prepared reviewer package/plan, and return the complete corrected Gate 3 candidate.

---

## Rereview 1 — corrected Gate 3 candidate

- Reviewed source: exact source `7d07e9716b4b7f43a653f521725756ba7709d65ffe5552b1f080204e260191ce`; base `99bd0974150617a01e195cec28f7d886f1ede761`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234308Z-8f6b1a5155/package.json`
- Corrected snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234308Z-8f6b1a5155/snapshot/source.patch`, SHA-256 `56e77f5a8b24b05c267d10add79f7c69325eb636b4d45c27f2546eff0a721e3b`
- Delta from prior reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234308Z-8f6b1a5155/delta.patch`, SHA-256 `ed2d5552b407f36ccb312433c3bef95e50cca88fe77a5d4b9c8f30599e93681a`
- Verification plan SHA-256: `9da6da1986d1d13f0d92d923734ea54a5278bda8abf9a1c409d37f541fcad45c`
- Current verdict: `CHANGES_REQUESTED`

### Fresh evidence

- `php tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php` — `INTENDED_RED`, exit 255, 9.232 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293330557693000-ff0545d0584a4fcda6a72a97d05507c5.json`. Start/end source is `7d07e9716b4b7f43a653f521725756ba7709d65ffe5552b1f080204e260191ce`; failure remains the absent `Сумма к распределению` presentation at corrected test line 61, not setup.
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php` — `GREEN`, exit 0, 14.807 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293354481950000-89b675e1172c4729909324082093fff5.json`; exact start/end source matches.
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php` — `GREEN`, exit 0, 19.208 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293354481965000-67d5275405744c578682a75a97f876bd.json`; exact start/end source matches.
- Bound corrected bytes match the refreshed plan: canonical spec SHA-256 `9aac3324851c1d79765f8c22bd0fb7bdc3eb57a4a13af756145cdb3ca789916c`; PHP test SHA-256 `7b706fb915128fd7a2734ef0c99023708734525ce181d3de380f52f42a1db29d`; browser test SHA-256 `1e5b82f278f5bf5bd8425b2c2e418ee4a4bb488befb7cba3324f3a1c4f4ac195`.

### Prior findings disposition

1. **OPEN — A02 is only partially corrected.** The corrected test requests both snapshots and lines 65 distinguish snapshot 822 from snapshot 821. However, the snapshot 821 assertions at lines 60-64 do not assert absence of `Т-88`, `Участник последующего snapshot`, `Основание последующего snapshot`, `246,80 ₽`, or the later date. An implementation that returns the correct first rows plus incorrectly mixed rows from snapshot 822 still passes the first response. The original requirement was bidirectional: each GET shows only its own publication.
2. **FIXED — A03 uses a real current-composition boundary.** Lines 48-50 seed an installation case, registered assignment order and `fm2_order_installers` replacement, while HTTP line 62 and browser line 13 assert that the current participant is absent from the old snapshot. The old saved workers remain asserted.
3. **FIXED — expected values are asymmetric and worker-scoped.** Canonical A01 and the delta now use `23%/1.17/31%/33.33` and `77%/0.93/69%/90.12` against `123.45`; PHP lines 63-64 bind values to each worker's `<details>`, and browser lines 13-14 retain the same worker scoping. Field substitution and the obvious amount-from-share recalculation no longer pass.
4. **FIXED — A10 is coherently narrowed to forms #263.** Canonical A10 and the OpenSpec delta now require unchanged settlement form actions/fields and error recovery only, explicitly leaving ledger, XLSX and financial POST commands outside the changed presentation boundary. The two mapped #263 regressions are fresh GREEN.

### New finding from the corrected delta

5. **BLOCKING — denied amount assertions still use the obsolete fixture amount.** The fixture changed from `100,00 ₽` to `123,45 ₽` with worker totals `33,33 ₽` and `90,12 ₽`, but guest and 403 checks at `tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php:58,67` still search only for `100,00 ₽`. Identity/tab/basis leakage remains covered, but a denial response that leaks the current distributed or worker amount without identity would pass, contrary to A07's explicit amount-confidentiality requirement. Replace the stale token and assert absence of the actual distributed and worker amounts for both guest and near-match permission denial.

### Complete current findings list

1. **BLOCKING:** first-snapshot response lacks negative assertions for all later-snapshot identity/date/amount/basis values, leaving A02 mixing insufficiently sensitive.
2. **BLOCKING:** guest and 403 amount-confidentiality assertions use obsolete `100,00 ₽` instead of the current fixture's actual distributed/worker amounts.

All other reviewed concerns are closed: public seam choice is appropriate; current-composition fallback is exercised; saved columns use independent expected values; #263 scope/mapping is coherent; no-write inventory, escaping, issue separation, empty state, native details, narrow viewport, deterministic prefix isolation and fresh intended RED remain adequate.

### Required changes for rereview 2

1. On the snapshot 821 response, assert absence of the complete snapshot 822 identity/date/distributed amount/worker amount/basis set (and retain the existing reverse exclusions for snapshot 822).
2. For both guest and near-match 403 responses, assert absence of the actual fixture distributed amount and worker amounts (`123,45 ₽`, `33,33 ₽`, `90,12 ₽`) alongside identity/tab/basis.
3. Capture fresh exact-source intended RED, refresh the package/plan, and return the bounded corrected delta with both open findings explicitly disposed.

---

## Rereview 2 — final Gate 3 decision

- Reviewed source: exact source `1242be18e10e8406f517e97a4e527cccfea24a101fe4c204b8ede32364fcd172`; base `99bd0974150617a01e195cec28f7d886f1ede761`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234604Z-1871a7dd20/package.json`
- Corrected snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234604Z-1871a7dd20/snapshot/source.patch`, SHA-256 `2c02d1a67f3707cd0ce7d97774d959155d66db13e699259a9dc6c146e1d2abd8`
- Delta from prior reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234604Z-1871a7dd20/delta.patch`, SHA-256 `4e618f681139860a26dec67d6795ad057be927e312db5d29f7a26b9bf137d3e0`
- Verification plan SHA-256: `3912fb3ccb2025c713b3f4cb77dda7bdf32b7662ce49b2e73cb99d250d32d296`; lane `CRITICAL`; required reviews `gate3`, `final`
- Verdict: `APPROVED`

### Fresh evidence

- `php tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php` — `INTENDED_RED`, exit 255, 9.798 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293503984428000-35283f7e8f43467ca7e12a4b359a4efc.json`. Start/end source is `1242be18e10e8406f517e97a4e527cccfea24a101fe4c204b8ede32364fcd172`; command blob/test SHA-256 `9df40494ef9cf9228724c49dc2c54d6aae62f038a4275e6ee422642bd6a3da5a`. The failure at line 61 remains the intended absent `Сумма к распределению` behavior, not fixture/setup.
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php` — `GREEN`, exit 0, 14.288 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293525563449000-e06474f3a4e4483d88c9184f98f8f856.json`; exact start/end source matches.
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php` — `GREEN`, exit 0, 19.328 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790293525557170000-dc20143503f44762b1f59735d10d70df.json`; exact start/end source matches.

### Last blockers disposition

1. **FIXED — bidirectional snapshot isolation is now sensitive.** `tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php:62` excludes snapshot 822's tab, identity, basis, date and distributed amount from snapshot 821, while line 65 retains the reverse exclusions from snapshot 822. The actual current-composition participant remains excluded as well.
2. **FIXED — denial checks use current sensitive amounts.** Lines 58 and 67 assert guest and near-match 403 responses omit `123,45 ₽`, `33,33 ₽`, and `90,12 ₽`, alongside saved identity/tab/basis.

### Complete findings list

None. All findings from the initial review and Rereview 1 are fixed in the reviewed exact source. Traceability A01-A10, public seam, expected-value independence, sensitivity, real current-composition isolation, denied/no-write behavior, escaping, deterministic fixture isolation, Chromium interaction and the narrowed #263 regressions are adequate for Gate 3.

### Required changes

None. The reviewed tests and expectations are approved for Gate 4 implementation. This Gate 3 verdict does not approve production implementation, Gate 5, CI, publication, merge or deployment.
