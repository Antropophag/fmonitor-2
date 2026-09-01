# WORKFORCE-CANONICAL-RUNNER-001 runtime v2 apply — independent test rereview v3

- Дата: `2026-09-02`
- Reviewer: отдельный свежий agent
  `workforce_runtime_v2_apply_test_rereview_20260902ag`
- Reviewed evidence:
  `docs/operations/workforce-canonical-runner-runtime-v2-apply-red-evidence-v3.md`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, особенно §§1, 5
- OpenSpec:
  `register-workforce-history-canonical-v5/specs/deployment/canonical-workforce-migration`
- Verdict: `CHANGES_REQUESTED`

## Finding

### MEDIUM — retained unit contract не покрывает обязательную ownership matrix

Production scanner и executable source assertion исправлены: pattern проходит
через переносы строк до `;`, распознаёт обе формы
`WorkforceCatalogSchemaMigration::apply(...)` и
`BitrixWorkforceHistorySchemaMigration::apply(...)`, а fixture exception для
`verify-*` ограничен `rapid-pilot/`. Независимая synthetic probe подтвердила
findings для обеих apply-форм и для one-line/multiline `CREATE`, `ALTER` и
`DROP` workforce target.

Однако committed unit suite закрепляет только один multiline `CREATE` example
(`test_multiline_workforce_ddl_is_forbidden`). В нём нет examples для one-line
`CREATE`, one-line/multiline `ALTER`, one-line/multiline `DROP` и обеих direct
apply-форм. Поэтому следующая правка regex либо owner logic может снова открыть
часть запрещённых §5 форм, сохранив зелёным `test_debt_fingerprint`. Это также
не закрывает exact required correction предыдущего review v2: добавить unit
examples для one-line и multiline `CREATE`, `ALTER`, `DROP`.

Required correction: добавить table-driven unit matrix, которая через
`collect()` требует `workforce_migration_ownership` finding для обеих
direct-apply форм и каждой one-line/multiline DDL формы `CREATE`, `ALTER`,
`DROP`. Production/spec/baseline менять не требуется. После correction нужен
новый свежий independent rereviewer.

## Подтверждённые свойства

- `WORKFORCE_DDL` использует `[^;]*` без запрета newline и ловит target после
  multiline DDL prefix до statement boundary.
- Exact canonical runner и `app/InstallationProcess/*SchemaMigration.php`
  разрешены; current `rapid-pilot/docker-bootstrap.php` запрещён.
- `rapid-pilot/verify-*` и demo paths разрешены, тогда как `bin/verify-*`,
  `public/verify-*` и `app/verify-*` не получают filename escape.
- `compare()` делает каждый workforce ownership finding non-baselineable.
- Current runner не даёт false positive; оба обязательных RED seam доходят
  только до существующего runtime v2 fallback.
- Reviewed-input SHA-256 совпадают с evidence v3 для test, checker, checker
  units и current bootstrap. Evidence file имеет SHA-256
  `7f4cf9cbc65b0f24708049d86768ad097b2c7f2ef8a51834b8e2742ba4b9a749`.

## Verification run

```text
$ python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 15 tests
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

Текущие scanners функционально обнаруживают проверенную §5 matrix, а RED
квалифицируется и локализован правильно. Но test gate ещё не устойчив:
обязательная matrix не сохранена как regression unit contract. До её добавления
и нового rereview переход к исправлению production fallback не одобрен.
