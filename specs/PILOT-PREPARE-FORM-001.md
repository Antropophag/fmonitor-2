# PILOT-PREPARE-FORM-001 — открыть форму состава первого распоряжения

- Статус: `DRAFT — Gate 1 rereview required`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: exact active legacy-пользователь с active legacy-ролью и capability `assignment_order.prepare`
- Публичный seam: HTTP `GET|HEAD /pilot/objects/{positive-id}/assignment-order/prepare`
- Successor contracts: `PILOT-HTTP-AUTH-001 v0.12`, `PILOT-OBJECT-CARD-001 v0.2`, `ORDER-PREPARE-001..010`, `WORKFORCE-CATALOG-001`, `PROCESS-USER-DIRECTORY-001`

## 0. Утверждённая upload-first поправка v0.2

Версия 0.2 заменяет перечисленные ниже assertions. Успешный read-only
`GET|HEAD` остаётся тем же RBAC seam и не принимает
файл, не вызывает command и не сохраняет выбор. Card launch имеет exact текст
`Загрузить распоряжение`. GET показывает sole `h1` `Загрузить распоряжение`,
intro `Укажите состав и прикрепите подписанный оригинал.`, breadcrumb current
`Распоряжение` и compact immutable object summary.

Монтажники передаются в inert `<template data-picker-data>` как escaped
normalized records и выбираются через кнопку `Выбрать монтажников`. External
same-origin `picker.js` строит результаты только DOM API/`textContent`, никогда
не `innerHTML`; query нормализуется, минимум 2 символа, максимум 20 видимых
результатов. Result — native button с `aria-pressed`; выбранные люди видимы как
remove-buttons и отражаются exact hidden `installerTabIds[]`. Search имеет
label, result container — polite live semantics, popover управляется native
button/keyboard. При отсутствии/ошибке JS hidden IDs не появляются и command
не может получить скрытый состав. Исходный read-only DOM не содержит
state-changing submit.

Каждая inert record — ровно один empty `span` с exact attributes
`data-id`, `data-name`, `data-tab`, `data-position`, `data-busy`,
`data-selected`; значения HTML-escaped. `data-id` — canonical unpadded decimal
`installerTabId` и future hidden value (`1042`), `data-tab` — display-only
шесть цифр (`001042`), `data-busy` — empty либо `YYYY-MM-DD`,
`data-selected=0` initial. Parser читает только эти six dataset fields из
direct template descendants и fail closed: duplicate/unknown/missing field,
invalid ID/tab/flag/date, несогласованные ID/tab, malformed UTF-8 либо больше 500 records даёт
redacted `503`, а не partial picker.

Единственный route-specific script — `<script
src="/pilot/assets/picker.js" defer></script>` после shared navigation asset.
Exact `GET|HEAD /pilot/assets/picker.js` наследует asset admission: GET `200`,
exact JavaScript media type/length/cache и immutable bytes; HEAD те же
headers/empty body; другой method — `405/Allow: GET, HEAD`; missing, unreadable,
changed или non-regular configured asset — redacted `503`. Asset/error/redirect
responses получают BASE_CSP без script permission; successful prepare HTML —
SCRIPT_CSP. Никаких CDN/remote/inline/fallback bytes.

Все picker controls, кроме search input, имеют explicit `type=button`. Open
button имеет `aria-controls=installer-picker`, initial `aria-expanded=false` и синхронный state;
popover получает focus в search, Escape закрывает и возвращает focus opener,
Tab остаётся в native document order без trap. Result buttons имеют exact
`aria-pressed`; removal button exact accessible name `Убрать {ФИО}`. Count и
result meta находятся в `aria-live=polite`, selection summary имеет label
`Выбранные монтажники`. Exact announcements: `Введите минимум 2 символа`,
`Найдено: {N}`, `Найдено {N}. Показаны первые 20`, `Ничего не найдено.
Проверьте ФИО или табельный номер.`, `Выбрано: {N}`. После удаления focus
возвращается на opener picker, если chip исчез. Ни цвет, ни `+`/`✓` не являются
единственным state.

