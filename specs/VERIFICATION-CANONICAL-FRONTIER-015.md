# VERIFICATION-CANONICAL-FRONTIER-015

Версия0.1,2026-09-07. Gate1 required.

## Простыми словами

Проверки актуального migration runner должны ожидать одобренный terminal15,
сохраняя точность проверок прежних таблиц, данных и ошибок. Это test-only
reconciliation уже принятого поведения; новые migrations или product decisions
не вводятся.

## Authority and public seam

Actor: release engineer. Public action: существующий `bin/fmonitor2-migrate.php`
и verifier workflows, которые его вызывают. Reuse approved canonical owners:
13 OriginalAttemptAuditSchemaMigration,14 AssignmentOrderIdentityRegistryMigration,
15 AssignmentOrderSelectionSchemaMigration. Records:
`reviews/code/ASSIGNMENT-ORDER-ORIGINAL-ATTEMPT-AUDIT-001-v1.md` и
`reviews/code/SELECTION-CANONICAL-REGISTRATION-001-v1.md`.

Original engine metadata/backfill/coherence/audit и typed failure contracts не
пересматриваются. OS/test DB permissions прежние, production imports запрещены,
синтетические fixtures изолируются и очищаются. Нет новых grants/domain writers.

## Exact current-consumer expectations

Только вызовы ПОЛНОГО актуального canonical runner получают новое positive
ожидание `schemaVersion:15`. Clean `appliedVersions` точно `[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15]`;
repeat точно `[]`. Если fixture уже имеет v1-v4 и только partialv5, successful
recovery применяет точно `[5,6,7,8,9,10,11,12,13,14,15]`.
Другие partial fixtures получают exact прежний необходимый набор плюс отсутствующие
13/14/15 в порядке регистрации; не вычислять expected terminal из production.

Isolated engine/restricted verification compositions остаются на своей exact
version. Например race verification, намеренно composing только до11, сохраняет
schemaVersion11. Ошибка более раннего владельца сохраняет её actual version,
reason/exit/stderr и запрет более поздних writes. Никакой массовой замены всех12.

Canonical catalogue включает ровно прежние v1-v12 tables, original audit13 family
и семь имён registry/selection14/15. Сохраняются strict no-extras checks для всего
каталога, metadata/index/FK/CHECK проверяемых predecessor families, populated
history/counters/prefix/permission/interruption/invariant assertions.
Новые metadata expectations берутся из отдельно approved test-only literals либо
существующего approved composed catalog proof в production_migration_runner_001_test.
Если proof делегирован approved engine readiness, это делегирование явно
записывается; прежние table checks не заменяются общей readiness boolean.

## Bounded files

Возможные правки только следующих13 consumers и test-only catalog support:
- tests/InstallationProcess/checklist_template_schema_001_test.php
- tests/InstallationProcess/classification_provenance_schema_001_test.php
- tests/InstallationProcess/identity_access_schema_001_test.php
- tests/InstallationProcess/inspection_evidence_schema_001_test.php
- tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
- tests/InstallationProcess/inspection_planning_schema_001_test.php
- tests/InstallationProcess/installation_completion_schema_001_test.php
- tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
- tests/InstallationProcess/pilot_case_import_001_test.php
- tests/InstallationProcess/pilot_http_auth_001_test.php
- tests/InstallationProcess/workforce_canonical_runner_001_test.php
- tests/Verification/harness_otiz_canonical_compat_001_test.php
- rapid-pilot/verify-calendar-projections.php

Production code, protected pilot_e2e_flow_001_test.php и его fixtures/assertions
не изменяются. pilot_demo_bootstrap_001_test.php повторяется как consumer
pilot_case_import после исправления его canonical precondition, без ослабления
собственных assertions. Если обнаружится иная причина failure — отдельный scope.

## Evidence and gates

Первый fullverify source7cb79d0 показывает устаревшие terminal12 assertions при
actual15; это диагностическая evidence, не RED отсутствующего нового поведения.
После Gate1 amended tests проверяются against separately isolated pre-registration
source `fbbb41e` (canonical13) с overlay только этих tests/support: должны падать
из-за отсутствия approved successors14/15 в real CLI result. Никаких fake
migration outputs, runtime interception, замен native drivers или допускаfailure.
Gate3 независимо проверяет oracle provenance, сохранность старых assertions и
intended pre-registration RED. Gate4 применяет test-only patch к текущему already
approved implementation; production code не требуется. GREEN всех affected
consumers, независимый Gate5, architecture/lint/diff и полный VERIFY обязательны.
E2E user-visible legacy-label failure этим slice не закрывается и не скрывается.
