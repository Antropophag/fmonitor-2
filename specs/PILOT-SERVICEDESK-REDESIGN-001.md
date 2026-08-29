# PILOT-SERVICEDESK-REDESIGN-001 — продуктовый пилот FMonitor в визуальном языке Service Desk

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-29`
- Режим: `Operate`
- Актор: сотрудник ФКР, прошедший inherited trusted-server authentication и exact capability checks
- Публичный seam: существующие pilot HTTP routes, успешный HTML DOM и configured `/pilot/assets/{shlz,pilot}.css`
- Predecessor contracts: все approved `PILOT-*`, особенно `PILOT-E2E-FLOW-001 v0.4`, `PILOT-UI-SHELL-001 v0.4`, `PILOT-SHLZ-ASSETS-001 v0.2`, плюс их domain/security predecessors

## 1. Job, outcome и неизменяемая граница

Сотрудник ФКР должен за один цельный рабочий сеанс пройти уже реализованный путь:

```text
очередь объектов
→ карточка объекта 4512
→ выбрать монтажника 1042 и подтвердить инженера 73
→ сформировать и скачать распоряжение с приложением
→ вручную внести номер 12-Р из 1С ДО
→ указать фактическую дату и открыть работы
→ увидеть состояние «В работе» и следующий шаг инженера
```

Успех среза — этот путь воспринимается как один готовый продукт АО «ЩЛЗ»: пользователь с первого viewport понимает, где находится, каково состояние объекта и какое одно действие требуется сейчас; на desktop работает в плотной очереди, на узком экране получает целенаправленные строки-карточки без потери фактов.

Срез заменяет визуальную композицию configured production pilot и реорганизует существующий поддерживаемый PHP view layer. Он **не меняет** route grammar, HTTP methods, normalized view-model facts, InstallationProcess commands/state, authorization, CSRF/PRG, artifacts, persistence, audit, queue membership/order, exact security failures или business eligibility. Новые поиск, фильтрация, сортировка, pagination, tabs, drawers и JavaScript не вводятся. История Bitrix, CI, harness и архитектурный рефакторинг вне нужного view/CSS seam исключены.

## 2. Визуальная власть и provenance

Порядок источников обязателен:

1. Figma-derived local exports — первичная геометрическая/визуальная власть: `../shlz-ui/shlz-design-source/raw/svg/Обращения.png` и соседние SVG в `../shlz-ui/shlz-design-source/raw/svg/`.
2. Реальный Service Desk source — власть над shell и screen composition, но не runtime-зависимость:
   - `src/layouts/main-layout.tsx:15-24` и `src/layouts/styles.module.scss:1-3` — sider/header/content и content margins `0 32px 24px`;
   - `src/widgets/navbar/Navbar.tsx:43,133-159` — collapsed navigation, `260px` expanded rail, logo region padding `32px 20px 32px 24px`, dark menu;
   - `src/widgets/header/header.tsx:24-37` и `styles.module.scss:1-4` — `96px` header rhythm and `0 32px` padding;
   - `src/shared/config.ts:10-164` — Golos, `#0B1623` navigation/text, `#253D98` primary, `#F4F6F9` page, `#FFFFFF` surfaces, 8px surface radius, 24px control radius, 40/48px controls;
   - `src/pages/applicationsList/ApplicationsList.tsx:20-48` — page shortcuts plus one white table workspace with filters and rows;
   - `src/entities/application/ui/applications-table.tsx:170-269,373-399` — dense semantic columns, linked identity, textual status and pagination region;
   - `src/pages/application/Application.tsx:73-138,192-201` — task-oriented detail sections and two-column desktop composition; the observed secondary rail is approximately `425px`.
3. Публичный `shlz-ui` export and docs — единственная власть над reusable runtime component markup/classes. Используются только существующие documented classes from `packages/styles/components/*.css`: `shlz-button[--primary|--sm]`, `shlz-link`, `shlz-field`, `shlz-input`, `shlz-choice`, `shlz-checkbox`, `shlz-radio`, `shlz-status[--neutral|--source-blue|--orange|--green|--bright-green]`, `shlz-table-wrap`, `shlz-table`, `shlz-table__head|row|cell|cell-content`, `shlz-document-list`, `shlz-document-row*`, `shlz-person-tag`, `shlz-tag`, `shlz-empty-state*`, `shlz-notification*`. Documentation anchors are `../shlz-ui/docs/components/{button,input,table,status,document-row,person-tag,empty-state,notification}.md` and `docs/typography-profiles.md`.

