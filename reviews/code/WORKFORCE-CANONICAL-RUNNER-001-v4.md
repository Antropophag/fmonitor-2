# Final code rereview v4: WORKFORCE-CANONICAL-RUNNER-001 v0.1

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_runner_code_rereview_20260902am`
- Executable spec: `specs/WORKFORCE-CANONICAL-RUNNER-001.md` v0.1
- OpenSpec change: `register-workforce-history-canonical-v5`
- Supersedes code-review verdict:
  `reviews/code/WORKFORCE-CANONICAL-RUNNER-001-v3.md`
- Corrected test review:
  `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-architecture-alias-indirect-v2.md`
- Verdict: `APPROVED`

## Standards

Blocking finding v3 закрыт минимальным расширением существующего
non-baselineable `workforce_migration_ownership` rule. Checker извлекает
реальный alias из `use ... as ...` для обеих migration classes и ищет
`::apply(...)` по этому alias. Отдельно он связывает произвольную PHP variable
с workforce basename и запрещёнными `CREATE TABLE`, `ALTER TABLE` и
`DROP TABLE`, включая interpolated variable target.

Исправление не привязано к identifiers из первоначальной reproduction:
18 architecture units используют разные arbitrary aliases и разные variable
names для трёх DDL operations. Каждый из пяти новых запрещённых examples даёт
ровно один finding, поэтому duplicate finding не маскирует чувствительность.
Literal one-line/multiline matrix сохранена и остаётся green.

Owner allowlist не расширен: exact canonical runner, classes с suffix
`SchemaMigration.php`, `rapid-pilot/verify-*` и demo fixtures остаются
разрешёнными по прежним path predicates. Production bootstrap/importer не
попали в allowlist. `compare()` по-прежнему безусловно возвращает ошибку для
каждого workforce ownership finding, даже если тот уже записан в baseline;
`tools/architecture/baseline.json` не изменён.

## Spec

Полный corrected slice соответствует approved contract:

- canonical runner содержит literal v1→v5 order, final `schemaVersion=5` и
  composed prefix 25/26 до DB connection;
- direct v5 migration сохраняет отдельный family-local предел 37/38;
- clean v2/v5 DDL использует database-default collation в migration ownership;
- Compose entrypoint запускает public canonical runner до bootstrap;
- bootstrap и production importer выполняют только fail-closed read-only
  `WorkforceHistorySchemaReadiness::assertReady`, без workforce self-healing;
- production scan и architecture check не обнаруживают runtime v2/v5 apply
  либо workforce-targeted CREATE/ALTER/DROP вне утверждённых owners.

Оставшийся `ChecklistSync` runtime DDL относится к прежнему отдельному
checklist-operation schema debt и не является workforce catalogue/history
target этого executable slice. Он не скрывается новым workforce rule и не
расширялся в исправлении.

## Independent verification

Свежий isolated Compose project `fmonitor2-wcr-rereview-am`, host port
`26444`:

```text
workforce_canonical_runner_001_test.php
PASS: WORKFORCE-CANONICAL-RUNNER-001 complete public-runner matrix.

production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

pilot_case_import_001_test.php
PASS: PILOT-CASE-IMPORT-001 CLI contract
```

Этим независимо воспроизведены clean/repeat/partial, earlier/v5 conflict,
unexpected v5 failure, exact schema/collation и composed prefix 25/26. После
проверки reviewer удалил собственные container, network и volume; `ps --all`
вернул пустой список.

```text
python3 -m unittest tools.architecture.tests.test_debt_fingerprint
Ran 18 tests — OK

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid

git diff --check
PASS
```

Последняя full-suite evidence в
`docs/operations/workforce-canonical-runner-runtime-ownership-green-evidence.md`
фиксирует PASS setup/migrate/architecture/lint/unit/characterization/diff и
только established восемь DB regressions плюс дублирующий один E2E artifact.
Текущая focused correction не меняла эти behavior assertions; свежие 18/18
architecture units, public runner и связанные regressions проходят.

## Verdict

`APPROVED`

Исправленный checker доказывает absolute runtime workforce-DDL ownership для
literal, alias и indirect variable-target forms без allowlist/baseline bypass.
Предыдущие runtime ownership, canonical ordering, prefix и collation blockers
остаются закрыты. Gate 5 пройден.
