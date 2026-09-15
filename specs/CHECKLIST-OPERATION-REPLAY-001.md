# CHECKLIST-OPERATION-REPLAY-001 — точный replay native Yii2 checklist operations

## Простыми словами

Повтор отправки после потерянного HTTP-ответа должен вернуть уже сохранённый результат. Но один и тот же идентификатор нельзя использовать для другой операции, другого объекта, пользователя, устройства или других значимых данных: такой запрос получает конфликт и ничего не записывает. Исправление ограничено пятью существующими операциями; `item_completed`, авторизация #130, offline-клиент, UI и схема данных не меняются.

## 1. Идентификатор, actor и public seam

- Spec ID: `CHECKLIST-OPERATION-REPLAY-001`.
- Actor: аутентифицированный пользователь, прошедший существующую command authorization и имеющий read access к checklist объекта.
- Public seam: существующие Yii2 `POST /pilot/objects/{objectId}/checklist/operations` и `POST /pilot/objects/{objectId}/checklist/photos`, которые делегируют сохранение в native `ChecklistSync::accept(...)`.
- Persisted owner: существующий native checklist mutation owner и существующие `fm2_checklist_operations`, `fm2_checklist_revisions`, `fm2_checklist_photos`, installer facts и private content-addressed photo storage.
- Base: актуальный `main` на момент начала delivery, `0a286f3eccb5230d51e787baeeaea0acf7beb894`.

## 2. Scope

Нормативное изменение относится только к:

1. `item_installers_changed`;
2. `completion_retracted`;
3. `photo_uploaded`;
4. `photo_revoked`;
5. `section_completed`.

`item_completed` остаётся у существующего canonical `InspectionEvidence` owner и проверяется только как regression. Существующий #130 read-authorization contract сохраняется и также проверяется только как regression.

Не входят frontend/service worker/offline queue, общий idempotency/replay subsystem, новый owner/event store/command bus, schema migration, `rapid-pilot`, issues #35/#52/#132/#141, UI/Vue и unrelated checklist semantics.

## 3. Canonical replay identity

`duplicate` SHALL означать повтор ранее принятого того же намерения. Для любого типа из §2 сохранённая operation и текущая команда эквивалентны только при одновременном совпадении:

- resolved `installation_case_id` запрошенного `objectId`;
- `operation_type`;
- server-authenticated `actor_user_id`;
- `device_installation_id`;
- применимых `section_id` и `item_id`;
- type-specific normalized payload из таблицы ниже.

`client_operation_id` выбирает кандидата для сравнения, но сам по себе не доказывает replay. `baseRevision` и `deviceTime` не являются частью намерения: при retry сохраняются исходные audit values и accepted revision. Неизвестный, повреждённый или не соответствующий ожидаемой shape сохранённый payload сравнивается fail-closed как conflict.

| Type | Applicable context | Meaningful normalized payload |
|---|---|---|
| `item_installers_changed` | section + item | уникальный отсортированный набор `installerTabIds`; порядок входного JSON незначим |
| `completion_retracted` | section + item | exact `originalClientOperationId` + `trim(reason)` |
| `photo_uploaded` | section; item отсутствует | lowercase SHA-256 + exact MIME + integer byte size + exact original name |
| `photo_revoked` | section; item отсутствует | integer `photoId` + `trim(reason)` |
| `section_completed` | section; item отсутствует | пустой object; дополнительные/переставленные JSON keys, не используемые операцией, незначимы |

Raw JSON byte equality запрещена. Значения сравниваются по приведённым типам и canonical content.

## 4. Observable outcomes

### 4.1 Exact replay

После действующей authorization и validation exact replay SHALL вернуть HTTP `200`, body `status=duplicate`, исходную `accepted_revision` и разрешённую текущую projection. Он SHALL NOT:

- добавлять operation или installer fact;
- увеличивать revision;
- добавлять/отзывать photo row;
- создавать/изменять private file;
- изменять любой другой persisted fact.

