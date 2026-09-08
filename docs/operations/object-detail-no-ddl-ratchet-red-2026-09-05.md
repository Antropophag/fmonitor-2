# Object-detail runtime-DDL ratchet — RED

Дата: 2026-09-05. Автор test: `/root`.
Technical Gate1: `object-detail-no-ddl-ratchet-gate1-review-2026-09-05.md`,
APPROVED. Production importer и baseline ещё не менялись.

Команда: `python3 tools/architecture/tests/test_object_detail_no_ddl.py`, exit1.
Реальный public `check.py --json` запускается в каждом task-owned temp repo
с копиями фактических scanner/baseline и одним literal PHP fixture.
SQL fixtures не исполняются.

```text
test_01_canonical_owner_is_accepted ... ok
test_02_readonly_runtime_precondition_is_accepted ... ok
test_03_old_runtime_ddl_cannot_return ... FAIL
AssertionError: 1 != 0 : RED: historical importer CREATE must no longer be grandfathered
Ran 3 tests
FAILED (failures=1)
```

Оба controls доказывают healthy tool/setup. Intended RED вызван тем, что
актуальный baseline ещё содержит оба exact historic importer DDL exceptions.
Expected rejection происходит из уже approved canonical ownership contract;
исторические SQL строки — adversarial inputs, не ожидаемые output values.

```text
079944d3797bdd0016d76b1ab2572c9ef48436a06d932b9c9b455c56cd1aa908  specs/OBJECT-DETAIL-NO-DDL-RATCHET-001.md
0469a590f0c119e493a03ca3bb65703804861a6ca4cbc1c9ed16e3ddcc32f62d  tools/architecture/tests/test_object_detail_no_ddl.py
bffc609d76b3e803ff172a7ed784607f8d06317bdc5304c6f263d270f235aee7  /tmp/fmonitor2-object-detail-ratchet-red.log
```

TemporaryDirectory cleanup завершён. Original tree/scanner/baseline неизменны;
git diff-check PASS. Fresh independent Gate3 обязателен до удаления четырёх
baseline entries. Они удаляются только вместе с/после actual importer no-DDL
correction, которая имеет собственный Gate3 и regression contract.
