## Context

Существующий Yii2 queue уже владеет server-side ownership/query/completed filtering, pagination, local offline sync indicator, shipment projection, Bitrix document status и inspection planning. Проблема находится в presentation: address/checklist link оборачивает основную identity, inspection commands рендерятся текстовыми кнопками внутри той же ячейки, а мобильная карточка резервирует место под старые status/chevron cells. См. `proposal.md` и capability spec.

## Goals / Non-Goals

**Goals:**

- один read-only queue projection с нужными identity и action targets;
- компактная mobile composition на shipped `shlz-ui`;
- независимые, доступные actions без nested interactive controls;
- сохранение существующих command/read semantics и offline indicator.

**Non-Goals:**

- изменение inspection-planning, Bitrix refresh или checklist application seams;
- schema/migration, persistence, authorization или изменение permission-aware состава navigation; responsive composition shell входит в scope;
- перенос логики в `rapid-pilot` либо изменение shared `shlz-ui` package.

## Decisions

### 1. Yii2 view остаётся owner presentation

`app/YiiRuntime/Views/construction-control.php` рендерит semantic HTML из существующей queue projection. `app/YiiRuntime/Assets/pilot.css` получает локальные `.fm2-control-*` rules; shared `shlz.css` не меняется. Альтернатива — новый client renderer — добавила бы дублирующую state model и ухудшила no-JS/accessibility behavior.

### 2. Projection явно публикует заводской номер и document target

Read owner уже выбирает `m.zavnumber` как `order_number` для Bitrix exact mapping. Он дополнительно возвращает presentation field `factoryNumber`, а server query включает exact effective order number. `technicalDocument` уже содержит проверенный URL; view не строит и не угадывает его. Persistence owner и SQL schema не меняются.

### 3. Three-action composition

Document action является `<a>` только при `available`; иначе это disabled `<button>`. Calendar остаётся `<button>` с текущими `data-inspection-*`. Checklist является отдельным `<a>` в правой rail-area; identity становится неинтерактивным block. Это исключает nested actions и сохраняет service-worker checklist discovery через существующий `a[href*="/checklist"]`.

### 4. Existing plan uses one calendar entry point

`inspection-schedule.js` продолжает владеть dialog configuration. Для planned row view рендерит один reschedule trigger; dialog получает отдельную cancel control, которая переключает тот же form на `cancel`, сохраняет plan id/version/request identity handling и отправляет explicit user action. Альтернатива с двумя row buttons возвращает визуальный шум и увеличивает высоту записи.

### 5. Responsive composition

Desktop сохраняет table semantics. До 680 px row становится двухколоночной card: content и 44px checklist rail. Address/identity занимают верх, status/activity и document/calendar actions — нижнюю строку. Toolbar остаётся белой surface; heading/description/count скрываются. Контентные padding 14–16px сохраняют расстояние от краёв, длинный адрес переносится без clamp.

### 6. Existing indicators and shlz assets

Local sync использует текущий `.fm2-local-sync` и JS state mapping. Shipment marker использует `delivery-box.svg`; actions — `folder-file-open.svg`, `calendar-interface.svg`, `chevron-right-duo.svg`. Внешние asset/runtime dependencies не добавляются. Press feedback ограничен transform 120ms и отключается reduced-motion.

### 7. Verification and architecture impact

Root-authored focused PHP/DOM assertions фиксируют projection, semantic actions and canonical copy. Browser matrix на generated real Yii HTML проверяет 320/390/desktop, overflow, filters, long address, focus/actions, dialog create/reschedule/cancel and local sync. Existing inspection, server filtering, shipment, Bitrix and active queue focused suites остаются regression consumers. Architecture ownership не меняется; `rapid-pilot` и `app/PilotHttp` не затрагиваются.

## Risks / Trade-offs

- [Three actions plus shipment crowd 320px] → checklist rail owns fixed 44px; remaining actions live only in lower flex row and address uses full content width above.
- [Document contract formerly prohibited a button] → canonical spec is explicitly superseded for queue presentation while exact mapping and safe URL rules remain.
- [Cancel wiring could weaken command identity] → reuse the existing form, generated request identity and hidden plan/version fields; focused HTTP/browser tests retain rejection/replay behavior.
- [Hiding mobile heading removes context] → active main navigation and filter surface retain page context; desktop keeps full heading/description.
- [Long real addresses unknown locally] → verify multiple long Cyrillic fixture addresses at 320px and preserve unbounded wrapping rather than truncation.

## Migration Plan

1. Deploy additive presentation/read changes with no DDL or backfill.
2. Existing URLs, permissions and persisted facts remain compatible.
3. Rollback restores prior view/CSS/JS; no data rollback is required.
