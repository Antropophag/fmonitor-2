## Context

PR #260 поставил `YiiInspectionPlanning` с командами create/reschedule/cancel и canonical `currentPlan`. Текущие Yii object/calendar adapters содержат legacy schedule presentation; construction-control не имеет действий. Параллельный #258 меняет только installer-utilization artifacts/tests и общие additive registrations.

## Goals / Non-Goals

**Goals:** один current-plan read source; real POST + CSRF/request/version; доступный единый dialog; server-side Moscow today priority before pagination; deterministic fail-closed reads; focused browser/security/concurrency coverage.

**Non-Goals:** новое persistence/schema/model назначения; #45; outcomes/violations/notifications; checklist/progress writers; assignment changes; #258 workforce/directory/person UI; shared `object-ui.js`, picker, `navigation.js`, `pilot.css`.

## Decisions

1. Расширить существующий public `YiiInspectionPlanning` bounded bulk-read методом, который делегирует canonical projection store и применяет тот же scope; view/controller не читает events.
2. Командные routes живут у Yii construction-control boundary и передают request identity/version дословно. Redirect feedback содержит только классифицированный outcome; form context хранится в server session/flash, а неизвестный exception не преобразуется в успех.
3. Существующий локальный `inspection-schedule.js` управляет dialog state/focus и не выполняет fetch/retry: browser native form POST сохраняет security/redirect semantics.
4. Queue SQL применяет existing filters/scope, затем `today-rank`, stable business order и object-id tie-breaker до COUNT/LIMIT. Clock/date передаются сервером в projection; JS не определяет today.
5. Calendar потребляет тот же bulk current-plan result и связывает event с `/pilot/objects/{id}`. Overflow/schema failure aborts rendering.

## Risks / Trade-offs

- Bulk projection может раскрыть объект вне scope → actor-scoped seam принимает только already-visible identities и повторно валидирует scope.
- Redirect теряет введённую дату → classified feedback payload/session хранит action/object/date/version; unknown оставляет пользователя на безопасной форме без retry.
- Midnight race → один Moscow date вычисляется на request и передаётся всем reads/order/count.

## Verification

Focused PHP integration покрывает routes, authorization, CSRF, replay/stale, queue/calendar agreement, before-pagination today group, midnight/no-DML and failures. Playwright покрывает desktop/narrow, keyboard/focus and retained form state. Обязателен HTTP global-calls test при изменении `app/PilotHttp`; этот slice не планирует такие изменения. Full local suite запрещён; final matrix — exact-source GitHub CI.
