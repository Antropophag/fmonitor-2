# OTIZ-OBJECT-LEDGER-HISTORY-001 — сквозная история ledger объекта

## Простыми словами

Сотрудник ОТиЗ из существующей детализации объекта в расчёте открывает полную текущую историю выплат, удержаний и сторно этого объекта из всех расчётов. История объясняет уже показываемый общий итог, но не меняет его формулу, финансовые факты или сохранённые значения прошлых расчётов.

Slice не реконструирует ledger на прошлую дату, не добавляет выплату/сторно, не меняет права, schema, snapshots, XLSX, расчёт, нормы, округление, общую навигацию/routes/CSS, календарь или интеграции. Источник решения: issue #29, актуализация 24.09.2026; очередь и границы параллельной работы: #169.

## 1. Actor, public seam и контекст

Actor — текущий Yii2-пользователь с действующим permission `otiz.manage`.

Public seam — `GET /pilot/otiz/snapshots/{snapshotId}?object={objectId}&ledgerPage={page}` через существующий GET-only route и существующую страницу snapshot. Обычный запрос без `object` сохраняет прежнюю детализацию и не читает сквозную историю для всех drawers.

`snapshotId`, `objectId` и `page` — положительные decimal integers. История разрешена только когда `(snapshotId, objectId)` существует в сохранённых объектах указанного snapshot. `ledgerPage` по умолчанию равен 1; размер страницы равен 10 строкам. Невалидный object/page или object, не принадлежащий snapshot, возвращает безопасный 404 без ledger projection. Страница за последней непустой страницей показывает пустую страницу с корректными границами и теми же full-set totals; данные другого объекта не подставляются.

Guest получает прежний 303 login redirect с return URL на полный безопасный requested snapshot URL. Authenticated actor без `otiz.manage` получает 403. Ни один denial не раскрывает строки, totals, count, source links или основания.

## 2. Projection одной записи

История содержит только rows `fm2_pilot_otiz_payment_closures.object_id = objectId` независимо от `snapshot_id`. Для каждой строки UI показывает:

- тип: `Сторно`, если `reverses_payment_closure_id` задан; иначе `Выплата`, если `paid_cents > 0`; иначе `Удержание`;
- `closed_on` как дату записи;
- все три signed-компонента без изменения смысла: `paid_cents`, `discipline_cents`, `deadline_cents`;
- `basis` как escaped plain text;
- идентификатор, report date и доступную ссылку исходного snapshot;
- для сторно — идентификатор исходной closure и локальную связь на исходную строку, только если исходная строка относится к тому же объекту и присутствует в доступной истории.

Смешанная строка сохраняет все компоненты и не классифицируется ценой их потери. Сторно остаётся отдельной отрицательной строкой; исходная строка не удаляется и не переписывается. Неконсистентная reversal-ссылка на строку другого объекта не раскрывает ту строку и не создаёт переход к ней.

Source snapshot link использует существующий `/pilot/otiz/snapshots/{sourceSnapshotId}?object={objectId}`. Он показывается только в рамках тех же действующих прав; новая permission model не вводится.

## 3. Порядок, пагинация и согласованность

Rows фильтруются и пагинируются в SQL, а не после загрузки полного ledger в PHP. Порядок: `closed_on DESC, id DESC`; `id` является стабильным tie-breaker.

Один ответ формирует в одном consistent read:

- `total` — количество всех rows выбранного объекта;
- `paid_cents`, `discipline_cents`, `deadline_cents` — signed sums каждого компонента по всему набору;
- `global_closed_cents` — сумма этих трёх component totals по всему набору;
- только rows текущей страницы.

Новая committed ledger-row между отдельными запросами может появиться в следующем ответе, но не может привести к тому, что count/totals и page одного ответа описывают разные committed состояния.

## 4. Сохранённый snapshot и текущий ledger

Страница отдельно показывает:

- «Сохранено в расчёте #N» — `closed_before_cents` и другие уже сохранённые поля выбранного snapshot object без пересчёта;
- «Учтено сейчас по всем расчётам» — current full-set component totals и `global_closed_cents`.

