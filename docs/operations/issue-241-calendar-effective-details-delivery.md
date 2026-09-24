# Issue 241 delivery context

- Scope: issue #241 only, from audit base `b1542f92009b8dc4216a36962ff38a51e0b6c388`.
- Branch/worktree: `codex/issue-241-effective-calendar` in `/private/tmp/fmonitor-241-effective-calendar`.
- Owner authorization: autonomous delivery to PR-ready; no merge, deploy, stand or real-data changes.
- Root authors: OpenSpec artifacts, normative spec and tests.
- Executor and independent reviewer authors are recorded when their roles complete.
- Excluded: dates, #233, OTIZ, writers, history, schema, import, general UI, deployment and #45.

## Gate 2 RED

- Exact base: `b1542f92009b8dc4216a36962ff38a51e0b6c388` plus root-authored spec/test artifacts only; no production change.
- Isolated service: Compose project `fm2-issue241-test`, host port `25421`, project-scoped network/volume; worktree-local Composer `vendor/autoload.php`.
- Command: `FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=25421 FMONITOR_TEST_DB_ADMIN_USER=root FMONITOR_TEST_DB_ADMIN_PASSWORD=... php tests/Yii2/yii2_calendar_effective_object_details_001_test.php`.
- Intended failure: `INTENDED_RED effective address in inspection`, expected `true`, actual `false`. The request returned HTTP 200 and failed on stale legacy details, not setup.
- Gate 3 correction RED: after adding absent/null/empty/non-empty coverage for all three fields and an ordered event identity/date comparison, the refreshed test returned HTTP 200 and failed at `INTENDED_RED explicit null does not fall back in inspection` (expected `false`, actual `true`).
- Gate 3: initial `CHANGES_REQUESTED` findings were corrected; independent `/root/gate3_review` (`gpt-5.6-sol`, low) approved candidate source `e169e0bde10bbbd12729157edeb1f36a29aad05db0d2bf496b9f0c19dae3b0a2` with plan SHA `872a38853784df2d0c05a999572e6fe480c94d40ad0d9e3fda85323bab6aafec`.

## Gate 4 GREEN

- Executor: `/root/executor` (`gpt-5.6-sol`, low); production author of `app/InstallationProcess/MariaDbYiiObjectQueue.php` only.
- Implementation: both existing bounded calendar queries join the single current edits table and call `MariaDbEffectiveObjectDetails::sqlValue()` for address, entrance and registration number. No new resolver, date/order/limit/writer/schema change.
- Isolated Compose project/port: `fm2-issue241-test` / `25421`; worktree-local `vendor/autoload.php`; Playwright official ESM entry `/Users/antropophag/code/shlz-ui/node_modules/playwright/index.mjs`.
- GREEN: new effective-details HTTP/browser acceptance; existing object card; existing object registry; existing calendar HTTP; existing calendar browser; 18 change-verification tests; 59 architecture guard tests; PHP lint and diff check.
- Full local `make test` / `make verify` was not run.
- Gate 5: independent `/root/final_review` (`gpt-5.6-sol`, low) APPROVED source `bc947d0eaf2b3e3b1c10fa4a569059c89bd4491988bf7ccc99dc4dff9da4ddbd`, plan SHA `86416d3b7f080c47b8e12a1cd2863cb4f2adfdd22df73b337d22d8bdfb7cb142`, with no findings. The review record is the sole reviewer-authored repository addition.
