# Test review: WORKFORCE-CANONICAL-RUNNER-001 runtime v2 apply

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_runtime_v2_apply_test_review_20260902ae`
- Автор test/RED: отдельный свежий агент
  `workforce_runtime_v2_apply_red_20260902ad`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`
  (`APPROVED — GATE 1 PASSED`)
- OpenSpec change: `register-workforce-history-canonical-v5`
- RED evidence:
  `docs/operations/workforce-canonical-runner-runtime-v2-apply-red-evidence.md`
- Verdict: `CHANGES_REQUESTED`

Этот append-only review проверяет только новое source-ownership
assertion. Production code, test, evidence и planning artifacts reviewer не
изменял.

## Reviewed identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
0744c5c99c7a8aa4dbce70cf5b26d86a2f3497aca9b92553490d680d75acfae0  openspec/changes/register-workforce-history-canonical-v5/specs/deployment/canonical-workforce-migration/spec.md
dc7eabe79c6ca6a4d2ad5c80a6270fa9387f3f45bf6365ad90e5e78a1da21b33  tests/InstallationProcess/workforce_canonical_runner_001_test.php
043ef2cfbac48dabc0fa89ea6acdb82f32d72d882d1592b5122bcf630c169fbd  rapid-pilot/docker-bootstrap.php
3098c845a182440bc0180a1f8e1bba776c87e8eb9465a2533a8f845b788061a5  bin/fmonitor2-migrate.php
```

## Finding

### BLOCKER — canonical runner не включён в allowlist разрешённых owners

`WORKFORCE-CANONICAL-RUNNER-001` §1 явно запрещает workforce migration calls
**вне** canonical migration classes **и runner**. Значит
`bin/fmonitor2-migrate.php` — разрешённый owner. Но
`wcrAssertRuntimeOwnership()` исключает из проверки только
`*SchemaMigration.php` и fixture/demo files; canonical runner не исключён.

Сейчас runner проходит лишь потому, что вызывает catalogue entries
динамически через `$migration::apply(...)`. Поведенчески
эквивалентный и разрешённый прямой
`WorkforceCatalogSchemaMigration::apply(...)` в этом же runner даст
false positive. Такая чувствительность к несущественной форме
разрешённого вызова противоречит approved ownership seam и делает
тест implementation-coupled.

Исправление должно явно включить exact canonical runner path в
allowlist, сохранив запрет для `rapid-pilot/`, HTTP/UI, importer и
workers. После исправления нужен свежий независимый rereview.

## Подтверждённые свойства RED

- Сам запрет v2 соответствует §1 и OpenSpec requirement
  `Runtime callers do not own workforce DDL`: v2 catalog migration также
  является workforce migration.
- Production roots дают ровно одно текущее совпадение:
  `rapid-pilot/docker-bootstrap.php: direct workforce-v2 apply`.
- Tests не входят в production scan; migration-owner classes и
  существующие `verify-*`/`demo` fixtures исключаются.
- Падение возникает в конце того же executable test после
  прохождения прежних public-runner/schema/prefix assertions. RED не
  является environment/setup failure.

## Verification

```text
$ php -l tests/InstallationProcess/workforce_canonical_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/workforce_canonical_runner_001_test.php

$ php tests/InstallationProcess/workforce_canonical_runner_001_test.php
PHP Fatal error: Uncaught TestFailure: Runtime consumers must not own workforce migration calls or DDL.
Expected: array (
)
Actual: array (
  0 => 'rapid-pilot/docker-bootstrap.php: direct workforce-v2 apply',
)
Process exit: 255

$ make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

$ openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid

$ git diff --check
exit 0, no output
```

## Verdict

`CHANGES_REQUESTED`

RED по текущему runtime fallback квалифицирован, но Gate 3 не
пройден до тех пор, пока test-owned allowlist не будет точно отражать
разрешённый canonical runner owner.