До successful initialization trigger и popover имеют `hidden`, а visible
fallback говорит `Для выбора монтажников включите JavaScript или вернитесь к
карточке объекта.` Init сначала атомарно валидирует весь dataset, затем снимает
hidden и скрывает fallback. Load/error/parser rejection оставляет fallback,
zero hidden IDs и zero request/session/domain mutation.

Инженеры сохраняют нормативный §6: radio group, допустимый legacy prefill и
отдельный unchecked confirmation checkbox. Это не read-only reference из
карточки. Пользователь выбирает и явно подтверждает инженера до будущего
upload command.

Read-only response содержит GET form на canonical path, neutral link `Отмена`
на карточку и helper `Нужен шаблон?`, но без file input, multipart, CSRF,
upload/template submit или mutation. Эти controls принадлежат отдельно gated
HTTP command composition. Eligibility, provenance, ordering, authorization,
GET/HEAD/error precedence и zero-mutation правила сохраняются.

| Старый clause | v0.2 replacement |
|---|---|
| §1 installer checkbox part | picker contract выше; engineer radio/confirmation остаются |
| §4 card link `Сформировать распоряжение` | `Загрузить распоряжение` |
| §5 checkbox markup | inert data + result buttons + exact hidden selected IDs |
| §7 installer empty state | `Нет допустимых монтажников.` без picker/results |
| §8 heading/breadcrumb/intro/form controls | exact v0.2 values выше; engineer §6; external picker script; no command controls |
| §9 installer checkbox example | insertion-independent picker data order `1042, 2088`, initially no hidden selected IDs |
| §10 assertions naming old markup | same authorization/failure/zero-write outcomes over v0.2 representation |

## 1. Цель и единственный acceptance tracer

Дать сотруднику ФКР первый read-only шаг подготовки распоряжения из карточки одного допустимого объекта монтажа: увидеть неизменяемый контекст объекта, выбрать одного или нескольких допустимых монтажников, выбрать ровно одного допустимого инженера строительного контроля и проверить происхождение и свежесть кадрового каталога. Ничего не формируется и не сохраняется.

Единственное acceptance statement среза:

> Уполномоченный сотрудник ФКР открывает обычную ссылку из карточки явно импортированного объекта в состоянии `needs_assignment_order` и получает каноническую форму состава: все и только допустимые на дату чтения монтажники представлены независимыми checkbox в порядке ФИО/табельного номера, все и только допустимые инженеры — одним radio-group в порядке ФИО/user ID, допустимый legacy `responsstroicontrol` предвыбран, но отдельно не подтверждён; источник и момент свежести кадровых данных видимы, а GET/HEAD не изменяют ни одного факта.

Это только форма выбора. В срезе нет submit, команды, документа или draft persistence.

## 2. Наследуемые границы и порядок проверок

Без исключений наследуются:

- trusted Host, `REMOTE_USER`, exact active legacy user/role, CSS descriptor, headers, redaction, correlation/reporting и attempt-all cleanup из `PILOT-HTTP-AUTH-001 v0.12`;
- правило явного pilot case, нормализация identity/plan и integrity semantics из `PILOT-OBJECT-CARD-001 v0.2`;
- серверные правила обязательного состава и допустимости людей из `ORDER-PREPARE-001`, `ORDER-PREPARE-003`, `ORDER-PREPARE-004`;
- production-owned текущий кадровый каталог и process capabilities из `WORKFORCE-CATALOG-001` и `PROCESS-USER-DIRECTORY-001`.

Для matched route порядок exact:

```text
valid Host
→ exact route and GET|HEAD method
→ REMOTE_USER syntax
→ validated public shlz-ui CSS
→ exact active user with active legacy role
→ actor capability assignment_order.prepare
→ imported object/card integrity and current process state
→ current workforce, sync metadata, eligible engineers and legacy prefill
→ render
```

