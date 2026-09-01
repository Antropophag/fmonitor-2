# Test review: WORKFORCE-CANONICAL-RUNNER-001 architecture alias/indirect RED

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_arch_alias_indirect_test_review_20260902ak`
- Executable spec: `specs/WORKFORCE-CANONICAL-RUNNER-001.md` v0.1
- RED evidence:
  `docs/operations/workforce-canonical-runner-architecture-alias-indirect-red-evidence.md`
- Verdict: `CHANGES_REQUESTED`

## Что доказано

Оба новых теста используют существующий seam `architecture_check.collect()` и
production-like временные PHP fixtures под `rapid-pilot/`. Они не подменяют
checker и независимо требуют ровно один non-baselineable
`workforce_migration_ownership` finding на каждый запрещённый пример.

Локально воспроизведён qualifying RED: обе imported aliases (`v2`, `v5`) и все
три variable-target операции (`CREATE`, `ALTER`, `DROP`) прочитаны collector-ом,
но дают ноль findings. Итог — `Ran 2 tests`, пять subtest failures. Одновременно
текущий production source остаётся чистым: `make architecture-check` проходит.
Это подтверждает именно заявленный пробел §5, а не setup/import failure.

Существующие проверки сохраняют exact production-owner allowlist и отдельно
покрывают direct one-line/multiline `CREATE`/`ALTER`/`DROP`; новые fixtures не
изменяют allowlist или baseline.

## Blocking findings

### 1. Матрица переобучаема на два alias и одно имя переменной

Imported-alias cases используют только literal aliases `WorkforceV5` и
`WorkforceCatalog`, а все indirect-DDL cases используют только `$table` и одну
форму присваивания `$prefix . '<basename>'`. Реализация, которая распознает
ровно эти два alias и ровно переменную `$table`, сделает новые тесты зелёными,
не обеспечив абсолютный invariant §5.

Перед GREEN нужен чувствительный RED, в котором alias и target-variable names
не совпадают с примерами из code review и различаются между cases. Ожидание
должно оставаться поведенческим — finding для imported v2/v5 class identity и
для связанного workforce target независимо от выбранного допустимого PHP
identifier. Это не требует исчерпывающего PHP data-flow анализа, но исключает
очевидную hard-coded correction под fixture.

### 2. Evidence hash не соответствует reviewed test input

Evidence фиксирует SHA-256
`8ab61772...` для `tools/architecture/tests/test_debt_fingerprint.py`, тогда как
фактически рассмотренный и запущенный файл имеет SHA-256
`baf43ab6c2e26218957cb91a2cc7a8fcc40428debc79f5f36873c4f452068992`.
Остальные три recorded identities совпадают. Append-only superseding evidence
должен идентифицировать точный исправленный RED input и привести фактический
вывод повторного запуска.

## Independent verification

```text
focused alias/indirect unittest: RED, 5 expected failures
make architecture-check: ARCHITECTURE CHECK PASSED (7 rules)
openspec validate register-workforce-history-canonical-v5 --strict: valid
git diff --check: PASS
```

## Verdict

`CHANGES_REQUESTED`

RED направлен на правильный approved seam и специфично обнаруживает текущий
defect, но до минимального GREEN необходимо устранить переобучаемость fixtures
и выпустить superseding evidence с identity фактически reviewed теста. После
этого требуется свежий независимый test rereview.
