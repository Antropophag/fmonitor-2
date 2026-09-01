# Workforce canonical runner v5 regression test alignment

Date: 2026-09-01
Scope: inherited public-runner and harness expectations superseded by `WORKFORCE-CANONICAL-RUNNER-001 v0.1`
Production code changed: no

## Alignment

- Updated the migration fixture contracts in `pilot_case_import_001_test.php` and `pilot_http_auth_001_test.php` from schema version 4 / applied versions `[1,2,3,4]` to version 5 / `[1,2,3,4,5]`.
- Updated `production_migration_runner_001_test.php` to require the canonical v1-v5 result and all 11 canonical tables, while retaining the exact intermediate v3/v4 failure assertions where v5 is intentionally unreachable.
- Extended the independent literal `ProductionMigrationRunnerCatalogContract` with the approved v5 workforce columns, indexes, checks, and foreign keys. It still does not load migration classes or production SQL.
- Updated the OTIZ compatibility harness to preserve and verify all canonical v1-v5 tables and accept only a unique applied-version subset of `[1,2,3,4,5]`.
- Replaced the unavailable-database fixture's over-ceiling generated prefix with the valid `unavailable_` prefix, so the case reaches connection setup and continues to prove exact `DATABASE_UNAVAILABLE` behavior.
- Changed the workforce sentinel insert to name its legacy columns explicitly, preserving the fixture meaning after the additive v5 catalog columns.

## Verification evidence

- `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php` — PASS.
- `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_case_import_001_test.php` — PASS.
- `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/Verification/harness_otiz_canonical_compat_001_test.php` — PASS.
- `pilot_http_auth_001_test.php` passed its updated v5 migration fixture and then reached the pre-existing CSP mismatch (`script-src 'self'`).
- `git diff --check` — PASS.
- `make architecture-check` — PASS (`6 rules`).
- `openspec validate register-workforce-history-canonical-v5 --strict` — PASS.
- `make verify`:
  - test DB reset, canonical v5 migration, architecture, lint, unit, characterization, and diff-check stages — PASS;
  - DB stage — the same eight known regressions remain;
  - E2E stage — the same `pilot_e2e_flow_001_test.php` regression remains and is already included in those eight;
  - no v5-alignment regression remains.

## Remaining baseline regressions (unchanged count: 8)

1. `pilot_demo_bootstrap_001_test.php` — inherited `pilot_shlz_assets_001_test.php` CSP mismatch.
2. `pilot_e2e_flow_001_test.php` — expected isolated artifact fault currently escapes as `ArtifactNotFoundException`.
3. `pilot_http_auth_001_test.php` — CSP expectation omits current `script-src 'self'`.
4. `pilot_object_card_001_test.php` — broad reader receives 403 instead of 200.
5. `pilot_object_list_001_test.php` — collection route receives 403 instead of 200.
6. `pilot_prepare_form_001_test.php` — form route receives 403 instead of 200.
7. `pilot_shlz_assets_001_test.php` — CSP expectation omits current `script-src 'self'`.
8. `pilot_ui_shell_001_test.php` — shell receives 403 instead of 200.

## Review state

Independent test review is required before this alignment is accepted. The author of this record must not review these changes.
