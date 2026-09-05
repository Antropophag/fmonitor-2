# V12 consumer fixture patch — первый прогон после Gate 3

Дата: 2026-09-05. Patch author: `/root/importer_authority_review`;
применение/run: `/root`. Approved Gate 3 commit `072c2f8`.
Применён exact patch
`ca22acc2980d7505c215cc20019fac60307c36ad21d04b11c7879f41a83bf5a4`.

Последовательно выполнены 11 affected PHP commands и unchanged
pilot_demo_bootstrap consumer с documented test-only admin password.
Каждому child дан внешний timeout180s; ни один timeout не сработал. Log:
`/tmp/fmonitor2-v12-consumer-green.log`, SHA-256
`f843522a21bc26547d0d1c273eb67b7b5e3b9f3cef67afa9f50adbd745f06f0b`.
Имя log отражает попытку GREEN, не результат: общий exit1, три failures.

## Результаты

PASS (exit0): checklist_template_schema, identity_access_schema,
inspection_evidence_schema, inspection_item_complete_001_mariadb,
inspection_planning_schema, installation_completion_schema, pilot_case_import,
pilot_http_auth и verify-calendar-projections.

Две ошибки approved proposed patch:

1. classification_provenance_schema: expected race winner terminal12/[11],
   actual11/[11]. Read-only source подтверждает, что
   `classification_provenance_barrier_runner.php` вызывает family-only
   `ClassificationProvenanceSchemaMigration::apply`, не full runner. Worker
   hash `409a00d9d6c0cb929a6a91800d115cc81245e7349e768ef21f66fb798a6a6c56`.
   Его historical local v11 нельзя было менять. Initial inventory/Gate1/Gate3
   ошиблись в classification этого seam; correction возвращается в Gate1/3.
2. workforce_canonical_runner: exact tables присутствуют, но patch добавил
   object-detail entries перед invitations. Binary order требует invitations,
   затем object_detail_quarantine, object_details, role_permissions. Expected
   literal order исправляется по именам, не sort/relax фактического результата.

Unchanged pilot_demo_bootstrap прошёл corrected pilot_case_import prerequisite,
затем получил existing protected pilot_e2e_flow child failure. Этот отдельный
blocker сохраняется и не разрешает изменение protected test/dependencies.

## Продолжение

Supporting spec candidate уточнён без test correction: race local11 отдельно от
ordinary full-repeat12; binary position двух table names прописана явно.
Нужны новый independent technical Gate1, unapplied correction patch, fresh
Gate3, минимальное применение и targeted rerun обоих affected cases. Остальные
9 checks повторяются только если новое изменение затронет их контракт.
Gate5 и full integration остаются открытыми. Production/test skips не добавлены,
history не переписана, no full GREEN/VERIFY_OK claim.