### 4.2 Operation-ID conflict

Если найденный по ID кандидат отличается хотя бы по одному полю §3, разрешённый запрос SHALL вернуть HTTP `409`, body `status=conflict`, без новой operation/revision/photo/file/installer/fact. Он не получает `duplicate` и не присваивает историческую operation текущему actor/device.

Существующие authentication, CSRF, command authorization, read authorization, validation и not-found outcomes сохраняют приоритет и свои HTTP/domain envelopes. Пользователь без read access получает безопасный HTTP `403`, `status=rejected`, без `revision`, `projection` или данных чужой операции.

## 5. Concurrency and exception recovery

Обычная early-duplicate ветка и повторный lookup после unique/integrity exception MUST вызывать одну и ту же canonical equivalence policy.

- Два concurrent equivalent request с одним ID дают ровно одну accepted operation/fact/revision; loser возвращает exact replay исходной revision.
- Два concurrent request с одним ID и разным meaningful payload дают ровно одну accepted operation/fact/revision; loser возвращает conflict, а не success.
- Transaction loser не оставляет installer/photo/file/revision или иных partial effects.

## 6. Acceptance matrix

### A — Healthy exact retry

Для каждого типа §2 принять command, затем повторить тот же ID/object/type/actor/device и семантически тот же normalized payload (включая иной key order, иной installer order или outer reason whitespace где применимо). Ожидание: `200 duplicate`, исходная revision, exact before/after persistence snapshot.

### B — Different object

Разрешённый actor повторяет ID принятой operation через public seam другого существующего checklist object. Ожидание: `409 conflict`; обе case revisions и полный persistence/file snapshot неизменны.

### C — Different operation type

Разрешённый actor повторяет ID с другим допустимым operation type. Ожидание: `409 conflict`; persistence/file snapshot неизменён.

### D — Different meaningful payload

Для каждого типа §2 изменить по одному применимому значимому полю: installer set; original operation и reason; photo SHA-256/MIME/size/name; revoked photo identity/reason; section. Ожидание: каждый запрос получает `409 conflict`, полный snapshot неизменён.

### E — Authorization and identity

1. Actor с read/command access, отличный от stored actor, не получает duplicate и не присваивает operation себе: `409 conflict`, no writes.
2. Другой device получает `409 conflict`, no writes.
3. Actor без checklist read access получает #130 safe `403 rejected` без projection/revision/protected fields и без private/persistence changes.

### F — Equivalent race

Два отдельных DB/HTTP execution context синхронизированы так, что оба проходят initial absence check до unique insert. Команды эквивалентны. Ожидание: один accepted, один duplicate, одна operation/fact/revision и no partial artifacts.

### G — Conflicting race

Тот же setup, но payload contenders различается. Ожидание: один accepted, loser `409 conflict`, одна operation/fact/revision и no partial artifacts.

### H — Existing `item_completed`

Существующий public test доказывает exact retry `duplicate` и changed normalized command `conflict` без новых facts. Его production implementation не меняется.

### I — Existing #130 read authorization

Существующий Yii2 inspection journey доказывает safe denial для no-read actor, включая attempts с reused accepted ID, и отсутствие новых DB/private facts.

## 7. Verification and Done

- Новый executable verifier использует public Yii2 HTTP seam и disposable real MariaDB; setup обязан сначала принять healthy commands и достичь replay branch.
- Response status и persistence проверяются независимо. Snapshot включает counts/content `fm2_checklist_operations`, `fm2_checklist_revisions`, `fm2_checklist_operation_installers`, `fm2_checklist_photos`, остальные fixture facts и hashes private files.
- Intended RED на base должен быть вызван false `duplicate` для altered context/payload и отдельно доказать race recovery defect; setup/auth/CSRF/JSON failures RED не засчитываются.
- Planner-selected focused checks, независимые reviews и один exact-source GitHub CI должны быть GREEN.
- PR открыт в `main`; merge/deployment не выполнены.