Capability проверяется до чтения объекта и каталогов людей. Поэтому пользователь без capability получает `403` независимо от существования/состояния запрошенного объекта и не может перечислять pilot IDs. Успешное широкое право чтения карточки само по себе не разрешает этот экран.

## 3. Route, query и методы

| Request | Наблюдаемый результат |
|---|---|
| `GET /pilot/objects/4512/assignment-order/prepare` | `200` форма при выполнении всех предусловий |
| `HEAD` того же exact path | тот же status и application-controlled headers, exact GET `Content-Length`, пустой body |
| тот же path с любым query | query полностью игнорируется; тот же canonical результат |
| ID `0`, leading zero/sign/space/overflow/non-decimal, trailing slash, extra/encoded segment | inherited `404` до identity/config/DB/CSS |
| `POST|PUT|PATCH|DELETE` и иной метод exact route | inherited `405`, `Allow: GET, HEAD`, до identity/config/DB/CSS и без чтения body |

Canonical route не имеет alias и redirect. Card link имеет exact href `/pilot/objects/{ID}/assignment-order/prepare`.

## 4. Предусловия успеха и launch из карточки

Успех требует одновременно:

1. актор имеет `assignment_order.prepare`;
2. существует ровно один explicit `fm2_installation_cases` для exact legacy object;
3. process state exact `needs_assignment_order`;
4. нет актуальной/исторической версии распоряжения, opening facts, completion или `pto_act_date`;
5. все обязательные card/object facts валидны по `PILOT-OBJECT-CARD-001`;
6. каталоги и metadata структурно валидны.

Только при этих условиях карточка этого объекта для такого актора добавляет в раздел «Распоряжение и команда» обычную compact `shlz-ui` Button/Link-ссылку с видимым текстом `Сформировать распоряжение` и canonical href. Она расположена после причины состояния и до исторических сведений. Для broad-read пользователя без capability и для любого другого process state ссылки/текста/URL нет. Карточка остаётся read-only.

Прямой запрос неизвестного либо не импортированного объекта наследует exact `404`. Dangling/duplicate case, повреждённые object facts или противоречивое состояние возвращают inherited redacted `503`. Валидный объект не в `needs_assignment_order` возвращает:

```text
409
Content-Type: text/plain; charset=UTF-8
Формирование распоряжения недоступно для текущего состояния объекта монтажа.\n
```

`409` не раскрывает конкретный state, документ или состав.

## 5. Допустимые монтажники

Дата допустимости формы — календарная дата в Europe/Moscow, полученная от того же production clock snapshot один раз на request. `plannedFinishDate` берётся из утверждённого object snapshot.

Строка `fm2_workforce_catalog` является selectable, только если:

- `installer_tab_id` — positive integer;
- trimmed `fio`, `position`, `workforce_source` непусты;
- `workforce_source_updated_at` — валидный RFC 3339 момент со смещением;
- `employment_status = employed`;
- `employed_from` — валидная дата и `employed_from <= requestBusinessDate`;
- `employed_to` равно `null` либо валидно и `employed_to >= plannedFinishDate`;
- при наличии history-v5 полей `reconciliation_state = delivered`, `last_successful_sync_run_id` и `last_successful_sync_at` согласованы с текущим успешным metadata run; legacy v2 rows без этих additive полей не выдумывают history provenance и используют утверждённые `workforce_source*`.

Это preview eligibility; команда всё равно заново проверяет людей в момент будущего POST. Уволенные, ещё не трудоустроенные, заканчивающие трудовые отношения раньше planned finish и missing-from-delivery строки не показываются даже disabled. Неявки, отпуска, квалификации, загрузка и пересекающиеся назначения не фильтруются: утверждённых production facts/policy для них в этом срезе нет.

Каждый кандидат представлен native `input.shlz-checkbox[type=checkbox]` внутри `label.shlz-choice`, `name="installerTabIds[]"`, canonical decimal `value`. Checkbox изначально unchecked. Видимый label exact:

