# Code review: OTIZ-SAVED-INSTALLER-ALLOCATION-EXPLANATION-001

- Reviewer: independent `gpt-5.6-sol / low` agent `/root/issue29_final_review`
- Artifact authors: root delivery agent authored scope/specification/tests; separate executor `/root/issue29_executor` authored production implementation; reviewer authored neither
- Reviewed exact source: `0d55b084db458e740203f6588f7ac2abf08904b7c2aabeffae5e15f9811872f7`
- Base: `99bd0974150617a01e195cec28f7d886f1ede761`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T000602Z-04d8e6afb7/package.json`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T000602Z-04d8e6afb7/verification-plan.json`, SHA-256 `b8ae7a4d26a4adbff581e95916b6cbd3496baa5f9ced1e908358e4c445a34c78`; lane `CRITICAL`; required reviews `gate3`, `final`
- Reconstructible source: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T000602Z-04d8e6afb7/snapshot/source.patch`, SHA-256 `a1fb1d6580019ac998092653886364c817f31f654b3be4d8dffde5436f1449ce`
- Gate 3: `reviews/tests/OTIZ-SAVED-INSTALLER-ALLOCATION-EXPLANATION-001.md`, final rereview verdict `APPROVED`
- Verdict: `APPROVED`

## Complete findings list

None.

## Review

- **Canonical behavior and saved-source boundary:** the view renders `report_date`, object `distributed_cents`, and each allocation's saved identity, `contribution_bp`, `effective_ktu_bp`, `share_bp`, `participation_basis`, and `amount_cents`. The change does not read current workforce or assignment data and does not add fallback behavior. The two-publication and real current-composition fixtures prove both directions of snapshot isolation.
- **Formatting, formula and legacy heuristics:** contribution/share use their own `/100` percent scale, KTU uses its own `/10000` coefficient scale, and money uses the saved cents. Worker amount is never recomputed from the object amount, share, contribution or KTU. The removed `effective_ktu_bp == 0`/basis-text workbook compatibility heuristic is not reproduced; saved zero remains zero.
- **Missing history and issue separation:** empty/whitespace basis and absent allocation rows receive the exact honest empty-state copy. Object issues remain rendered outside each worker `<details>` and are not presented as personal provenance.
- **Security and authorization:** the existing authorized snapshot GET remains the only public seam. Guest redirect and case-mismatched permission denial are covered with absence of identity, basis, distributed amount and worker amounts. Saved identity, tab and basis are escaped with `Html::encode`; numeric/date output is produced only from integer casts or a digit-only date match. No raw JSON, `inputs_json`, source payload or technical allocation column names are exposed by the new partial.
- **Read-only/history:** production changes are confined to rendering. There is no route, controller, persistence, command, POST, job, outbox or schema change. The focused test compares byte-equivalent inventories for snapshots, objects, allocations, issues, closures, OTIZ events, settlement operations, jobs and outbox intents after repeated authorized GET/browser interaction and denial.
- **#263 regression and integration:** allocation `<details>` is outside existing settlement forms and does not alter form actions, fields, `operationId`, recovery markup, JS submit flow, ledger or XLSX boundaries. Both retained PHP and browser recovery regressions are exact-source GREEN.
- **Accessibility/responsive behavior:** one native keyboard-operable `<details>` is rendered per worker. Browser coverage verifies independently scoped worker values, long basis wrapping, no page-level horizontal overflow, desktop/narrow interaction, empty state and absence of executable injected markup.
- **Architecture and scope:** the change reuses the existing selected-snapshot projection and Yii partial mechanism. It adds no dependency or second owner. The production diff is limited to `app/YiiRuntime/Views/_otiz-installer-allocation-details.php` and the worker block in `app/YiiRuntime/Views/otiz-snapshot.php`; the verification inventory registration is expected. Declared non-goals—writers, allocation/snapshot/ledger semantics, permissions, schema, XLSX, financial commands, shared assets, router, dashboard, calendar and checklist—are untouched.
- **Before/after record:** `docs/operations/issue-29-saved-installer-allocation-delivery.md` accurately records the former compact worker row, the new saved-fact disclosure, snapshot/current-composition isolation, XSS/long-text behavior, authorship, evidence, and the still-open remainder of broad issue #29. It does not claim merge, deployment or completion of the broad issue.

## Verification evidence reviewed

- `php tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php` — GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790294652277935000-4f9595b1ca8949e5b6e1ca9f1d5807ed.json`
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php` — GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790294652286191000-c7da4deb169c4d55b6a4dde9dcbafe63.json`
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php` — GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790294652277528000-7d4312d04960493a9ed7357e37dd6c88.json`
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` — GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790294695236350000-ab880c5e974f4818967b9a0720262927.json`
- `python3 tests/Verification/change_verification_001_test.py` — GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790294695232583000-f57c62dc77c840d8bfb85c5ae0f9f929.json`
- `php tests/Runtime/runtime_storage_001_test.php` — GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790294731841693000-6637bb9e3b10489aa52cfc829164afb1.json`
- `python3 tests/Verification/architecture_guard_001_test.py` — GREEN; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790294695255102000-53ee0cbbeeb14720a74c706e65ea50e5.json`

All listed records are bound to exact source `0d55b084db458e740203f6588f7ac2abf08904b7c2aabeffae5e15f9811872f7` and report GREEN. The canonical full `make test` was not run locally, in accordance with owner policy. Exact-source GitHub CI remains `UNKNOWN`/pending and is a publication/Done prerequisite; this code-review approval does not represent CI, PR, merge or deployment approval.

## Required changes

None. The reviewed exact-source implementation is approved for Gate 5. Any subsequent code, test, specification or verification-mapping change requires refreshed exact-source review as applicable.

---

## Final rereview after CI regression correction

- Rereviewer: independent `gpt-5.6-sol / low` agent `/root/issue29_final_review`; authored neither the correction nor tests/specification
- Prior approved source preserved above: `0d55b084db458e740203f6588f7ac2abf08904b7c2aabeffae5e15f9811872f7`
- Corrected exact source: `f7135c271121a92fe69420320d86b4c16e25e22f0eb9391aa6c092d9c350d4a6`; committed parent/head `2e83d5e82d860edb7f59a2777788c67c99cde94f`
- Prepared rereview package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T002907Z-ab0e861afe/package.json`
- Corrected snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T002907Z-ab0e861afe/snapshot/source.patch`, SHA-256 `61c60c6d9ff46ba61015bad73c0f44cfb28a5edd58e7463920d81a49c3a0ddb7`
- Delta from prior approved snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T002907Z-ab0e861afe/delta.patch`, SHA-256 `e48336bfa935fd2b79021c7613b8956bc28bef17daf50224bca1464ef83385fc`
- Refreshed verification plan SHA-256: `ca97e5e159b78666a437dcab701d890c1fe5063042a61dd9d30c3ccf6348a10f`; lane `CRITICAL`; required reviews `gate3`, `final`
- Rereview verdict: `APPROVED`

