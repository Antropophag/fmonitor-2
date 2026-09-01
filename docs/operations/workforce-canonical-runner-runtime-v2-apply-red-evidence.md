# WORKFORCE-CANONICAL-RUNNER-001 — runtime v2 apply RED evidence

- Дата: `2026-09-02`
- Автор test/RED: отдельный свежий агент
  `workforce_runtime_v2_apply_red_20260902ad`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- OpenSpec change: `register-workforce-history-canonical-v5`
- Проверяемый seam: machine-checkable source ownership assertion в
  `tests/InstallationProcess/workforce_canonical_runner_001_test.php`
- Verdict автора:
  `QUALIFYING_RED_CAPTURED — AWAITING_FRESH_INDEPENDENT_TEST_REVIEW`

Этот append-only record фиксирует code-review finding, не переписывает
предыдущие RED/GREEN/review records и не является self-review.

## Уточнённый executable architecture contract

Approved specification требует, чтобы после canonical deployment bootstrap,
importer, HTTP/UI и workers были только consumers workforce schema. Поэтому
существующая source ownership assertion расширена одним независимым
запрещённым invocation:

```text
WorkforceCatalogSchemaMigration::apply(...)
```

Она сохраняет прежний запрет прямого
`BitrixWorkforceHistorySchemaMigration::apply(...)` и workforce-targeted
`CREATE`/`ALTER`/`DROP`. Canonical runner, migration-owner classes и test/demo
fixtures остаются разрешёнными владельцами. Production code и остальные
assertions не изменены.

## Fresh qualifying RED

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

Process exit: `255`. Все предшествующие public-runner, exact-schema,
repeat/recovery/conflict/prefix assertions в этом же executable test прошли;
failure возник в `wcrAssertRuntimeOwnership()`. Поэтому RED локализован в
текущем runtime fallback `WorkforceCatalogSchemaMigration::apply(...)`, а не в
DB availability, fixture setup или canonical runner.

## Reviewed-input identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
0744c5c99c7a8aa4dbce70cf5b26d86a2f3497aca9b92553490d680d75acfae0  openspec/changes/register-workforce-history-canonical-v5/specs/deployment/canonical-workforce-migration/spec.md
dc7eabe79c6ca6a4d2ad5c80a6270fa9387f3f45bf6365ad90e5e78a1da21b33  tests/InstallationProcess/workforce_canonical_runner_001_test.php
043ef2cfbac48dabc0fa89ea6acdb82f32d72d882d1592b5122bcf630c169fbd  rapid-pilot/docker-bootstrap.php
3098c845a182440bc0180a1f8e1bba776c87e8eb9465a2533a8f845b788061a5  bin/fmonitor2-migrate.php
```

## Final checks

```text
$ php -l tests/InstallationProcess/workforce_canonical_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/workforce_canonical_runner_001_test.php

$ git diff --check
exit 0, no output

$ make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

$ openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid
```

Текущий общий architecture ratchet остаётся green, но новый более узкий
approved-contract assertion намеренно RED. Следующий gate — свежий независимый
test review; только после `APPROVED` production author может удалить v2 runtime
fallback.
