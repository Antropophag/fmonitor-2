# OTIZ-SHLZ-UI-001 — рабочий интерфейс ОТиЗ на `shlz-ui`

## Простыми словами

Сотрудник ОТиЗ должен видеть период расчёта, готовность, сумму и следующее действие как один процесс. Доказательства и нарушения не теряются отдельно от объекта, а таблицы и формы остаются рабочими на телефоне, с клавиатурой и без JavaScript. Денежные правила, права и записываемые факты не меняются.

## Public seam и actor

Actor — аутентифицированный пользователь с текущим permission `otiz.manage`. Public seam — существующие Yii2 HTTP/browser routes `/pilot/otiz`, `/pilot/otiz/objects`, `/pilot/otiz/payments`, `/pilot/otiz/history`, `/pilot/otiz/snapshots/{id}` и их существующие POST/export descendants. Source oracle — `YII2-OTIZ-WORKFLOW-001`, OTIZ publication/settlement contracts и текущие application owners в `app/Otiz`.

## Acceptance

### A1 — workflow header и навигация

Overview/register/payments/history/snapshot используют общий authenticated application shell. На payments и snapshot выбранный период, readiness/status, финансовый итог и ровно одно разрешённое основное действие представлены в одном semantic workflow header. Текущий подраздел различим через tab/subnavigation state.

### A2 — объектная связь

Каждый snapshot object образует именованный semantic region. Внутри него находятся identity/address, начислено/доступно, allocations, trace/evidence и issues этого объекта. Blocker/warning сообщает severity, текст и owner role словами; связь не зависит только от цвета, hover или JavaScript.

### A3 — семантика действий

State-changing commands представлены button compositions: primary для текущего следующего шага, secondary для вспомогательной команды, danger для сторно/отмены. Navigation остаётся ссылками. Exact routes, HTTP methods, CSRF names/values, operation/object IDs и остальные form field names не меняются.

### A4 — стратегии таблиц

Object register использует labelled-row mobile presentation с доступными заголовками/labels. Payment ledger сохраняет колонки в contained horizontal-scroll region с accessible name и keyboard focus; horizontal overflow страницы не допускается.

### A5 — responsive, zoom и input

При viewport 320, 768, 1024 и 1440 CSS px, а также 200% browser zoom, основной контент и actions не перекрываются и не создают page-level horizontal overflow. Keyboard tab order следует DOM/workflow order, focus видим. В `pointer: coarse` интерактивные цели не меньше 44×44 CSS px.

### A6 — progressive enhancement и motion

С отключённым JavaScript authenticated SSR сохраняет workflow context, object/evidence/issue content и все разрешённые native links/forms. При `prefers-reduced-motion: reduce` необязательные transitions/animations отсутствуют без изменения content, state или tab order.

### A7 — edge states

Пустой register/history, длинные regnumber/address/basis/owner labels, blocked object, draft/accepted/paid snapshot, отсутствующие issues/allocations/closures и permission-limited actor сохраняют читаемую структуру. Запрещённые actions отсутствуют; error/status feedback имеет соответствующий live role и понятный следующий шаг.

### A8 — domain invariants

GET/HEAD остаются read-only. Authorization выполняется до обработки невалидного query/payload. Формулы, суммы, permissions, routes, redirect/return paths, accepted snapshot/object/allocation/issue facts, replay/idempotency/concurrency outcomes и append-only audit/history остаются неизменными. Rejected/forbidden/invalid команды не добавляют snapshot, closure, operation или event facts; exact replay не дублирует денежные/audit facts.

### A9 — полный Yii object register

`/pilot/otiz[/objects]` показывает девять колонок существующей проекции:
«Объект», «Прогресс», «Фонд премии», «Кшах», «Заработано», «Выплачено»,
«Удержано», «Остаток фонда», «Состояние». View не вычисляет суммы и не заменяет
отсутствующий норматив на `0,00 ₽`. Summary относится ко всему реестру независимо
от страницы и фильтров.

### A10 — legacy-коды Кшах

Существующий legacy oracle `Integration.php:1612–1622` и read-only справочник
`fm_fields_values(field_id=13)` доказывают: `41` — «железобетон» и `72` —
«Железобетон и металл» дают `10000bp`; `86` — «кирпич» и `112` — «кирпич и
металл» дают `11500bp`; `123` — «Металлокаркас и сетка» даёт `12500bp`.
Другие numeric-коды не угадываются. Разрешение одинаково применяется в register
и premium inputs и не переписывает detail snapshot.

### A10a — визуальный контракт таблицы

Основная поверхность register белая. Таблица использует полный public markup:
`thead.shlz-table__head`, `tr.shlz-table__row`, `th/td.shlz-table__cell`, а
числовые заголовки и значения — `shlz-table__cell--numeric`. Заголовки имеют
`scope="col"`; прозрачная таблица не наследует серую secondary surface.

### A11 — server-side pagination

GET controls сохраняют `q`, `state`, `sort`, `pageSize`; смена отбора начинает
страницу 1. При 51 строке и `pageSize=50` страницы содержат 50 и одну строку.
Pager сообщает диапазон и total, сохраняет query context, обозначает текущую
страницу и работает ссылками с клавиатуры.

### A12 — единая `shlz-pagination`

Очередь объектов, монтажники, стройконтроль и ОТиЗ используют один renderer:
purpose-specific `nav.shlz-pagination`, `ul.shlz-pagination__list`, один `li` на
item, ссылки `.shlz-pagination__item`, `aria-current="page"`, non-link disabled
directions и ellipsis. Локальные `.fm2-pagination` и button-like substitutes
запрещены; query filters каждой surface сохраняются.

## Worked examples

### E1 — accepted object

Для объекта `BROWSER-1` с начислением `1 000,00 ₽`, удержанием `100,00 ₽` и доступной выплатой `900,00 ₽` один region показывает identity, доказательство, warning и allocation; primary действие периода — завершить выплату. Эти суммы определены существующим settlement oracle, а не HTML.

### E2 — сторно

Ledger row с исходным удержанием `100,00 ₽` предлагает danger-action «Сторнировать». Успех добавляет отдельную reversal запись `−100,00 ₽`, связанную с исходной, и не меняет исходный факт.

## Verification matrix

| Acceptance | Public evidence |
|---|---|
| A1–A4, A7 | Authenticated Yii2 HTTP DOM structure test и browser roles |
| A5–A6 | Playwright matrix 320/768/1024/1440, zoom, keyboard, coarse pointer, reduced motion, JS-off |
| A3, A8 | Existing publication/settlement browser + HTTP/domain regressions with before/after facts |
| A9–A11 | Authenticated Yii2 register browser и public ObjectRegister assertions |
| A12 | Shared renderer contract плюс Yii HTTP/browser consumers |

## Non-goals

Новые формулы, acceptance semantics, permissions, routes, schema/writer, изменение исторических фактов, redesign других экранов, SPA и новая зависимость не входят.
