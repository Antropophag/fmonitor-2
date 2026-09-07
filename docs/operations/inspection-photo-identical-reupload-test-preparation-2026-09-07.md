# Подготовка contract/RED для identical photo re-upload

Дата: 2026-09-07. Change: `allow-identical-photo-reupload-after-revoke`.
Repository HEAD при итоговой фиксации: `b662e1e3a08c206c06f4df40a992fbd083856c1b`.

## Frontier и schema seam

Read-only catalogue показывает exact versions 1..18; следующий свободный successor
— `19`. Historical `InspectionEvidenceSchemaMigration` v8 остаётся predecessor
oracle с unique `(installation_case_id,section_id,sha256)`. Планируемый
`InspectionPhotoContentIndexSchemaMigration` v19 владеет exact predecessor→final
transition, final compatibility и catalogue registration.

Новый schema test подготовлен, но не запускался: production class v19 намеренно
отсутствует до independent test review. Он требует populated revoked row + upload/
revoke operations + AUTO_INCREMENT preservation, exact non-unique ordered content
lookup, retained unique upload-operation identity, repeat no-op, opposite-prefix
isolation, malformed-order index refusal, literal-v8 historical mismatch, successor
final compatibility, `ChecklistSync` admission, `MariaDbInspectionAuthorization`
admission и catalogue version 19.

## Characterization supersession

Executable spec v0.2 сохраняет v0.1 SQL-1062 milestone/hash как historical
provenance и ссылается на GRILL-007. Active final scenario теперь требует new upload
fact at revision 3, two photo identities, three operations, one active/one revoked
row and one physical blob. Entire first row equals the post-revoke snapshot.

Verifier/meta-test создают exact canonical v8 predecessor через production migration,
применяют будущий real successor v19, затем проверяют valid revoke actor с exact
capability, current registered engineer assignment и bounded reason. Они подготовлены
к accepted re-upload и отдельному fresh active-duplicate probe. Zero-mutation
fingerprints остаются для revoke replay, already-revoked rejection и active duplicate. Первые три historical scenario, cleanup,
collision, failure classification, ambient/foreign decoys and deterministic stdout
сохранены. Новый independently calculated transcript hash:

```text
09b498760d9ed265d35372bdd6630e1959bb3073abf2ac2692ef0371d0ccfe0c
```

## Authorization/concurrency regression map

- `tests/AssignmentOrderComposition/manual_checklist_http_smoke_test.php` остаётся
  HTTP fixture для upload admission, exact revoke capability/current engineer,
  reason and completed-section replacement gate.
- `tests/Verification/characterize_inspection_photo_upload_001_test.php` сохраняет
  exact upload replay idempotency and storage failure behavior.
- `tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php`
  сохраняет real same-case concurrent serialization and same-content active duplicate
  zero mutation. Target Gate 3 review SHALL проверить, достаточно ли объединённой
  чувствительности; task 2.2/2.3 остаются unchecked до этого решения.

Standalone photo-upload authority не расширяется до revoke-only/read-only actor;
revoke authority не становится upload authority. Upload denial остаётся на existing
HTTP/local-role admission seam; `ChecklistSync::accept` не получает новую upload-
authorization ответственность. Production/HTTP code пока не менялся.

## Intended RED commands after independent test review

```text
PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/InstallationProcess/inspection_photo_content_index_schema_001_test.php
```

Observed RED on the exact prepared hash: `INTENDED_RED: canonical photo content-index migration v19 is absent`
before fixture DB creation. No database connection or mutation occurred.

```text
PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/Verification/characterize_inspection_photo_revoke_001_test.php
```

Expected RED: verifier creates a healthy canonical v8 predecessor and stops because
the real successor v19 class is absent; it MUST NOT fail on revoke authorization,
assignment, reason, handmade final schema or SQL `1062`. Schema test above is the
qualifying target RED. The characterization command was not run while the shared
DB slot belonged to another verification process.

## Exact hashes

```text
4d3d5c63607036db4532dde59e10948d20cbfa3d0cec5f2d08be1b5b1ea3494e  specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md
57d32139c4718e0fb2268615ba03878fbb2fceb70a8e15eb25527cbc04223965  rapid-pilot/verify-checklist-photo-revoke.php
0d695d528b8ecc915ce3d1eb2a5c04c69ffd2b9549f6e74c32f487916904c46e  tests/Verification/characterize_inspection_photo_revoke_001_test.php
d7a4dbea75a91950fb8ab3f59881b2be466b7a84d853c5e5c15f5c6529abadc4  tests/InstallationProcess/inspection_photo_content_index_schema_001_test.php
5721c82528be21804f9a9641d4ad800c1ee07be9c3af2f6e8c60fcea06345fce  proposal.md
c1f77e1be95559e0cfef3aae192a831678fd47af9d04e05e49d986f40a81843b  design.md
20d036762b375ca43502c255f8c61adc4008b0154d7b28f008cc3a275fb9d66b  tasks.md
38163d1fc3494d648ea9d38e01144d27ac8d43222f0cab4e6061b054aa68c9c9  specs/inspection/identical-photo-reupload/spec.md
fbbc7f9118a62289a50ab5893d9608ebca8608d18d0bddb03ac924a0d19367be  specs/deployment/canonical-inspection-evidence-schema/spec.md
9a67b19242bc1609d00c8a9e923246096b9730a89c988af6390ceb6541b5a6c8  tools/architecture/baseline.json
```

PHP lint for all three executable test/verifier files, strict OpenSpec validation
and focused diff-check pass. No DB test beyond the pre-connection intended RED,
production/schema implementation,
deployment, stand/user-data mutation, global reset, remote/CI or Bitrix action was
performed. Tasks 1.3, 2.2–2.5 and all implementation/deployment tasks remain pending.