FMonitor owns only `.fm2-*` shell, grid, grouping, responsive and domain composition. Он не копирует Ant Design markup/CSS/tokens, не подключает `antd`, React, Service Desk bundle or source, private Showcase CSS, CDN/font/network dependency and does not locally imitate an existing `shlz-*` primitive. `pilot.css` may consume exported custom properties with documented fallbacks, but may not override `.shlz-*`, use `!important`, raw duplicated component families or inline style/script.

Композиционная теза: **тёмная служебная рамка, спокойный серый рабочий фон, одна плотная белая рабочая поверхность и контекстная правая колонка следующего действия**. Brand is expressed by structure, Golos profile and restrained blue, not decorative cards, gradients or oversized marketing headings.

## 3. Shared Service Desk shell — `...-A`

Каждая configured successful page keeps inherited document, stylesheet order, skip link, landmarks, title and one `h1`, and renders:

- desktop `>=1024`: fixed-in-flow dark left rail `80px` wide (not overlay), top header `96px`, fluid content with `32px` side gutters and `24px` bottom gutter;
- rail contains product mark/short name and icon+accessible-text navigation; active item has visible high-contrast selected surface. Only real current pilot destinations are links. Unavailable future areas remain noninteractive and visually secondary with inherited `aria-disabled` semantics;
- header contains route title/context at left and actor identity `Сидоров Сергей Сергеевич` at right. Actor is text, not a fake dropdown. No fake notification badge, hamburger, icon-only mystery action or role switcher;
- `1024..768`: rail remains compact where content keeps at least `minmax(0,1fr)`; header and content gutters become `24px`;
- `<768`: rail becomes a compact top product bar followed by a native wrapping navigation row; header height is content-driven, gutters `16px`; unavailable navigation is hidden; no off-canvas control is invented;
- product brand, navigation, breadcrumb and status are not headings. DOM heading levels never skip.

The old hero-like `clamp(2rem,4vw,3.5rem)`, scattered bordered cards and repeated product identity are removed. Route `h1` is dense Service Desk scale: computed desktop size `32px`, line-height approximately `1.18`; section headings use a smaller consistent hierarchy. Exact numeric styling is verified against served CSS/computed style, not inferred from screenshots.

## 4. Queue workspace — `...-B`

`GET /pilot/objects` retains exact inherited object membership/order and all approved facts, but its configured DOM becomes a single white workspace:

1. compact page heading `Объекты монтажа` and inherited explanatory text;
2. noninteractive result summary only if already derivable from the rendered collection (for the fixed fixture: `3 объекта`); no false search/filter controls;
3. desktop semantic `.shlz-table-wrap > table.shlz-table` with one `thead`, one `tbody`, one row per object and columns in this order:
   - `Объект`: sole canonical link with visible `4512`, plus registration `77-000123`;
   - `Адрес`: `Москва, ул. Примерная, д. 10`, `Подъезд 2`;
   - `Плановые сроки`: humanized `5 окт. — …` while an accessible/full text or `<time datetime>` preserves exact ISO meaning;
   - `Состояние`: humanized textual process status in `shlz-status`;
   - `Следующий шаг`: truthful current projection and one affordance `Открыть карточку` sharing the same canonical URL only where predecessor composition already exposed it.

The complete journey successor allows configured queue to show already available E2E projection state/next-step, superseding only the old `PILOT-UI-SHELL-001-B` prohibition. It does not add a query, join or inferred state: values must be passed by the already-approved full-flow composition. If a predecessor/compatibility composition supplies only the narrow list projection, the renderer must not invent status/next-step; those columns use honest neutral absence text. Compatibility DOM is preserved only when the explicit predecessor composition/configuration requires it.

At `<=767`, the `table` is replaced in the server-rendered configured composition by one semantic list (`ul`); duplicate desktop/mobile copies must not both be accessibility-visible. Each purposeful row order is `ID + status → registration → address/entrance → dates → next step/link`. At `390` and `320`, every fact wraps without horizontal table scrolling. There is no whole-row click, hover-only action, fixed row height or clipped ellipsis hiding required facts.

Empty data uses `.shlz-empty-state.shlz-empty-state--simple` with inherited exact title/description and refresh link. Infrastructure errors never render empty state. Queue page has no breadcrumb.

## 5. Object detail information architecture — `...-C`

`GET /pilot/objects/4512` is one stable IA across all four states. DOM and visible order:

