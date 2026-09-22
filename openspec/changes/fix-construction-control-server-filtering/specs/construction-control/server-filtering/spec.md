## Purpose

Определяет фильтрацию всей доступной очереди стройконтроля до пагинации.

## ADDED Requirements

### Requirement: Фильтры предшествуют пагинации

Authorized GET/HEAD SHALL применять `ownership`, `query` и `completed` до COUNT и LIMIT/OFFSET. Строки, total и pages MUST происходить из одного предиката со стабильной сортировкой.

#### Scenario: Объект за первой страницей
- **WHEN** собственный или найденный объект находится за первой общей страницей
- **THEN** он появляется на page 1 соответствующего фильтра

### Requirement: Existing process semantics

`mine` SHALL означать текущее native-закрепление actor. `all` MUST NOT расширять authorization. Completed SHALL быть существующим `pto_act AND declaration`; default исключает completed, включённый режим добавляет их, PTO-only остаётся исключённым.

#### Scenario: Сочетание фильтров
- **WHEN** actor сочетает поиск, ownership и completed
- **THEN** сервер применяет их конъюнктивно до пагинации

### Requirement: URL является состоянием фильтров

View SHALL отражать фильтры в controls и pagination links. Изменение фильтра MUST отправлять page 1; refresh MUST воспроизводить набор. Неверные значения SHALL давать контролируемый ответ без фактов.

#### Scenario: Переход и обновление
- **WHEN** пользователь меняет фильтр, переходит по странице и обновляет её
- **THEN** URL, controls, total, pages и строки согласованы

### Requirement: Existing UI and local sync remain intact

Очередь SHALL сохранять внешний вид, shipment indicator, photo/checklist links и offline/local-sync/prefetch. Browser code MUST NOT скрывать server rows или заменять server total.

#### Scenario: Пустой результат
- **WHEN** серверная выборка пуста
- **THEN** показано правдивое empty state и zero total
