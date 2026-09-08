# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — quote-aware capability RED evidence

- Date: `2026-09-05`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Reviewed implementation: `e4ab2fc3ff3dd7a8612a7f3fd9afb0e604ab244f`
- Triggering Gate 5 finding: `0e9c96a25d2f6533c6051c5041ae40aa77108e7e`
- Scope: только Gate 2 regression; production и девять незакоммиченных command-slice файлов не изменены.

Регрессия использует существующий public seam
`AssignmentOrderOriginalSchemaMigration::apply` и task-owned isolated MariaDB.
Fixture сохраняет синтаксически точную whole-expression форму
`capability IN (...)`, но меняет bytes двух quoted capability literals на
`ASSIGNMENT_ORDER.PREPARE` и `Construction_Control_Engineer`. Capability
identifiers являются exact strings, поэтому approved fail-closed contract
требует `CONFLICT`, единственный affected table
`fm2_process_user_capabilities`, отсутствие original tables и byte-identical
snapshot до/после вызова.

## Exact hashes до запуска

```text
e418c4cf9e2d37e6bf9da799f6bdfc5b2faa0f79e123b2be62810dd614b4cb85  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
72845e1fa73fed45dbb719d28538ea471ad202984ad7bb7cf633fa451d0d00c5  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
064b2ffe51a1582d850eaed665232b84f267eec9027eb31ad523d05486d45bbc  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
150aaf096a6e3eb928f765f9d48500a44adc2dc10eb18c07fb3967cf8eafb41b  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v2-v2.md
```

## RED transcript

```text
$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
PHP Fatal error:  Uncaught TestFailure: uppercase_literals capability conflicts before original DDL with zero state change.
Expected: array (
  0 => AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT,
  1 => array ('fm2_process_user_capabilities'),
  2 => array (),
  3 => true,
)
Actual: array (
  0 => AssignmentOrderOriginalSchemaMigrationStatus::APPLIED,
  1 => array (
    'fm2_assignment_order_original_roots',
    'fm2_assignment_order_original_revisions',
    'fm2_assignment_order_original_requests',
    'fm2_assignment_order_original_events',
    'fm2_assignment_order_original_audits',
    'fm2_assignment_order_original_maintenance_requests',
    'fm2_assignment_order_original_maintenance_audits',
    'fm2_process_user_capabilities',
  ),
  2 => array (
    'fm2_assignment_order_original_roots',
    'fm2_assignment_order_original_revisions',
    'fm2_assignment_order_original_requests',
    'fm2_assignment_order_original_events',
    'fm2_assignment_order_original_audits',
    'fm2_assignment_order_original_maintenance_requests',
    'fm2_assignment_order_original_maintenance_audits',
  ),
  3 => false,
)
exit 255
```

Classification: intended RED. Live isolated MariaDB, fixture construction and
public migration invocation completed. Единственное расхождение — production
lowercases bytes внутри quoted literals, принимает не-exact V4 CHECK, выполняет
original DDL и публикует V5. Тест одновременно доказывает неправильный status,
точный affected-table contract, наличие запрещённых original tables и изменение
snapshot; setup failure или skip отсутствуют.

Изменение теста возвращает finding к Gate 2. До production correction обязателен
fresh independent Gate 3; после minimal GREEN обязателен fresh independent Gate 5.
