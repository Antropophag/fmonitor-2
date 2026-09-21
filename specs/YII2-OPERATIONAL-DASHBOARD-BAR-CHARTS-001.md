# YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001 — операционные столбчатые диаграммы

## Простыми словами

Руководитель видит не декоративную статистику, а три ответа: где скопилась работа, в какие недели ожидаются старты и окончания, какие активные объекты давно не подтверждали движение. Каждый столбец показывает точное число и открывает тот же набор объектов в реестре. Срез ничего не меняет в процессе и не добавляет аналитику по людям, произвольные периоды или новую chart-библиотеку.

## Подтверждённые публичные seams

1. Data seam: `YiiOperationalDashboard::read(actorId, cutoff)` возвращает полный DTO минимальной сводки и трёх диаграмм либо fail-closed infrastructure failure.
2. HTTP seam: реальный Yii `GET|HEAD /pilot/dashboard` и `GET /pilot/objects?chart=<kind>&bucket=<key>` под обычным authenticated `objects.read`.
3. Browser seam: серверный HTML в существующем shell на 1440/390, keyboard focus/activation и отсутствие горизонтальной прокрутки страницы.

Другие seams не являются acceptance authority. В частности, прямые private SQL helpers, CSS geometry без rendered browser и скрытые DOM-наборы ID не заменяют эти проверки.

## A. Авторизация, единый cutoff и read-only

На существующем `/pilot/dashboard` диаграммы доступны только активному аутентифицированному пользователю с `objects.read`. Guest сохраняет безопасный `/pilot/dashboard` return path; authenticated пользователь без permission получает `403` без значений/объектов. Controller фиксирует одну дату `Europe/Moscow`; все значения, ссылки и подпись среза используют её.

`GET`, `HEAD`, repeat и concurrent reads при неизменных входах детерминированы и не создают/изменяют/удаляют предметные факты или аудит. `HEAD` возвращает тот же status с пустым body.

## B. Канонические этапы

Диаграмма «Объекты по этапам процесса» содержит ровно шесть взаимоисключающих buckets в порядке:

1. `needs_assignment_order` — «Требуется распоряжение»;
2. `ready_to_open` — «Готов к открытию»;
3. `installation` — «Монтажные работы»;
4. `document_closeout` — «Документарное закрытие»;
5. `completed` — «Работы завершены»;
6. `needs_assignment_change` — «Требуется изменение».

Значение каждого bucket равно `filters.total` реестра при соответствующем каноническом status filter на том же fixture/cutoff; сумма равна dashboard `total`. Правила статуса имеют одного production owner, а не параллельную упрощённую формулу.

Пример B1: значения `[2,1,2,1,1,1]` дают total `8`. Объект «Требуется изменение» учитывается только в шестом bucket.

## C. Плановая нагрузка на шесть недель

Диаграмма «Плановая нагрузка на 6 недель» начинается с понедельника календарной недели cutoff и содержит эту и следующие пять недель, каждая `Monday..Sunday` inclusive. У недели два ряда: «Плановые начала» по известной planned start и «Плановые окончания» по актуальному planned finish. Актуальный finish берётся из последнего подтверждённого переноса срока, иначе из исходной плановой даты; отсутствие/невалидность даты ничего не подставляет. Один объект может учитываться один раз в starts и один раз в finishes.

Пример C1: cutoff `2026-09-21`; starts `09-21`, `09-27`, `09-28`, `11-02` дают `[2,1,0,0,0,0]`. `11-02` за пределами окна.

Пример C2: исходный finish `2026-09-25`, актуальный подтверждённый перенос `2026-10-06` — значение относится только к finishes недели `2026-10-05..2026-10-11`.

## D. Давность подтверждённой активности

Диаграмма включает только объекты канонических статусов `installation`, `document_closeout`, `needs_assignment_change`. Последняя подтверждённая активность — максимум известных на конец cutoff day значений:

- `server_received_at` принятой checklist operation;
- server-owned timestamp актуальной неотозванной фотографии раздела;
- `recorded_at` root/correction факта `pto_act` или `declaration`.

`device_time` и локально сохранённая, но не принятая операция не являются подтверждением. Buckets взаимоисключающие: `age_0_7`, `age_8_14`, `age_15_30`, `age_31_plus`, `never`. Возраст — разность московских календарных дат cutoff и activity; `0..7`, `8..14`, `15..30`, `>=31`. Сумма равна числу объектов трёх активных статусов.

