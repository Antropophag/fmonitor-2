# Handoff — construction-control responsive list and inspection planning

Date: 2026-09-25 (Europe/Moscow). Owner requested a session transition before PR/merge. This file is the continuation entry point; preserve the working stand and all uncommitted history.

## Exact workspace state

- Worktree: `/Users/antropophag/code/fmonitor-2-control-list-delivery`
- Branch: `codex/construction-control-mobile-actions`
- Branch HEAD/base: `403a57bded679ec79819a2833f6ed50832f01d86`
- Current `origin/main` observed at handoff: `7aa2951d` (`Merge pull request #273 from Antropophag/codex/expose-dashboard-all-roles`)
- Worktree is intentionally dirty and uncommitted. Do not discard or overwrite it.
- The branch now trails `origin/main`; before publication, fetch and reconcile/rebase onto the then-current `origin/main`, preserving every change in this worktree and re-running focused verification.
- No PR exists for this delivery. Nothing has been pushed or merged.

## Owner-approved product/UI decisions

1. Construction-control records are not globally clickable. Only the dedicated right checklist rail opens the checklist.
2. Mobile records are compact. Address/entrance/registration/factory number remain visible; long real addresses wrap.
3. Technical documents use a circular action (disabled when unavailable), not a factory-number link.
4. No-plan inspection is primary blue; planned inspection is outlined. `Инспекция сегодня` omits the date.
5. Shipment marker and colored local-sync marker are retained.
6. `Готов к открытию` can schedule an inspection: the opening visit is also an inspection. Eligible planning states now include `working`, `needs_assignment_change`, `needs_assignment_order`, and `assignment_order_prepared`, with a unique current native engineer and capability.
7. The inspection dialog uses the public `shlz-ui` DatePicker. On mobile the redundant footer Close is hidden; the round header Close remains; primary submit stays inside the visual viewport. Create does not show `Отменить инспекцию`; reschedule does.
8. Phone `<=680px`: bottom navigation.
9. Tablet `681–1180px`: compact 64px side rail by default; expansion is a 260px overlay drawer. The drawer MUST NOT resize/reflow the workspace/table. It closes on outside click, Escape, and navigation; focus returns to the trigger.
10. Desktop `>=1181px`: persistent collapsible sidebar using saved state.

## Current implementation

Production changes are in:

- `app/InspectionEvidence/MariaDbYiiChecklistRead.php`
- `app/InstallationProcess/MariaDbYiiInspectionPlanning.php`
- `app/YiiRuntime/Assets/control-queue.js`
- `app/YiiRuntime/Assets/inspection-schedule.js`
- `app/YiiRuntime/Assets/navigation.js`
- `app/YiiRuntime/Assets/pilot.css`
- `app/YiiRuntime/Views/construction-control.php`

Root-authored specifications/tests and lifecycle material are listed by `git status`. Canonical new contract: `specs/YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001.md`. OpenSpec change: `openspec/changes/redesign-construction-control-mobile-list/`.

Authorship: root wrote/updated specs and tests. Production implementation was performed by `/root/implement_mobile_list` (gpt-5.6-sol/low). Gate 3 test review was independently performed by `/root/gate3_mobile_list`; record: `reviews/tests/YII2-CONSTRUCTION-CONTROL-MOBILE-LIST-001.md`. Because tests/specs changed after the earlier Gate 3 approval, the exact final source still needs the planner-required independent reviews before publication.

## Working stand

- URL: `http://127.0.0.1:8093/pilot/construction-control?ownership=all&completed=1`
- Compose project: `fm2-local-timofey`
- Main containers: `fm2-local-timofey-php-1`, `fm2-local-timofey-web-1`
- Database volumes were preserved. Forward migrations 34 and 35 were applied.
- A base/overlay image workaround exists because a clean floating PHP build hit PHP 8.5/PECL `pdo_sqlsrv` incompatibility:
  - overlay Dockerfile: `/tmp/fm2-control-list-overlay.Dockerfile`
  - base image: `fmonitor2-runtime:stand-base`
  - overlay/current image tags: `fmonitor2-runtime:control-list-local`, `fmonitor2-runtime:local`
