# OTIZ-GUIDED-WORKFLOW-001 — Пошаговая работа ОТиЗ в согласованном интерфейсе

## Простыми словами

Сотрудник ОТиЗ в одном native-разделе выбирает дату, продолжает или готовит сохранённый расчёт, разбирает объекты и вопросы, подтверждает допустимый расчёт, скачивает настоящий XLSX и отдельно фиксирует уже выполненную вне FMonitor выплату. Срез не меняет формулы, исходные данные, права, финансовые команды или историю; он делает существующий процесс понятным и устраняет ложное сообщение о выплате.

## 1. Актор и публичная граница

Актор — активный пользователь с capability `otiz.manage`. Публичная граница — канонические Yii GET/POST/download маршруты `/pilot/otiz/...`, существующие application seams `SnapshotPublication` и `OtizSettlement`, их сохранённые проекции и наблюдаемый server-rendered HTML/download.

Визуальный oracle — выбранный вариант B из локального `otiz-redesign`; behavioral truth — `PRODUCT.md`, `CONTEXT.md`, существующие native owners и сохранённые факты. Иллюстративные prototype/rapid-pilot данные не задают финансовое поведение.

## 2. Обязательное поведение

1. Раздел содержит три режима: «Экономика объектов», «Выполнение расчёта», «Архив расчётов». Это не wizard: отдельная полоса из четырёх абстрактных шагов не показывается; текущий статус, препятствие и одно следующее действие должны быть понятны из заголовка и рабочей таблицы.
2. GET, reload, back, tab/drawer open/close не создают и не изменяют snapshots, acceptance events или settlement ledger.
3. Подготовка создаёт сохранённый draft только через действующую команду и после успеха открывает его id. Повтор operation id идемпотентен. Существующий snapshot продолжается без пересоздания. Дата выбирается публичным `shlz-ui` Date Picker с настоящим календарём и стабильным ISO-значением; текстовая подсказка формата не заменяет компонент.
4. Проверка показывает snapshot id/date, saved totals, objects, workers, trace, blockers и warnings. Blocker запрещает accept с объяснением; warning сам по себе не запрещает. Исправление источника требует нового snapshot и не меняет старый.
5. Accept preview содержит id/date/scope/saved amount. Успех показывает accepted status; XLSX доступен только для разрешённого snapshot и является настоящим download. Действие XLSX представлено кнопкой с публичной иконкой `file-xlsx`. Accept/download не означают выплату.
6. Complete-payment preview содержит date/scope/current available amount и текст, что FMonitor только регистрирует внешне выполненную выплату. Cancel ничего не пишет; replay/no_change/error не изображаются новой выплатой.
7. Snapshot status, saved snapshot amounts, current available amount и ledger rows подписаны раздельно. Draft, отсутствие blocker и нулевая сумма не являются proof of payment. Вместо необъяснённого «Нет новой суммы» интерфейс сообщает, что к регистрации выплаты сейчас доступно 0 ₽ и почему действие отсутствует. Discipline не называется выплатой; reversal относится к конкретной ledger row.
8. Экономика сохраняет серверные search/filter/sort/pagination/summary и колонки Объект/Прогресс/Фонд премии/Кшах/Заработано/Выплачено/Остаток фонда/Состояние/Действия. Одна колонка «Выплачено» показывает выплаченную сумму и связанное удержание как два явно подписанных значения; отдельная колонка «Удержано» не требуется.
9. Страница snapshot является массовым реестром объектов, а не последовательностью больших объектных карточек. Таблица показывает объект, состояние, прогресс, сохранённые суммы, доступно сейчас и action-иконку; КТУ, работники, трассировка, источники и замечания относятся к выбранной строке и открываются в общей детализации exact object+snapshot. Отдельной текстовой ссылки «Открыть отдельную детализацию» нет.
10. История выплат, удержаний и сторно является отдельной таблицей с заголовками, типом записи, объектом, датой, раздельными суммами, основанием и действием; значения не выводятся неразмеченной строкой.
11. Архив различает snapshots одной даты по id и показывает сохранённые metadata/amount/document eligibility. Последняя колонка называется «Действия», использует icon-only controls и не содержит текстовой ссылки «Открыть». Текущие остатки, если показаны, имеют отдельную подпись.
11. Отсутствующее значение, ноль и «ещё не рассчитывался» различаются. Неизвестная source date не заменяется current date.
13. Страницы используют серый canvas и отдельные белые поверхности для навигации, сводки/toolbar и таблицы; общий белый вложенный page-layout с острыми краями запрещён. Содержимое не прилипает к краям поверхностей.
14. При отсутствии замечаний пустой раздел и текст «Замечаний нет» не выводятся.
15. Forbidden/invalid/stale/replayed rejected cases сохраняют действующие status/redirect semantics и не добавляют новых фактов.

## 3. Авторизация, аудит и история

Все reads/actions наследуют `otiz.manage`, Yii auth/CSRF и server-side validation. Actor/time/operation id берутся по действующим правилам owner. История snapshots и settlement ledger append-only; UI не переписывает старый snapshot или исходную closure row. Drawer/list state не является audit evidence.

## 4. Приёмочные примеры

- Draft без blocker и с `available=0` показывает «На проверке»/«Нет новой суммы», но не «Выплата по объекту выполнена».
- Draft с blocker `DEADLINE_EVIDENCE_ABSENT` показывает владельца факта, запрещённый accept и действие подготовки нового расчёта после исправления; удаление DOM-строки не разблокирует серверный accept.
- Draft только с warning позволяет вызвать существующий accept.
- Accepted snapshot с положительной current available amount позволяет скачать настоящий XLSX и отдельно открыть payment confirmation. После успешного complete видна ledger row; повтор с тем же operation id не изображается вторым успехом.
- Discipline row с `paid_cents=0` и положительным удержанием подписана как удержание. Reversal с `reverses_payment_closure_id` подписан как сторно этой записи, а исходная row остаётся в истории.
- Два snapshots одной даты открываются по разным ids и сохраняют собственные objects/allocations/trace независимо от последующих изменений источников.

## 5. Доступность и визуальная проверка

Native HTML использует публичные `shlz-ui` primitives и scoped ОТиЗ CSS. Таблица имеет first column не уже 210 px, устойчивые financial columns и contained horizontal scroll; headings/body соответствуют 12/18 и 14/20. Drawer открывается доступной кнопкой/строкой, закрывается кнопкой, Escape и backdrop, переводит/возвращает focus. Без behavior JS tabs, forms и table reading остаются доступны, а архивная icon-only ссылка продолжает вести на exact snapshot; удалённая текстовая fallback-ссылка не требуется.

## 6. Verification contract

Обязательны focused unit/read-model, HTTP/render и Playwright tests на изолированной fixture: полный journey prepare → persisted draft → details/workers → accept → XLSX → conditional payment → history; blocker/warning; false-payment regression; discipline/reversal; reload/back/list query context; historical immutability; row/drawer parity; desktop и 390 px keyboard/focus. Локальный full `make test`/`make verify` запрещён; final exact source проходит planner-selected CI и независимые reviews.

## 7. Явно вне контракта

Новые формулы/нормативы/rounding, primary facts, schema, financial commands, object editor/history #222, own effective-values mechanism, SPA, fake actions из прототипа, merge, deployment и реальные финансовые действия.
