## Context

См. `proposal.md` и `specs/ui/operational-dashboard-refinement/spec.md`. Baseline — смерженный PR #217 на `origin/main` `6e6ccbdb`; локальный стенд содержит согласованный владельцем throwaway prototype и служит визуальным первичным источником, но не production-кандидатом. Текущая реализация использует публичные Dashboard/Chart Widget shells `shlz-ui`, application-owned marks и bounded read model в `app/InstallationProcess`.

Главный дефект высоты вызван inline custom property под CSP `style-src 'self'`. Навигационный дефект вызван смешением fill/stroke SVG с общим CSS и несколькими AssetBundle URL одного `pilot.css`. Новая risk-семантика заменяет activity-age и поэтому затрагивает data DTO, queue drill-down и presentation одним вертикальным срезом.

## Goals / Non-Goals

**Goals:**

- сохранить один bounded read owner в `app/InstallationProcess` и один server-side queue filter seam;
- воспроизвести подтверждённый владельцем UI без переноса throwaway-кода как доказательства корректности;
- гарантировать CSP-safe, accessible и responsive marks;
- использовать только публичные exports и tokens закреплённого `shlz-ui`;
- оставить все чтения без предметных writes.

**Non-Goals:**

- изменение process states, календарных фактов, KPI-смысла просроченного окончания или permissions;
- новая persistence/materialization/cache таблица, background refresh или chart runtime;
- произвольные клиентские диапазоны, сохранённые настройки и персональные разрезы;
- изменение `rapid-pilot`, design-system source или внешних зависимостей.

## Decisions

### 1. Risk aggregate заменяет activity-age в существующем DTO

`MariaDbYiiOperationalDashboard` возвращает фиксированный `startRisk[5]` вместо `activityAge[5]`. Границы `0..6` и `7..13` выводятся один раз из server cutoff; SQL применяет canonical stage predicates того же queue owner. DTO validator фиксирует порядок/shape/nonnegative values, но не требует искусственной суммы: категории намеренно являются управленческим срезом, а не полным partition total.

Альтернатива — оставить activity-age рядом — отклонена владельцем как неинформативная и раздувающая экран. Агрегация в PHP отклонена как unbounded.

### 2. Risk drill-down становится отдельным allowlisted chart filter

Object queue принимает только `chart=start-risk` плюс один из пяти bucket keys. Cutoff и date ranges пересчитываются сервером; `from/to` запрещены. Search/page продолжают компоноваться, conflicting status/chart закрывается `400`. Это сохраняет точность ссылки и не сериализует ID в DOM.

### 3. SVG geometry заменяет inline CSS variable

Server renderer вычисляет normalized integer height и задаёт обычные SVG `rect y/height` attributes. Внешняя mark frame владеет доступной шириной grid-ячейки; SVG растягивается внутри неё и не участвует в min-content расчёте. Нулевой baseline остаётся 4 units. JS/canvas/inline style не нужны.

Альтернатива — 113 CSS height classes — отклонена как шумная; typed `attr()` — как недостаточно надёжная для поддерживаемого браузерного контура; ослабление CSP запрещено.

### 4. Цвета являются отображением существующих semantic families

Stage tones повторяют status mapping реестра. Weekly start/finish используют bright-blue/orange families календаря. Risk применяет error/orange/yellow/source-blue/bright-green progression, но подписи и значения остаются обязательными non-color cues. Новых global tokens нет.

### 5. Геометрия задаётся явными grid tracks

KPI используют одинаковые tracks title/value/basis. Bar использует фиксированные mark/value/label tracks; multiline labels не меняют baseline. Week range занимает отдельные строки start/divider/end. Paired chart header track и intro offset одинаковы. При `<=1200` paired charts stack; `<=680` weeks переходят в две колонки.

### 6. Навигация потребляет exact public icon exports

Нужные SVG копируются losslessly из `../shlz-ui/packages/icons/dist/icons` в pinned application assets с provenance/hash assertions. `calendar-interface` сохраняется как Interface family. CSS на root не навязывает fill/stroke: path presentation остаётся владельцем paint mode. Все menu-bearing AssetBundle публикуют versioned `pilot.css`, чтобы маршрут и порядок регистрации не возвращали stale paint.

### 7. Ownership и архитектура

Owning module агрегатов и классификации — `app/InstallationProcess`; Yii controller/view/asset/navigation только компонуют read DTO. Persistence owner отсутствует, schema/backup/restore/migrations не меняются. `rapid-pilot` не модифицируется. Architecture checks должны подтвердить public `shlz-ui` provenance, отсутствие inline style/runtime dependency и сохранение route/auth boundaries.

## Risks / Trade-offs

- [Risk] Risk chart не является partition общего числа объектов → подпись ограничивает scope, тесты отдельно фиксируют каждую формулу и не утверждают сумму total.
- [Risk] Five bucket SQL может разойтись с queue drill-down → один predicate vocabulary/fixture используется агрегатом и фильтром, parity проверяется по каждому bucket.
- [Risk] SVG intrinsic sizing снова вызовет overlap → отдельная frame с `minmax(0,1fr)`, browser bounding-box assertions на граничных viewport.
- [Risk] Несколько AssetBundle версий создадут stale CSS → единый fingerprint/version helper для всех menu-bearing bundles вместо ручных постоянных query strings.
- [Risk] FAST может быть отклонён из-за data/filter semantics → lane и required reviews выбирает planner; задачи не обещают FAST до `harness.py prepare`.

## Migration Plan

1. На чистом candidate от актуального `origin/main` перенести только подтверждённые решения прототипа; prototype branch сохраняется отдельно как primary visual source.
2. Добавить RED data/HTTP/browser coverage и пройти planner-selected review.
3. Реализовать aggregate/filter, затем presentation/navigation/cache behavior.
4. Выполнить bounded focused checks, независимый final review и один exact-source CI run.
5. Rollback — возврат application image к PR #217; schema/data migration не требуется.
