# Code rereview v3: WORKFORCE-CANONICAL-RUNNER-001 v0.1

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_runner_code_rereview_20260902ai`
- Executable spec: `specs/WORKFORCE-CANONICAL-RUNNER-001.md` v0.1
- OpenSpec change: `register-workforce-history-canonical-v5`
- Supersedes review verdict:
  `reviews/code/WORKFORCE-CANONICAL-RUNNER-001-v2.md`
- Verdict: `CHANGES_REQUESTED`

## Standards

Предыдущий runtime-ownership blocker в production code закрыт:
`rapid-pilot/docker-bootstrap.php` больше не импортирует и не вызывает
ни `WorkforceCatalogSchemaMigration`, ни
`BitrixWorkforceHistorySchemaMigration`, не выполняет workforce DDL и
fail-closed читает exact v5 readiness. Importer также остаётся
read-only schema consumer, а entrypoint запускает canonical runner до
bootstrap.

### Blocking finding — absolute ownership rule даёт ложные negative

Executable spec §5 требует machine-checkable запрет любых runtime
workforce migration calls и workforce `CREATE`/`ALTER`/`DROP`, а новая
architecture policy делает finding non-baselineable. Текущие regex в
`tools/architecture/check.py:27-34` проверяют только два literal
class names и только SQL, где workforce basename находится в том же
literal fragment.

Независимая fixture reproduction показала пустой
`workforce_migration_ownership` для обоих запрещённых production
форм:

```php
use FMonitor2\InstallationProcess\BitrixWorkforceHistorySchemaMigration as WorkforceV5;
WorkforceV5::apply($db, $prefix);

$table = $prefix . 'fm2_workforce_catalog';
$db->query("CREATE TABLE `{$table}` (id INT)");
```

Существующая matrix в
`tools/architecture/tests/test_debt_fingerprint.py:480-504` содержит восемь
literal examples и не доказывает alias/dynamic-name resistance. PASS самого
ratchet поэтому пока не доказывает утверждённый абсолютный
invariant.

Нужна минимальная correction: детектировать imported aliases и
variable/dynamic workforce table targets (либо эквивалентно закрыть
эти формы более глубоким анализом) и добавить чувствительные
unit cases для v2/v5 alias, direct/dynamic class, а также
one-line/multiline/variable-target `CREATE`, `ALTER` и `DROP`. Запрет должен
остать non-baselineable.

Неблокирующий design smell: public
`BitrixWorkforceHistorySchemaMigration::classify()` принимает connection и
три связанных string-аргумента; это data clump/primitive obsession и
расширение migration API. Текущий readiness seam передаёт их
согласованно, поэтому observable defect в этом slice не доказан;
более узкий read-only classifier API снизил бы misuse risk.

## Spec

Помимо недоказанного architecture invariant, полный corrected slice
соответствует executable/OpenSpec behavior:

- runner имеет literal order v1→v5 и final `schemaVersion=5`;
- composed prefix 25/26 валидирован до DB connection, а direct v5
  сохраняет family-local 37/38;
- migration-owned database-default collation correction закрывает v1
  review blocker без runtime repair;
- bootstrap и importer используют read-only readiness, а production
  ordering запускает canonical runner первым;
- workforce facts, import semantics и storage design не изменены.

## Independent verification

Свежий isolated Compose project
`fmonitor2-wcr-rereview-ai`, host port `25491`:

```text
workforce_canonical_runner_001_test.php
PASS: WORKFORCE-CANONICAL-RUNNER-001 complete public-runner matrix.

production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

pilot_case_import_001_test.php
PASS: PILOT-CASE-IMPORT-001 CLI contract
```

Таким образом independently green clean/repeat/partial, v1/v5 conflict,
unexpected v5 failure, exact schema/collation, composed 25/26 и direct 37/38.
Reviewer удалил свои container, network и volume.

```text
python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 16 tests — OK

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid

git diff --check
PASS
```

Последняя full-suite evidence в
`docs/operations/workforce-canonical-runner-runtime-ownership-green-evidence.md`
фиксирует PASS setup/migrate/architecture/lint/unit/characterization/diff
и только известные восемь DB regressions плюс дублирующий один из
них E2E artifact. Focused correction новых behavior regressions не добавила.

## Verdict

`CHANGES_REQUESTED`

Production implementation закрыла оба прежних blocker — clean
collation и runtime v2 fallback. Gate 5 ещё не закрыт, потому что
machine-checkable absolute workforce-ownership rule не ловит alias invocation
и variable-target workforce DDL, хотя обе формы нарушают §5.
