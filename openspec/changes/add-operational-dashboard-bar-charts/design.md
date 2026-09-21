## Context

См. `proposal.md` — Why и `specs/ui/operational-dashboard-bar-charts/spec.md`. Согласованный экран расширяет ещё не интегрированный change `add-minimal-operational-dashboard` (ветка `codex/issue-21-minimal-dashboards`, последний известный commit `193fa1ea`), который вводит `/pilot/dashboard`, четыре числа, два списка внимания и bounded read model. Текущий active source относится к delivery tooling №157 и не содержит файлов predecessor; это явная последовательная зависимость, а не разрешение переносить код из соседней ветки в грязный checkout.

Публичный `shlz-ui` предоставляет семантические оболочки Dashboard и Chart Widget, но осознанно оставляет chart marks, оси, легенду, tooltip и доступную data alternative приложению. Поэтому новый срез расширяет существующий Yii consumer и не добавляет chart runtime. Все диаграммы read-only; persistence owner и state-changing seams не меняются.

## Goals / Non-Goals

**Goals:**

- Один глубокий dashboard read seam, возвращающий фиксированный DTO трёх диаграмм на общей дате среза.
- Одно каноническое определение отображаемого статуса, используемое реестром, диаграммой этапов и stage drill-down.
- Bounded SQL aggregation и server-side drill-down filters без materialization полного набора.
- Доступные column marks внутри публичных `shlz-chart-widget` slots и responsive layout 1440/390.
- Полная fail-closed ошибка среза и доказуемая read-only семантика.

**Non-Goals:**

- Новая таблица, materialized view, background refresh, analytics warehouse или browser-side data aggregation.
- Произвольный период, сохранённые настройки, экспорт, tooltip-only значения, real-time polling или animation choreography.
- Диаграмма загрузки по инженерам/монтажникам и раскрытие персональных разрезов.
- Изменение статусов, сроков, активности или иных предметных фактов через дашборд.
- Изменение `rapid-pilot/`: он остаётся read-only behavioral evidence и не получает новую логику.

## Decisions

### 1. Последовательный candidate поверх минимального дашборда

До Gate 2 root формирует новый чистый worktree/candidate от актуального интегрированного predecessor либо от явно согласованной exact-source композиции. `add-minimal-operational-dashboard` не копируется вручную в текущую ветку №157, а его контракт и реализация должны быть достижимы из base нового candidate.

Альтернатива — реализовать оба изменения заново одним diff — отклонена: она стирает независимую историю и review evidence predecessor. Альтернатива — редактировать нынешний dirty checkout — отклонена из-за смешения авторов, WIP и verification binding.

### 2. InstallationProcess владеет агрегированным read model

Owning module остаётся `app/InstallationProcess`: существующий dashboard read model расширяется фиксированными массивами `stages[6]`, `weeks[6]{start,end,starts,finishes}` и `activityAge[5]`. Контроллер передаёт одну `Europe/Moscow` cutoff date; view получает уже классифицированный DTO и не вычисляет доменные категории.

Read model зависит только от Yii DB connection, существующих canonical installation/order/application/completion/checklist/photo/deadline projections и legacy object dates. Он не зависит от `rapid-pilot`, ОТиЗ, браузерного JS или `shlz-ui`. Persistence owner не меняется; запросы ничего не записывают.

Альтернатива — получить все строки через объектную очередь и агрегировать PHP — отклонена из-за unbounded memory/N+page. Отдельная summary table отклонена как новый persistence owner без доказанной необходимости.

### 3. Общая каноническая SQL-классификация статуса

Существующее правило `MariaDbYiiObjectQueue`/projection извлекается или инкапсулируется так, чтобы queue count/filter, dashboard stages и stage drill-down использовали одну SQL predicate vocabulary. Шесть пользовательских статусов остаются взаимоисключающими, включая «Требуется изменение»; stage aggregate дополнительно проверяет сумму против total в focused tests.

