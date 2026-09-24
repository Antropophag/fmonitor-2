# YII2-ORIGINAL-OBJECT-IDENTITY-001 — понятный объект в original flow

## Простыми словами

Сотрудник узнаёт объект при первичной загрузке, исправлении и просмотре истории оригинала по текущему регистрационному номеру, адресу/подъезду и заводскому номеру, как в карточке. Внутренний ID остаётся техническим и не выдаётся за номер объекта. Этот срез не меняет загрузку, документы, историю, общий resolver реквизитов или остальное приложение.

## Актор и публичный seam

- Актор: вошедший сотрудник, которому существующая policy разрешает соответствующее чтение original flow; upload/correction дополнительно сохраняют свои действующие write permissions.
- Публичный seam: real HTTP `GET /pilot/objects/{objectId}/assignment-orders/{orderId}/originals/submit` для initial/correction и `GET .../originals/history`, а также существующие `POST .../originals` и download links как regression boundary.
- Source oracle текущих реквизитов: существующий публичный effective object details mechanism, объединяющий legacy import с разрешёнными ручными поправками. Второй resolver, копия projection и чтение реквизитов из historical order/PDF snapshots запрещены.

## Предусловия и примеры

Для всех примеров существует доступный actor объект `4512`, распоряжение `81`, выбранный состав и действующая original policy. В URL и командах `4512`/`81` сохраняются как внутренние IDs.

| Пример | Effective регномер | Effective адрес | Подъезд | Заводской номер | Ожидаемое пользовательское представление |
|---|---|---|---|---|---|
| A imported | `TEST-4512` | `Москва, Тестовая, 1` | `2` | `Z-77` | `TEST-4512` основной; адрес и `Подъезд 2` рядом; `Заводской номер: Z-77` вторично |
| B manual | `РЕГ-&-НОВЫЙ` | `Очень длинный <script>alert(1)</script> & адрес …` | `12А` | `ЗАВ-<42>` | текущие manual values как текст, без старых imported values и без исполняемой разметки |
| C missing | `NULL`/пусто | `Адрес без номера` | `7` | `NULL`/пусто | `Регистрационный номер не указан`; адрес и подъезд видны; `Заводской номер не указан`; нет fallback `4512` |

Whitespace-only строка для показываемого номера считается отсутствующим представлением. Это presentation normalization, не изменение сохранённых данных.

## Нормативные требования

### OI-01 — единый текущий контекст формы и истории

1. Initial form, correction form, их breadcrumbs и непосредственно связанная history surface MUST показывать effective регистрационный номер как основной идентификатор объекта.
2. Те же поверхности MUST показывать effective адрес и подъезд как видимый контекст и effective заводской номер как дополнительный реквизит.
3. После разрешённой ручной поправки все эти поверхности MUST без повторного импорта показать ровно текущие реквизиты, которые показывает карточка; legacy values действуют только по контракту existing effective owner.
4. History показывает текущий контекст объекта над списком, но MUST NOT приписывать его отдельным историческим редакциям как snapshot тех редакций.

### OI-02 — отсутствие номера без подмены ID

1. При отсутствующем effective регномере MUST быть виден точный текст `Регистрационный номер не указан`.
2. При отсутствующем effective заводском номере MUST быть виден текст `Заводской номер не указан`.
3. Внутренний object ID MUST NOT появляться в breadcrumbs, заголовке, подписи или fallback-реквизите initial/correction/history surfaces. Отсутствие обоих номеров MUST NOT блокировать допустимый upload/correction.

### OI-03 — техническая и документарная идентичность сохраняется

1. Object/case/order/revision IDs MUST сохраняться без изменения в URL, командах, hidden fields, data attributes, download links и domain relationships.
2. Номер распоряжения, версия распоряжения и номер редакции документа MUST сохраняться в существующих местах: это идентификаторы документа, а не подмена номера объекта.
3. PDF body, SHA-256, historical revision rows/snapshots и ранее принятые исходные документы MUST оставаться byte-for-byte и row-for-row неизменными от любого GET и от presentation change.

### OI-04 — upload/correction/replay не регрессируют

Существующие PDF validation/limit, document date, composition confirmation, correction reason, requestId fingerprint/replay, safe retry after lost response, immutable prior revision и return-to-card behavior MUST сохраняться. Этот срез не меняет writer, transport или idempotency/concurrency semantics.

### OI-05 — безопасность и права

1. Все effective values MUST выводиться через HTML escaping. Строки примера B видны буквально и MUST NOT создавать DOM element/script из пользовательского значения.
2. Effective read MUST происходить только после существующего admission конкретного original surface. Гость получает прежний login redirect; actor без `assignment_order.original.read` получает прежний `403`; недоступные object/order получают прежний safe `403/404/503` outcome без реквизитов.
3. GET form/history MUST быть side-effect-free: не создавать domain/audit facts, файлы или внешние операции.

### OI-06 — читаемость и адаптивность

На viewport 1440×900 и 320×568 effective регистрационный номер, длинный адрес/подъезд и заводской номер MUST быть видимы и читаемы, без горизонтального overflow документа. Реализация использует существующие shlz/UI blocks и MUST NOT изменять общие `ViewSupport`, CSS или JS.

## Acceptance matrix

| ID | Наблюдение через public seam |
|---|---|
| A1 | Initial GET показывает пример A на form и breadcrumb; строк `Объект № 4512`/`Объект монтажа № 4512` нет. |
| A2 | После initial upload correction GET показывает текущий effective контекст и сохраняет document version/revision labels. |
| A3 | После manual edits correction GET и history GET показывают пример B; imported values отсутствуют; XSS payload остаётся текстом. |
| A4 | При missing numbers form/history показывают обе явные missing labels, адрес/подъезд и не подставляют `4512`; upload остаётся доступным. |
| A5 | Guest/no-read/unavailable requests не раскрывают ни imported, ни manual details и сохраняют прежние HTTP outcomes. |
| A6 | Before/after GET facts, original rows, private-file hashes и downloaded historical bytes совпадают. |
| A7 | Existing initial upload, simulated lost-response replay, correction с причиной/датой/confirmation и два exact historical downloads проходят. |
| A8 | Browser на desktop и 320px показывает контекст без page overflow; hostile value не создаёт element/script. |

## Inapplicable boundaries

- Persistence/schema/import/backup/restore: новых фактов и таблиц нет; существующая effective projection только читается.
- Deployment/readiness/external integrations: runtime dependencies, Compose, календарь, ОТиЗ, ERP/Bitrix, navigation и CI policy не меняются.
- Concurrency: state-changing owner не меняется; regression A7 подтверждает прежний replay contract.
- rapid-pilot: не исполняется и не изменяется; Yii public seams являются проверяемой поверхностью.

## Done

Gate 1 contract и planner mapping полны; intended RED чувствителен к текущему ID-output/effective mismatch; planner-required независимые reviews APPROVED; focused HTTP/browser/architecture checks GREEN; exact committed candidate имеет GREEN CI и открытый PR `Closes #243`. Merge/deploy не входят в Done.