Пример D1: cutoff `2026-09-21`, activity dates `09-21`, `09-14`, `09-13`, `09-06`, `08-21` дают `[2,1,1,1,0]`. Объект без accepted evidence даёт `[0,0,0,0,1]`.

## E. Drill-down

Каждый bar является native link в `/pilot/objects` и воспроизводит множество bucket на сервере. Разрешены только:

- `chart=stage&bucket=needs_assignment_order|ready_to_open|installation|document_closeout|completed|needs_assignment_change`;
- `chart=planned-start|planned-finish&bucket=0|1|2|3|4|5`;
- `chart=activity-age&bucket=age_0_7|age_8_14|age_15_30|age_31_plus|never`.

Сервер сам выводит week boundaries/cutoff; клиент не передаёт доверенные `from/to`. Chart filter сочетает обычные search/page, сохраняет полный filtered total и обычную row-level/permission область. Обычный `status` вместе с `chart`, неизвестный chart/bucket, extra chart dimensions и malformed values дают `400` без широкого fallback и данных. Переход не является сохранённым историческим snapshot: реестр явно показывает собственный текущий cutoff.

## F. Bounded read и целостная ошибка

Количество production queries и размер DTO фиксированы и не растут с числом объектов. Для 30 000 объектов PHP/DOM не материализует полный набор: DTO содержит шесть stage values, шесть week records/двенадцать values и пять activity values. Никакой новой таблицы, cache, background writer или client aggregation нет.

Если любой обязательный aggregate/schema/classification недоступен или DTO нарушает фиксированный shape/sum invariants, HTTP показывает единое `503` «Данные временно недоступны» без частичных charts, SQL/schema details и object identities. Пустой доступный набор является успешным `200` с нулями и честным empty explanation.

## G. Presentation и accessibility

Используются только закреплённые публичные `shlz-dashboard`, `shlz-chart-widget`, status/control/link/button/empty-state contracts. Chart marks принадлежат приложению; сторонних chart libraries, canvas runtime или private `shlz-ui` imports нет.

Stage widget занимает полную строку; weekly/activity widgets располагаются рядом на широком viewport и одним document-flow столбцом при 390px. Страница не имеет horizontal overflow. Каждый bar показывает число и подпись, имеет accessible name с chart/series/category/value, native keyboard focus и видимый focus indicator. Легенда/текст позволяют понять данные без цвета. Zero values не получают выдуманные данные.

## H. Characterization и соседние потоки

Predecessor четыре metrics, два top-5 списка, dashboard navigation, guest return path, object/card authorization и существующие обычные queue filters сохраняют поведение. Stage parity характеризует все шесть queue branches. Dashboard/queue reads не меняют fingerprints correctness-bearing tables. `rapid-pilot`, OTIZ, landing redirect, DDL, backup/restore и deployment dependencies не меняются.

## I. Rejections

- invalid actor/permission: existing `303`/`403`, без disclosure;
- invalid/conflicting chart filter: `400`, без fallback;
- incompatible schema/query/shape/invariant: atomic `503` dashboard error;
- unknown dates/activity: отдельное исключение из week row либо `never`, никогда fabricated timestamp;
- state-changing method/field на dashboard: existing route admission rejection, без новых facts.

## J. Audit и история

Успешное или отклонённое чтение не создаёт domain audit. Существующие append-only order, checklist, photo, completion и deadline facts остаются единственными источниками; диаграмма не исправляет, не удаляет и не переинтерпретирует их задним числом.

## K. Focused evidence

Gate 2 должен дать intended RED через public seams, а Gate 4 — GREEN теми же expectations. Browser evidence сохраняет валидные screenshots 1440/390 для populated и zero/error states. Data evidence включает independently calculated B1/C1/C2/D1, every boundary `7/8/14/15/30/31`, invalid filters, query/memory/DOM bounds на 30k и before/after fingerprints.

## L. Done

Done требует planner-selected bounded checks, strict OpenSpec validation, обязательные независимые reviews, один GREEN exact-source GitHub CI consumer и delivery record с точными авторами/source/evidence. Локальные полные `make test`/`make verify`, merge и deploy не входят.