1. inherited breadcrumb;
2. compact identity header: `Объект монтажа № 4512`, registration, address/entrance, one textual status;
3. desktop grid `minmax(0,1fr) minmax(320px,425px)`:
   - main: `Об объекте`, `Команда`, `Документы`, `История`;
   - right: `Следующий шаг`, containing the only primary mutation for the current state and its short reason/constraint;
4. narrow: rail becomes an ordinary section immediately after identity and before details; it is never sticky/fixed and retains DOM reading/tab order.

State-specific rail and content are exact:

| State | Status text | Primary next action | Supporting content |
|---|---|---|---|
| no order | `Требуется распоряжение` | link `Сформировать распоряжение` | no document rows; team explains it is not yet confirmed |
| prepared v1 | `Распоряжение подготовлено` | form field `Номер распоряжения в 1С ДО`, primary `Сохранить номер 1С ДО` | immutable date/version/team plus two download rows |
| registered v1 | `Готов к открытию` / order `Зарегистрировано в 1С ДО` | date field and primary `Открыть работы` | visible `12-Р`, date constraint and two download rows |
| working | `В работе` | no FKR mutation; text `Инженеру строительного контроля: провести первую инспекцию объекта.` | `Ответственный: Анна Волкова`, actual start, audit and `Чек-лист: Доступен` |

An unavailable action is absent, never a disabled-looking fake button. Permissions retain truthful state and explain that the current user cannot perform the action without exposing capability codes.

`Документы` uses `.shlz-document-list` and exactly two `.shlz-document-row` items for current order artifacts: human titles `Распоряжение` and `Приложение`, visible order metadata/version, native download links with inherited exact URLs. Raw filenames, MIME, byte size, SHA, `assignment_order_prepared`, `manual`, actor IDs, user IDs, installer status enum and ISO timestamps are not primary UI labels.

`Команда` shows `Иванов Иван Иванович` and `Анна Волкова` using `.shlz-person-tag` or plain semantic list with human roles and snapshot positions. Tab number `1042` may appear as secondary `Табельный № 1042`; user `73` is never displayed as raw ID.

`История` is a quiet chronological list (newest first per inherited projection) with localized events:

- `Распоряжение сформировано`;
- `Номер 12-Р внесён из 1С ДО`;
- `Работы открыты`.

Each item uses Russian human-readable date/time (`28 авг. 2026, 12:45`) and actor display name (`Сидоров Сергей Сергеевич`) while `<time datetime>` retains machine value. Raw event types such as `assignment_order_prepared`, ISO strings like `2026-08-28T12:45:00+03:00`, and bare actor ID must not occur in visible text.

## 6. Prepare selection screen — `...-D`

`GET /pilot/objects/4512/assignment-order/prepare` preserves exact POST form/hidden values and candidate eligibility. It uses the shared shell and a focused form surface:

- inherited breadcrumb and `h1` `Состав распоряжения`;
- compact immutable object summary (4512, registration, address, period), not a second oversized card;
- two numbered fieldsets with real legends: `1. Монтажники`, then `2. Инженер строительного контроля`;
- each candidate is one dense selection row, not a card. Native input composes documented `.shlz-choice` plus `.shlz-checkbox`/`.shlz-radio`; label is the human name, metadata is position, tab number/employment or engineer role;
- installer `1042 / Иванов Иван Иванович` is selectable; engineer `73 / Анна Волкова` is prefilled but the separate confirmation checkbox starts unchecked;
- provenance is localized `Кадровые данные: 1С ЗУП через Bitrix24 · обновлены 27 авг. 2026, 18:15`, never `one_c_zup_via_bitrix` or raw ISO;
- footer actions: neutral `Вернуться к объекту монтажа` and one `.shlz-button.shlz-button--primary` `Сформировать распоряжение`. At narrow widths they stack, primary remains last in DOM/tab order and full-width only when necessary.

Field labels are not placeholder-only. Checkbox/radio target is at least `40×40 CSS px` including label hit area. Validation summary appears before the first invalid group as `.shlz-notification.shlz-notification--danger` or semantically equivalent application wrapper, has `role=status` as inherited, exact human correction text and anchor to the field; invalid controls keep `aria-describedby`/`aria-invalid` when predecessor provides them. User selections survive PRG exactly as inherited.

## 7. Registration/open forms, success and concurrency — `...-E`