```text
{ФИО} · табельный № {tabId} · {должность}
```

Порядок: Unicode code-point ascending полного нормализованного ФИО; tie — numeric `tabId ASC`. Duplicate tab identity либо invalid required value в любой delivered/current row — `503`, а не silent omission. Hard ceiling — 500 eligible rows; 501-я даёт `503`, без truncation.

## 6. Допустимые инженеры и prefill

Инженер selectable, только если exact legacy user и его legacy role активны, существует exact capability `construction_control_engineer`, а trimmed `users.name` и capability `position_snapshot` непусты. Capability `assignment_order.prepare` не делает пользователя инженером.

Все eligible инженеры представлены одним native `fieldset` с `legend` `Инженер строительного контроля` и same-name `input.shlz-radio[type=radio]` внутри `label.shlz-choice`; `name="controlEngineerUserId"`, canonical decimal `value`. Видимый label exact:

```text
{ФИО} · {должность}
```

Порядок: Unicode code-point ascending ФИО; tie — numeric user ID. Duplicate eligible user identity либо invalid required value — `503`. Hard ceiling — 100; 101-я строка даёт `503` без truncation.

Legacy `fm_maintable.responsstroicontrol` используется только как подсказка:

- если это canonical positive ID одного eligible инженера, соответствующий radio имеет `checked`;
- рядом после group расположен unchecked native `input.shlz-checkbox` с label `Подтверждаю выбор инженера строительного контроля` и `name="controlEngineerConfirmed"`, `value="yes"`;
- если legacy value отсутствует, invalid или не eligible, ни один radio не checked, confirmation checkbox всё равно unchecked;
- legacy значение никогда не создаёт option, не показывается как raw ID/ошибка и не обходит eligibility.

Таким образом предзаполнение не является подтверждением. Выбрать можно ровно одного инженера благодаря native radio semantics; подтвердить выбор пользователь должен отдельно в будущем submit-срезе.

## 7. Provenance, freshness, empty states

Над группой монтажников видимы:

```text
Источник кадровых данных: {workforceSource}
Актуально на: {sourceUpdatedAt}
```

Если selectable rows имеют разные source/update moments, показывается отдельный provenance у каждой строки после основного label. Если они одинаковы, показывается одна group-level пара. Значения выводятся без переименования, HTML-escaped. Свежесть информативна: этот срез не вводит age threshold.

Ноль eligible монтажников — успешный `200` с exact текстом `Нет монтажников, допустимых для планового периода объекта.` и без installer checkboxes. Ноль eligible инженеров — успешный `200` с exact текстом `Нет доступных инженеров строительного контроля.` и без engineer radios. При любом empty state видна ссылка возврата, но отсутствуют confirmation control и будущая primary action placeholder. Если обе группы непусты, форма содержит controls обеих групп.

Отсутствующая singleton sync metadata при history-v5, failed/in-progress latest referenced run, malformed timestamps, mixed unsound provenance, DB/schema/query failure или ceiling overflow дают inherited redacted `503` с `Retry-After: 60`.

## 8. Successful HTML, доступность и responsive composition

GET success наследует shell/headers и содержит:

- один `<h1>Состав распоряжения</h1>`;
- breadcrumb/navigation: `Объекты монтажа` → `/pilot/objects`, `Объект монтажа № {ID}` → card, current non-link `Состав распоряжения`;
- компактную read-only сводку: регистрационный номер, адрес, подъезд, planned start/finish;
- пояснение `Выберите состав. Распоряжение будет сформировано только после отдельного подтверждения.`;
- один semantic `<form method="get" action="/pilot/objects/{ID}/assignment-order/prepare">`, содержащий только controls разделов 5–6 и не имеющий submit control;
- обычную ссылку `Вернуться к объекту монтажа` на canonical card.

