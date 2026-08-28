# PILOT-UI-SHELL-001 — цельный read-only UI-shell пилотного пути ФКР

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-29`
- Актор: exact active legacy-пользователь с active legacy-ролью, аутентифицированный доверенным HTTP-сервером
- Публичный seam: HTTP `GET|HEAD /pilot/`, `/pilot/objects`, `/pilot/objects/{positive-id}`, `/pilot/objects/{positive-id}/assignment-order/prepare` и их CSS assets
- Successor contracts: `PILOT-HTTP-AUTH-001 v0.12`, `PILOT-OBJECT-LIST-001 v0.1`, `PILOT-OBJECT-CARD-001 v0.2`, `PILOT-PREPARE-FORM-001 v0.1`

## 1. Цель и граница

Собрать уже утверждённый read journey сотрудника ФКР в один визуально цельный продуктовый путь:

```text
очередь импортированных объектов монтажа
→ карточка выбранного объекта монтажа
→ форма выбора состава для подготовки распоряжения
```

Срез меняет только успешную HTML-композицию и application-owned responsive layout. Он не добавляет POST, command URL, process state, task/SLA, поиск, фильтры, pagination, session/CSRF, document download или любую доменную, audit либо legacy-мутацию. Форма подготовки остаётся read-only GET/HEAD из `PILOT-PREPARE-FORM-001`; отправка формы будет отдельным command-срезом.

Поддерживаемый UI-слой означает: общий document shell, навигация, page header, status/next-action, collection, details и form-field композиции имеют отдельные PHP view/render abstractions; route/application orchestration передаёт им нормализованные view models и не конкатенирует page HTML в `PilotHttp.php`. Общий application CSS отдаётся отдельным фиксированным asset рядом с неизменённым публичным `shlz-ui` export. Это проверяется через публичный HTTP seam, manifest исходных файлов и запрет page-template markup в orchestration entrypoint, а не через private renderer methods.

## 2. Наследуемые контракты и приоритет

Без исключений наследуются:

- exact route grammar, Host/identity resolution, method priority, authorization, redaction, security/cache headers, HEAD parity и `Content-Length`;
- membership/import boundary, значения, canonical ordering и ceiling очереди; этот successor дополнительно присоединяет уже утверждённую card process projection для state/next-step, не вводя новый domain fact;
- approved card process projection и route-specific availability;
- prepare-form capability, eligibility, provenance, prefill и empty-state rules;
- HTML escaping всех DB-derived значений и нулевой persistence footprint GET/HEAD.

Эта версия заменяет только запреты successor contracts на application CSS и прежнюю минимальную successful-page DOM-композицию. Exact generic `400/401/403/404/405/503` plaintext outcomes не брендируются: они остаются security/error states предшествующих контрактов, включая `Retry-After: 60` для `503`. Ошибка не маскируется пустым состоянием и не раскрывает внутреннюю причину.

## 3. Публичные assets и композиционная граница

Каждая успешная HTML-страница подключает в указанном порядке ровно два local stylesheet:

1. `/pilot/assets/shlz.css` — байты валидированного public `@shlz/styles/shlz.css` export;
2. `/pilot/assets/pilot.css` — application-owned layout/composition stylesheet FMonitor 2.0.

`GET|HEAD /pilot/assets/pilot.css` наследует unauthenticated asset semantics, headers, HEAD parity и fail-closed filesystem handling CSS route из `PILOT-HTTP-AUTH-001`. Имя фиксировано; filename/path parameter отсутствует. CSS не копирует исходники, component rules или token literals `shlz-ui`, не переопределяет `.shlz-*` family classes и не использует `!important`. Он вправе задавать только `.fm2-*` layout/composition classes и media queries, используя публичные CSS custom properties `shlz-ui` с безопасным fallback там, где export их документирует.

Production PHP source разделён минимум на:

- HTTP orchestration/routing;
- HTML document shell;
- shared navigation/page-header/feedback compositions;
- queue, object-card и prepare-form views;
- escaping/URL helpers с одним владельцем.

Ни один route handler не хранит полный HTML-документ или page-sized heredoc/string. View не выполняет SQL, authorization, process commands, file writes или header decisions. View model содержит только уже утверждённые нормализованные значения и URLs.

JavaScript и `@shlz/behaviors` в этом срезе не подключаются. Все переходы — обычные ссылки; все controls формы — native HTML.

## 4. Общий shell, навигация и доступность — `PILOT-UI-SHELL-001-A`

Каждая успешная страница содержит:

- `<!doctype html>`, `<html lang="ru">`, UTF-8, responsive viewport и title вида `{Название страницы} · FMonitor`;
- `body.shlz-scope` и первую focusable ссылку `Перейти к содержанию` на `#main-content`;
- landmark header с product identity `АО «ЩЛЗ»` и `FMonitor 2.0`, затем actor name;
- primary navigation с доступными ссылками `Моя работа` → `/pilot/` и `Объекты монтажа` → `/pilot/objects`;
- каждый из пунктов `Распоряжения`, `Инспекции`, `Монтажники`, `Расчёты`, `Нарушения` — отдельный `span.fm2-nav__unavailable[aria-disabled="true"]`, внутри которого ровно два дочерних `span`: `.fm2-nav__label` с названием и `.fm2-nav__hint` с exact text `Не входит в пилот`; wrapper и children не имеют `href`, `role`, `tabindex` или button semantics;
- ровно один текущий navigation item с `aria-current="page"`;
- `<main id="main-content" tabindex="-1">`, один `h1`, логическую последовательность heading levels и visible keyboard focus;
- breadcrumb — `nav[aria-label="Хлебные крошки"]` с ordered list; каждый предшествующий item содержит ровно одну ordinary canonical link, последний item содержит `span[aria-current="page"]` и не содержит link.