Альтернатива — повторить CASE expression в dashboard class — отклонена: расхождение уже является главным риском predecessor. Альтернатива — агрегировать по `process_state` — неверна, потому что документарное завершение является проекцией фактов, а не отдельным persisted state.

### 4. Шесть недель — фиксированные пары столбцов

Границы вычисляются один раз чистым календарным helper относительно cutoff: Monday текущей недели и шесть пар inclusive `YYYY-MM-DD`. SQL conditional aggregation возвращает двенадцать чисел одним фиксированным запросом. Актуальный planned finish использует тот же владеющий deadline projection, который обслуживает карточку/реестр: последний валидный подтверждённый перенос, иначе исходный plan finish.

Drill-down принимает не произвольные даты клиента, а allowlisted `chart=planned-start|planned-finish` и `bucket=0..5`; сервер заново выводит границы из своей cutoff date. Это исключает широкий произвольный range API и гарантирует совпадение графика с реестром.

Альтернатива — передавать `from/to` из DOM — отклонена из-за tampering и риска расхождения. Rolling 42 days отклонён, потому что календарные недели легче планировать и однозначно подписывать.

### 5. Активность определяется только принятыми сервером доказательствами

Для активных канонических статусов read model выбирает максимум из: `server_received_at` checklist operations; server-owned timestamp актуальной, не отозванной фотографии; `recorded_at` корневых completion facts и их corrections. Device time не определяет давность. Факты после конца cutoff date не участвуют в историческом срезе. Отсутствующий максимум образует отдельную категорию, а не нулевую дату.

Чтобы запрос оставался bounded, источники предварительно агрегируются по case в derived subqueries/CTE и соединяются с canonical case set; PHP получает пять counts. Если production MariaDB/runtime frontier не гарантирует нужную CTE-форму, используются fixed derived subqueries с эквивалентным планом, без изменения контракта.

Альтернатива — только checklist operations — отклонена: документарное закрытие является подтверждённой активностью. `device_time` отклонён как недоверенный/офлайн timestamp. Assignment/order события не включаются: диаграмма измеряет ход открытых работ, а не административную подготовку.

### 6. Drill-down расширяет существующий query seam ограниченными bucket-параметрами

Объектный реестр получает mutually exclusive chart filters: `chart=stage&bucket=<canonical-status-key>`, `chart=planned-start|planned-finish&bucket=0..5`, `chart=activity-age&bucket=0..4`. Они проходят allowlist и разрешены только при обычном `objects.read`; поиск и page сохраняются, обычный status filter либо преобразуется в stage bucket, либо запрещается вместе с другим chart filter. UI отображает применённый человекочитаемый фильтр и полный total.

Контроллер/reader никогда не доверяет числу из диаграммы: filtered total пересчитывается сервером. URL не является исторически закреплённым snapshot; переход воспроизводит bucket на текущем серверном cutoff и явно показывает дату нового среза. В одном page request cutoff фиксирован.

Альтернатива — client-side скрытый список ID — отклонена по bounded/privacy требованиям. Ссылка на общий реестр без фильтра отклонена как ложный drill-down.

### 7. Приложение владеет marks, shlz-ui — оболочкой

View использует `shlz-dashboard`, `shlz-dashboard__section/grid`, `shlz-chart-widget` и semantic slots из закреплённого asset. Marks строятся семантическим списком ссылок с CSS custom property `--fm2-bar-value` и нормализацией относительно максимума ряда; нулевой ряд использует минимальный нулевой baseline без выдуманной высоты. Visible values и accessible names находятся в HTML; CSS/color не несут единственный смысл. JS, canvas и SVG chart runtime не требуются.

Первый widget занимает grid span full; два последующих располагаются рядом при достаточной ширине и складываются в один столбец. Горизонтальная прокрутка внутри шести недель допустима только как локально обозначенный chart viewport, если content-stress докажет невозможность читаемых двенадцати marks; страница целиком не прокручивается горизонтально. Предпочтение — responsive compact labels без локального scroll.

