## Context

Существующий writer сохраняет валидированные JPEG/PNG/WebP как content-addressed `.bin` в приватном `<artifact-root>/checklist` и записывает `storage_name`, MIME и размер в `fm2_checklist_photos`. Read projection намеренно выбирает только неотозванные строки, но не публикует read URL. Клиент умеет показывать лишь локальный blob `previewUrl`.

## Goals / Non-Goals

**Goals:**

- использовать существующие row/blob и checklist access policy;
- добавить один минимальный read boundary без мутаций;
- дать durable URL, исходный размер и явный retry UI;
- удержать изменения общих controller/reader/JS/CSS адресными.

**Non-Goals:**

- thumbnails, transforms, CDN или новая media abstraction;
- изменения upload/offline queue, checklist state/history или storage lifecycle;
- изменения карточки, реквизитов #222, ОТиЗ и construction-control filtering.

## Decisions

1. **Read принадлежит InspectionEvidence/Yii checklist boundary.** Reader возвращает активную photo metadata/storage identity через узкий read method; controller сначала применяет тот же `access()` check, затем получает photo. Альтернатива — public web root/symlink — отвергнута, потому что обходит authorization.
2. **URL включает object и photo ids.** Это позволяет проверять ownership и не превращать photo id в глобальный bearer token. Projection формирует относительный same-origin URL, не раскрывая storage name.
3. **Исходные bytes выдаются напрямую.** Отдельные thumbnails/transcoding не нужны и расширили бы storage/dependencies. CSS ограничивает только отображение, ссылка открывает тот же original.
4. **Client выбирает `previewUrl || viewUrl`.** Локальный blob остаётся приоритетным для pending/offline; accepted projection обеспечивает durable fallback. Ошибка изображения заменяет содержимое только своей card на retry control.
5. **Revoked row не читается.** Запрос использует object ownership и `revoked_at IS NULL`; существующая история/retention не меняется.

Owning module — `InspectionEvidence`; Yii controller — HTTP adapter. Разрешённые зависимости остаются Yii DB/request/response и существующим private artifact root. `rapid-pilot` не меняется. Изменение routes/controller/reader/asset требует architecture check и focused Yii inspection journey/browser tests.

## Risks / Trade-offs

- [Большие изображения загружаются в original размере] → текущий лимит upload 5 MiB ограничивает стоимость; thumbnails вне slice.
- [TOCTOU между DB row и file read] → fail closed 503 и отсутствие мутаций; проверка размера перед ответом.
- [Параллельные изменения checklist.js/CSS] → коммит и handoff перечисляют точные участки функций photo render/error retry и CSS selectors для ручного объединения.
- [Browser cache после отзыва] → private/no-store headers и authorization на каждом request; UI projection после reload исключает запись.

## Migration Plan

Схема и данные не мигрируются. Deployment добавляет route/code/assets; rollback удаляет route и возвращает прежнюю заглушку, не меняя сохранённые rows/blobs. Перед публикацией выполняются focused checks, независимые reviews и один exact-source CI run.
