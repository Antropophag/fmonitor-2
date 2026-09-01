# WORKFORCE-CANONICAL-RUNNER-001 runtime v2 apply — independent test rereview v2

- Дата: `2026-09-02`
- Reviewer: отдельный свежий agent
  `workforce_runtime_v2_apply_test_rereview_20260902af`
- Reviewed evidence:
  `docs/operations/workforce-canonical-runner-runtime-v2-apply-red-evidence-v2.md`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, особенно §§1, 5
- OpenSpec:
  `register-workforce-history-canonical-v5/specs/deployment/canonical-workforce-migration`
- Verdict: `CHANGES_REQUESTED`

## Findings

### 1. HIGH — workforce DDL rule не обнаруживает обычный multiline SQL

`WORKFORCE_DDL` ограничивает участок между `ALTER TABLE`/`CREATE
TABLE`/`DROP TABLE` и workforce target шаблоном `[^;\n]*`. Поэтому
семантически тот же production DDL обходит абсолютный rule при
обычном formatting:

```php
$sql = "ALTER TABLE
 fm2_workforce_catalog ADD x INT";
```

Независимая synthetic `collect()` probe обнаружила exact v2 apply,
exact v5 apply и one-line workforce DDL, но не вернула finding для
multiline примера. Это не выполняет без оговорок требование
§5 запрещать workforce-targeted DDL в production runtime layers.

Required correction: распознавать workforce DDL независимо от
переносов строк до statement boundary и добавить unit examples для
one-line и multiline `CREATE`, `ALTER`, `DROP` workforce targets.

### 2. MEDIUM — fixture exemption не является exact test/support allowlist

И executable source assertion (`$isFixture`) и repository checker
(`production_file`) исключают файл по basename `verify-*` независимо от
его каталога. Так production runtime файл в `rapid-pilot/` или `public/`
может стать невидимым для нового rule только из-за имени. Это
шире разрешённого §5 `test/support ownership` и не доказывает
запрет в bootstrap/importer/HTTP/UI/worker code.

Required correction: привязать fixture exemption к exact reviewed
test/support/demo paths, а не к basename в любом production root; добавить
negative unit example для `rapid-pilot/verify-*.php` или эквивалента.

## Confirmed strengths

- Reviewed-input SHA-256 из evidence совпадают для test, checker,
  checker unit examples, current bootstrap, executable spec и OpenSpec spec.
- Exact `bin/fmonitor2-migrate.php` и
  `app/InstallationProcess/*SchemaMigration.php` разрешены; current
  `rapid-pilot/docker-bootstrap.php` запрещён.
- `compare()` безусловно выдаёт ошибку для каждого current
  `workforce_migration_ownership` finding; тождественная baseline
  запись не может скрыть violation.
- Existing behavior assertions сохранены: public CLI matrix доходит
  до ownership assertion; RED локализован в exact current
  `rapid-pilot/docker-bootstrap.php` v2 fallback.

## Verification run

```text
$ python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 14 tests
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
exit 0
```

## Machine-checkable §5 conclusion

Executable source assertion + repository architecture rule совместно дают
правильный двухуровневый seam, но в текущей реализации они
недостаточны для machine-checkable §5: оба пропускают multiline
workforce DDL и имеют слишком широкое filename-based fixture exemption.
После закрытия двух findings нужен новый свежий independent
rereviewer.
