## Purpose

Разрешает после отзыва ошибочного фото принять те же bytes как новое доказательство, сохранив отдельные identity, неизменяемую историю и один retained content-addressed blob.

## ADDED Requirements

### Requirement: Identical content after revoke creates a new evidence fact

Существующий public photo-upload seam SHALL после revoke принять identical bytes с новым canonical client operation ID как новый `photo_uploaded` fact. Система MUST создать новую photo identity и новую accepted revision, сохранить прежнюю revoked photo identity и всю operation history без изменения.

#### Scenario: Identical re-upload after revoke
- **WHEN** фото принято на revision 1, затем отозвано на revision 2, и новый upload на base revision 2 содержит те же case, section, bytes, MIME, size, filename и SHA-256, но новый canonical operation ID
- **THEN** upload возвращает `accepted` revision 3
- **AND** projection содержит ровно одно active фото с новой photo identity
- **AND** прежняя photo row остаётся revoked с исходными upload metadata и revoke time
- **AND** operation history содержит исходный upload, revoke и новый upload в порядке accepted revisions 1, 2, 3

#### Scenario: Active identical photo remains idempotent
- **WHEN** в case/section уже существует active фото с тем же SHA-256 и поступает identical upload
- **THEN** seam возвращает существующий duplicate result без новой photo identity, operation fact или revision

### Requirement: Authorization and serialization remain unchanged

Identical re-upload SHALL использовать только существующую photo-upload authorization. Он MUST NOT наследовать `inspection.photo.revoke`, supervisor override или broad viewer authority. Existing revoke SHALL по-прежнему требовать exact `inspection.photo.revoke`, current engineer assignment, explicit confirmation и bounded reason. Same-case upload/revoke admission MUST оставаться сериализованным существующей case transaction.

#### Scenario: Re-uploader lacks upload authority
- **WHEN** пользователь может читать фото либо ранее отозвал фото, но не проходит существующую photo-upload authorization
- **THEN** identical re-upload отклоняется тем же authorization result, что и любой новый upload
- **AND** revoked row, operations, revision и blob остаются неизменными

#### Scenario: Two same-case identical uploads race after revoke
- **WHEN** две новые operation identity одновременно загружают identical bytes после revoke на одном base revision
- **THEN** case serialization допускает не более одного нового accepted active photo
- **AND** проигравшая команда получает существующий duplicate либо revision-conflict result без partial facts

### Requirement: Permanent retention separates logical facts from physical content

Система MUST бессрочно сохранять active и revoked photo rows и их operation history. Новый identical upload MAY ссылаться на тот же content-addressed physical blob; physical blob count SHALL не увеличиваться только из-за identical bytes и MUST не уменьшаться при revoke или повторной загрузке.

#### Scenario: Blob reuse preserves two evidence identities
- **WHEN** identical content принято после revoke
- **THEN** SQL/evidence projection различает две upload identities и одну revoke identity
- **AND** storage содержит один byte-identical blob с исходным SHA-256
- **AND** cleanup/reset/background product lifecycle не удаляет этот blob или revoked evidence

### Requirement: Historical characterization is superseded only for re-upload outcome

Executable characterization SHALL сохранить существующие upload→revoke, exact revoke replay и fresh already-revoked rejection scenarios. Его identical-reupload scenario MUST наблюдать target accepted behavior и MUST NOT ожидать SQLSTATE `23000`/vendor `1062` как успешный milestone.

#### Scenario: Updated deterministic characterization
- **WHEN** focused photo-revoke verifier выполняется на canonical schema
- **THEN** final milestone фиксирует accepted revision 3, active photos 1, total photo rows 2, revoked rows 1, operations 3 и blobs 1
- **AND** audit доказывает новую operation/photo identity, полную byte/value-неизменность первой row относительно post-revoke snapshot и отсутствие SQL exception
- **AND** transcript/meta-test остаются deterministic и cleanup сохраняет foreign/ambient decoys
