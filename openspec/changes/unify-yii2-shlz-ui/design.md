## Context

См. `proposal.md` — Why и delta spec `ui/yii2-shlz-visual-contract`. Production runtime уже собирает закреплённый `shlz-ui` до локального `pilot.css`; менять цепочку сборки не требуется. Несогласованность находится у потребителей: разметка, общие PHP-композиции, локальные JS controllers и несколько поколений CSS.

Аудит выполнен для main `bced877aec8a8802e97037749ca4251d3098df1a`, но текущий checkout — грязная ветка №157 с конфликтом. Перед Gate 1/2 реализация обязана переснять diff от актуального `main`, поднять разрешённый Yii runtime и подтвердить, какие V01–V09 ещё воспроизводятся. Неподтверждённые визуальные последствия остаются рисками, а не фактами.

Первый Gate 3 вернул `CHANGES_REQUESTED`: isolated login seam доказал V09, но существующие authenticated installers/users/preopening/ОТиЗ journeys на exact main не достигли новых assertions из-за ранних 503/RED. Владелец разрешил включить восстановление этого baseline в change.

Владелец frontend-композиции — `app/YiiRuntime`: Views/ViewSupport формируют HTML, Assets владеют прикладной геометрией и поведением поверх публичных primitive contracts. `shlz-ui` остаётся read-only dependency через публичные exports. Persistence owner и все application/domain seams остаются прежними. `rapid-pilot` не является target и не получает новую логику.

## Goals / Non-Goals

**Goals:**

- Создать небольшой слой повторяемых page/data-list/field/overlay/feedback композиций без универсального grid framework.
- Дать общему shell и компонентным композициям однозначное владение геометрией, focus и responsive policy.
- Мигрировать страницы волнами на один договор, удаляя заменённые локальные правила в той же волне.
- Сделать сохранность поведения и адаптивность проверяемыми через существующие PHP/HTTP/browser seams и просмотренные изображения.

**Non-Goals:**

- Не менять доменную модель, persistence, authorization, маршруты, formulas, payloads, offline protocol или историю.
- Не обновлять `shlz-ui`, не копировать его внутренности и не добавлять Vue/SPA, grid/chart dependency или второй browser harness.
- Не выравнивать разные предметные экраны до одной раскладки: object card, calendar, checklist и финансовые таблицы сохраняют оправданную структуру.
- Не редизайнить retained `rapid-pilot`, не выполнять deployment, imports, финансовые команды или merge.

## Decisions

### 1. Публичные примитивы shlz-ui остаются единственным primitive layer

Общий слой приложения композирует публичные button, field, select, table, status, tabs, modal/drawer contracts и токены закреплённой версии. Он не переопределяет глобально внутреннюю геометрию библиотеки. Если baseline выявит дефект самого pin, его воспроизводят минимально отдельно и выносят в отдельное изменение.

Альтернатива — продолжать локальные overrides или копировать компоненты — отклонена, потому что сохраняет нескольких владельцев одного поведения.

### 2. Shared compositions остаются маленькими и семантическими

Повторяемая статическая разметка оформляется небольшими PHP partials/helpers рядом с Yii views. API различает полный Field и голый Control по имени и типу результата; запрещён неоднозначный helper, который можно повторно обернуть. Data list helper/partial отвечает за wrapper и class contract, но получает предметные headers/cells/actions от страницы. Page/shell variants задаются явными модификаторами.

Альтернатива — универсальный schema-driven renderer — отклонена: она скроет предметные различия, усложнит доступность и создаст новую внутреннюю UI-платформу.

### 3. Один владелец overlays и focus lifecycle

Общеупотребительные подтверждения и selection/edit dialogs используют native dialog либо публичный shlz modal/drawer механизм с единым controller для open, initial focus, trap/inert background, Escape/cancel, close и focus return. Предметный JS передаёт контекст и выполняет существующую команду, но не реализует второй focus lifecycle. No-JS forms остаются рядом как отдельный разрешённый путь.

Альтернатива — исправлять ARIA и focus отдельно в каждом asset — отклонена из-за расхождения поведения и повторных обработчиков.

### 4. Responsive policy выбирается по типу данных

Плотные и финансовые таблицы получают локальную горизонтальную прокрутку и сохраняют полные values/actions. Простые каталоги могут использовать документированный card variant. Длинные идентификаторы, адреса, даты, числа и statuses имеют разные wrap policies. Layout реагирует на доступную ширину workspace; shell/sidebar остаются владельцами внешней сетки.

Альтернатива — единое `white-space: nowrap`, ellipsis или принудительное «без скролла на desktop» — отклонена как потеря данных.

### 5. Миграция идёт одной последовательной foundation-first волной

Один executor сначала фиксирует shell и shared compositions, затем переносит реестры, overlays/forms и оставшиеся поверхности небольшими логическими коммитами. До стабилизации `pilot.css` и `ViewSupport.php` параллельные writers не допускаются. Непересекающиеся страницы можно делить только после зафиксированного общего контракта.

