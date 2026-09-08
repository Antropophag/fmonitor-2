## Why

Владелец запретил отдельный пользовательский шаг «Применить состав»: после загрузки и подтверждения оригинала карточка должна предлагать явное «Открыть работы». Применение текущего подтверждённого оригинала и открытие должны выполняться одним атомарным application command, чтобы отказ открытия не оставлял частичное применение.

## What Changes

- Добавить публичную compound-команду `openConfirmedOriginal`, которая под `installation.open` валидирует current accepted original, composition/eligibility/date/template и в одной транзакции применяет состав и открывает работы.
- Повтор уже принятой команды безопасно возвращает прежний success; corrected original до opening может создать append-only reapplication внутри той же транзакции.
- Добавить HTTP action `open_confirmed` с trusted actor/object и полями `requestId`, `orderId`, `revisionId`, `sequence`, `actualStartDate`.
- Сохранить standalone apply seam и его exact `assignment_order.composition.apply` permission; upload остаётся pure и не открывает работы.

Behavior slice: явное атомарное открытие по подтверждённому оригиналу. Actor: активный сотрудник ФКР/Руководитель с `installation.open`. Source oracle: latest owner correction и существующие application/opening contracts. Target public seam: compound application command и POST `/pilot/objects/{id}/execution`. Release value: один понятный action без промежуточного UI и без partial facts.

Non-goals: auto-opening on upload/GET, изменение standalone apply authority, удаление append-only application history, UI implementation, real data/deploy/remote actions. NEEDS_GRILL отсутствует.

## Capabilities

### New Capabilities

- `installation/open-confirmed-original`: атомарное применение current confirmed original и явное открытие.

### Modified Capabilities

Нет.

## Impact

`app/AssignmentOrderComposition`, `app/PilotHttp/ExecutionHttpHandler.php`, focused synthetic tests. Schema и внешние системы не меняются.
