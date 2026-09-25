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
