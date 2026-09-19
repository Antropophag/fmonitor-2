## Why

Активный Yii2-раздел ОТиЗ после runtime-переноса сохраняет правильные финансовые owners, но его server-rendered представление разрывает период, готовность, объекты, доказательства, нарушения и действия между несогласованными HTML-регионами. Из-за этого сотруднику ОТиЗ трудно увидеть следующий шаг, а таблицы и формы нестабильны на промежуточных и мобильных ширинах; №196 завершает этот bounded UI slice сейчас, пока финансовое поведение остаётся неизменным.

## What Changes

- Собрать период расчёта, readiness и одно основное разрешённое действие в единый workflow header на Yii2 HTTP/browser seam.
- Перевести overview/register/snapshot/history и settlement/evidence/violation/object regions на публичные композиции `shlz-ui`, сохранив корпоративный visual world из `refresh-yii2-shlz-ui`.
- Заменить command-like текстовые ссылки на корректные primary/secondary/danger button compositions и явно связать каждое нарушение и доказательство с его объектом.
- Для каждой таблицы закрепить labelled-row либо contained-scroll mobile strategy; обеспечить 320/768/1024/1440 px, keyboard, coarse pointer, reduced motion и JS-off.
- Добавить root-authored HTTP/browser acceptance tests и провести planner-selected Gates 1–5 отдельными executor/reviewer agents.
- Не менять формулы, permissions, routes, methods, CSRF, form payloads, idempotency/concurrency, append-only facts, схему БД или state-changing owners.

## Capabilities

### New Capabilities

- `ui/otiz-operational-workflow`: Наблюдаемый responsive и accessible `shlz-ui` контракт рабочих экранов ОТиЗ без изменения финансового поведения.

### Modified Capabilities

Нет.

## Impact

Актор — сотрудник ОТиЗ с `otiz.manage`. Source oracle — текущие Yii2 routes, `app/Otiz` application owners, `specs/YII2-OTIZ-WORKFLOW-001.md`, settlement/publication contracts и существующие focused browser/HTTP tests. Target public seam — authenticated Yii2 HTTP/browser flow `/pilot/otiz/**`.

Затрагиваются только Yii2 OTIZ controller/view composition, shared pilot CSS/JS при необходимости, bounded tests, stable executable spec и delivery records. `../shlz-ui` используется только через публичные exports. `rapid-pilot/` не получает новой логики.

Release value: сотрудник ОТиЗ видит целостный период расчёта, состояние готовности, связанные с объектом основания и одно очевидное следующее действие на desktop и mobile.

Non-goals: новые финансовые правила или формулы, изменение принятой истории, новая схема/writer, изменение RBAC, redesign других экранов, общий runtime cutover, merge/deploy/settings. Неопределённые acceptance semantics из characterization changes остаются `NEEDS_GRILL` и не блокируют presentation-only slice.