Prepared and registered actions live only in the next-action rail and use documented `.shlz-field`, `.shlz-input` and primary button markup. Hidden security fields remain hidden. The registration label is `Номер распоряжения в 1С ДО`; the open label is `Фактическая дата начала работ`. Helper/error text is directly associated and never conveyed only by color.

Validation and stale-concurrency copy remain inherited exact Russian text. On PRG success, the resulting current state is the primary feedback: no redundant permanent success banner is required. If a flash exists, it is placed before rail/content, receives polite live semantics and visible focus-safe text. It does not disappear automatically.

Generic `400/401/403/404/405/409/503` remain inherited exact plaintext outcomes; no product shell, object facts or technical diagnostics are added. Permission failure on a successful read renders the readable current state but no unauthorized form/link, exactly where predecessor authorization permits that read. Infrastructure failure is never converted into UI empty/error card.

## 8. Responsive, zoom/text and accessibility — `...-F`

Required visual matrix for queue, prepare and every detail state:

| Viewport | Expected topology |
|---|---|
| `1440×900` | 80px rail, 96px header, dense table; detail main + 320–425px rail |
| `1024×768` | compact rail/header; table remains usable; detail rail fits without overlap |
| `768×1024` | one-column detail, next action precedes facts; queue may use mobile rows if no safe table width |
| `390×844` | top product/nav, purposeful rows, stacked fields/actions |
| `320×568` | same order, no clipped labels or page overflow |
| `320×568` with root text `200%` | complete reflow, no horizontal scroll or lost control/text |

At every case:

- `html/body/main/workspace/form` scroll width does not exceed client width; no fixed/min width causes page overflow;
- no required text uses clipping, line clamp, `white-space:nowrap` or hidden overflow; long Cyrillic names and escaped hostile text remain whole;
- DOM and visual section order agree; non-ancestor visible peers do not overlap;
- sequential Tab reaches skip link, navigation, breadcrumb, object/action/download links and enabled form controls in DOM order; focused element is in viewport and has computed visible outline `>=1px`;
- Enter/Space preserve native link/button/choice behavior; no action depends on hover, drag, icon recognition or pointer precision;
- status meaning is textual; normal text/status/focus meets WCAG 2.2 AA contrast; touch targets are at least `40×40` (preferred 44);
- one `h1`, no skipped headings, real `table` semantics on desktop, real list on mobile, real fieldset/legend, correctly associated labels/errors, decorative icons empty/hidden;
- 200% means root text size changed while viewport/device scale stays fixed, not page zoom.

Motion is limited to `120–180ms` color/opacity/focus transitions and respects `prefers-reduced-motion`; no layout animation, scroll choreography or loading theater.

## 9. Exact automated raw HTTP/DOM/CSS oracle — `...-G`

Gate 2 writes one focused RED through the existing real HTTP process seam; no private renderer call, snapshot of implementation output, SQL-as-assertion seam, browser dependency or harness extension. Expected values come from this spec.

For configured production at fixed time/data, parsed DOM asserts:

1. shared shell landmark order, stylesheet order, local-only assets, no inline style/script, no Ant class/runtime string, no duplicate `id`, one `h1`, exact active navigation and accessible name for every control;
2. queue desktop table headers/order, canonical 4512 link, visible real facts, and an application-authored narrow semantic list contract selected by CSS/media or explicit nonduplicating markup; no raw enum/ISO labels;
3. no-order detail has only prepare action; prepared detail has only registration mutation and two exact artifact URLs; registered detail has only open mutation and `12-Р`; working detail has no three FKR mutation labels and contains exact engineer next step;
4. prepare POST action/field names/hidden fields, installer value `1042`, engineer value `73` checked, confirmation unchecked, localized provenance, exact primary/neutral actions and field semantics;
5. document rows, person presentation and localized history labels; visible DOM text contains none of `assignment_order_prepared`, `assignment_order_registered`, `installation_opened`, `one_c_zup_via_bitrix`, bare ` · 18`, ` · 73` or ISO timestamp regex `\d{4}-\d\d-\d\dT`;
6. validation summary anchors/ARIA, selected-value retention, empty queue/catalog states, read-only permissions, hostile escaped names and inherited exact plaintext error responses;
7. zero mutation fingerprints for `fm2_*`, legacy fixtures, artifacts and `../shlz-ui` across GET/HEAD and rejected requests.

Served `pilot.css` raw/parsed assertions prove:

