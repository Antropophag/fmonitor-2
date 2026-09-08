# Независимый Gate 3 review: ATTEMPT-AUDIT compatibility patch v1

- Дата review: `2026-09-06`
- Reviewer: отдельно назначенный agent `/root/data_transport_gate1`
- Unapplied patch SHA-256: `f2fdb97f00eab364867f3e6af782ca0b40e972afa7b1e9a5553c04fa2df6b9c4`
- Companion SHA-256: `426101221aff8c1a5896459c3570fbdd5a29d7b538884589fc0c12495276b1da`
- Candidate source HEAD: `2470126a4a5dccea6bd765c9590353fe5045a9b5`
- Verdict: **APPROVED**

Reviewer не писал patch, tests, helper или production source. Patch проверен в
неприменённом состоянии; `git apply --check` проходит. Production и protected E2E
не входят в шесть targets и byte changes не получают.

## Exact scope

Patch меняет пять существующих tests и добавляет один TEST-only helper:

```text
tests/InstallationProcess/assignment_order_original_data_attempt_clock_001_test.php
tests/InstallationProcess/assignment_order_original_safe_log_isolation_001_test.php
tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php
tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
tests/Support/ProductionOriginalAuditCatalogV13.php
tests/InstallationProcess/production_migration_runner_001_test.php
```

Denial clock expectation меняется только для теперь обязательной audit attempt:
denied получает один instant, invalid/auth unavailable/terminal unavailable/
composition unavailable/replay сохраняют clock0. Repository attempt, fresh open и
stream ownership assertions не ослаблены.

Lifecycle и safe-log tests сохраняют прежние literal traces и добавляют только
нормативный последний diagnostic: `file_failure`, `submission` либо `terminal`.
Abort/close/release/commit/audit/delivery counts и выбранные Result остаются
прежними. Cases с throwing logger всё ещё доказывают isolation; actual trace не
фильтруется и failure не превращается в skip.

Upload authorization fixture получает explicit confirmed test audit port только
для двух denial examples. Старые capability/result/no-stream-read assertions
сохранены; новые assertions независимо проверяют audit count и точные request,
actor, mode. Test port не подменяет production API и не используется для
file-failure success.

## Canonical v13 independence

Новый `ProductionOriginalAuditCatalogV13` расширяет прежний canonical v12 oracle
из независимых TEST-side `AssignmentOrderOriginalDatabaseSetupV1` literals. Он
не вызывает production DDL/expected/fingerprint helpers. Ожидаемые columns,
charsets, complete indexes, foreign-key multiset и CHECK tuples включают семь
original tables, audit-v3 removal/delta и exact capability-v5 literals/name.
Physical metadata сравнивается целиком; FK и CHECK collections сортируются как
полные multisets без отбрасывания элементов.

TEST normalizer сохраняет bytes внутри quoted literals, fold/compact применяет
только unquoted SQL, а встроенные sensitivity assertions отдельно проверяют
space и backtick. Existing valid formatting/permutation и near-match
case/whitespace/extra/duplicate/operator controls сохранены на completed v5.
Standalone migration v3/v4 tests не меняются. Expected schemaVersion/applied
versions изменены строго с12 на13, включая recovery; clean/repeat и full catalog
no-op проверки остаются.

## Evidence

Final canonical candidate archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-attempt-compat-v3-k5rspwyx`.

```text
1c80e27859b22af8920a6fef4f07564d91a64bcc1a390a9a882a632544ab927d  manifest.json
05e641aaf4bdbe3dccf7390221048d6a51c6d1adeae1aae28b5058296097e521  evidence.json
ea454c934554ff7015ce733b5d4ffcbfbddf5a6441fa05a2431a5844e29f8d03  00.log
```

Manifest фиксирует before/after hashes всех шести files. Canonical runner PASS
на source2470126; evidence `complete=true`, exit0 и `sameSourceAfter=true`.

Четыре остальных after hashes совпадают с earlier completed candidate archive
`original-attempt-compat-v1-k36bi1g3`, где attempt-clock, safe-log isolation,
command lifecycle и upload validation проходят. Quote-only source correction
между DD15 и2470126 не затрагивает их application dependencies. Первый
canonical ordering mismatch сохранён как неавторитетный failed candidate; final
v3 повторно прошёл после полного FK sorting и quote-preserving TEST oracle.

**APPROVED** разрешает применить только exact patch SHA выше. После применения
нужен единый clean exact-SHA regression run и Gate 5. Решение не утверждает
production implementation, combined command, full VERIFY_OK или launch.