Shell не содержит role switcher, fake notification count, burger button без поведения, icon-only control без accessible name, row-level click handler, inline `style`, inline script или remote font/CDN request. Fira Sans/Golos и иконки могут использоваться только как локальные публичные exports `shlz-ui`; отсутствие декоративной иконки не меняет смысл.

Desktop (`viewport >= 960 CSS px`): navigation занимает устойчивую левую колонку, content — fluid main column с readable maximum width. Narrow (`viewport <= 767 CSS px`): identity и actor не перекрываются, navigation становится горизонтальным wrapping/scroll-free списком доступных разделов, недоступные разделы скрываются как вторичный контекст, main остаётся одноколоночным. При ширине `320 CSS px`, 200% text zoom и длинных фиксированных примерах нет horizontal page overflow, clipped text или hover-only action.

Heading contract детерминирован: в `main` ровно один `h1`; прямые именованные content sections используют `h2`; подразделы внутри них — только `h3`; `h4..h6` отсутствуют, и никакой heading level не пропускается. Product identity, navigation, breadcrumb, eyebrow, status и empty-state description не изображаются headings. Для обычной queue page breadcrumb exact: link `Моя работа` → `/pilot/`, затем current `Объекты монтажа`. Для card: link `Объекты монтажа` → `/pilot/objects`, затем current registration number. Для prepare: link `Объекты монтажа` → `/pilot/objects`, link registration number → canonical card URL, затем current `Подготовка распоряжения`.

Automated responsive oracle ограничен существующим raw HTTP/CSS seam. Отданный `/pilot/assets/pilot.css` обязан содержать активный `@media (max-width: 767px)` contract, который для application classes переводит `.fm2-shell` и `.fm2-object-layout` в `grid-template-columns: minmax(0, 1fr)`, скрывает `.fm2-nav__unavailable`, разрешает `.fm2-primary-nav` переносом `flex-wrap: wrap` и переводит `.fm2-queue-table`/её row/cell composition в вертикальный flow без fixed/min-width. Base application rules обязаны задавать `max-width: 100%`, `min-width: 0` для fluid content owners, `overflow-wrap: anywhere` для DB-derived long text и visible `:focus-visible` outline для `.fm2-shell a`/native controls. Gate 2 наблюдает эти exact served-CSS contracts как text/parsed declarations и DOM order; он не заявляет pixel/layout/browser proof.

## 5. Очередь — `PILOT-UI-SHELL-001-B`

