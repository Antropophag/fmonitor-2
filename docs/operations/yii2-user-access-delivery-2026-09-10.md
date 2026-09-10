# Yii2 user access — #76

## Current candidate

Root prepared complete YII2-USER-ACCESS-001 specification, dependency matrix,
owner/HTTP, concurrency and browser tests before implementation. Separate sol/low
executor and independent reviewer follow. Current owner authorization is the
2026-09-10 autonomous #82 → #76 assignment. #82 is delivered (PR84).

Pre-Gate2 verification plan: `openspec/changes/yii2-user-access/verification-input.json`
→ `.local/verification/76-plan.json`; all e2e/governance/integration/unit obligations
read. No schema change (v24), no new dependency, no stand switch. Isolated DB on
existing test service23306, random database/user per fixture, DML-only application
connections. Composer lock installed with repository-owned bootstrap.

## RED

- `php -d display_errors=0 tests/Yii2/yii2_user_access_001_test.php`:
  intended guest admin route returns404 instead of303/login.
- `php -d display_errors=0 tests/Yii2/yii2_user_access_concurrency_001_test.php`:
  intended public YiiUserAccess application owner absent.
- `php -d display_errors=0 tests/Yii2/yii2_user_access_browser_001_test.php`:
  real browser login works, users route returns404 instead of200.

Logs: `/tmp/76-user-access-final-red.log`, `/tmp/76-concurrency-final-red.log`,
`/tmp/76-browser-final-red.log`. Tests individually run on isolated fixtures.
All are intended failures after working setup. The later browser asset assertion
is additive to the same missing-route RED, recorded before review.

Inventory registration and historical-baseline additions are included upfront;
`python3 tests/Verification/verification_inventory_001_test.py`: 15/15 GREEN.
PHP/JS syntax checks passed. No implementation or full CI yet.

## Remaining #76

Current Yii routes: auth/logout/roles/health/assets and OTIZ settlement/read/export.
Production still uses public/runtime.php → rapid router; no cutover occurred.
After this family: object queue/card/process/inspection/completion, remaining OTIZ
build/accept/reconciliation, console/jobs/imports, runtime retirement and rollout.
Read-only independent audit `/root/yii76_inventory` confirmed the broad remaining
map and object queue's hidden schema/bootstrap edge. This is not full #76 completion.