Текст сообщает, что текущая история включает более поздние записи и не является реконструкцией ledger/`closed_before_cents` на report date. Новая история не изменяет сохранённые snapshot amounts. `global_closed_cents` использует существующую формулу `SUM(paid_cents + discipline_cents + deadline_cents)` для объекта.

## 5. Observable acceptance matrix

### A01 — A/B/C из контекста B

Fixture содержит один объект: payment `+100000` в A; discipline `+12000` и deadline `+3000` в B; reversal в C с exact copied components `-100000/0/0`, linked к A. GET из B показывает все три rows, их основания, source A/B/C, ссылку сторно на A и totals: paid `0`, discipline `12000`, deadline `3000`, global `15000`. Никакая строка не становится новой положительной выплатой.

### A02 — соседний объект

При наличии rows другого object id rows/count/component totals/global total истории выбранного объекта не меняются. Ни source, ни reversal join не раскрывает соседний объект.

### A03 — сохранённое против текущего

Если B сохраняет `closed_before_cents=100000`, а после B записаны C и более поздняя row, UI продолжает показывать `100000` как сохранённое значение B и отдельный current ledger total. Он не называет current rows историей «на дату B».

### A04 — pagination и порядок

При `pageSize + 2` rows page 1 и page 2 не пересекаются, вместе дают ожидаемый ordered prefix/full set, одинаковые full-set totals и total. Rows с одинаковым `closed_on` отсортированы по descending id. Page после последней пуст, сохраняет totals/total и не подменяется последней страницей или другим объектом.

### A05 — empty

Объект snapshot без closure rows показывает явное «Выплат и удержаний пока нет», total 0 и четыре нулевых totals; snapshot facts остаются видимыми.

### A06 — context и права

Unknown snapshot, unknown object, object другого snapshot и invalid decimal parameters дают безопасный 404. Guest получает 303 login redirect; authenticated actor без `otiz.manage` получает 403. Ответы не содержат fixture basis, amounts или source identifiers.

### A07 — escaping

Basis `<script>alert("ledger")</script>&` отображается как текст через HTML escaping. В DOM отсутствует созданный из basis `script`, событие не выполняется, а исходный текст читаем пользователем.

### A08 — read-only GET/transitions

Before/after inventory таблиц snapshots, snapshot objects, closures, OTIZ events, settlement operations, jobs и outbox идентичен после authorized GET, pagination и перехода к source snapshot. Не создаются внешние вызовы. Новая история не содержит POST forms/buttons выплаты, удержания или сторно. HEAD не добавляется: существующий route остаётся GET-only согласно явной границе владельца не менять routes.

### A09 — retained snapshot behavior

Обычный `/pilot/otiz/snapshots/{id}` сохраняет прежние суммы, drawer actions, current-snapshot ledger и financial forms. Он добавляет только lazy link «Все выплаты и удержания по объекту» в объектном drawer; full cross-snapshot rows не загружаются для каждого объекта.

### A10 — browser desktop/narrow

Chromium открывает историю из drawer B, видит A/B/C rows, signs, bases, source/reversal transitions и pagination на desktop и narrow viewport. Таблица использует существующий contained-scroll/mobile behavior; тип, дата, amounts, basis и links остаются доступны. Browser использует только isolated fixtures и не касается рабочего стенда.

## 6. Rejections, history и concurrency

Это read-only capability: idempotency означает одинаковый ответ для одинакового committed DB state; replay не создаёт facts. Финансовая история остаётся append-only у существующих writers. Новый reader не блокирует и не сериализует writers, но count/totals/page одного response должны быть согласованы транзакционным snapshot. Ошибка чтения возвращает штатный server error и не считается пустой историей.

## 7. Done

Done требует RED публичного HTTP/browser test, planner-required Gate 3, GREEN focused checks, browser evidence desktop/narrow, architecture check, независимый final review и один exact-source GitHub CI run для PR SHA. Полный локальный `make test`/`make verify`, merge, deployment и реальные финансовые операции запрещены.

Остаток полной #29 после slice: объяснимость суммы по монтажникам, учтённым работам/прогрессу, правилам и причинам исключений должна приниматься отдельно; этот slice не закрывает #29 автоматически.