Успешный `/pilot/objects` сохраняет membership/order `PILOT-OBJECT-LIST-001` и показывает:

1. eyebrow `Моя работа`;
2. `h1` `Объекты монтажа`;
3. пояснение `Выберите объект монтажа, чтобы продолжить подготовку работ.`;
4. компактное summary `В очереди: N`, где `N` — точное число уже прочитанных импортированных cases, без отдельного count query;
5. semantic collection в canonical order.

Desktop collection использует native `table.shlz-table` с четырьмя смысловыми колонками: `Объект монтажа`, `План`, `Состояние`, `Следующий шаг`. Каждая строка содержит один canonical object link; registration number и numeric ID, адрес/подъезд, плановые даты, утверждённое process state карточки и одно действие.

Этот срез не выдумывает process task. Следующий шаг детерминирован только из уже утверждённой projection:

| Projection | Visible status | Следующий шаг |
|---|---|---|
| нет актуального распоряжения, preparation gate available | `Требуется распоряжение` | ссылка `Выбрать состав` на approved prepare-form URL |
| актуальное распоряжение `prepared` | `Распоряжение подготовлено` | text `Внести номер 1С ДО в карточке объекта` |
| актуальное распоряжение `registered`, дело не открыто | `Готов к открытию` | text `Открыть работы в карточке объекта` |
| дело `working` | `В работе` | text `Перейти в карточку объекта` |
| gate запрещает подготовку или projection не даёт безопасного action | текущий truthful status | canonical object link `Посмотреть причину` |

List read model читает для каждого case ту же утверждённую process projection, что карточка, одним bounded production read без N+1 queries. Любая комбинация, которую `PILOT-OBJECT-CARD-001` считает corrupt, остаётся fail-closed `503` для полной очереди. Последний fallback применяется только к валидному состоянию, в котором predecessor projection не доказывает доступную команду; неизвестное/corrupt состояние им не маскируется.

Narrow collection — не сжатая таблица: header скрыт presentation-only способом, каждая row остаётся в DOM и визуально становится card-like vertical list item в порядке `identity → address → plan/status → next step`. Labels сохраняются через visible/pseudo-free markup; table semantics не ломаются `display: contents`. На странице отсутствует горизонтальный scroll container.

При `N=0` table и summary отсутствуют, а public `shlz-empty-state` показывает title `Сейчас нет объектов монтажа`, description `Импортированные объекты монтажа пока отсутствуют.` и ссылку `Обновить страницу` на `/pilot/objects`. Это ordinary empty state без `role=alert`.

## 6. Карточка — `PILOT-UI-SHELL-001-C`

Успешная карточка сохраняет все факты и state rules `PILOT-OBJECT-CARD-001`, но располагает их в task-first порядке:

1. breadcrumb `Объекты монтажа / {registration number}`;
2. identity header: `h1` с registration number, numeric ID, address/entrance и textual `shlz-status`;
3. prominent section `Следующий шаг` с одним truthful primary link либо объяснением блокировки;
4. main section `Распоряжение и состав`;
5. side summary `Сроки и состояние`;
6. secondary `История процесса`, только если approved projection действительно содержит события.

При отсутствии актуального распоряжения и доступном preparation gate primary link имеет exact text `Выбрать монтажников и инженера` и href `/pilot/objects/{ID}/assignment-order/prepare`. Если gate запрещён, control не рендерится disabled: рядом с heading показываются visible reason и neutral canonical card context, без ложного action.

Факты документа используют public `shlz-document-row` только когда документ реально существует; состав — text/person-tag presentation без remove controls; status всегда имеет visible text и не полагается на цвет. История не вытесняет next action и не изображает интерактивный stepper.

Desktop content grid — основная fluid колонка и summary шириной не более одной трети. Narrow order exact: identity → next step/reason → deadlines → order → team → history. Links wrap, document actions remain reachable, no sticky/fixed CTA appears.

## 7. Форма подготовки — `PILOT-UI-SHELL-001-D`

Успешная read-only prepare page сохраняет exact data/authorization/eligibility `PILOT-PREPARE-FORM-001` и показывает:

