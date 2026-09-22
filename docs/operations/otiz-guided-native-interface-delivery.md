# OTIZ guided native interface — delivery record

## Assignment and authorship

- Owner assignment: 2026-09-21, direct request in the active Codex session.
- Base: `origin/main` `c8bfc42d774bbe52a6528eb44a371c8a9003e6d3`.
- Worktree/branch: `/Users/antropophag/code/fmonitor-2-otiz-workflow`, `codex/otiz-guided-workflow`.
- Root authors: scope, OpenSpec artifacts, `OTIZ-GUIDED-WORKFLOW-001`, verification input and tests.
- Production executor and independent reviewers: recorded when dispatched.
- Publication constraint: owner stand review before PR; no merge/deploy/real financial action.

## Existing command and state mapping

| User stage | Canonical seam | Persisted result / refusal |
|---|---|---|
| Date and preparation | `POST /pilot/otiz/calculate` → `SnapshotPublication::buildAndPublish` | New immutable-content draft snapshot; operation replay returns the same id; invalid date/id/conflict creates no successful draft claim. |
| Review | `GET /pilot/otiz/snapshots/<id>` → `MariaDbOtizSettlementView::snapshot` | Read-only snapshot objects, allocations, issues, trace inputs and current global closures; GET adds no facts. |
| Confirmation | `POST /pilot/otiz/snapshots/<id>/accept` → `SnapshotPublication::accept` | `draft` → `accepted` only when published/complete/unblocked/current; blocker/stale/immutable refusal adds no acceptance fact. |
| Documents | `GET /pilot/otiz/snapshots/<id>/export.xlsx` → `workbook` | Real XLSX only for `accepted`; download does not record transfer or payment. |
| Payment registration | `POST .../payments/complete` → `OtizSettlement::completeSnapshotPayments` | Append-only closure rows for positive available amounts; replay/no-change is not a new payment. |
| Discipline | `POST .../closures` → `recordDiscipline` | Separate append-only discipline row with basis/artifact; not a payment. |
| Reversal | `POST /pilot/otiz/closures/<id>/reverse` → `reverse` | New row linked by `reverses_payment_closure_id`; original remains; not cancellation of snapshot. |

Snapshot status observed on current schema is `draft` or `accepted`; presentation MUST NOT infer persisted snapshot statuses `paid`/`reversed`. Current availability is a live ledger-relative value and is labeled separately from saved snapshot amounts.

## #222 overlap audit — updated 2026-09-22

#222 was merged through PR #226 into `origin/main` `3c242f34e8f30986f1b8354c4ef947a4c63936dc`. The OTIZ candidate was state-preservingly integrated onto that exact base. The public `FMonitor2\InstallationProcess\MariaDbEffectiveObjectDetails` read seam now exists; this slice does not duplicate it, call its persistence directly, or modify object-card editing/history/schema/imports or `MariaDbNativePremiumInputs*`.

Mechanical overlaps were reconciled in `.quality-graph/verification-policy.json`, `app/YiiRuntime/Assets/pilot.css`, `app/YiiRuntime/Controllers/PilotAssetController.php`, `app/YiiRuntime/ViewSupport.php`, `config/yii/assets.php`, and this current-goal record. Upstream's removed `delivery-4.svg` was not restored; OTIZ keeps only public icons that are actually mapped by `PilotAssetController`. `MariaDbOtizSettlementView` remains a snapshot/ledger presentation projection and does not establish a second effective-object-details owner. Source compatibility is established structurally; runtime semantics remain subject to bounded focused checks and independent exact-source review, not claimed GREEN here.

## Evidence state

- Selected prototype rendered with Playwright at 1440×1100 and 390×844: complete.
- Gate 1 contract and OpenSpec strict validation: complete.
- Verification plan: CRITICAL, required reviews `gate3` and `final`; local obligations are the mapped Yii/Playwright test, two adjacent OTIZ regressions, one e2e category check and unit/governance checks. Full integration remains exact-source CI only.
- Intended RED: `php tests/Yii2/yii2_otiz_shlz_ui_001_test.php` reached the browser seam and failed on the first new requirement: current tabs are `Экономика объектов / Подготовка выплат / Архив расчётов / Текущий расчёт`, while the contract requires exactly three work modes and label `Выполнение расчёта`. Earlier dependency failures were setup-only and are not RED evidence.
- Gate 3: `APPROVED` by independent `gpt-5.6-sol / low` reviewer for exact candidate `a0eaa07c9b98043acbccb7525690297c6e7f08b6cae00b1daeae7647aa895c53`; four correction rounds closed the full findings list. Review: `reviews/tests/OTIZ-GUIDED-WORKFLOW-001.md`.
- Gate 4 executor: separate `gpt-5.6-sol / low`; production files limited to the declared OTIZ views/assets/controller/read projection. Planner-selected local checks GREEN; one concurrent evidence attempt for two otherwise-GREEN stateful tests was correctly marked `UNKNOWN` for source drift and repeated sequentially GREEN. Full local suite was not run.
- Impeccable detector: `[]`. Playwright fixture desktop/narrow/keyboard journey GREEN.
- Local stand 8093: latest dirty candidate runtime image `sha256:f7d3f45fff20686ffb4cbeb85bac34f6d76bcfe69567165b1d0e7697b82464bc` installed state-preservingly; DB volume retained, php/web/jobs-worker/jobs-scheduler healthy. Read-only Playwright returned working authenticated pages for `/pilot/objects/1427`, `/pilot/otiz/objects`, `/pilot/otiz/payments`, `/pilot/otiz/history`; latest 1440px pages have no document overflow, all three tab bars have the same bounding box, and action cells render the public double-chevron without ellipsis dots. Screenshots/results: `/Users/antropophag/.local/share/fmonitor-2/otiz-guided-native-interface/stand-20260921/`, `stand-polished-20260921/`, `stand-recomposed-20260922/`, `controls-fixed-20260922/`, `stand-20260922/`.
- #222/PR #226 is integrated at base `3c242f34`; public `MariaDbEffectiveObjectDetails` is present and no duplicate mechanism was introduced. Bounded regression evidence and exact-source review remain pending after integration.
- The current integrated WIP contains the root-authored archive icon-link expectation. On base `3c242f34`, all eight planner-listed local obligations are GREEN, including `yii2_otiz_shlz_ui_001_test.php`; executor did not author or alter that test during integration.
- Detailed continuation record and ready-to-use next-session prompt: `docs/operations/otiz-guided-native-interface-handoff-2026-09-22.md`.
- Owner final stand approval: received 2026-09-22 after the integrated candidate was shown on stand 8093. Refreshed Gate 3 and Gate 5: `APPROVED` for source `13c9fa4b98f93bb8a06baf69a481edd5df3853f6bd6944c83eff8b03f2f8ad00`; the only later source changes before commit are append-only review records and delivery-state documentation. PR / CI: pending. No real financial POST was sent to the stand.
