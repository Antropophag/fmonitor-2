# WORKFORCE-CANONICAL-RUNNER-001 v5 regression alignment — test review

- Дата: 2026-09-02
- Reviewer: независимый test-review agent `workforce_v5_regression_test_review_20260902ab`
- Scope: выравнивание существующих regression fixtures с approved `WORKFORCE-CANONICAL-RUNNER-001 v0.1`
- Verdict: `APPROVED`

## Проверенное соответствие

- Публичные успешные вызовы canonical runner теперь ожидают exact v5 result:
  `schemaVersion:5`, clean `appliedVersions:[1,2,3,4,5]`, repeat
  `appliedVersions:[]`; canonical catalogue проверяет exact 11 v1-v5 tables.
- Изменения не подменяют промежуточные контракты: v3/v4 conflict и failure
  assertions, short-circuit до v4/v5 и exact восемь таблиц для незавершённых
  v1-v4 состояний сохранены.
- Unavailable-DB case использует допустимый composed prefix `unavailable_`
  (12 ASCII bytes, то есть не более 25) и поэтому достигает DB connection path,
  где по-прежнему требует exact `DATABASE_UNAVAILABLE`/exit 69.
- Literal catalogue contract расширен exact v5 workforce columns, indexes,
  checks и foreign keys; он не загружает production migration implementation.
- Добавление `tinyint` в нормализацию metadata-column типа необходимо для
  независимого сравнения MariaDB fingerprint и не ослабляет ожидаемый тип.
- OTIZ harness требует только уникальное подмножество `[1,2,3,4,5]` для
  повторно используемой disposable schema и сохраняет/сравнивает все 11
  canonical tables. Production schema assertions при этом не ослаблены.
- Иных изменений ожиданий в рассмотренных тестах не обнаружено. Явная вставка
  legacy workforce sentinel перечисляет прежние восемь колонок, сохраняя смысл
  fixture после additive v5 migration.

## Воспроизведение

- `production_migration_runner_001_test.php` — PASS.
- `pilot_case_import_001_test.php` — PASS.
- `harness_otiz_canonical_compat_001_test.php` — PASS.
- `pilot_http_auth_001_test.php` проходит обновлённый v5 schema fixture и затем
  падает на известном CSP mismatch `script-src 'self'`.
- `make verify` подтвердил ровно известный baseline: восемь DB regression
  failures; E2E повторяет один из этих восьми (`pilot_e2e_flow_001_test.php`).
  Canonical v5 runner и regression alignment проходят.
- `git diff --check` — PASS.
- `make architecture-check` — PASS (`6 rules`).
- `openspec validate register-workforce-history-canonical-v5 --strict` — PASS.

## Verdict

`APPROVED`: выравнивание ограничено superseding public-runner v5 contract,
сохраняет промежуточные v1-v4 доказательства и не ослабляет несвязанные
assertions. Известный baseline из восьми DB failures не изменился.