Группы имеют native `fieldset/legend`; wrapping labels дают accessible names; DOM order совпадает с visual/tab order; checkbox и radio работают pointer/keyboard без JavaScript. Страница использует только public `shlz-ui` CSS (`shlz-scope`, typography, Link, Checkbox, Radio, Button/Link на карточке), не копирует tokens/components и не добавляет CSS/JS. `Select` намеренно не используется: public `shlz-ui` Select single-only, требует behavior JS и не подходит множественному выбору монтажников.

На узком viewport сводка и option labels остаются в document flow, длинные ФИО/должности переносятся, controls не требуют horizontal scrolling, hover или скрытых действий. Никакой ряд целиком не становится ссылкой.

Страница не содержит submit/button, `action` с mutation route, CSRF/session token, hidden selected people (кроме отсутствующих здесь Select controls), inline script/style, artifact/document preview, date/order number, organization-form input, conflict/loading indicator, role switcher или command URL. Query не предзаполняет controls.

## 9. Независимый executable example

Actor `18 / Сидоров Сергей Сергеевич / sidorov@shlz.ru` active, имеет active role и `assignment_order.prepare`. Object `4512` явно импортирован, `needs_assignment_order`, без распоряжения/opening/PTO/completion:

```text
registrationNumber = 77-000123
address = Москва, ул. Примерная, д. 10
entrance = 2
plannedStartDate = 2026-10-05
plannedFinishDate = 2026-12-20
responsstroicontrol = 73
requestBusinessDate = 2026-08-28
```

Workforce fixtures in deliberately noncanonical insertion order:

| tabId | ФИО | должность | status / period | source / updated | result |
|---:|---|---|---|---|---|
| 2088 | Петров Пётр Петрович | Электромеханик по лифтам | employed, `2025-01-10..null` | `one_c_zup_via_bitrix` / `2026-08-27T18:15:00+03:00` | selectable |
| 1042 | Иванов Иван Иванович | Электромеханик по лифтам | employed, `2024-02-01..null` | same | selectable |
| 3099 | Соколов Семён Семёнович | Электромеханик по лифтам | employed, `2026-09-01..null` | same | excluded: not employed yet |
| 4001 | Уволен Устин Устинович | Электромеханик по лифтам | dismissed, `2020-01-01..2026-08-01` | same | excluded |

Engineer fixtures in deliberately noncanonical insertion order:

| userId | ФИО | state/capability | должность | result |
|---:|---|---|---|---|
| 74 | Борисова Вера Ильинична | active user/role + engineer capability | Инженер строительного контроля | selectable |
| 73 | Анна Волкова | active user/role + engineer capability | Инженер строительного контроля | selectable, checked prefill |
| 75 | Громов Олег Игоревич | inactive user + engineer capability | Инженер строительного контроля | excluded |
| 76 | Дроздов Максим Ильич | active, no engineer capability | — | excluded |

Parsed DOM proves exactly:

```text
installer checkbox order: 1042, 2088; both unchecked
engineer radio order: 73, 74; only 73 checked
controlEngineerConfirmed: present and unchecked
source: one_c_zup_via_bitrix
freshness: 2026-08-27T18:15:00+03:00
```

IDs/names for `3099`, `4001`, `75`, `76` are absent. Card contains the exact launch link for actor 18. The same card for active broad-reader actor 19 without capability contains no launch text/link/URL.

## 10. HEAD, authorization, failures и отсутствие мутаций

HEAD выполняет те же authorization, state, eligibility, ordering, ceiling and integrity decisions as GET. Он возвращает те же status и application-controlled headers, включая GET body length, но empty body.

| Condition after valid Host/route/method | Result |
|---|---|
| missing/malformed `REMOTE_USER` | inherited exact `401` |
| CSS/config/auth infrastructure failure | inherited exact `503` |
| unresolved/inactive/ambiguous user or inactive/missing role | inherited exact `403` |
| active user without `assignment_order.prepare` | exact inherited `403 Access denied.\n` before object/people reads |
| unknown/non-imported object | inherited exact `404` |
| valid object in another coherent state | exact `409` from section 4 |
| object/catalog integrity or DB failure | inherited exact `503`, `Retry-After: 60` |
| one/both eligible sets empty | `200` with section 7 empty state |

