## Why

Владелец разрешил загрузку готового оригинала без шаблона. Independent inventory `docs/operations/assignment-order-direct-upload-seam-inventory-2026-09-05.md` показывает, что существующий public creator всегда вызывает renderer; original command требует уже сохранённый order/composition. Поэтому прямой пользовательский путь пока отсутствует.

## What Changes

- Добавить public application command сохранения выбранного состава одного распоряжения без вызова renderer и без generated artifacts.
- Проверять кадровые/объектные prerequisites и ровно одного инженера до atomic persistence immutable composition identity.
- Возвращать identity, пригодную для existing original upload; отдельное формирование шаблона остаётся необязательным действием.
- Сохранить append-only history, exact replay и concurrency protection. Выбор состава не применяет назначения, не открывает работы и не создаёт signed original.
- Сохранить observable текущие назначения, availability counters и inspection attribution при появлении новой неподписанной selection; запись новых rows не должна скрывать прежний applicable order через общий MAX(version).
- Зафиксировать exact command API, capability mapping, statuses/audit и draft correction semantics в executable Gate 1 до RED; planning не утверждает неизвестные legacy outcomes.

## Capabilities

### New Capabilities

- `pilot/assignment-order-composition-selection`: подготовка immutable выбранного состава без обязательного PDF render.

### Modified Capabilities

Нет изменений main capabilities; integration с existing prepare path требует отдельного exact Gate 1 disposition.

## Impact

Behavior slice: `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001`. Actors — сотрудник и Руководитель ФКР в approved direct-original workflow. Источники: PRODUCT, pilot spec и owner original decision; legacy creator используется только для анализа coupling. Target seam candidate: `selectAssignmentOrderComposition(command)` внутри production application module. Release value — direct upload возможен без renderer/storage template dependency.

В scope: selection persistence и handoff original command. Вне scope: original bytes processing, read grants, opening, применение состава во времени, 1С ДО, изменение protected PILOT-E2E-FLOW-001, новая domain logic в rapid-pilot. Existing prepare/render не удаляется до reviewed integration.

Planning gaps для exact Gate 1: authorization selection vs existing prepare/upload capabilities, изменение ошибочного выбора до original и immutable version identity, physical date compatibility с existing composition hash. Они не заполняются догадками об observed legacy behavior; NEEDS_GRILL только если потребуется новое продуктовое решение сверх approved original workflow.