Альтернатива — сторонняя chart library — отклонена по scope и dependency cost. Самодельная карточка вместо `shlz-chart-widget` отклонена как скрытый fork дизайн-системы.

### 8. Ошибка атомарна для трёх диаграмм

Read model возвращает полный validated DTO либо infrastructure failure; controller/view не показывают частично успешные charts. Нулевые данные являются успешным состоянием. Internal errors остаются в существующем safe error/log seam без SQL/schema details в ответе.

### 9. Проверка по публичным seam

Root-authored acceptance tests фиксируют три seam до Gate 2:

1. Data seam: авторизованный dashboard read на explicit cutoff возвращает независимо рассчитанный DTO, bounded query/row envelope и не меняет DB fingerprint.
2. HTTP seam: `GET|HEAD /pilot/dashboard` и `/pilot/objects?chart=...&bucket=...` проверяют RBAC, valid/invalid filters, totals, links, error/empty states и predecessor content.
3. Browser seam: реальный keyboard activation, accessible names/focus, visible labels/values и отсутствие page overflow на 1440/390.

Schema frontier, backup/restore и deployment остаются unchanged, поскольку DDL/runtime dependency не добавляются; focused architecture/public-export checks доказывают это. Изменения в `app/PilotHttp` не планируются, поэтому специальная HTTP qualification команда для этой директории не применима. Verification planner после появления exact predecessor source окончательно выбирает lane и команды.

## Risks / Trade-offs

- [Predecessor ещё не интегрирован] → блокировать Gate 2 до чистого exact-source candidate; не считать старую ветку частью HEAD по умолчанию.
- [Статусы dashboard и queue расходятся] → один canonical classification owner плюс parity fixtures для каждого статуса и суммы total.
- [Актуальный перенос срока читается иначе в разных экранах] → использовать существующий deadline owner/projection и добавить fixture исходная дата → перенос.
- [MAX по нескольким activity tables дорог на 30k] → агрегировать каждый источник до case ID, проверить `EXPLAIN`/bounded query count на 30k fixture; новый индекс оформлять отдельным scope, если существующих недостаточно.
- [Исторический cutoff не полностью поддерживается текущими projections] → диаграмма является текущим срезом на серверную дату запроса; не обещать time travel. Факты после конца текущего cutoff исключаются детерминированно.
- [Двенадцать недельных столбцов тесны на mobile] → короткие date labels, семантическая текстовая сводка и структурная stacking/локальная chart overflow только при доказанной необходимости.
- [Clickable bars воспринимаются только как графика] → native links, visible values, focus-visible, accessible names и текстовая legend/data summary.
- [Полный fail-closed скрывает две здоровые диаграммы] → целостность одного среза важнее частичных достоверно выглядящих чисел; ошибка сообщает повторить позже.

## Migration Plan

1. Завершить/интегрировать `add-minimal-operational-dashboard` либо подготовить явно составной exact-source candidate в отдельном чистом worktree; обновить current delivery goal и harness binding для нового задания.
2. Root добавляет стабильную нормативную спецификацию `specs/YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001.md`, verification input и RED tests на согласованных seams.
3. Запустить `harness.py prepare`; planner выбирает lane/reviews. При Gate 3 передать полный RED candidate независимому reviewer.
4. Отдельный `gpt-5.6-sol / low` executor реализует вертикальные slices, используя prepared role package; root не пишет production code.
5. Выполнить bounded focused data/HTTP/browser/architecture checks, одну desktop/mobile visual pass, один `impeccable detect`, независимый Gate 5 и один exact-source GitHub CI run. Локальный полный `make test`/`make verify` не запускать.
6. Rollback удаляет presentation/read/filter delta и возвращает predecessor dashboard; предметные данные и схема не требуют отката.
