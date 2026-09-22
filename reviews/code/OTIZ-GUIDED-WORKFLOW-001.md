# Gate 5 final candidate review: OTIZ-GUIDED-WORKFLOW-001

- Reviewer: independent `/root/otiz_reviewer`; authored neither specification, tests nor production.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T002058Z-2b9117df14/package.json`.
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`.
- Exact candidate source: `d0381212115f669ace6adf7b491c7f58336c7c81161cd6a714fd621a5d2495c6`; executable source `538759dcdc664c8a606b288858c2e6fde13f40ede02d03d8a0c550a72ecf4306`.
- Snapshot patch SHA-256: `faae709a0f553171f0fa58a91e10f9262b2c0265e75f4ba11c1d0f50f08d5ba8`.
- Verdict: `CHANGES_REQUIRED`.

## Complete findings

1. **HIGH — every archive/list request performs an unused unbounded full-detail query cascade.** `app/Otiz/MariaDbOtizSettlementView.php:18-21` first obtains all snapshots, then calls `snapshot()` for every row and attaches `objects`. `snapshot()` (`:10-15`) loads all objects and, for every object, separately queries global closures, issues and allocations, plus another closure query for the snapshot. Neither `app/YiiRuntime/Views/_otiz-snapshot-list.php` nor the overview/controller consumers read `row['objects']`. Consequently `/pilot/otiz/payments`, `/pilot/otiz/history` and the economy page pay roughly one plus `snapshots × (objects × 3 + 2)` queries and materialize all historical detail merely to render list metadata; growth of immutable snapshot history can make ordinary page loads time out or exhaust memory. Remove the unused detail loop and return only the aggregate metadata needed by the list (or introduce an explicitly bounded detail consumer if one is genuinely required), then add a query-count/boundedness regression with multiple snapshots and objects.

2. **HIGH — the exact candidate's canonical contract contradicts its owner-approved economy implementation/test.** `specs/OTIZ-GUIDED-WORKFLOW-001.md:22` requires separate paid and withheld columns, while `app/YiiRuntime/Views/otiz.php` and `tests/Yii2/otiz_shlz_ui_browser.mjs:82-86` deliberately combine them under the visible «Выплачено» column. This is the same blocking coherence defect recorded by the refreshed Gate 3 review. Update the normative requirement to the owner-approved representation; do not revert the stand-approved layout.

3. **MEDIUM — the prepared final-review evidence is incomplete for its own selected local obligations.** `package.json` selects eight local commands but contains exact-source records for only five PHP commands. The reviewer independently obtained GREEN for `tests/Verification/change_verification_001_test.py` (18 tests) and `tests/Verification/architecture_guard_001_test.py` (59 tests). `tests/Deployment/pilot_jobs_compose_001_test.py` had no package evidence and the diagnostic attempt was stopped after its Docker build produced no output for over two minutes; this is `UNKNOWN`, not GREEN. Capture a completed exact-source record for that selected obligation in the corrected package. Do not substitute this diagnostic interruption for a failure or repeat the forbidden full local suite.

## Assessment

The archive icon action is a real accessible exact-snapshot link and remains functional without behavior JavaScript. The five supplied exact-source focused records are GREEN, the snapshot digest matches the package, `git diff --check` and PHP lint for the changed views are GREEN, and the two bounded governance checks above are GREEN. CI, PR, merge and deployment remain `UNKNOWN` and are not implied by this review.

## Required correction

- Remove the unused full-detail loading from `snapshots()` and add a boundedness/query regression.
- Reconcile the canonical economy-column wording with the owner-approved combined display.
- Complete the missing selected compose obligation, rebuild the exact-source package, and return the whole corrected candidate for independent Gate 3/Gate 5 rereview.

---

## Correction round 1 — 2026-09-22

