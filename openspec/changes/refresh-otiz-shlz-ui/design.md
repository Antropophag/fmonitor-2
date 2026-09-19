## Context

См. `proposal.md` и `specs/ui/otiz-operational-workflow/spec.md`. Текущий `OtizSettlementController` формирует крупные HTML-строки без общего `ViewSupport` shell, хотя `pilot.css` уже содержит частичные OTIZ compositions, а `../shlz-ui` предоставляет публичные framework-agnostic primitives. Финансовые операции принадлежат `app/Otiz`; controller остаётся HTTP adapter и не должен получать SQL/domain rules.

## Goals / Non-Goals

**Goals:**

- Дать всем `/pilot/otiz/**` единый Yii2 `ViewSupport` shell, semantic partials/views и shared `shlz-ui` vocabulary.
- Сохранить точные routes/forms/outcomes и поддержать SSR-first responsive presentation.
- Сделать desktop/mobile visual evidence воспроизводимым bounded browser-тестом.

**Non-Goals:**

- Перемещение или изменение `app/Otiz` owners, persistence либо formulas.
- Новая клиентская state model, SPA, custom component library или зависимость от private `shlz-ui` internals.
- Восстановление retired `rapid-pilot` runtime.

## Decisions

### 1. Controller передаёт projection в Yii views

HTML composition переносится из строк controller в OTIZ views/partials. Controller продолжает получать projection из существующих read seams и вызывать существующие command owners. Это отделяет presentation от HTTP/domain orchestration и позволяет применять `ViewSupport::begin/end`. Альтернатива — продолжить конкатенацию строк — отклонена из-за слабой семантики, escaping-review и высокой стоимости responsive изменений.

### 2. Существующий корпоративный world расширяется, а не заменяется

Используются публичные классы `shlz-button`, `shlz-status`, `shlz-table`, `shlz-field`, tabs/pagination и semantic tokens; новые `fm2-otiz-*` классы отвечают только за композицию. `../shlz-ui` остаётся read-only dependency через уже обслуживаемый `shlz.css`. Альтернатива — локальные кнопки/таблицы — нарушила бы единый visual vocabulary.

### 3. Один workflow header и объектные regions

Header связывает период, readiness, итог и главное действие. Snapshot body строится как список object regions; trace/evidence, allocations и issues находятся внутри региона объекта. Это сохраняет сканируемость и доказуемую связь фактов. Не используется глобальная mixed table, потому что на mobile она разрывает принадлежность данных.

### 4. Две явные table strategies

Object register использует semantic labelled-row collapse: `data-label`/headers сохраняют смысл каждой ячейки. Ledger сохраняет колоночное сравнение и получает contained scroll с focusable container/accessible label. Page-level overflow запрещён. На coarse pointer интерактивные цели увеличиваются независимо от viewport.

### 5. Progressive enhancement

GET content, navigation и POST forms полностью server-rendered. `otiz.js` может улучшать поиск/restore state, но не создаёт обязательных actions. Reduced-motion media query обнуляет необязательную motion. JS-off проверяется отдельным browser context.

### 6. Верификация и границы

Root-authored executable spec/HTTP/browser tests фиксируют DOM semantics, routes/payloads/facts, четыре ширины, zoom, keyboard, coarse pointer, reduced motion и JS-off. Architecture check подтверждает отсутствие нового controller SQL/domain ownership; изменения `app/PilotHttp/*.php` не планируются. Verification planner выбирает lane и required reviews; локальный full suite запрещён.

Schema frontier, backup/restore и migration inventory неприменимы: schema и persistence не меняются. Runtime dependency остаётся текущей цепочкой Yii2 asset bundle → public `shlz.css`/`pilot.css`; отсутствие live adapter evidence остаётся `UNKNOWN`, а не GREEN.

## Risks / Trade-offs

- [Крупный controller одновременно содержит HTTP и HTML] → вынести presentation в views без изменения command orchestration и сравнить exact form contracts тестами.
- [Responsive CSS может затронуть другие surfaces] → ограничить selectors корнем `.fm2-otiz` и выполнить shared 320/768/1024/1440 scan.
- [Contained ledger scroll может быть неочевиден keyboard user] → focusable labelled container, видимый focus и сохранённые table headers.
- [Тесты presentation могут стать хрупкими] → проверять semantic roles/classes/relationships и geometry invariants, не пиксельные координаты каждого элемента.

## Migration Plan

1. Ввести views и scoped composition styles без DDL/data migration.
2. Прогнать focused HTTP/browser/architecture checks и независимые Gates 3/5 по exact source.
3. Выполнить один exact-source CI run. Rollback — откат presentation commit; persisted facts и schema не меняются.
