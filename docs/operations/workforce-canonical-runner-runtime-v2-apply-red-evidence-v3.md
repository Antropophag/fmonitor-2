# WORKFORCE-CANONICAL-RUNNER-001 — corrected runtime v2 apply RED evidence v3

- Дата: `2026-09-02`
- Автор correction/test/RED: отдельный свежий агент
  `workforce_runtime_v2_apply_red_20260902ad`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- Verdict автора:
  `QUALIFYING_RED_CAPTURED — AWAITING_FRESH_INDEPENDENT_TEST_REREVIEW`
- Supersedes:
  `docs/operations/workforce-canonical-runner-runtime-v2-apply-red-evidence-v2.md`
- Основание correction:
  `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-runtime-v2-apply-v2.md`
  (`CHANGES_REQUESTED`)

Исторические evidence/review не переписаны. Production code и architecture
baseline не изменялись.

## Исправления findings

- workforce `CREATE`/`ALTER`/`DROP TABLE` теперь распознаётся в пределах всего
  SQL statement до `;`, включая переносы строк;
- fixture allowlist path-specific: только `rapid-pilot/verify-*` и demo paths;
  произвольный `bin/verify-*` или иной production filename больше не является
  исключением;
- exact canonical runner и `app/InstallationProcess/*SchemaMigration.php`
  остаются явными разрешёнными owners;
- новый unit example доказывает multiline workforce DDL finding, а existing
  unit examples — exact allowlist и невозможность скрыть finding baseline-ом.

## Qualifying RED results

```text
$ php tests/InstallationProcess/workforce_canonical_runner_001_test.php
PHP Fatal error: Uncaught TestFailure: Runtime consumers must not own workforce migration calls or DDL.
Expected: array (
)
Actual: array (
  0 => 'rapid-pilot/docker-bootstrap.php: direct workforce-v2 apply',
)
Process exit: 255

$ make architecture-check
ARCHITECTURE CHECK FAILED
- workforce_migration_ownership: forbidden production owner: workforce-migration|rapid-pilot/docker-bootstrap.php|4cbcdf66501c86f4
make: *** [Makefile:92: architecture-check] Error 1
```

Оба независимых machine-checkable seams локализуют единственный текущий RED в
runtime v2 fallback. Предшествующая полная public-runner матрица проходит до
source ownership assertion.

## Reviewed-input identities

```text
43df8f99f264945eec4738632bcf6c43e9b72dce2b87cd62f1caa4f5e8338bd1  tests/InstallationProcess/workforce_canonical_runner_001_test.php
d3dba2138dea86b4bc108e82545a3b6aae8d9bf960d9c0dd006be362c5f98ce5  tools/architecture/check.py
da8047fa83d76cad3e83c748046bfc519b34b1a58bbfacaedd5db6de938ca8a6  tools/architecture/tests/test_debt_fingerprint.py
043ef2cfbac48dabc0fa89ea6acdb82f32d72d882d1592b5122bcf630c169fbd  rapid-pilot/docker-bootstrap.php
```

## Остальные проверки

```text
$ python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 15 tests
OK

$ php -l tests/InstallationProcess/workforce_canonical_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/workforce_canonical_runner_001_test.php

$ openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid

$ git diff --check
exit 0, no output
```

Следующий gate — новый свежий независимый rereview. До `APPROVED` production
fallback не исправляется.
