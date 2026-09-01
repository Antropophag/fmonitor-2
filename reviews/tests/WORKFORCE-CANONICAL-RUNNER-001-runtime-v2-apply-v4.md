# WORKFORCE-CANONICAL-RUNNER-001 runtime v2 apply — independent test rereview v4

- Дата: `2026-09-02`
- Reviewer: отдельный свежий agent
  `workforce_runtime_v2_apply_test_rereview_20260902ah`
- Reviewed evidence:
  `docs/operations/workforce-canonical-runner-runtime-v2-apply-red-evidence-v4.md`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, особенно §§1, 5
- OpenSpec:
  `register-workforce-history-canonical-v5/specs/deployment/canonical-workforce-migration`
- Verdict: `APPROVED`

## Findings

Блокирующих или неблокирующих замечаний нет. Test-only correction полностью
закрывает единственное замечание review v3: retained unit suite теперь через
`collect()` требует ровно один absolute `workforce_migration_ownership` finding
для обеих direct-apply форм v2/v5 и для one-line/multiline вариантов каждого
`CREATE TABLE`, `ALTER TABLE`, `DROP TABLE` по workforce target.

## Подтверждённый contract

- Production scanner и executable source assertion распознают обе формы
  `WorkforceCatalogSchemaMigration::apply(...)` и
  `BitrixWorkforceHistorySchemaMigration::apply(...)`.
- Workforce DDL scanner проходит через перенос строки до границы `;`, поэтому
  one-line и multiline `CREATE`, `ALTER`, `DROP` одинаково запрещены.
- Сохранён owner allowlist canonical runner,
  `app/InstallationProcess/*SchemaMigration.php`, `rapid-pilot/verify-*` и demo;
  проверки отдельно исключают runtime bootstrap и verify-пути вне
  `rapid-pilot/`.
- `workforce_migration_ownership` остаётся абсолютным non-baselineable rule:
  совпадение с baseline всё равно возвращает error.
- Existing public-CLI assertions §§2–6 не ослаблены; reviewed executable test
  сохранил прежний SHA-256.
- Оба RED достигают только текущего production fallback
  `WorkforceCatalogSchemaMigration::apply(...)` в
  `rapid-pilot/docker-bootstrap.php`; иных ownership findings нет.

## Reviewed-input identities

```text
43df8f99f264945eec4738632bcf6c43e9b72dce2b87cd62f1caa4f5e8338bd1  tests/InstallationProcess/workforce_canonical_runner_001_test.php
d3dba2138dea86b4bc108e82545a3b6aae8d9bf960d9c0dd006be362c5f98ce5  tools/architecture/check.py
de767ca0ebb9a6a16b682f2c700714ae812e34e43ced89f6a9631a0cfd208cd7  tools/architecture/tests/test_debt_fingerprint.py
043ef2cfbac48dabc0fa89ea6acdb82f32d72d882d1592b5122bcf630c169fbd  rapid-pilot/docker-bootstrap.php
5e33c3c2ba310f8aca0d44ecede11fb2495cd8db4d90feed4e301b18c18658da  docs/operations/workforce-canonical-runner-runtime-v2-apply-red-evidence-v4.md
```

## Independent verification

```text
$ python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 16 tests
OK

$ php -l tests/InstallationProcess/workforce_canonical_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/workforce_canonical_runner_001_test.php

$ php tests/InstallationProcess/workforce_canonical_runner_001_test.php
exit 255
Actual: rapid-pilot/docker-bootstrap.php: direct workforce-v2 apply

$ make architecture-check
exit 2
workforce_migration_ownership: forbidden production owner:
workforce-migration|rapid-pilot/docker-bootstrap.php|4cbcdf66501c86f4

$ openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid

$ git diff --check
exit 0, no output
```

## Gate conclusion

Исправленный RED-набор соответствует approved §§1/5 и OpenSpec runtime DDL
ownership requirement. Gate independent test rereview пройден; production
fallback может переходить в minimal GREEN у отдельного implementation owner.