- Normal compose recreation currently requires unavailable ERP/bootstrap variables. Latest production files were safely copied into the running PHP container with `docker cp`; asset hashes were checked after copying.
- Stand data includes real imported objects and user 1 has coordinator capability for validation.

Latest visual evidence:

- phone navigation: `/tmp/fm2-fixed-mobile-nav.png`
- tablet closed rail: `/tmp/fm2-tablet-drawer-closed.png`
- tablet open overlay drawer: `/tmp/fm2-tablet-drawer-open.png`
- tablet DatePicker/modal: `/tmp/fm2-fixed-tablet-modal.png`

At handoff, 1032×1376 measurement showed:

- closed sidebar: 64px; open drawer: 260px;
- workspace before/after: identical `x=64`, `width=968`;
- checklist rail before/after: identical `x=949.546875`;
- document `scrollWidth=1032` (no horizontal overflow).

## Verification already GREEN

Do not run full local `make test` / `make verify`; owner constitution forbids it. Focused checks that passed during the final iterations include:

- `php tests/Yii2/yii2_construction_control_mobile_list_browser_001_test.php`
- `php tests/Yii2/yii2_construction_control_mobile_list_001_test.php`
- `php tests/Yii2/yii2_inspection_planning_ui_255_browser_test.php`
- `php tests/Yii2/yii2_inspection_planning_002_test.php`
- `php tests/Yii2/yii2_construction_control_active_queue_001_test.php`
- `php tests/Yii2/yii2_sidebar_state_icons_001_test.php`
- relevant PHP/JS syntax checks and `git diff --check`
- real-stand invalid-date POST to `/pilot/construction-control/objects/180/inspection-plan` returned expected 422 rather than 404
- real-stand browser console was clean after the ready-row sync fix
- Impeccable detector returned `[]` before the final navigation iterations; rerun once on exact final UI source after reconciliation.

## Remaining path to PR and merge

1. Start by reading repository `AGENTS.md`, `docs/operations/current-delivery-goal.md`, this handoff, `PRODUCT.md`, `CONTEXT.md`, pilot specs/data model, and `docs/development-process.md`.
2. Run `python3 tools/delivery/harness.py state` in this worktree. The previously injected harness binding in the original checkout concerned unrelated issue #157; do not confuse it with this delivery. Prepare/rebind the exact construction-control source as required by the repo workflow.
3. Confirm owner validation of the latest tablet drawer and Safari DatePicker on the working stand. The owner has not yet explicitly approved the final drawer iteration.
4. Fetch current `origin/main`; inspect new commits and reconcile this dirty worktree onto the exact latest main without losing local files. Do not use destructive reset/checkout.
5. Re-run bounded focused tests, including the mandatory pilot HTTP auth check if any `app/PilotHttp/*.php` boundary becomes touched. Do not run the prohibited local full suite.
6. Run one final Impeccable detector pass and real browser screenshots at phone 402×874, tablet 1032×1376 (closed/open drawer), and desktop 1440×900. Safari/device validation remains important because Chromium initially missed Safari-specific visual defects.
7. Obtain planner-required independent Gate 3/final reviews on the exact reconciled source. Preserve reviewer independence.
8. Commit with authorship/history records, push branch, create PR, and run the selected exact-source GitHub CI full matrix once. Inventory every failed job before any correction.
9. Merge only after owner approval, required reviews, and exact-source CI GREEN. Report any UNKNOWN honestly.

## Immediate cautions

- Do not touch the original dirty checkout `/Users/antropophag/code/fmonitor-2`; work only in the delivery worktree above.
- Do not remove the document/shipment/sync states while changing responsive layout.
- Do not restore whole-row click handling.
- Do not regress preopening inspection scheduling.
- Do not treat Chromium screenshots as sufficient proof for Safari DatePicker or safe-area behavior.
- Do not create the PR before the owner validates the current stand.
