## Context

См. `proposal.md`. Существующие writers уже владеют durable таблицами workforce, ERP facts и jobs. Новый slice только объединяет bounded reads. Нормативное поведение задано `specs/ADMIN-INTEGRATION-STATUS-001.md` и delta spec.

## Goals / Non-Goals

**Goals:**

- Один owning read-only controller module с тремя независимыми bounded page queries и summary двух источников.
- Yii controller проверяет active identity и canonical `access.administer` до чтения.
- View компонует поставленные shlz-ui contracts без нового общего asset.

**Non-Goals:**

- Retry, интеграционные вызовы, migrations, новые durable facts и deployment authority.
- Обобщённая observability platform и расширение за Bitrix workforce/ERP equipment.

## Decisions

1. Новый `IntegrationStatusController` владеет узким read model и читает существующие таблицы напрямую read-only запросами. Отдельный новый persistence namespace отклонён, потому что planner требует зарегистрированного capability owner, а изменение общей policy запрещено scope. Переиспользование `MariaDbOperatorJobs::listFailed` также отклонено: его deployment authority не является web-auth.
2. Summary строится по последнему run и отдельному последнему completed run. ERP `receipt_json` не отдаётся наружу: allowlist полей извлекается reader; workforce использует typed columns.
3. Workforce diagnostics первого slice — current catalog rows с `missing_from_delivery`. Identity conflicts не имеют row-level durable detail; UI отмечает «детали не регистрируются», а расширение остаётся в #30.
4. ERP diagnostics показывают HMAC номера заказа и stable reason, но не исходный номер/payload. Jobs показывают id/type/attempt/failure code/timestamps, никогда payload.
5. Независимые query parameters `workforcePage`, `equipmentPage`, `jobsPage`; limit фиксирован приложением. COUNT + LIMIT/OFFSET выполняются в SQL. Это продолжает существующий server-rendered pagination contract.
6. Route содержит явный GET/HEAD rule и method fallback; controller не имеет command dependencies. Точечная ссылка добавляется только в `MainNavigation`.
7. Persistence owner не меняется; `rapid-pilot` не получает adapter. Architecture check должен подтвердить отсутствие новых global calls/нарушений boundary.

## Risks / Trade-offs

- [Очень большие OFFSET становятся дороже] → bounded page size и существующие indexes; keyset pagination остаётся будущей оптимизацией при измеренном bottleneck.
- [Разные timestamp formats источников] → форматировать только валидные сохранённые строки, неизвестные показывать безопасно.
- [Schema отсутствует/частично обновлена] → fail-closed read outcome «Данные недоступны», без ложного empty/success.
- [Отсутствует явный enabled flag] → не показывать disabled/not-configured; это честный остаток #30.

## Migration Plan

Схема не меняется. После review и exact-source CI кандидат может быть объединён обычным порядком; rollback — удалить route/navigation/controller/view/reader, durable данные не затрагиваются.
