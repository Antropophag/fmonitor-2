# OTIZ-SAVED-INSTALLER-ALLOCATION-EXPLANATION-001 — сохранённое распределение по монтажникам

## Простыми словами

Сотрудник ОТиЗ раскрывает работника в существующей детализации объекта и видит, как именно выбранный сохранённый расчёт представил его участие и сумму. Экран не пересчитывает распределение, не обращается к сегодняшнему составу и не выдаёт расчётную сумму за выполненную выплату.

Это ограниченный следующий этап #29 после ledger slice #248. Он не закрывает широкую #29, не повторяет историю выплат, не меняет формы #263, финансовые writers, формулы, snapshots, allocations, ledger, права, schema, XLSX или POST-команды.

## 1. Actor, public seam и источник истины

Actor — текущий Yii2-пользователь с действующим permission `otiz.manage`.

Public seam — авторизованный `GET /pilot/otiz/snapshots/{snapshotId}` и существующий drawer объекта расчёта. Из блока каждого работника доступно локальное server-rendered раскрытие без нового маршрута. Guest и actor без точного permission получают действующие denial outcomes до раскрытия данных.

Единственный источник — выбранный snapshot: его `report_date`, сохранённый объект и его rows `fm2_pilot_otiz_snapshot_allocations`. Текущий workforce, текущие assignment orders, checklist и иные сегодняшние источники не читаются и не используются как fallback.

## 2. Канонический смысл полей

Для каждого allocation UI показывает независимо:

- `full_name` и `tab_id` — сохранённая identity работника;
- `report_date` — «Расчётная дата»;
- `snapshot_objects.distributed_cents` — «Сумма к распределению» этого объекта в выбранном расчёте;
- `contribution_bp` — «Вклад», процент с двумя знаками после запятой (`bp / 100`);
- `effective_ktu_bp` — «КТУ», безразмерный коэффициент с двумя знаками после запятой (`bp / 10000`);
- `share_bp` — «Доля», процент с двумя знаками после запятой (`bp / 100`);
- `participation_basis` — «Основание участия», escaped plain text;
- `amount_cents` — «Итог работника в расчёте», деньги с двумя знаками после запятой.

Форматирование масштаба не является расчётом распределения. UI не вычисляет `amount_cents` из суммы/доли/КТУ, не проверяет его собственной формулой и не заменяет одно поле другим. Сохранённый ноль показывается нулём. Workbook compatibility heuristics не являются правилом HTML-представления.

## 3. Историческая полнота и замечания

Пустое после trim `participation_basis` показывается как «Не сохранено в этом расчёте». Если у объекта нет allocation rows, drawer показывает «В этом расчёте распределение по работникам не сохранено». Система не восстанавливает отсутствующее основание, identity или allocation из текущего состава.

Сохранённые snapshot issues остаются отдельными замечаниями объекта. Без сохранённой персональной связи issue не называется основанием участия, исключения или суммы конкретного работника. Raw JSON, source payload и технические имена колонок не выводятся.

## 4. Observable acceptance matrix

### A01 — несколько работников и точные сохранённые значения

Snapshot на 31.08.2026 хранит `distributed_cents=12345`. Allocation Анны Т-01: contribution 2300, effective KTU 11700, share 3100, amount 3333, basis «Распоряжение № 7». Allocation Бориса Т-02: contribution 7700, effective KTU 9300, share 6900, amount 9012, длинное basis.

Drawer показывает сумму к распределению 123,45 ₽. Раскрытие Анны показывает вклад 23,00 %, КТУ 1,17, долю 31,00 %, итог 33,33 ₽. Раскрытие Бориса показывает 77,00 %, 0,93, 69,00 %, 90,12 ₽. Эти намеренно асимметричные значения не позволяют незаметно подменить вклад долей или пересчитать итог из суммы и доли.

### A02 — первая и последующая публикации

Первый snapshot и последующий snapshot одного object id имеют разные report dates, distributed amounts, identities, shares и amounts. Каждый GET показывает только собственные сохранённые значения. Последующий snapshot и текущий состав не меняют HTML первого.

### A03 — старый snapshot после изменения состава

После сохранения snapshot текущий состав заменён. Старый участник остаётся в старом drawer, новый текущий участник не появляется. Никакие current composition строки не раскрываются.

### A04 — отсутствующее основание и allocations

Пустое/whitespace-only сохранённое основание получает текст «Не сохранено в этом расчёте». Объект без allocations получает один понятный empty state. Ни issue, ни текущий assignment order не подставляется как основание.

### A05 — замечания без персональной причинности

Объектный warning с текстом «Просрочка по объекту» остаётся в общем блоке замечаний. Он не появляется внутри details Анны или Бориса и не подписывается как их причина/исключение.

### A06 — escaping

Identity `<img src=x onerror=alert(1)> & Монтажник` и basis `<script>alert("allocation")</script>&` отображаются как текст. В DOM не появляются исполняемые `img`/`script` из сохранённых значений.

### A07 — denied actor

Guest получает действующий login redirect; authenticated actor с near-match permission `OTIZ.MANAGE` получает 403. Ответы не содержат fixture identity, tab, basis, distributed amount или worker amount. Denial не добавляет facts.

### A08 — read-only и не выплата

До и после повторных authorized GET и browser раскрытий byte-equivalent inventories snapshots, snapshot objects, allocations, issues, payment closures, OTIZ events, settlement operations, jobs и outbox. Текст «Итог работника в расчёте» отделён от выплаты и не утверждает, что деньги выплачены.

### A09 — desktop, несколько работников и narrow viewport

В Chromium на desktop и narrow viewport каждый работник имеет собственный keyboard-operable `<details>`. Длинное основание переносится в document flow; раскрытие не создаёт горизонтальную прокрутку страницы. Подписи и значения остаются сопоставимыми одному работнику.

### A10 — retained forms #263

Существующие settlement forms сохраняют actions/fields и восстановление money/basis/`operationId` после ошибки по контракту #263. Новый details не вложен в form и не меняет submit/JS flow. Ledger, XLSX и финансовые POST-команды не входят в changed boundary этого presentation slice и не изменяются.

## 5. Rejections, history и concurrency

Это read-only capability. Одинаковое committed состояние даёт одинаковое представление; повторный просмотр не создаёт фактов. Concurrent публикация может стать видна только отдельному последующему GET и не смешивается с уже выбранным snapshot id. Ошибка чтения остаётся server error, а не синтетическим пустым распределением.

## 6. Done и остаток #29

Done требует public HTTP/browser RED, planner-required Gate 3, GREEN focused checks, independent final review и один exact-source GitHub CI run для PR head. Полный локальный `make test`/`make verify`, рабочий стенд, реальные финансовые действия, merge и deploy запрещены.

После slice остаются широкие части #29: объяснение учтённых работ/прогресса и правил, доказанный персональный provenance исключений/причин и итоговая пользовательская приёмка всей расшифровки. Issue #29 автоматически не закрывается.
