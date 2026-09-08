# Inspection photo content index v19 — focused GREEN

Дата: 2026-09-07. Implementation author: `/root/auth_review`.
Review base at final hash capture: `67667cf9540bd6665042e065253db2bd2c794d52`.
Independent Gate 3: `reviews/tests/INSPECTION-PHOTO-CONTENT-INDEX-019-2026-09-07.md` — `APPROVED`.

## Реализация

- Новый canonical `InspectionPhotoContentIndexSchemaMigration` v19 принимает только
  exact final v8 predecessor либо exact final v19. Он заменяет unique
  `(installation_case_id,section_id,sha256)` тем же ordered non-unique lookup,
  не меняя columns/rows/allocator.
- Catalogue v8 entry остаётся historical v8 owner, но распознаёт exact v19 как
  successor no-op; v19 зарегистрирован contiguous successor. Полный повтор
  catalogue поэтому не пытается применить literal v8 к final v19.
- `ChecklistSync` и `MariaDbInspectionAuthorization` принимают exact historical v8
  или exact successor v19. Malformed/intermediate forms остаются unavailable, runtime
  DDL не добавлен.
- Photo verifier создаёт predecessor через real v8 migration, применяет real v19,
  использует revoke actor с exact capability/current registered engineer/bounded
  reason, принимает identical re-upload revision 3 и затем доказывает active
  identical duplicate без mutation.
- Upload authorization остаётся в существующем HTTP/local-role admission seam;
  revoke authority не расширяет upload authority. Existing same-case transaction
  serialization не изменена.

## GREEN

```text
inspection_photo_content_index_schema_001_test.php
PASS canonical photo content index v19 preserves populated evidence, runtime compatibility and full repeat

characterize_inspection_photo_revoke_001_test.php
ok - CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001 oracle is deterministic, audited, isolated, and correctly classified

characterize_inspection_photo_upload_001_test.php
ok - CHARACTERIZE-INSPECTION-PHOTO-UPLOAD-001 public oracle is deterministic, isolated, and correctly classified

characterize_inspection_photo_limit_concurrency_001_test.php
ok - CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 oracle is deterministic and isolated

manual_checklist_http_smoke_test.php
PASS manual HTTP applied composition -> checklist -> photo -> correction -> completion

pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)
```

Первый post-RED schema run дошёл до final index assertion и выявил только test-side
mysqli scalar type mismatch (`1` integer против `'1'` string). Expected values были
исправлены на фактические native integer types без изменения index semantics; repeat
GREEN. Первый characterization run выявил только отсутствие fixed repository
autoload в standalone verifier; добавлен `app/autoload.php`, после чего real v19 был
обнаружен и behavior GREEN. Эти fixture corrections требуют supplemental review.

## Exact hashes

```text
f987eda25f5701e5a6c68a862fbadb22252c242f55a015c54971d4d02f722551  app/InstallationProcess/InspectionPhotoContentIndexSchemaMigration.php
70e924bb0b9ce7a44b28397ac6e4d022ae8e4047b71a257588e5e4e8e9146f61  app/InstallationProcess/ProductionPilotMigrationCatalogue.php
232cdf869152114ac294332f9b47d56410429752036e234c6646a09850c8a506  app/PilotHttp/ChecklistSync.php
56fc03edd187d60f729c60c386274a69b8ad1280821dbd606c2b9607b2790ef2  app/InspectionEvidence/MariaDbInspectionAuthorization.php
4d3d5c63607036db4532dde59e10948d20cbfa3d0cec5f2d08be1b5b1ea3494e  specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md
c8d150f9c97b9731f5ace820c8c753eebfc8c099994d554407562f64954d08ce  rapid-pilot/verify-checklist-photo-revoke.php
0d695d528b8ecc915ce3d1eb2a5c04c69ffd2b9549f6e74c32f487916904c46e  tests/Verification/characterize_inspection_photo_revoke_001_test.php
82289b83ce7c4a97e4fa7b2260cd3e832713584e07a8480ed4e6826ed1973310  tests/InstallationProcess/inspection_photo_content_index_schema_001_test.php
fce19ebe77237dce6404de92bcb07e2d6071698d76c7b4b25d34905937faab47  openspec tasks.md
9a67b19242bc1609d00c8a9e923246096b9730a89c988af6390ceb6541b5a6c8  tools/architecture/baseline.json
```

Combined tracked diff hash: `6eaec6a874359caa04c5401dcd30d8f0b38e4075e9cdc2c7c66f44bf3e69b601`.
New migration diff hash: `a081b5e2588426456dcf37a658c9543b60049becc06d7baa5a51b32767564f28`.
New schema-test diff hash: `2c2ec4f862f5ec62223f0edd131cb6494eaf370a31d73e66a40c9c6b25c896e3`.

No global reset, stand/user-data migration, deployment, remote/CI or Bitrix action
was performed. Tasks 5.2–5.4 remain pending independent review/deployment work.
