# Delivery — issue #203, YII2-CALENDAR-003

- Base: `fa930ed7bd308ede3ab1b083f7e366ba451ecb98` (`origin/main`).
- Owner request: restore Yii calendar; FAST requested, planner result `planner_not_fast`.
- Authorship: root authored scope, OpenSpec, stable contract, verification input and RED tests. Separate `gpt-5.6-sol/low` executor authors production implementation. Independent reviewer authors Gate 5 verdict.
- Planner package after test registration: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T201333Z-9def5d555b/package.json`; plan SHA-256 `80fcdf0f051feb7ea409b47689c033c4f2c771bec1ebb30828f01cd0b51fbeb8`.
- Required local checks: calendar HTTP/browser, pilot jobs compose, change-verification, runtime storage, architecture guard. Full `make test` is CI-only.
- RED 2026-09-19: `php tests/Yii2/yii2_calendar_003_test.php` and `php tests/Yii2/yii2_calendar_003_browser_test.php` both reached fixture/server/login and failed at intended Yii `/pilot/calendar` `404` versus expected `200`.
- Gate 3: not required by planner. Gate 5/CI/PR: pending; UNKNOWN is not approval or GREEN.