- Reviewer: independent `/root/otiz_reviewer`; still authored neither specification, tests nor production.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T004659Z-b9bd681f5e/package.json`.
- Base: `3c242f34e8f30986f1b8354c4ef947a4c63936dc`.
- Exact candidate source: `13c9fa4b98f93bb8a06baf69a481edd5df3853f6bd6944c83eff8b03f2f8ad00`; executable source `b57ccb1eacf43ffe4e13c0008a6d4acec98701fb6ba9f873545beb2a8d3be09c`.
- Snapshot patch SHA-256: `8a635d0ad1f77c2dd2e0b5f6570fa40ccfa3aa06f202924588b1dcc7e0e3df48`.
- Verdict: `APPROVED`.

### Prior finding disposition

1. **Prior HIGH (unused unbounded archive query cascade): RESOLVED.** `MariaDbOtizSettlementView::snapshots()` now returns the single aggregate metadata query directly; it no longer invokes `snapshot()` or attaches unused object detail. The root-authored regression in `tests/Otiz/snapshot_publication_001_test.php:156-164` uses a multi-snapshot/multi-object fixture, asserts two session questions for the measurement sequence and proves that no `objects` field is materialized. The exact-source record is GREEN. The earlier implementation produced 62 questions against the same expected 2, so the test is sensitive to the defect rather than merely checking output.

2. **Prior HIGH (canonical contract contradicted owner-approved economy table): RESOLVED.** Requirement 8 now explicitly specifies the combined visible paid/withheld cell and matches both the owner decision and existing UI/test. Production presentation was not changed.

3. **Prior MEDIUM (three missing local evidence records): RESOLVED.** The corrected package contains eight GREEN evidence records matching source `13c9fa4b…` and executable source `b57ccb1e…`, including `pilot_jobs_compose_001_test.py` (exit 0, 38.57 s), `change_verification_001_test.py`, and `architecture_guard_001_test.py`. `missing_tests=[]`.

### New delta risk assessment

No new finding. The production correction removes only data that no list consumer reads and retains the aggregate author/object/blocker/warning metadata. The test-only Yii connection observes query count on its own fixture session and closes before fixture teardown. The canonical wording change records an explicit owner decision without broadening financial semantics. The five behavior-focused PHP/browser checks and all three category checks are exact-source GREEN.

CI, PR, merge and deployment remain separate and `UNKNOWN`; this approval does not imply their completion.

### Required changes

None.

---

## CI correction review — 2026-09-22

- Reviewer: independent `/root/otiz_reviewer`; authored neither correction tests nor production.
- Prior approved package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T004659Z-b9bd681f5e/package.json`.
- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T015854Z-0f73f3c4c7/package.json`.
- Exact source: `1636023072f1a1af9437813378ddd33ff4b9464a7039a31c0511af3c7d138653`; executable source `596003f3a01b1b7ddc6fe058083f1f123f0f0f228f421ac303d203e8bae998ea`.
- Base commit: `faddb58393a22e4e61a8c7476d24898cece08753`; snapshot patch SHA-256 `6f2565518d4bbd51f1b1cb52154f85b40103e7c251d52fe357766287c39d5c00`.
- Verdict: `APPROVED`.

### Delta and prior approval disposition

No findings. The full delta from the prior approved snapshot contains only two production corrections, adjacent tests/support hashes, and lifecycle/review state:

1. `otiz.js` now clears `fm2.otiz.publication` whenever an OTIZ page is reached with `created=1`, before checking for the calculate form. This preserves the stored key during uncertain response/retry, clears it only after the canonical success redirect is observed, and prevents a later calculation from replaying the completed snapshot.
2. `otiz-snapshot.php` restores a neutral status flash for `reversed=1`. It does not describe reversal as a new payment and leaves the append-only ledger as the fact owner.

The browser regressions bind both outcomes through real public routes and persisted facts. Navigation, DatePicker, drawer/detail selectors and asset digest updates are compatibility corrections to the approved presentation, not new product behavior. No formula, schema, authorization, command, ledger or effective-values owner changed; no scope drift was found.

The package contains nine matching exact-source GREEN records. Retained first-attempt failures for compose (external Docker registry manifest timeout) and governance (nested local harness exceeded its 20-second test timeout after the other 17 tests passed) have explicit same-source reasons and successful retries; neither failure points to the corrected product behavior.

The earlier CI run `35673629867` remains failed and is not converted to GREEN by this review. A new exact-HEAD CI result is still required before merge readiness.

### Required changes

None.
