# WORKFORCE-CANONICAL-RUNNER-001 — corrected architecture alias/indirect RED evidence v2

- Дата: `2026-09-02`
- Автор correction: отдельный свежий агент
  `workforce_architecture_alias_indirect_red_20260902aj`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- Публичный seam: `architecture_check.collect()` для production-like PHP
  source fixture
- Supersedes:
  `docs/operations/workforce-canonical-runner-architecture-alias-indirect-red-evidence.md`
- Основание correction:
  `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-architecture-alias-indirect.md`
  с verdict `CHANGES_REQUESTED`
- Verdict автора: `QUALIFYING_RED_CAPTURED — AWAITING_FRESH_INDEPENDENT_TEST_REREVIEW`

Предыдущий evidence не переписан. Production runtime, migration code,
`tools/architecture/check.py`, owner allowlists и baseline не изменялись.

## Исправление test-review finding

Матрица больше не привязана к именам, которые production checker мог бы
перечислить специально для прохождения теста:

- v5 alias параметризован как произвольный `HistoryUpgradeOwner`;
- v2 alias параметризован как другой произвольный
  `ImportedCatalogUpgrade`;
- `CREATE`, `ALTER` и `DROP` используют три различных произвольных target
  variables: `$catalogRelation`, `$observationLedger` и
  `$obsoleteRunRegister`.

Migration class, alias, variable, workforce basename, DDL operation и suffix
передаются как отдельные matrix values, из которых fixture source строится
для каждого subtest. Все исходные пять запрещённых вариантов сохранены.
Ожидание остаётся literal и независимо от checker implementation: ровно один
non-baselineable `workforce_migration_ownership` finding на fixture.

## Fresh qualifying RED

```sh
python3 -m unittest -v \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_workforce_migration_ownership_detects_imported_apply_aliases \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_workforce_migration_ownership_detects_variable_target_ddl
```

Результат:

```text
Ran 2 tests
FAILED (failures=5)

v5-alias:  AssertionError: 1 != 0
v2-alias:  AssertionError: 1 != 0
create:    AssertionError: 1 != 0
alter:     AssertionError: 1 != 0
drop:      AssertionError: 1 != 0
```

Таким образом текущий checker по-прежнему не обнаруживает ни одну из пяти
запрещённых форм, теперь независимо от конкретных alias/variable identifiers.

## Reviewed-input identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
4c8ce37cfbef3eaa56c02ffb3ceb630964584f3e9f25e5b55c4a700c8b38f088  tools/architecture/tests/test_debt_fingerprint.py
d3dba2138dea86b4bc108e82545a3b6aae8d9bf960d9c0dd006be362c5f98ce5  tools/architecture/check.py
7694ae1f247d24c7475e2d599aec4ea5418f13031303b6ab11f1cabc31dbaf5c  reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-architecture-alias-indirect.md
daa081f1447b29eb3966708ae92de481b133338d5b10ab559b316fe1e09488e6  docs/operations/workforce-canonical-runner-architecture-alias-indirect-red-evidence.md
```

Следующий gate — новый свежий независимый test rereviewer с новым именем.
До его `APPROVED` checker correction не разрешена.

## Repository-check expectation

`make architecture-check` должен оставаться зелёным на текущем repository
production source, где запрещённых форм нет. Новая unit matrix намеренно RED
до минимальной checker implementation.
