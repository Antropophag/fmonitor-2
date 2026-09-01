# Test rereview v2: WORKFORCE-CANONICAL-RUNNER-001 architecture alias/indirect RED

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_arch_alias_indirect_test_rereview_20260902al`
- Executable spec: `specs/WORKFORCE-CANONICAL-RUNNER-001.md` v0.1
- Corrected RED evidence:
  `docs/operations/workforce-canonical-runner-architecture-alias-indirect-red-evidence-v2.md`
- Supersedes test-review verdict:
  `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-architecture-alias-indirect.md`
- Verdict: `APPROVED`

## Traceability и seam

Исправленные тесты напрямую доказывают approved invariant из §5 и executable
example §6.10: production-like PHP fixtures передаются через существующий
публичный seam `architecture_check.collect()`, а ожидаемый результат — ровно
один non-baselineable `workforce_migration_ownership` finding на каждый
запрещённый пример. Checker, production source, allowlist и baseline тестами не
подменяются.

Сохранены все пять вариантов, из-за которых Gate 5 вернул slice в RED:

- imported alias для v5 migration apply;
- imported alias для v2 migration apply;
- variable-target `CREATE TABLE`;
- variable-target `ALTER TABLE`;
- variable-target `DROP TABLE`.

## Correction предыдущих findings

Fixture matrix больше не переобучена на identifiers из первоначального review
или code-review reproduction. Два class aliases различаются между собой
(`HistoryUpgradeOwner`, `ImportedCatalogUpgrade`), а три DDL target variables
также произвольны и различны (`catalogRelation`, `observationLedger`,
`obsoleteRunRegister`). Class identity, alias, variable, workforce basename,
operation и suffix являются отдельными matrix values. Поэтому очевидная
hardcoded correction только под `WorkforceV5`, `WorkforceCatalog` или `$table`
не удовлетворит тесту.

Ожидания не выведены из regex checker-а: они следуют из абсолютного запрета §5
на runtime v2/v5 apply и workforce-targeted `CREATE`/`ALTER`/`DROP`. Existing
literal one-line/multiline matrix остаётся без ослабления и дополняет новые
alias/indirect cases.

Все SHA-256 identities из corrected evidence независимо сверены с текущими
reviewed inputs и совпадают, включая test input
`4c8ce37cfbef3eaa56c02ffb3ceb630964584f3e9f25e5b55c4a700c8b38f088`.

## Independent RED verification

Focused запуск двух исправленных методов:

```text
Ran 2 tests in 0.178s
FAILED (failures=5)
```

Ровно пять ожидаемых subtest failures имеют одинаковую чувствительную причину:
checker возвращает `0` findings вместо требуемого `1` для v5 alias, v2 alias,
variable-target create, alter и drop. Это отсутствие утверждённого поведения,
а не ошибка setup.

Полный architecture unit suite:

```text
Ran 18 tests in 2.885s
FAILED (failures=5)
```

Все прежние тестовые методы проходят; падают только пять новых subtests. Это
подтверждает специфичность RED и сохранность текущих checks.

Дополнительные проверки:

```text
git diff --check
PASS

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid
```

Текущий production source остаётся чистым, а новые unit fixtures чувствительны
к требуемой минимальной checker correction. Изменений
`tools/architecture/baseline.json` и owner allowlist в reviewed RED diff нет.

## Verdict

`APPROVED`

Исправленный RED детерминирован, изолирован, трассируется к approved
architecture invariant и закрывает оба blocking finding первоначального test
review. Gate 3 пройден; разрешён только минимальный GREEN checker correction без
изменения approved expectations, allowlist или baseline, после чего нужны
regression checks и новый независимый code review.
