# OBJECT-DETAIL-NO-DDL-RATCHET-001 v0.1

Статус: DRAFT / TECHNICAL_GATE_1_PENDING. Дата: 2026-09-05.
Supporting architecture contract под `canonicalize-object-detail-snapshot-schema`
task3.3. Owner authority — approved OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4.

## Простыми словами

После удаления двух CREATE из importer их старые исключения должны исчезнуть
из architecture baseline. Проверка должна отклонить возврат тех же строк в
runtime. Новая проверка использует действующий публичный architecture CLI;
новый parser или изменение других правил не требуются.

## Actor and seam

Actor — developer/CI. Public seam:
`python3 tools/architecture/check.py --json`, exit/JSON result.
Test запускает тот же scanner и фактический baseline в отдельном task-owned
fixture repository: копируются только check.py/baseline.json и перечисленные
ниже synthetic source files. Private collect/compare APIs не подменяются.
Fixture SQL никогда не исполняется и не подключается к DB.

## Exact contract

1. Fixture `rapid-pilot/import-production-object-details.php` содержит только
   две прежние runtime CREATE statements (как adversarial inputs). Их current
   approved-debt fingerprints:
   `0869fae855bd5c76` и `5e45e35f56e1f931`.
   После удаления exceptions public CLI MUST вернуть exit1 и JSON `ok:false`
   с ddl_ownership finding для каждой exact строки/пути. Это подтверждает, что
   возврат старого debt больше не разрешён; одного unrelated error недостаточно.
2. Такие же literal family CREATE в разрешённом canonical path
   `app/InstallationProcess/ObjectDetailSnapshotEngineSchemaMigration.php`
   дают exit0, `ok:true`, empty errors. Другие правила scanner не выключаются.
3. Runtime fixture с единственным public read-only вызовом
   `ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($db,$prefix)`
   без DDL даёт exit0, `ok:true`, empty errors. Readiness check не становится
   schema owner и не требует baseline exception.
4. Baseline correction удаляет ровно четыре existing entries:
   оба `ddl|rapid-pilot/import-production-object-details.php|<fingerprint>`
   и оба `rapid-mutation|rapid-pilot/import-production-object-details.php|<fingerprint>`
   с fingerprints пункта1. Остальные debt, hotspots, seams, ownership rules и
   scanner code сохраняются byte-equivalent. `--write-baseline` не используется.
5. Correction применяется только вместе с или после фактического удаления
   обеих runtime CREATE из importer после его approved Gate3. Нельзя уменьшить
   budget на ещё существующий debt или добавить новый exception для GREEN.

## Fixture ownership and expectations

Новый test file: `tools/architecture/tests/test_object_detail_no_ddl.py`.
Он создаёт только собственный TemporaryDirectory, копии двух tool inputs,
source root directories и один explicit PHP fixture на case. Original tree,
baseline и files не меняются при test execution. Cleanup удаляет только этот
owned temporary root; production/source data/secrets не используются.
Expected findings выводятся из approved ownership rule и historical input
fingerprints, не из stdout текущего scanner. Child CLI имеет timeout30s;
missing PHP/Python/неисправный fixture — SETUP_FAILURE, не qualifying RED.

## Gates and Done

После technical Gate1 добавить test и доказать intended RED: текущий baseline
ещё принимает исторические runtime CREATE, тогда как contract требует rejection.
Canonical-owner/readiness controls должны быть healthy. Fresh independent Gate3
проверяет test/spec/RED до изменения baseline/importer.
После reviewed importer correction удалить только четыре entries, получить
targeted test GREEN и `make architecture-check`, затем independent Gate5.
Importer regression, полный make verify и parent Done остаются отдельными
обязательными gates. Этот supporting contract не меняет serial DML или product
policy и не требует повторного owner table-transfer approval.
