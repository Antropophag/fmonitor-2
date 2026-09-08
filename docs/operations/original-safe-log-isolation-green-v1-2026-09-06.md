# Original command diagnostic isolation GREEN v1

Implementation SHA: `3583ef866be64765017b995f80d4d1c64b8db695`, unchanged through all28 commands.
Gate1 and Gate3 APPROVED before production change; all13 diagnostic-isolation cases now PASS with unchanged oracles.

Minimal change: one guarding observer at Dependencies construction, plus eager direct import. Existing command flow and direct opened owner errors remain unchanged. Generic observers get no binding callback; failed binding suppresses only current invocation; each record Throwable is swallowed once without suppressing later independent diagnostic attempts.

All20 original command scripts, three supporting production tests, architecture7, unit, lint, OpenSpec strict and diff-check PASS. No baseline/hotspot growth. This is focused GREEN, not full make verify/VERIFY_OK or combined Gate5.

Private archive: `/Users/antropophag/.local/state/fmonitor2-verification/original-combined-green-475ujgo9`. Evidence JSON SHA256 `d3d33705d66572b5b490134b44f103f06d2f00e0ee82d66f12cbba5bc3b843a5`. Includes exact source/test manifests, command exits, timings, raw log hashes.

| Command | Exit | Seconds |
| --- | --- | --- |
| `php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php` | 0 | 3.553 |
| `php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php` | 0 | 95.508 |
| `php tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php` | 0 | 0.22 |
| `php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php` | 0 | 0.067 |
| `php tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php` | 0 | 0.073 |
| `php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php` | 0 | 0.293 |
| `php tests/InstallationProcess/assignment_order_original_maintenance_001_test.php` | 0 | 0.212 |
| `php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php` | 0 | 0.679 |
| `php tests/InstallationProcess/assignment_order_original_pdf_parser_incremental_001_test.php` | 0 | 0.055 |
| `php tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php` | 0 | 0.113 |
| `php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php` | 0 | 0.503 |
| `php tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php` | 0 | 0.076 |
| `php tests/InstallationProcess/assignment_order_original_safe_log_owner_001_test.php` | 0 | 0.06 |
| `php tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php` | 0 | 2.269 |
| `php tests/InstallationProcess/assignment_order_original_upload_001_test.php` | 0 | 0.11 |
| `php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php` | 0 | 0.053 |
| `php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php` | 0 | 0.075 |
| `php tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php` | 0 | 0.271 |
| `php tests/InstallationProcess/assignment_order_original_worker_protocol_001_test.php` | 0 | 0.66 |
| `php tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php` | 0 | 8.585 |
| `php tests/InstallationProcess/process_command_authorization_001_test.php` | 0 | 0.347 |
| `php tests/InstallationProcess/production_migration_runner_001_test.php` | 0 | 5.857 |
| `php tests/InstallationProcess/production_composition_001_test.php` | 0 | 0.215 |
| `make architecture-check` | 0 | 20.882 |
| `make unit-test` | 0 | 12.654 |
| `make lint` | 0 | 31.214 |
| `openspec validate replace-pilot-registration-with-original-upload --strict` | 0 | 0.904 |
| `git diff --check f0862b0..HEAD` | 0 | 0.034 |
