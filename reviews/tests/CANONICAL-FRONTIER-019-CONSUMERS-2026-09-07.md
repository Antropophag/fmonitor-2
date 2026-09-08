# Canonical frontier 19 — test consumer reconciliation

Автор: `/root`. Independent reviewer: `/root/photo_review`.
Статус: **APPROVED**, focused GREEN на exact hashes ниже.
Change: `allow-identical-photo-reupload-after-revoke`, task 3.2.
Основание: reviewed v19 migration contract и
`reviews/tests/INSPECTION-PHOTO-CONTENT-INDEX-019-2026-09-07.md`.

## Scope и сохранение исторических контрактов

Ровно 16 текущих consumers полного `bin/fmonitor2-migrate.php`/canonical runner
ожидают terminal 19 и добавляют 19 к фактически применяемым successor lists.
Empty appliedVersions на повторе сохраняется. Actor 18, исторические migration
versions, отрицательные причины/коды и старые isolated engines не заменяются.
Predecessor `6aa39aa` read-only catalogue отдельно подтвердил terminal 18;
его полный verify уже сохранён. Intended migration RED (класс v19 отсутствует)
зафиксирован в отдельном approved review. Здесь не заявляется новый behavioral RED
на основании одной механической замены номера.

Два consumers проверяют и форму photo index:

- `production_migration_runner_001_test.php`: current catalogue явно переводит
  единственный literal photo-content UNIQUE в INDEX; shared historical V13 oracle
  и все остальные indexes/columns/FK/checks сохранены.
- `inspection_evidence_schema_001_test.php`: только два вызова после полного
  current runner используют explicit current-content-index mode. Остальные
  isolated v8 fixtures проверяют прежний unique index. Прямой v8 вызов на v19
  обязан сообщить exact photo-table conflict без мутаций; отдельный v8 namespace
  сохраняет прежнее доказательство повторного direct-v8 no-op. Full canonical
  repeat остаётся успешным с empty appliedVersions. Runtime DML-only/DDL prohibition,
  prefix isolation, metadata and row/allocator preservation сохранены.

Никакие production files, shared historical catalog helpers, protected E2E,
manual stand или пользовательские данные этим patch не изменены.

## Проверки

PHP lint всех 16 файлов PASS. Independent focused execution: все 16 consumers
PASS. Первый запуск `harness_otiz_canonical_compat_001_test.php` корректно сообщил
`SETUP_FAILURE`: shared prepared canonical DB ещё находилась на v18, а его preflight
применил `[19]`. Немедленный повтор на выполненной обязательной v19 precondition
PASS с exact no-op `appliedVersions: []`; expectation не расширялась. Это сохранено
как setup history, не behavioral RED. Private evidence:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/photo19-independent-review.log`,
SHA-256 `02425ce7c746370d6846d973ea6dff25a894a8578b6402b3faee942bf0cd06e6`.

Review verdict: **APPROVED**. Изменения механически продвигают только current
canonical frontier 18→19, production runner отдельно проверяет exact replacement
photo index, а isolated historical v8 and actor 18 assertions сохранены.
Не запускать новый полный verify посреди параллельной реализации.

## Exact test hashes

```text
a8c37da48d5a5daa13c642df6925e244b27cb8321324678a5458b417c54ccb07  tests/InstallationProcess/assignment_order_original_attempt_audit_schema_001_test.php
a73b6fb09ba183164a2d587ec82b6ae13c9c23b610af50f3b5ac41eef6ddc702  tests/InstallationProcess/selection_canonical_registration_001_test.php
d8d8798b87e8e1f374b147b7a47910329a7316b7aa657ac3883ff9f59dbbf6d3  tests/InstallationProcess/classification_provenance_schema_001_test.php
f04382f849da6bd6db8498f1d68d2eded6e86651d85018ffa6c954e4ca6684d8  tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
ad944feadb8f646dd9d32bfe50d68fccce9ba066101dbf50d2f1531d9c6f5afa  tests/InstallationProcess/inspection_planning_schema_001_test.php
61e2c222baf8ee8ee932b45428630ceff96e89ebb8f3d6fc34c029810c3f5235  tests/InstallationProcess/production_migration_runner_001_test.php
20bb42b96847fee7d3ada6e09bb05dd29106b7194c22c887c2dbab86e6f8d48e  tests/InstallationProcess/checklist_template_schema_001_test.php
5e2facd410627537fb807dfade53363e112f69123610c527040780b035c3db8d  tests/InstallationProcess/pilot_http_auth_001_test.php
02e8f3dbd8b52cbf5961606159a25b8f300a72a4a12b74b7ad953527c7f22c43  tests/InstallationProcess/pilot_case_import_001_test.php
ed86cf88d6bce60c2deeb6c09c35074437c6d84ead96530ba634bff775e9073e  tests/InstallationProcess/workforce_canonical_runner_001_test.php
2ad628c242f3e9f83b4512ca6a9413595dd6ff363d92b1530d01a3323a0fe18f  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
0464965276cbaee894a3a8ba333c0f5f5a639788b393a05af76d09a6dad6674f  tests/InstallationProcess/identity_access_schema_001_test.php
f775d63afd6bdf73549a67959fc842875b065e88d506402854950ff7dd90a422  tests/InstallationProcess/installation_completion_schema_001_test.php
1b4a6e318479a868b3b2a5863ed81c28bf1d21633864ee0c8aea563223f3fe6c  tests/InstallationProcess/inspection_evidence_schema_001_test.php
df862a94189fa582b1a2e49535c8e3f97139069001f333ef3fd43799fbe81600  tests/Verification/harness_otiz_canonical_compat_001_test.php
d6fa74f973d08839d18ece1cde289eb861e212b14c4e7c894184ab784c414a2a  rapid-pilot/verify-calendar-projections.php
```
