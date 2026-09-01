# WORKFORCE-CANONICAL-RUNNER-001 — corrected runtime v2 apply RED evidence v4

- Дата: `2026-09-02`
- Автор correction/test/RED: отдельный свежий агент
  `workforce_runtime_v2_apply_red_20260902ad`
- Verdict автора:
  `QUALIFYING_RED_CAPTURED — AWAITING_FRESH_INDEPENDENT_TEST_REREVIEW`
- Supersedes:
  `docs/operations/workforce-canonical-runner-runtime-v2-apply-red-evidence-v3.md`
- Основание correction:
  `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-runtime-v2-apply-v3.md`
  (`CHANGES_REQUESTED`)

История не переписана; production code и baseline не изменялись.

## Test-only correction

Retained architecture unit suite теперь table-driven проверяет все обязательные
варианты абсолютного workforce ownership rule:

1. прямой v2 `WorkforceCatalogSchemaMigration::apply`;
2. прямой v5 `BitrixWorkforceHistorySchemaMigration::apply`;
3. one-line и multiline `CREATE TABLE` workforce target;
4. one-line и multiline `ALTER TABLE` workforce target;
5. one-line и multiline `DROP TABLE` workforce target.

Предыдущие unit examples exact allowlist, path-specific fixtures, multiline DDL
и non-baselineable semantics сохранены.

## Qualifying results

```text
$ python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 16 tests
OK

$ php tests/InstallationProcess/workforce_canonical_runner_001_test.php
Actual: array (
  0 => 'rapid-pilot/docker-bootstrap.php: direct workforce-v2 apply',
)
Process exit: 255

$ make architecture-check
ARCHITECTURE CHECK FAILED
- workforce_migration_ownership: forbidden production owner: workforce-migration|rapid-pilot/docker-bootstrap.php|4cbcdf66501c86f4
make: *** [Makefile:92: architecture-check] Error 1

$ openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid

$ git diff --check
exit 0, no output
```

Оба RED seam по-прежнему имеют единственную текущую production-причину — v2
fallback в `rapid-pilot/docker-bootstrap.php`.

## Reviewed-input identities

```text
43df8f99f264945eec4738632bcf6c43e9b72dce2b87cd62f1caa4f5e8338bd1  tests/InstallationProcess/workforce_canonical_runner_001_test.php
d3dba2138dea86b4bc108e82545a3b6aae8d9bf960d9c0dd006be362c5f98ce5  tools/architecture/check.py
de767ca0ebb9a6a16b682f2c700714ae812e34e43ced89f6a9631a0cfd208cd7  tools/architecture/tests/test_debt_fingerprint.py
043ef2cfbac48dabc0fa89ea6acdb82f32d72d882d1592b5122bcf630c169fbd  rapid-pilot/docker-bootstrap.php
```

Следующий gate — новый свежий independent rereview. До его `APPROVED`
production fallback не исправляется.