Before/after fingerprints are byte-equivalent for every legacy/process/workforce/user/capability/history/sync table including auto-increment state, artifact files and `../shlz-ui`, for success, HEAD, `401`, `403`, `404`, `409` and `503`. No process command/delegate that writes is called. No draft, selection, session, cookie, audit/event, task, read marker, timestamp, artifact access or filesystem write is created. HTTP DB credentials require only `SELECT` on the tables read. Request body is never read.

All failures redact principal, requested/raw ID, object/person/catalog values, counts, state, SQL/schema/path/config, exception and stack.

## 11. Gate 2 sensitivity and review manifest

Gate 2 writes one RED real-HTTP tracer for section 1. It independently fixes section 9 values and observes only status, raw headers/body and parsed DOM; before/after DB/filesystem fingerprints are allowed only for zero mutation. It proves route/method/query/GET-HEAD parity, capability-first non-enumeration, launch visibility, eligibility/exclusion/order, unchecked installers, eligible prefill plus unchecked confirmation, provenance/freshness, both empty states, ceilings, corruption/failure redaction, forbidden controls/URLs and write-denied SELECT-only execution.

Expected values must be literals from this specification, never production mapping/render output or private SQL. Gate 3 manifest follows `PILOT-OBJECT-CARD-001` scanned-set rules and additionally pins this spec, the complete new test/support bytes, every `app/PilotHttp/*.php`, every transitively loaded `app/InstallationProcess/*.php`, `public/router.php`, production entrypoint and review record. Any membership/spec/test/support change invalidates approval; Gate 4 changes production only.

## 12. Не входит в срез

- POST, CSRF/session/Origin policy, body parsing/limits, PRG and command invocation;
- сохранение draft/selection, validation response, submit button или preview документа;
- `prepareAssignmentOrder`, renderer/artifacts/download;
- conflicts/load/qualification/absence search, filtering, pagination or remote lookup;
- changed assignment, registration, opening, checklist;
- new catalog sync/import/history policy or stale-age threshold;
- любой custom JS кроме exact same-origin `/pilot/assets/picker.js`; `shlz-ui`
  source changes и Select behavior asset;
- изменение domain model, `CONTEXT.md`, ADR или broad object-card redesign.

## 13. Решения и доказательства

- `PRODUCT.md`, pilot spec/screen flow: форма запускается из одного объекта; минимум один монтажник, ровно один инженер; provenance/freshness видимы; `responsstroicontrol` — только неподтверждённый prefill.
- `PILOT-OBJECT-CARD-001 v0.2`: canonical imported object, card facts and read boundary.
- `ORDER-PREPARE-001..010`: будущая команда повторно проверяет обязательность, кадровую допустимость, state, persistence and unknown-result behavior.
- `WORKFORCE-CATALOG-001`, `PROCESS-USER-DIRECTORY-001`: production sources and exact eligibility facts.
- Public `shlz-ui` docs: Checkbox supports multi-selection; Radio supports one visible choice without JS; Select is single-select and requires JS. Operate composition prioritizes scanability, native keyboard behavior and explicit next-step boundaries.

## 14. Gate 1 approval

- Product owner: project user
- Approved by: separately tasked Gate 1 Codex agent `/root/prepare_form_gate1`
- Date: `2026-08-28`
- Decision: `APPROVED`
- Comment: пользователь явно поручил автономно продолжать кратчайший путь к браузерному пилоту и параллелить независимые глобальные пункты без пропуска обязательных SSD + TDD gates. Version `0.1` ограничена read-only GET/HEAD формой: exact route, capability-first authorization, eligibility/prefill/provenance, empty/error states, доступная responsive `shlz-ui` composition и отсутствие любой persistence/command границы полностью разрешены до теста.

Gate 2 разрешён только для version `0.1` и должен быть написан новым отдельно поставленным агентом. Gate 3 и Gate 5 требуют собственных независимых reviewers.
