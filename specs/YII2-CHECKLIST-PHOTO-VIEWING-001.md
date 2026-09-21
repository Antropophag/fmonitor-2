# YII2-CHECKLIST-PHOTO-VIEWING-001 v1.0

## Простыми словами

Принятое сервером фото чек-листа видно после перезагрузки, на другом устройстве и другому пользователю, которому разрешено читать этот объект. Локальное превью остаётся для ещё не отправленного снимка; загрузка, offline-очередь, прогресс, состав, отметки и история не меняются.

## Public seam

Актор — активный Yii2-пользователь. Публичные seams:

- `GET|HEAD /pilot/objects/{objectId}/checklist/photos/{photoId}`;
- checklist page projection, передаваемая в `data-projection`;
- действия просмотра в полосе раздела и общей галерее.

Источник байтов — существующий приватный checklist storage. Схема, расположение и lifecycle файлов не меняются.

## Acceptance contract

1. Projection содержит только active (`revoked_at IS NULL`) фотографии данного installation case и для каждой содержит относительный same-origin `viewUrl`, однозначно связывающий `objectId` и integer `photoId`.
2. GET/HEAD разрешены ровно после существующей проверки `checklist.read` объекта: активный пользователь с `roleAccess`, `checklist.read` или `inspection.item.complete` может читать; право upload/revoke не требуется. Guest/inactive проходят существующий authentication flow. Активный пользователь без read получает 403. Несуществующий object, photo, cross-object photo и revoked photo дают 404 без метаданных/байтов.
3. Успешный GET возвращает исходные сохранённые bytes, persisted MIME, exact `Content-Length`, `Content-Disposition: inline` с безопасным generic filename, `X-Content-Type-Options: nosniff` и private/no-store caching. HEAD возвращает те же status/headers и пустое тело.
4. Read проверяет, что `storage_name` — допустимое внутреннее имя checklist blob внутри существующего `<private-root>/checklist`, файл обычный и его размер совпадает с persisted `byte_size`. Любая storage/integrity недоступность даёт безопасный 503 с `Retry-After`; путь не раскрывается. Read ничего не восстанавливает и не мутирует.
5. Accepted photo в чистом браузере без IndexedDB/blob URL рендерится через `viewUrl` и в section strip, и в общей gallery. Нажатие/активация открывает исходное изображение по `viewUrl`, а не отдельную уменьшенную копию.
6. Ошибка `<img>` оставляет photo card на месте, показывает exact copy `Не удалось загрузить фото` и кнопку `Повторить`. Повтор назначает тому же image URL новый query `retry=<monotonic value>`; он не создаёт checklist operation и не показывает empty-state.
7. Pending local photo продолжает использовать `previewUrl`; IndexedDB `meta/operations/photoBlobs`, upload request, sync ordering и состояния queued/sending/retryable_error/rejected/conflict сохраняются. После accepted projection серверный `viewUrl` становится durable fallback и переживает reload.
8. Отзыв сохраняет прежнюю append-only/history семантику и bytes, но фото немедленно исчезает из active projection/обоих представлений, а прежний read URL отвечает 404.
9. GET/HEAD просмотра не меняют checklist revision, operation/photo rows, progress, completed sections, crew, work marks, construction-control queue или private files.

## Deterministic examples

- Existing fixture object `4512`, case `6101`, accepted photo id `P`: reader `73` и отдельный разрешённый reader `95` получают exact fixture PNG по `/pilot/objects/4512/checklist/photos/P`; пользователь `96` без checklist read получает 403.
- `/pilot/objects/4513/checklist/photos/P` и неизвестный `photoId` дают 404.
- После accepted `photo_revoked(P)` projection не содержит P, а прежний URL P даёт 404; row/blob fingerprints остаются неизменными кроме ранее определённого `revoked_at` и revoke operation.
- Browser test начинает с projection, где у accepted photo есть `viewUrl`, но нет `previewUrl`; оба представления создают `<img>` и full-size link. Синтетический `error` создаёт retry UI, а click меняет только query URL.

## Rejections and safety

Неканонические/переполненные ids не матчат маршрут и дают 404. Методы кроме GET/HEAD дают 405 с Allow. Ответы отказа и 503 не содержат storage name, абсолютный путь, hash, original filename или exception text. Этот read-only slice не создаёт audit/domain event, потому что просмотр не является новым предметным фактом.

## Verification boundary

Обязательны адресные PHP HTTP/integration проверки, browser DOM test для clean projection/local preview/error retry/full-size action, HTTP global-call qualification при изменении `app/PilotHttp/*` (не ожидается), architecture check и planner-selected exact-source CI. Полный локальный `make test`/`make verify` запрещён owner decision.
