# Delivery — issue #203, YII2-CALENDAR-003

- Base: `fa930ed7bd308ede3ab1b083f7e366ba451ecb98` (`origin/main`).
- Owner request: restore Yii calendar; FAST requested, planner result `planner_not_fast`.
- Authorship: root authored scope, OpenSpec, stable contract, verification input and RED tests. Separate `gpt-5.6-sol/low` executor authors production implementation. Independent reviewer authors Gate 5 verdict.
- Planner package after test registration: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T201333Z-9def5d555b/package.json`; plan SHA-256 `80fcdf0f051feb7ea409b47689c033c4f2c771bec1ebb30828f01cd0b51fbeb8`.
- Required local checks: calendar HTTP/browser, pilot jobs compose, change-verification, runtime storage, architecture guard. Full `make test` is CI-only.
- RED 2026-09-19: `php tests/Yii2/yii2_calendar_003_test.php` and `php tests/Yii2/yii2_calendar_003_browser_test.php` both reached fixture/server/login and failed at intended Yii `/pilot/calendar` `404` versus expected `200`.
- Gate 3: not required by planner. Gate 5/CI/PR: pending; UNKNOWN is not approval or GREEN.

## Gate 4 evidence — 2026-09-19

- Executor: independent `gpt-5.6-sol/low` agent `/root/calendar_executor`; root later removed a worktree-only autoload workaround, moved projection access through the registered `yii-object-read-presentation` owner, and added selected-day focus/fragment behavior after visual inspection.
- `php tests/Yii2/yii2_calendar_003_test.php`: PASS.
- `php tests/Yii2/yii2_calendar_003_browser_test.php`: PASS; inspected desktop/mobile screenshots under `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-preopening-5e862f5b28cd/` and a preceding selected-focus capture.
- Consumer frontier: `yii2_object_card_001_test.php`, `yii2_object_queue_001_test.php`: PASS.
- Planner obligations: `pilot_jobs_compose_001_test.py`, `change_verification_001_test.py` (18), `architecture_guard_001_test.py` (59): PASS.
- `make architecture-check`: PASS, 7 rules; only file-size advisories, including the existing queue owner growing to 172 lines.
- PHP syntax, `git diff --check`, and Impeccable detector: PASS / no findings.
- Full local `make test`/`make verify`: not run by owner policy. Exact-source CI remains pending.

## Gate 5 return 1 — 2026-09-19

- Independent reviewer `/root/calendar_gate5`, package `20260919T203931Z-99b7f7b325`, verdict `CHANGES_REQUESTED`.
- P1: repeated scalar `date` was collapsed by PHP/Yii before validation. Correction validates multiplicity from raw `QUERY_STRING`; root added the exact repeated-scalar regression.
- P1: missing acceptance evidence for schema failure and bounded overflow. Root added real HTTP cases for 5,001 in-range rows and an incompatible schedule schema, including `503`, safe/no-partial response and byte-equivalent facts/schema.
- Visual finish: approved; shlz-ui fidelity, hierarchy, selected/today states, mobile containment and agenda usability had no blocking findings.

## Gate 5 rereview — 2026-09-19

- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T204600Z-5391b117aa/package.json`; exact source `0b023d324dc72919cf697eeef85c6f212faaf5580991d68afcfdc8bb00b509bf`.
- Seven source-bound focused records GREEN, including corrected HTTP acceptance and browser rendering.
- Independent reviewer `/root/calendar_gate5`: `APPROVED`; both prior P1 findings resolved, no correction-delta regression.
- Exact-source CI remains pending/UNKNOWN until the PR-triggered run completes.

## Exact-source CI attempt 1 — failed and triaged

- Run `35468465778`, SHA `b60db0948f437395314f51b7058c8c79e7c6af4a`; complete failed-job inventory: `governance`, `Integration (2/2)`, aggregate `verify` and `Quality Graph` only. Plan, fast, unit, e2e and Integration (1/2) passed; harness skipped as designed.
- Complete `REGRESSION_FAILURE` inventory: `tests/Verification/registered_yii2_focused_bootstrap_001_test.py` (two assertions) and `tests/Yii2/yii2_main_navigation_001_test.php` (one assertion). No other regression failure was recorded.
- Root cause: the newly visible Calendar link was absent from the existing shared-navigation permission matrix; the focused-bootstrap governance oracle also rejected the valid `semantic integration closure` rationale introduced by the protected navigation owner.
- Correction: add Calendar to every applicable navigation phase/order/group/icon/current-route matrix and allow only the known closure rationale in addition to the two mandatory direct reasons. Focused navigation profile passes. A fresh exact-source run is required because source changed; this is not a same-source retry.