Альтернатива — независимые редизайны страниц — отклонена, потому что именно она создала текущий drift.

### 6. Проверки сочетают структурные контракты и один bounded visual sweep

Root сначала пишет executable spec и focused RED tests для V02/V03/V04/V06/V09 и preservation invariants. Existing browser runner получает shared assertions; representative pages проверяются глубоко, полный inventory — на общие invariants. Основная baseline/final matrix повторяет реальные owner targets: laptop 1366×768 и 1536×864; Redmi Pad 2 Pro landscape 1280×800 и portrait 800×1280; mobile 360×800 и 390×844 CSS px. Для каждого профиля проверяются все 25 views, доступные overlay/open states, open/collapsed navigation, фактическая workspace/container width, long Russian content, touch и local-scroll ownership. 320, breakpoint boundaries, 1920 и 200% zoom/reflow остаются дополнительными stress cases, но не заменяют target matrix. WebKit заявляется только если реально доступен.

Audit выполняется одним batched before-pass с машинным manifest: route, viewport, HTTP status, JS/page errors, document overflow, offscreen interactive controls, intersecting visible text/control rectangles, table-local overflow и screenshot path. Root вручную просматривает полный контактный лист и формирует один consolidated defect inventory. Separate executor исправляет inventory одним пакетом; после него выполняется не более одного batched confirmation pass на той же fixture/environment.

После UI-изменений один раз запускается Impeccable detector по изменённым targets. Architecture impact ожидается только в существующих Yii boundary checks; любые изменения `app/PilotHttp/*.php` вне scope и потребовали бы отдельной квалификации.

### 7. Baseline runtime восстанавливается до нового Gate 2

До расширения browser matrix root строит минимальный deterministic HTTP loop для первого 503, затем ранжирует и проверяет причины. Отдельный executor исправляет только доказанную общую runtime/fixture boundary; route permissions, production failure semantics и доменные owners не меняются. Существующие journeys должны снова достигать предметных assertions, после чего root пишет независимые V03/V04/V06 RED tests. Baseline correction и visual implementation сохраняются отдельными логическими коммитами и авторством.

Альтернатива — считать ранний 503 новым UI RED или обходить его mocked HTML — отклонена: это не публичный seam и создаёт ложное Gate 3 evidence.

## Risks / Trade-offs

- [Широкий diff усложняет review и bisect] → foundation-first последовательность, малые внутренние коммиты, page inventory и единый final exact-source candidate.
- [Удаление старого CSS проявит скрытую зависимость] → before screenshots, computed-style probes на representative pages и удаление правил одновременно с миграцией их последнего consumer.
- [Browser baseline примет текущий дефект] → RED assertions формулируются до обновления images; snapshots принимаются только после человеческого просмотра.
- [Общий helper потеряет предметную семантику] → helper владеет только оболочкой/contract classes; labels, cells, actions и messages остаются у страницы.
- [Modal refactor изменит финансовое или offline действие] → JS controller отделён от существующей формы/command seam; cancel/no-JS/idempotency/history проверяются отдельно.
- [Аудит устареет относительно main] → первым deliverable становится source reconciliation; исчезнувшие findings отмечаются resolved-by-predecessor, новые расхождения не расширяют scope без обновления artifacts.
- [Текущий грязный checkout загрязнит candidate] → работа начинается в отдельном чистом worktree/branch от актуального main после завершения или сохранения №157; proposal files переносятся явно.
- [Baseline fix незаметно ослабит authorization/error handling] → минимальный authenticated GET repro, permission-denied control case и отдельный regression test проверяются до и после correction.

## Migration Plan

1. Сохранить текущий WIP №157 без reset/checkout, получить чистый worktree от актуального `main`, сверить audit SHA и #197.
2. Диагностировать и минимально восстановить baseline authenticated fixtures/routes; подтвердить существующими journeys и отдельным regression test.
3. Зафиксировать executable spec, page inventory, baseline screenshots и полное scenario-level Gate 2 RED evidence; пройти повторный planner-selected Gate 3.
4. Реализовать shell/shared compositions и точечные V02/V03/V04 regressions одним executor.
5. Мигрировать реестры, затем overlays/forms, затем остальные поверхности; после каждой волны запускать bounded focused checks и удалять orphaned rules.
6. Выполнить один bounded desktop/mobile visual sweep, один пакет исправлений, не более одного подтверждающего sweep, detector и architecture/focused checks.
7. Подготовить exact source через delivery harness, получить независимый финальный review и один GitHub CI run полного выбранного matrix.
8. Rollback выполняется откатом логических коммитов в обратном порядке; изменений данных или schema нет. При частичном rollback общий helper и его consumers откатываются вместе, чтобы не оставлять смешанный contract.