1. breadcrumb to canonical card;
2. `h1` `Подготовка распоряжения` и immutable object summary;
3. persistent contextual note `Распоряжение ещё не будет сформировано на этом экране.`;
4. section `1. Монтажники` с source/freshness рядом с heading, native checkbox candidates и visible employment/conflict metadata;
5. section `2. Инженер строительного контроля` с native radio candidates; prefill is visually marked `Предложено по объекту` but remains unchecked until explicitly confirmed in a future command form;
6. section `3. Проверка` with derived labor form preview and checklist of unresolved requirements;
7. neutral link `Вернуться к объекту` to canonical card.

Поскольку этот срез не вводит POST, primary `Сформировать распоряжение` отсутствует. Native checkbox/radio controls остаются enabled, keyboard-operable GET-form controls exactly as predecessor contract requires, но submit control отсутствует и состояние не сохраняется. Approved eligible prefill radio остаётся `checked`, а отдельный checkbox подтверждения — unchecked: предложение не считается подтверждением. Empty installer/engineer catalog uses `shlz-empty-state` with predecessor exact reason and card return link. A recoverable view-model validation problem is a persistent `shlz-notification shlz-notification--danger` with `role="alert"`, concise visible title and card return link; infrastructure/integrity failures remain inherited plaintext `503`.

Desktop candidates form a scan-friendly list, not one card per person and not a wide data grid. Narrow order is unchanged; labels wrap next to fixed-size native controls, metadata moves below the name, and no content requires horizontal scrolling.

## 8. Independently fixed executable examples — `PILOT-UI-SHELL-001-E`

All expected values below are fixed in this specification and must not be derived from production renderer output, CSS selectors discovered after implementation or SQL fixture order.

Actor:

```text
18 / Сидоров Сергей Сергеевич / sidorov@shlz.ru
```

Queue fixtures in deliberately noncanonical insertion order:

| ID | Registration | Address / entrance | Planned start | Projection |
|---:|---|---|---|---|
| 4515 | `77-000126` | `Москва, ул. Третья, д. 3 / 1` | `2026-10-05` | no order, prepare available |
| 4512 | `77-000123` | `Москва, ул. Примерная, д. 10 / 2` | `2026-10-05` | prepared v1 |
| 4513 | `77-000124` | `Москва, ул. Вторая, д. 7 / 1` | `2026-10-01` | registered v2, not opened |

Observable queue item order and actions:

```text
4513 → Готов к открытию → Открыть работы в карточке объекта
4512 → Распоряжение подготовлено → Внести номер 1С ДО в карточке объекта
4515 → Требуется распоряжение → /pilot/objects/4515/assignment-order/prepare
```

Card `4515` exposes one primary journey link `Выбрать монтажников и инженера` to that exact URL. Prepare view uses independently fixed candidates:

```text
installer 00017 — Иванов Иван Иванович — Трудоустроен
installer 00021 — Петров Пётр Петрович — Трудоустроен — занят до 2026-10-03
engineer user 31 — Смирнова Анна Олеговна — Инженер строительного контроля
prefill engineer user 31 — radio checked and visibly marked as a suggestion; confirmation checkbox unchecked
catalog provenance — Bitrix24 / 1С ЗУП; synchronized 2026-08-29 06:30 +03:00
```

For each of the three successful routes, raw HTTP + parsed DOM assertions prove common shell/nav landmarks, exact heading sequence, exact breadcrumb list/links, paired unavailable-item labels, no mutation form/action and no inline style/script. CSS asset assertions prove byte-stable GET/HEAD, local-only stylesheets and the exact responsive/focus declarations from section 4.

