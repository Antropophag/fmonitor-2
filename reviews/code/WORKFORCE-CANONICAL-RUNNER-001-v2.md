# Code rereview: WORKFORCE-CANONICAL-RUNNER-001 v0.1

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_runner_code_rereview_20260902ac`
- Executable spec: `specs/WORKFORCE-CANONICAL-RUNNER-001.md` v0.1
- OpenSpec change: `register-workforce-history-canonical-v5`
- Supersedes review verdict:
  `reviews/code/WORKFORCE-CANONICAL-RUNNER-001.md`
- Verdict: `CHANGES_REQUESTED`

## Standards

Исправление database-default collation находится в canonical migration
ownership: v2 читает и валидирует default collation database и явно использует
её при clean `CREATE`; v5 создаёт новые таблицы с тем же значением. Importer не
выполняет прежний workforce `ALTER` или direct v5 `apply`. Read-only
`WorkforceHistorySchemaReadiness` инкапсулирует exact v5 classification и не
создаёт второго DDL owner.

Canonical entrypoint передаёт тот же process prefix и DB configuration в public
runner, а затем запускает bootstrap. Public runner валидирует composed 25/26
границу до connection и содержит literal ordered catalogue v1–v5.

Однако runtime ownership boundary реализован не полностью: bootstrap всё ещё
импортирует и условно вызывает v2 workforce migration. Это не только возможный
code smell, а прямое расхождение с документированным slice contract.

## Spec

### Blocking finding — runtime bootstrap остаётся workforce schema owner

Executable spec §1 требует: «runtime bootstrap ... остаются только consumers
schema» и запрещает вне canonical migrations/runner «вызовы workforce
migration». Несмотря на то что entrypoint уже запускает canonical runner до
bootstrap, `rapid-pilot/docker-bootstrap.php:8,53-58` сохраняет fallback:

```php
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;
// ...
if (!$catalogExists) {
    $result = WorkforceCatalogSchemaMigration::apply($db, $processPrefix);
}
```

Этот путь является self-healing workforce DDL из production runtime bootstrap.
На штатном entrypoint он мёртв после успешного runner, но при отдельном запуске
bootstrap либо при regression порядка снова владеет созданием catalog. Поэтому
fail-closed deployment semantics не доказаны, а architecture check не ловит
весь утверждённый запрет: текущие шесть правил проходят при наличии вызова.

Минимальная correction: удалить импорт, existence probe и вызов v2 migration;
оставить единый `WorkforceHistorySchemaReadiness::assertReady(...)`, который
должен fail closed при отсутствующей/несовместимой v5 schema. Architecture
ratchet и executable ownership assertion следует расширить так, чтобы любой
production runtime вызов `WorkforceCatalogSchemaMigration::apply(...)` также
делал проверку красной. Изменённый test обязан получить свежий независимый test
review до следующего GREEN/code rereview.

## Независимая verification

Fresh isolated Compose project
`fmonitor2-wcr-code-rereview-20260902ac`, host port `25352`:

```text
workforce_canonical_runner_001_test.php
PASS: complete public-runner matrix

production_migration_runner_001_test.php
PASS: CLI contract

pilot_case_import_001_test.php
PASS: CLI contract

reset-test-db.php
TEST_DB_RESET_OK

make migrate
{"ok":true,"schemaVersion":5,"appliedVersions":[1,2,3,4,5]}

make migrate
{"ok":true,"schemaVersion":5,"appliedVersions":[]}
```

На reset database independently observed default и все четыре workforce table
collations равны exact `utf8mb4_unicode_ci`. Полная focused matrix отдельно
покрыла clean/repeat/partial, v1/v5 conflict, unexpected v5 failure, composed
25/26 и family-local 37/38. Regression-alignment test review и GREEN evidence
фиксируют, что полный `make verify` содержит только прежние восемь DB failures
и дублирующий один из них E2E artifact; focused correction новых failures не
добавляет.

```text
git diff --check
PASS

make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid
```

Reviewer cleanup удалил собственный Compose project, network и volume.

## Verdict

`CHANGES_REQUESTED`

Collation correction, v1–v5 behavior, prefix contracts, canonical ordering и
readiness seam зелёные. Gate 5 остаётся закрыт, пока runtime bootstrap сохраняет
fallback-вызов workforce v2 migration, запрещённый approved §1 contract.
