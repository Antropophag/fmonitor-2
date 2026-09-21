# YII2-OPERATIONAL-DASHBOARD-REFINEMENT-001 — согласованный refinement дашборда

## Простыми словами

Руководитель получает компактный и читаемый дашборд: этапы и календарные ряды имеют знакомые цвета, недельные пары не слипаются, а вместо технической давности активности показан риск ближайших стартов. Столбцы работают под production CSP и на узких экранах. Одновременно боковое меню использует согласованные публичные иконки `shlz-ui`. Срез ничего не меняет в монтажных делах и не добавляет новую систему аналитики.

Настоящий контракт дополняет и в части диаграммы активности/представления заменяет `YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001`. Неизменённые требования его разделов A–C, F и H–L сохраняются.

## Публичные seams и актор

- Актор: активный пользователь с `objects.read`; навигационные пункты дополнительно остаются ограничены своими прежними permissions.
- Data seam: `YiiOperationalDashboard::read(actorId, cutoff)` возвращает атомарный bounded DTO.
- HTTP seam: `GET|HEAD /pilot/dashboard` и `GET /pilot/objects?chart=start-risk&bucket=<key>`.
- Browser seam: реальный Yii shell, production CSP и viewport `1440/1201/1200/1051/900/681/680/390`.
- Все чтения read-only: domain facts/audit/schema/permissions не меняются.

## R1. Риск срыва ближайших стартов

Прежняя диаграмма давности активности удаляется. `charts.startRisk` содержит ровно пять записей в указанном порядке:

1. `overdue_start` — плановая дата начала `< cutoff`, этап «Требуется распоряжение» или «Готов к открытию»;
2. `order_0_7` — этап «Требуется распоряжение», дата `cutoff..cutoff+6` inclusive;
3. `order_8_14` — этап «Требуется распоряжение», дата `cutoff+7..cutoff+13` inclusive;
4. `ready_0_14` — этап «Готов к открытию», дата `cutoff..cutoff+13` inclusive;
5. `opened_0_14` — этап «Монтажные работы» или «Документарное закрытие», дата `cutoff..cutoff+13` inclusive.

Подписи: «Плановый старт прошёл», «0–7 дней: нет распоряжения», «8–14 дней: нет распоряжения», «Готовы к открытию», «Уже открыты». Неизвестная/невалидная дата не учитывается. Эти категории не объявляются полным partition total.

Пример R1: cutoff `2026-09-21`, независимо рассчитанные counts `[26,156,10,0,0]` отображаются без изменения. Объект с прошедшим стартом и непросроченным окончанием входит только в `overdue_start`; KPI/список «Просроченные объекты» по-прежнему означает просроченное плановое окончание.

## R2. Точный risk drill-down и отказы

Каждый risk bar — native link `chart=start-risk&bucket=<key>`. Сервер заново выводит cutoff/ranges и возвращает ровно тот же bucket с обычными permission/search/page и полным filtered total. Разрешены только пять ключей R1.

Неизвестный bucket, одновременно `status` и `chart`, второй chart dimension, `from/to` или malformed value дают `400` без wide fallback/disclosure. Guest/forbidden сохраняют прежние `303/403`. HEAD имеет статус GET и пустой body.

## R3. CSP-safe пропорциональные столбцы

Height вычисляется сервером относительно максимума ряда и кодируется без inline `style`, inline script, canvas или внешней chart library. Ненулевой mark выше 4-unit zero baseline; разные нормализованные значения дают разные heights. Production CSP не выдаёт violation.

SVG mark находится в отдельной frame, ограниченной grid-ячейкой. В каждой недельной паре обе frames имеют одинаковую доступную ширину и не пересекаются, включая значения `156/5` на всех viewport из публичного browser seam.

## R4. Палитра и non-color cues

Этапы используют status families в порядке: orange, source-blue, cyan, purple, bright-green, pink. Плановое начало использует calendar bright-blue family, плановое окончание — calendar orange family. Risk использует error/orange/yellow/source-blue/bright-green progression.

Цвет не является единственным кодом: visible value, category/week, legend и accessible name остаются обязательными. Focus indicator видим.

## R5. KPI, недельные подписи и baseline

Четыре KPI используют одинаковые tracks `title/value/basis`, одинаковую высоту строки и без избыточного пространства. Верх чисел совпадает в пределах 1 CSS px в четырёх- и двухколоночной композиции.

Все bars одного chart имеют фиксированные mark/value/label tracks; перенос «Документарное закрытие» или другой подписи не двигает mark/value. Под недельными bars отсутствуют повторные «Начало/Окончание»: смысл сохраняет легенда и accessible name. Неделя показывает start и end двумя строками с коротким разделителем.

Когда недельный и risk widgets стоят рядом, их header и начало marks совпадают в пределах 1 CSS px.

## R6. Responsive

При viewport `>1200` недельный/risk widgets стоят рядом; при `<=1200` идут одним столбцом. При `<=680` шесть недель образуют две колонки. На `1440/1201/1200/1051/900/681/680/390` нет page overflow, clipping обязательного текста или overlap marks. Mobile navigation/FAB не закрывают обязательные значения после scrollIntoView.

## R7. Навигация и публичные иконки

Точные public exports `shlz-ui`:

- «Объекты монтажа» — `docs`;
- «Стройконтроль» — `eye`;
- «Календарь» — `calendar-interface` (семейство Interface, не Sidebar);
- «ОТиЗ» — `graph`;
- «Монтажники» сохраняет существующую публичную иконку;
- «Дашборд» — `bar-chart-square-plus` и следует непосредственно после «Монтажники»;
- «Пользователи» — `user`;
- «Роли» — `settings`.

Copied bytes имеют закреплённую provenance/hash. Fill icons сохраняют fill, stroke icons — stroke на Dashboard/Calendar/Objects/Construction Control/Users/Roles независимо от active item, AssetBundle order и cache. Все menu-bearing bundles используют один content-derived version `pilot.css`, не постоянный prototype query.

## R8. Bounded, атомарность и history

Query count/DTO shape не растут с объектами; полный корпус не материализуется в PHP/DOM. Для 30k сохраняется фиксированный shape `6 stages + 6 weeks + 5 start risks` и существующий memory/query envelope. Новых таблиц/cache/jobs/writers нет.

Malformed shape или infrastructure failure любого обязательного aggregate дают единый безопасный `503` без частичных charts, SQL/schema/object identities. Empty dataset даёт `200` и честные нули. Repeat/concurrent GET/HEAD детерминированы и не меняют DB fingerprint.

## Done

Done требует planner-selected focused checks, strict OpenSpec validation, обязательные independent reviews и один GREEN exact-source CI consumer. Локальный полный `make test`/`make verify`, merge и deploy не входят.