The hostile fixture belongs only to the prepare candidate list: engineer user `32` has DB-derived full name literal `<script>не имя</script>`. The successful prepare DOM contains exactly one eligible engineer label whose `textContent` contains that exact literal, contains no `script` element, and whose serialized HTML contains escaped text (`&lt;script&gt;не имя&lt;/script&gt;`, accepting an HTML parser's semantically equivalent entity serialization). User `32` is not silently dropped or substituted. Queue/card continue to prove escaping for every DB-derived value under inherited contracts but do not need to expose this engineer.

Responsive visual acceptance remains P0, but it is mandatory delivery/manual smoke evidence rather than Gate 2 browser automation. Before Gate 5 can be `APPROVED`, the implementation author records inspection of real served pages at `1440×900`, `768×1024`, `320×568`, and 200% browser text zoom in the Gate 5 evidence handed to the independent reviewer. Evidence covers exact section order, no viewport horizontal overflow/clipping, visible keyboard focus, readable long Cyrillic/hostile-literal text, and keyboard reachability of every link/control. Any failure returns the implementation to Gate 4. No Playwright/browser dependency, screenshot framework or other harness improvement is introduced by this slice.

Empty queue independently returns `200`, the exact section 5 empty state and no table/item link. Broken list DB independently returns predecessor exact plaintext `503` with `Retry-After: 60`, never empty/product HTML. A prepare view with no eligible installers returns its approved empty reason and no candidate controls.

## 9. Zero mutation and rejected cases

Before/after fingerprints are byte-equivalent for all `fm2_*`, selected/nonselected legacy rows, auto-increment/catalog state, artifact storage and `../shlz-ui`. Repeated/concurrent GET/HEAD produce the same committed representation; no cookie/session/read marker/event/file is created.

Rejected route, method, Host, identity, authorization, missing object, predecessor CSS, DB and integrity cases retain exact predecessor status/body/header priority. They are inherited regression obligations, not newly duplicated Gate 2 examples: Gate 3 evidence must run the focused new RED plus the existing approved HTTP auth/list/card/prepare tests (or the complete existing sequential suite) and record their green/red state as applicable. The new asset alone adds focused exact cases: `GET|HEAD /pilot/assets/pilot.css`; trailing slash/path suffix/parameter gives inherited `404` before identity/config/DB reads; any non-GET/HEAD method gives inherited `405` with `Allow: GET, HEAD` before identity/config/DB reads; missing configured asset gives inherited redacted `503` with `Retry-After: 60`. No error response includes object values, actor, SQL/schema/path, exception or internal reason.

## 10. Out of scope

- POST/command handling, CSRF/session, validation submit and PRG notifications;
- selecting/saving people, preparing/downloading/registering/opening an order;
- new domain facts, task/SLA inference, fake progress or reads beyond the bounded approved card projection joined for section 5;
- search/filter/pagination and behavior JavaScript;
- `shlz-ui` edits, copied components/tokens or local imitation of its component families;
- refactoring InstallationProcess, legacy Bitrix history, harness, CI or deployment.

## 11. Gate 2 boundary

Gate 2 writes the smallest RED test set proving acceptance IDs A–E through the existing real raw HTTP, parsed HTML DOM, served CSS bytes/declarations and source-manifest seam. Expected text, URLs, order, structure, fixture states and CSS contracts come only from this version. Gate 2 does not execute a browser or claim pixel/layout proof; section 8 assigns that mandatory evidence to delivery/manual smoke before Gate 5. It may use separated focused assertions for shell architecture and each page, but they form one reviewed vertical UI slice. Tests do not invoke private view methods, inspect SQL as their assertion seam, add browser/harness infrastructure or weaken predecessor failure/security assertions.

## 12. Gate 1 approval

- Product owner: project user
- Approved by: separately tasked Gate 1 revision agent `/root/ui_spec`
- Date: `2026-08-29`
- Decision: `APPROVED`
- Comment: пользователь прямо поручил автономно довести пилот до цельного продуктового интерфейса, выделить поддерживаемый UI-layer, использовать публичный `shlz-ui` и не улучшать harness. Version `0.2` сохраняет P0 responsive intent, но делает Gate 2 полностью наблюдаемым через существующий raw HTTP/HTML/CSS seam; реальную viewport/zoom проверку фиксирует как обязательный manual delivery smoke до Gate 5. Также однозначно закреплены hostile escaping, heading/breadcrumb/unavailable-label DOM и способ наследования rejected-case regression evidence.

Gate 2 разрешён только для version `0.2` и должен быть написан новым отдельно поставленным агентом. Gate 3 и Gate 5 требуют собственных новых независимых reviewers.
