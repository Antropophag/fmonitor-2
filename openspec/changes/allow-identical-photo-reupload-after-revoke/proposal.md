## Why

Текущая canonical photo schema запрещает новую загрузку тех же bytes после отзыва исторического фото: retained row продолжает занимать unique `(installation_case_id, section_id, sha256)`, и публичный `ChecklistSync::accept(...)` падает SQL `1062`. Это production-дефект относительно более нового решения владельца GRILL-007, которое требует сохранить отозванный факт навсегда и принять identical content как новый evidence fact.

Behavior slice: повторная загрузка идентичного фото после отзыва. Actor: пользователь, уже допущенный существующей photo-upload авторизацией. Source oracle: `docs/operations/inspection-photo-revoke-retention-owner-decision.md` (`APPROVED_PERMANENT_RETENTION`, GRILL-007). Target public seam: текущий photo-upload command через `ChecklistSync::accept(...)`, с отдельным существующим revoke command. Release value: владелец может отозвать ошибочное фото и загрузить те же bytes новым фактом без потери истории и без SQL failure.

## What Changes

- Canonical inspection-evidence schema заменяет unique content index `(installation_case_id, section_id, sha256)` на non-unique lookup index, сохраняя все существующие rows byte-for-byte.
- После revoke identical bytes с новым canonical operation ID создают новый `photo_uploaded` fact и новую photo identity; прежняя revoked photo row остаётся неизменной.
- Content-addressed storage переиспользует существующий physical blob с тем же SHA-256; blob не удаляется и не дублируется как обязательное следствие нового факта.
- Active identical photo по-прежнему идемпотентно возвращает duplicate без нового fact; case transaction serialization остаётся владельцем concurrent same-case admission.
- Исторический `CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001` обновляется только в superseded identical-reupload scenario: вместо SQL `1062` он доказывает accepted revision, две отдельные photo identities, retained revoked history и один physical blob. Его upload/revoke/replay/already-revoked сценарии сохраняются.
- Canonical migration готовится и проверяется на populated fixture, но deployment/migration пользовательского stand data не выполняется этим planning change.

Явные non-goals: изменение upload/revoke ролей или capabilities, supervisor override, изменение last-photo completed-section gate, удаление/перезапись revoked rows, физическое удаление blobs, изменение photo size/MIME/hash validation, новый UI, изменение HTTP/CSRF, rebaseline architecture, stand restart, production migration, remote/CI или Bitrix action.

## Capabilities

### New Capabilities

- `inspection/identical-photo-reupload`: принятие identical photo bytes после revoke как нового append-only evidence fact через существующий upload seam.

### Modified Capabilities

- `deployment/canonical-inspection-evidence-schema`: canonical photo index разрешает несколько исторических rows одного content hash при сохранении active duplicate idempotency на application seam.

## Impact

Затрагиваются definition/canonical migration inspection-evidence schema, photo-upload persistence в пределах существующего `ChecklistSync` seam, schema/catalog tests и `CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001` spec/verifier/meta-test/transcript. Existing photo rows, operation history and content-addressed blobs must remain intact. Authorization, current-assignment checks, revoke reason/confirmation, section readiness, payment/completion and unrelated checklist behavior не меняются.
