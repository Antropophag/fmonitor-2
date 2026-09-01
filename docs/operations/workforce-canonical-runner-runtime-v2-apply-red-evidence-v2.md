# WORKFORCE-CANONICAL-RUNNER-001 — corrected runtime v2 apply RED evidence v2

- Дата: `2026-09-02`
- Автор correction/test/RED: отдельный свежий агент
  `workforce_runtime_v2_apply_red_20260902ad`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- OpenSpec change: `register-workforce-history-canonical-v5`
- Verdict автора:
  `QUALIFYING_RED_CAPTURED — AWAITING_FRESH_INDEPENDENT_TEST_REREVIEW`
- Supersedes:
  `docs/operations/workforce-canonical-runner-runtime-v2-apply-red-evidence.md`
- Основание correction:
  `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-runtime-v2-apply.md`
  (`CHANGES_REQUESTED`)

Исходные evidence и review не переписаны. Production code не изменялся.

## Исправление review finding

Source ownership assertion теперь явно, а не случайно по форме invocation,
разрешает exact canonical owner `bin/fmonitor2-migrate.php`. Также разрешены
`app/InstallationProcess/*SchemaMigration.php` и test/demo fixtures. Все
остальные production roots сохраняют запрет прямых v2/v5 workforce migration
calls и workforce-targeted DDL.

Чтобы выполнить §5 approved spec буквально через `make architecture-check`, в
architecture checker добавлен отдельный абсолютный
`workforce_migration_ownership` rule. Его finding нельзя легализовать записью в
debt baseline. Unit examples доказывают exact runner/migration allowlist,
запрет runtime bootstrap и non-baselineable compare semantics. Existing debt
baseline не изменён и не расширен.

## Fresh qualifying RED — executable behavior test

```sh
php tests/InstallationProcess/workforce_canonical_runner_001_test.php
```

Exact существенный результат:

```text
PHP Fatal error: Uncaught TestFailure: Runtime consumers must not own workforce migration calls or DDL.
Expected: array (
)
Actual: array (
  0 => 'rapid-pilot/docker-bootstrap.php: direct workforce-v2 apply',
)
```

Process exit: `255`. Все предшествующие public runner/schema/prefix assertions
прошли; единственный source violation — текущий v2 fallback bootstrap.

## Fresh qualifying RED — repository architecture seam

```sh
make architecture-check
```

Exact result:

```text
ARCHITECTURE CHECK FAILED
- workforce_migration_ownership: forbidden production owner: workforce-migration|rapid-pilot/docker-bootstrap.php|4cbcdf66501c86f4
make: *** [Makefile:92: architecture-check] Error 1
```

Это intentional RED по новому абсолютному rule, не baseline growth. Он станет
GREEN только после удаления запрещённого runtime migration invocation.

## Reviewed-input identities

```text
b5a6cde4369994570f812be5cbebbbf10195c44073be6a5115070aeccdc11322  tests/InstallationProcess/workforce_canonical_runner_001_test.php
87e7599638f06302720d06aab3435cd8e151285fafb0a9b36ae94f0d899fb640  tools/architecture/check.py
492e2c6c72cdf851b38cb213b3ccc83ede34a8b94bedfa55c189f8a8f00e2176  tools/architecture/tests/test_debt_fingerprint.py
043ef2cfbac48dabc0fa89ea6acdb82f32d72d882d1592b5122bcf630c169fbd  rapid-pilot/docker-bootstrap.php
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
0744c5c99c7a8aa4dbce70cf5b26d86a2f3497aca9b92553490d680d75acfae0  openspec/changes/register-workforce-history-canonical-v5/specs/deployment/canonical-workforce-migration/spec.md
```

## Остальные проверки

```text
$ python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 14 tests
OK

$ php -l tests/InstallationProcess/workforce_canonical_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/workforce_canonical_runner_001_test.php

$ openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid

$ git diff --check
exit 0, no output
```

Следующий gate — свежий независимый rereview corrected test и architecture
rule. Production author не должен исправлять fallback до verdict `APPROVED`.
