# Code review: WORKFORCE-CANONICAL-RUNNER-001 v0.1

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_runner_code_review_20260902w`
- Executable spec: `specs/WORKFORCE-CANONICAL-RUNNER-001.md` v0.1
- OpenSpec change: `register-workforce-history-canonical-v5`
- Verdict: `CHANGES_REQUESTED`

## Standards

Нарушений documented architecture boundary в production diff не найдено:
canonical runner владеет v1–v5 DDL, а bootstrap/importer используют read-only
`WorkforceHistorySchemaReadiness`. Direct runtime `apply` и importer-owned
workforce `ALTER` удалены; architecture ratchet проходит. Новая readiness
обёртка и открытие существующего classifier являются небольшим seam для
повторного использования exact v5 manifest и не добавляют второго schema
owner.

## Spec

### Blocking finding — clean canonical migration зависит от неоговорённого совпадения collations

`WORKFORCE-CANONICAL-RUNNER-001` §§4.1 и 6.1 требуют, чтобы clean compatible
database через public CLI вернула `schemaVersion=5`,
`appliedVersions=[1,2,3,4,5]` и exact v5 manifest. Это не выполняется на
каноническом test reset contour.

`tools/verification/reset-test-db.php` создаёт database с explicit
`utf8mb4_unicode_ci`, но v2 `WorkforceCatalogSchemaMigration` создаёт catalog
как `DEFAULT CHARSET=utf8mb4` без database-default `COLLATE`. На MariaDB 11.4.7
получается:

```text
database DEFAULT_COLLATION_NAME: utf8mb4_unicode_ci
fm2_workforce_catalog TABLE_COLLATION: utf8mb4_uca1400_ai_ci
```

Поэтому exact v5 preflight закономерно классифицирует только что созданный v2
catalog как conflict. Независимое воспроизведение на isolated Compose project,
host port `27328`:

```text
$ FMONITOR_TEST_DB_PORT=27328 php tools/verification/reset-test-db.php
TEST_DB_RESET_OK

$ FMONITOR_TEST_DB_PORT=27328 make migrate
{"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":5}
make: *** [Makefile:72: migrate] Error 2
```

Текущий executable test уже содержит explicit non-charset-default database
collation fixture и воспроизводит тот же RED на clean case, но его SHA-256
`55ee782559f7019b0a55ff1aadfc0670041019878acb15aa4494aeebc9274ce1`
не совпадает с approved v3 review identity
`d7395db426b4744c9373b7856ef0d7f46a8532edd01b0cddecc64739eba2ac94`.
Следовательно, correction ещё должна пройти обязательный независимый test
review до production fix.

Исправление не должно возвращать runtime/importer `ALTER`: database-default
collation необходимо обеспечить внутри canonical migration ownership, сохранив
strict conflict semantics для уже существующей несовместимой schema. После
этого нужны полный focused matrix, reset→migrate regression и свежий code
rereview.

## Verification

```text
git diff --check
exit 0

make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid

php tests/InstallationProcess/workforce_canonical_runner_001_test.php
RED: clean public runner returned exit 2 / SCHEMA_MIGRATION_CONFLICT v5
```

## Verdict

`CHANGES_REQUESTED`

Architecture ownership и ordered catalogue выглядят корректно, но основной
clean deployment contract не работает на поддерживаемом canonical reset
contour. Gate 5 не может быть одобрен до reviewed test correction, migration-
owned GREEN и свежего независимого rereview.
