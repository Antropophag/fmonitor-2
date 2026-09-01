# WORKFORCE-CANONICAL-RUNNER-001 — architecture alias/indirect RED evidence

- Дата: `2026-09-02`
- Автор теста/RED: отдельный свежий агент
  `workforce_architecture_alias_indirect_red_20260902aj`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- Публичный seam: `architecture_check.collect()` для production-like PHP
  source fixture
- Основание: blocking finding независимого code rereview
  `reviews/code/WORKFORCE-CANONICAL-RUNNER-001-v3.md`
- Verdict автора: `QUALIFYING_RED_CAPTURED — AWAITING_FRESH_INDEPENDENT_TEST_REVIEW`

Этот append-only record доказывает остающийся gap абсолютного ownership
инварианта. Production runtime, migration code, architecture checker,
allowlists и baseline автор RED не изменял.

## Executable adversarial matrix

В `tools/architecture/tests/test_debt_fingerprint.py` добавлены два
behavior-теста через существующий production collection seam:

1. imported migration aliases для обеих разрешённых только владельцам
   migration families:
   - `BitrixWorkforceHistorySchemaMigration as WorkforceV5` с
     `WorkforceV5::apply(...)`;
   - `WorkforceCatalogSchemaMigration as WorkforceCatalog` с
     `WorkforceCatalog::apply(...)`;
2. variable-target workforce DDL, где basename сначала присваивается
   `$table = $prefix . 'fm2_workforce_…'`, а затем используется в
   `CREATE TABLE`, `ALTER TABLE` или `DROP TABLE`.

Каждый fixture размещается временно под `rapid-pilot/`, поэтому он проходит
тот же production scan и exact allowlist, что и реальный runtime source.
Ожидание задано независимо и буквально: ровно один non-baselineable
`workforce_migration_ownership` finding на запрещённый fixture.

## Qualifying RED

```sh
python3 -m unittest -v \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_workforce_migration_ownership_detects_imported_apply_aliases \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_workforce_migration_ownership_detects_variable_target_ddl
```

Фактический результат:

```text
Ran 2 tests
FAILED (failures=5)

v5-alias:  AssertionError: 1 != 0
v2-alias:  AssertionError: 1 != 0
create:    AssertionError: 1 != 0
alter:     AssertionError: 1 != 0
drop:      AssertionError: 1 != 0
```

Все пять fixtures прочитаны checker-ом, но текущая реализация не создала ни
одного обязательного finding. RED вызван только недостающей alias/indirect
detection, а не import/setup ошибкой: существующие 16 unit-тестов до новой
матрицы были зелёными, а repository production source по-прежнему проходит
ratchet.

## Reviewed-input identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
baf43ab6c2e26218957cb91a2cc7a8fcc40428debc79f5f36873c4f452068992  tools/architecture/tests/test_debt_fingerprint.py
d3dba2138dea86b4bc108e82545a3b6aae8d9bf960d9c0dd006be362c5f98ce5  tools/architecture/check.py
4724c9b3711e97bcc8daa7643e5aaf236b1e93e9fc81e338c8a2ac09ece23db1  reviews/code/WORKFORCE-CANONICAL-RUNNER-001-v3.md
```

Следующий gate — свежий независимый test reviewer. Только после его verdict
`APPROVED` допустима минимальная correction `tools/architecture/check.py`.

## Repository checks

`make architecture-check` ожидаемо может оставаться зелёным: он сканирует
текущий production source, где запрещённых alias/variable-target форм нет.
Новая unit matrix намеренно остаётся RED до реализации checker correction.