### Complete current findings list

None.

### Delta assessment

- The production delta is confined to `_otiz-installer-allocation-details.php`. It restores compact summary KTU context from that allocation's own `effective_ktu_bp`; it does not read current composition, another allocation, raw payload or another numeric field.
- For the retained legacy shape only—stored numeric KTU `0` plus exact saved basis text containing `коэффициент 1,00`—the summary says `1,00 (из сохранённого основания; числовой КТУ не сохранён)`. This is explicitly attributed compatibility context, not a claim that `effective_ktu_bp` stores 1.00. The canonical labelled detail continues to render the actual saved numeric KTU as `0,00`; saved zero is therefore not rewritten or hidden.
- The correction adds no distribution formula, normalization, rounding owner or amount derivation. Contribution, share, amount and canonical KTU details retain the previously approved independent saved-field formatting. The summary compatibility branch cannot affect `amount_cents` or any persisted fact.
- Summary content remains safe: identity and basis display remain escaped; the compatibility sentence is a fixed server-owned literal selected by `str_contains` and interpolates no saved markup. Authorization, denial, no-write/history, snapshot isolation, issue separation and #263 form boundaries are unchanged.
- No tests, canonical specification, verification mapping, route, controller, writer, schema, JS/CSS, form, ledger or XLSX behavior changed in the correction. The delivery-record delta preserves the failed first CI inventory and accurately records why a new exact-source CI run is still required.

### CI failure inventory and correction evidence

GitHub run `36076186569` on PR #269 head `2e83d5e82d860edb7f59a2777788c67c99cde94f` remains a failed historical attempt: `Integration (1/2)` and `e2e` failed, while `verify`/`Quality Graph` reflected the failure. The complete recorded regression inventory contained exactly the three retained presentation checks below. Each is now locally GREEN and exact-source-bound to `f7135c271121a92fe69420320d86b4c16e25e22f0eb9391aa6c092d9c350d4a6`:

- `php tests/Yii2/yii2_otiz_settlement_001_test.php` — GREEN, 17.779 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790296108241563000-3e3e2f9383df4b03b7db1486317dc3eb.json`
- `php tests/Yii2/yii2_otiz_settlement_browser_001_test.php` — GREEN, 22.329 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790296108224840000-a4a4c867de2e46988106636c9fbfe7ed.json`
- `php tests/Yii2/yii2_otiz_shlz_ui_001_test.php` — GREEN, 25.099 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790296108239036000-1237f6d3cef84502a2637c222c416ee8.json`

Mapped acceptance evidence is also fresh and GREEN on the corrected exact source:

- `php tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php` — GREEN, 19.549 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790296108204447000-3cecc442cabf44e5a3c3e7ab6f062020.json`
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php` — GREEN, 20.601 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790296108222915000-972846dc67894a46addd4c6708aa4243.json`
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php` — GREEN, 25.015 s; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790296108217785000-74bc5f3597cf4caeab8e21642483b792.json`

### Required changes

None. The corrected exact source is `APPROVED` for Gate 5. The failed first CI attempt is not GREEN; a new exact-source GitHub CI run for the corrected committed head remains required before Done/merge, and this rereview does not approve deployment or merge.
