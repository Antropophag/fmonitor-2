# YII2-INSPECTION-JOURNEY-001

## Простыми словами

Инженер проходит из открытого объекта в чек-лист, отмечает работу, видит
сохранённый результат и возвращается к объекту либо очереди стройконтроля.
Перенос сохраняет действующие фото, исправления и отправку накопленных offline
действий. Денежные правила, ПТО/декларация, календарь и переключение стенда не входят.

## Основания и seams

Обязательны PRODUCT.md, CONTEXT.md, pilot spec/data model, INSPECTION-ITEM-COMPLETE-001,
CHECKLIST-CURRENT-CREW-001 и действующие fast-pilot правила. Characterization
inspection specs служат oracle только существующего поведения; их DRAFT/NEEDS_GRILL
не становится продуктовым одобрением. Исправление обнаруженного прежнего поведения
не добавляется молча к framework migration.

HTTP: GET/HEAD /pilot/construction-control; GET/HEAD
/pilot/{objects|construction-control/objects}/{id}/checklist; POST тех же путей
/checklist/operations и /checklist/photos; GET/HEAD
/pilot/construction-control/objects/{id}/sync-context. ID — положительный
канонический decimal. Существующий InspectionRecording::completeItem остаётся
единственным public application owner отметки; общий InspectionEvidence owner
обслуживает прочие существующие операции, не контроллер.

## Нормативный контракт

### A1 — маршрут и допуск

Гость получает303 /pilot/login и безопасный возврат на запрошенную страницу.
Активный account/role проверяются при каждом запросе. Checklist read допускается
активной ролью construction_control_engineer/manager либо exact checklist.read
или inspection.item.complete, как действующий manual pilot. Очередь требует
construction_control.read. Отсутствие допуска даёт403 без process facts.
Несуществующий объект404; method mismatch405 с Allow; HEAD без тела.
Объект4512 и case6101 различны и никогда не подменяют друг друга.

### A2 — экран и возврат

Страница сохраняет shlz-ui, адрес/подъезд/регномер, разделы, отметки, состав,
состояние синхронизации, фото и действующие доступные действия. Из object alias
возврат ведёт в /pilot/objects/{id}; из construction-control alias — в очередь.
Карточка открытого объекта ведёт к рабочему чек-листу. Закрытое для начала работ
дело показывает недоступность действий и не открывается от чтения/отметки.
Пункт42 не является обычной отметкой: POST с itemId42 даёт409 rejected с
сообщением «Последние 15% закрываются актом ПТО и декларацией в карточке объекта.»
UI сохраняет 41 монтажную работу, cap85 (100 при обоих completion facts) и
ссылку документарного закрытия на /pilot/objects/{id}#completion.
Читающий без mutation допуска видит данные без активных mutation controls.
GET не добавляет revision, snapshots, audit, schema repair или backfill.

### A3 — отметка и evidence

Полный контракт INSPECTION-ITEM-COMPLETE-001 наследуется. Actor берётся из Yii
identity, не JSON/заголовков FMONITOR_AUTH. При revision0 пункт28 раздела1,
crew7001/7002, actor73 сохраняет одну операцию item_completed в case6101,
revision1 и неизменные personnel/template snapshots; current assignment не
ограничивает право exact inspection.item.complete. Выбранные монтажники
нормализуются; deviceTime остаётся клиентским аудитом, server time — временем
приёма. HTTP200 {status:accepted,revision:1,projection:...}; projection содержит
пункт28 с actor73 и точной сохранённой идентичностью операции. Refresh сохраняет
результат. Ранее принятые факты и assignment/original/opening не изменяются.

### A4 — replay, conflicts, отказы

Тот же payload/id повторно даёт200 duplicate с исходной revision и без новых
фактов, включая потерянный ответ. Изменённый payload того же id даёт409 conflict;
новая item operation со stale revision даёт409. Две разные item operations с
одной базой на отдельных соединениях дают один accepted и один conflict.
Отсутствующее текущее дело HTTP-ресурса даёт404 без фактов, как неизвестный объект.
Отсутствующие template/crew, нерабочее дело и неверная команда сохраняют
действующие deterministic rejected причины (HTTP422); инфраструктурная ошибка
даёт503 retryable, generic без SQL/secrets. Отказ и rollback не добавляют фактов.
Current authorization rechecked для replay: blocked/revoked user не получает
accepted/duplicate и не создаёт фактов независимо от старого deviceTime.

### A5 — HTTP/session/CSRF

Yii владеет identity/session/CSRF и response. Клиентский X-FM2-CSRF передаёт
реальный Yii CSRF token; невалидный/отсутствующий token отклоняется400 либо403
без фактов (native400 — уже принятая platform adaptation). JSON content type,
размер<=32768 и корректность payload проверяются; неверный JSON400, превышение413.
Фото сохраняет X-FM2-Operation base64 JSON, bytes<=5MiB и MIME/hash validation.
Поддельный actor не меняет attribution и не расширяет права. Нет параллельной
cookie/session composition и outer Yii transaction вокруг native owner.

### A6 — соседние действия общего экрана

Сохраняются действующие item_installers_changed, completion_retracted,
photo_uploaded/photo_revoked, section_completed с прежним допуском и запретами.
Исправление completion с originalClientOperationId и причиной добавляет факт,
убирает отметку и открывает раздел; прежняя операция/attribution остаются.
Фото JPEG/PNG/WebP<=5MiB сохраняет hash/bytes/actor и private storage; одинаковое
содержимое в разделе не дублируется, максимум10 active photos; неверный hash/MIME
и отзыв последнего фото завершённого раздела не принимаются. Раздел требует все
его пункты и минимум одно принятое фото. Отзыв требует текущего назначенного
инженера и exact inspection.photo.revoke. Фото/section допускают активного
инженера или manager, как manual pilot. Атрибуция/replay прочих операций
сохраняют текущую pilot semantics и не объявляются новой утверждённой политикой.

### A7 — offline и browser

Сохраняются asset URLs, SW scope /pilot/, IndexedDB fmonitor2-fast-pilot v2,
meta/operations/photoBlobs и user:device:object scope. UI проверяется настоящим
browser: отметка → потеря ответа → retry → refresh → возврат, а также offline
queue → reconnect без потери identity/дублирования фактов. Очередь отправляет
операции, затем фото, затем section; conflict/rejected/retryable не удаляет
непринятое действие. Login/logout/account switch не раскрывает cached checklist
другого пользователя. CSRF обновляется через sync-context после login/restart.
CSP допускает необходимые same-origin assets, worker и local photo previews;
не вводит unsafe-inline. Desktop/mobile не имеют недоступных основных controls.

### A8 — очередь стройконтроля

Сохраняет только working cases, latest application engineer, последнюю checklist
активность, завершённость по существующим completion facts, mine/all/search и
show-completed controls, pagination50 и ссылки в checklist. Пустая выдача и
возврат работают. Сортировка сначала без activity, затем activity и object ID,
как oracle; миграция не меняет deviceTime на serverTime молча.

### A9 — границы runtime и regression

Новые Yii routes не загружают rapid-pilot или PilotHttp. Перенесённые rules имеют
одного module owner, прежний adapter может делегировать ему. Новые read/write
DB boundaries используют Yii DAO и явную атомарность; уже принятый native
completeItem вызывается целиком со своим соединением. DML-only canonical schema
работает без DDL. Schema frontier, table inventory и backup layout не изменяются.
Missing/drifted required schema даёт503 без repair. Проверяются unchanged
inspection/queue/preopening, photo concurrency, offline и actual architecture;
тесты не ослабляются ради удаления прежнего handler.
