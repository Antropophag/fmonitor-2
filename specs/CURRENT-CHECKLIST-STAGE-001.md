# CURRENT-CHECKLIST-STAGE-001 — единый этап по текущему checklist state

## Простыми словами

Отмена ошибочной отметки должна одинаково менять карточку, строку очереди, server-side фильтры и диаграмму. История остаётся append-only; исправляется только чтение текущего состояния.

## Контракт

Актор: активный пользователь с `objects.read`. Preconditions: открытые native installation cases с действующим распоряжением; операции приняты и упорядочены `accepted_revision,id`.

Публичные seams: `YiiObjectQueue::read(...)`, `YiiOperationalDashboard::read(...)` и существующие HTTP GET очереди/карточки/дашборда.

Для каждого item текущая выполненность определяется последней принятой операцией из `item_completed` и `completion_retracted`; прочие операции не меняют её. Каждый выполненный item учитывается один раз. Item 42, существующие веса и порог 85% сохраняются.

## Acceptance matrix

| ID | Дано / действие | Наблюдаемый результат |
|---|---|---|
| A | 41 монтажный item выполнен, документов нет | card/row/filter/total/dashboard: `document_closeout` |
| B | Последний completion одного item отозван | прогресс ниже порога; те же seams: `installation` |
| C | Item выполнен повторно | все seams снова `document_closeout` |
| D | Повтор completion того же item | итог не увеличивается повторно |
| E | После completion принят `item_installers_changed` | item остаётся выполненным |
| F | Соседний незавершённый объект | остаётся `installation` |
| G | Соседний объект с ПТО и декларацией | остаётся `completed`; retraction не переоткрывает его |
| H | Case `needs_assignment_change` | сохраняет `Требуется изменение` и свой filter bucket |
| I | Набор больше страницы, один case отозван | stage WHERE применяется до COUNT/LIMIT/OFFSET; строки, total, pages согласованы |
| J | Одинаковые данные/условия | dashboard stage value равен total stage drill-down |

Reads не создают и не меняют факты. Guest/unauthorized поведение наследуется и не меняется. Ошибки schema остаются fail-closed. Формулы, выплаты, ОТиЗ, документы, сроки, справки, offline sync и checklist writers вне среза.

## Deterministic expected values

В fixture с 52 полностью отмеченными объектами и page size 50: после отзыва одного объекта `document_closeout total=51`, pages=2, первая страница=50 строк, вторая=1; `installation total` увеличивается на один. После повторного выполнения значения возвращаются. Дополнительные соседние cases проверяют незавершённый, documentary-completed и `needs_assignment_change` этапы, но не входят в эти closeout totals.