- desktop shell dimensions `80px` rail and `96px` header, content gutters `32px`, detail rail bounded `320px..425px`;
- breakpoints covering `1024/768/767`, base `min-width:0/max-width:100%/overflow-wrap`, narrow one-column detail and queue rows, no fixed-width narrow owner;
- visible `:focus-visible`, `prefers-reduced-motion`, no `.shlz-*` selector ownership, `!important`, remote URL/import, Ant selector, gradient or old hero font rule;
- explicit narrow strategy does not expose simultaneous duplicate desktop/mobile content to accessibility APIs.

Existing full suite is regression obligation. A single test may group close assertions as this one cohesive visual vertical slice, but Gate 3 must reject assertions derived from production HTML/CSS or implementation selectors absent from this spec.

## 10. Mandatory exact-HEAD Chromium evidence — `...-H`

Before Gate 5 approval the implementation author records real Chromium evidence at the exact implementation Git HEAD (dirty worktree evidence is invalid):

- browser product/version, Git SHA, launch command/configuration, fixed fixture clock and actor;
- screenshots for queue, prepare, no-order, prepared, registered and working states at all six section-8 cases (representative state batching in one full-page screenshot is allowed only when every required route/state remains separately evidenced);
- request log proving all CSS/font/icon/document requests are local and successful; console log with zero error/warning attributable to the slice;
- per route/viewport JSON: client/scroll sizes of page and primary regions, bounding boxes and overlap results, computed font family/size/line-height for `h1/body`, shell/background/rail dimensions/colors, table/rail topology, focus outline, status colors/text, and root font size before/after 200%;
- keyboard trace showing exact focus order and in-viewport focused rect; screenshots with visible focus for queue link, document link, checkbox/radio and each primary action;
- provenance note mapping each sampled component to its public `shlz-*` class/docs and each application layout to the Service Desk/Figma anchors in section 2.

Evidence directory is outside the repository under `~/code/`, named with the exact Git SHA. Screenshots are evidence, not expected-value or automated-test sources. Any CSS/font/icon request error, console error, overflow, overlap, clipped text, absent focus, raw technical label, wrong state action or mismatch with source measurements returns Gate 4 to implementation. Gate 5 reviewer runs or independently samples the exact-head pages; author self-approval is prohibited.

## 11. Architecture, compatibility and zero mutation

Existing `PilotView`, `PilotShellView`, `ObjectListView`, `ObjectCardView`, `PrepareFormView` and `pilot.css` are the implementation boundary. Minimal additional small shared composition classes are allowed when they deepen the view layer (shell, field, document/history/person), but route/business orchestration must not regain HTML fragments. Views receive normalized data and perform no SQL, authorization, commands, filesystem writes, headers or process inference.

Configured production composition receives this redesign. Exact unconfigured compatibility composition remains byte/DOM-compatible only where an approved predecessor explicitly requires it. No generalized dual frontend, flag proliferation or fallback to old configured UI is introduced.

GET/HEAD and failed POST remain zero-domain-mutation as inherited. Successful POST performs only already-approved InstallationProcess mutations/artifacts/audit. UI rendering writes no cache, manifest, log, session beyond inherited CSRF/flash behavior, generated source, Service Desk tree or `../shlz-ui`.

## 12. Out of scope and explicit bans

- any domain/route/state/task/SLA/data-model change;
- mock data, invented people/statuses/counts, new filters/search/pagination;
- Ant Design/React runtime, copied Service Desk code or CSS, copied private Showcase markup;
- raw technical enums, ISO timestamps, IDs used as human labels, fake disabled actions;
- ubiquitous cards, giant headings, gradients, decorative dashboards, sticky mobile CTA, horizontal mobile table scroll;
- JS enhancement, modal/drawer/tabs, animation system, new browser harness;
- Bitrix history, CI, deployment, unrelated refactoring.

## 13. Gate 1 approval and delivery gates

- Product owner: project user
- Approved by: separately tasked Gate 1 specification agent `/root/sd_redesign_spec`
- Date: `2026-08-29`
- Decision: `APPROVED`
- Basis: пользователь прямо выбрал готовый Service Desk frontend and linked Figma as the design source, ordered immediate implementation, and required preservation of SSD/TDD and the complete existing pilot journey.

Gate 2 is authorized only for exact version `0.1` and must be authored by a fresh bounded-context test agent. Gate 3 test review and Gate 5 code review require two separately tasked fresh agents and recorded reviews under `reviews/tests/` and `reviews/code/`. Gate 4 may start only after Gate 3 `APPROVED`; it implements the smallest complete slice and captures exact-HEAD Chromium evidence. Main is not merged.
