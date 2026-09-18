## Context

См. `proposal.md`. В #192 literal `30` и `range(1, 30)` появились одновременно в трёх разных ролях: setup актуальной БД, независимый catalog frontier и точные historical upgrade/recovery contracts. Общий helper допустим только для первой роли и для ожиданий current-run/replay, опирающихся на независимый test oracle.

## Goals / Non-Goals

**Goals:**

- Один immutable PHP test contract задаёт literal current frontier и строит ожидаемые clean/replay результаты.
- Реальные предметные consumers используют его при подготовке актуальной БД, сохраняя собственные проверки фактов и изоляцию.
- Профильный frontier test остаётся способен отличить удалённую последнюю и промежуточную migration.

**Non-Goals:**

- Не обобщать historical v22/v23/v25 upgrade, recovery bundle schema, exact table inventories или contract конкретной migration.
- Не создавать shared database instance, fixture cache, framework либо production v31.
- Не менять `app/**`, DDL, runtime recovery, Python/Compose fixtures, classifier или CI lane.

## Decisions

1. Новый `tests/Support/CurrentProductionSchemaContract.php` владеет literal `CURRENT_VERSION = 30`, `versions()`, clean и replay tuples. Он не читает production catalogue, runner output или БД. Альтернатива — расширить `ProductionMigrationRunnerCatalogContract`; отвергнута, потому что существующий класс хранит исторические literal contracts и не должен молча стать latest-helper.
2. `production_schema_frontier_001_test.php` сверяет ключи production catalogue с независимым `versions()` и использует тот же contract для clean/replay результата. Два bounded mutation запуска подменяют только предоставленный catalogue: без v30 и без промежуточной v15. Ожидаемая граница не меняется, поэтому оба дефекта обязаны дать адресный mismatch.
3. Переводятся только assertions, где current frontier служит setup/replay оболочкой предметного сценария. Assertions точного каталога, historical start/suffix, recovery bundle version и compatibility rejection остаются literal или исторически вычисленными.
4. Persistence owner, rapid-pilot adapter и production dependencies отсутствуют: это test-only support. Architecture impact ограничен существующими разрешёнными test dependencies; при необходимости добавляется точная registration, не глобальное правило.

### Классификация assertions из #192

По first-parent diff merge #192 найдена 61 добавленная PHP-строка с current-version expectation. Из них 31 setup/replay expectation переведена на helper в 18 consumers: assignment-order audit, checklist template, classification provenance, construction-control filter, identity access, ОТиЗ settlement schema, pilot case import, production migration runner, Jobs schema, ОТиЗ runtime и concurrency, initial-owner provisioning, process readiness, readiness schema, runtime browser, schema frontier, Yii2 readiness и feedback replay.

Оставшиеся 30 literal-строк намеренно сохраняются по назначению assertion: historical start/suffix и partial recovery; recovery/bundle compatibility; exact catalog/current table inventory; migration-specific registration и compatibility matrices. В частности, сохраняются identity recovery suffixes, settlement v23→v24…v30, runner v3→v30, deadline/jobs/forward recovery bundles, feedback catalog frontier, inspection-item table inventory и inspection/selection/workforce migration contracts. Таким образом число дублирующих current-version setup expectations в выбранной (полной для PHP diff #192) области меняется 31 → 0, а не 61 → 30: оставшиеся 30 не являются setup-дублями.

## Risks / Trade-offs

- [Helper может скрыть удалённую migration] → его expected frontier literal и mutation proofs запускаются через профильный frontier test.
- [Слишком широкий перевод ослабит historical checks] → классификация ведётся по отдельным assertions, а не файлам; recovery/upgrade literals сохраняются.
- [Большой PHP-набор дорог локально] → до push выполняются bounded MariaDB consumers, mutation proofs и policy/architecture checks; полный suite оставлен exact-source CI.

## Migration Plan

Test-only изменение: добавить contract, перевести выбранные consumers, выполнить focused checks и mutation proofs, затем один exact-source CI. Rollback — revert PR; production data и schema не меняются.
