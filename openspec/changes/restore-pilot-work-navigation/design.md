## Context

См. `proposal.md`. Диагностический renderer probe и git history показывают:
link был удалён commit `5da1aff`, а текущий `PilotView` безусловно вызывает
disabled-item helper для «Моя работа». Approved object-list/UI-shell contracts
по-прежнему требуют `/pilot/` destination. Regression находится в presentation
renderer и не зависит от RBAC.

## Goals / Non-Goals

**Goals:**

- Восстановить один стабильный work-queue destination в общем shell.
- Сохранить корректный current-state на exact `/pilot/`.
- Изолировать UI regression от RBAC fixture и combined-PDF slices.

**Non-Goals:**

- Перестройка navigation model или mobile shell.
- Изменение authorization, route table, queue filtering или process commands.
- Перенос domain logic в rapid-pilot.

## Decisions

1. **Owner — `app/PilotHttp` presentation boundary.** Renderer получает
   нормализованный current navigation state и формирует link/current item.
   Application/domain modules не зависят от него. Persistence owner отсутствует.
2. **Destination фиксирован contract-ом `/pilot/`.** Permission-based hiding не
   используется: ссылка не является authorization grant, а destination сам
   продолжает проверять admission. Альтернатива — связать item с `objects.read`
   — отвергнута как смешение shell navigation и route permission.
3. **Один public HTTP/renderer RED.** Test фиксирует link вне root, current item
   на root, minimal/broad permission parity, repeat и zero-state-change. Все
   configured-composition callers используют один `PilotView::document`
   navigation composition; verifier покрывает root и по одному успешному
   representation каждого exact route family из spec. Existing
   object-list verifier остаётся downstream regression и не переписывается для
   обхода дефекта.
4. **Rapid-pilot adapter не меняется.** Новая domain logic и persistence не
   появляются; architecture baseline/hotspot исключения не расширяются.

## Risks / Trade-offs

- **[Два одинаковых пункта на root]** → exact count/current-state assertions.
- **[Link ошибочно воспринимается как grant]** → negative route authorization
  остаётся отдельным public assertion.
- **[Правка ломает shell snapshots]** → focused renderer/public HTTP plus
  object list/card/UI-shell regression suites.
- **[Shell навигация маскирует inherited transport outcome]** → existing
  HTTP regressions сохраняют exact `/pilot` redirect и `401/403/404/405/503`
  status/body/application-controlled headers; navigation test не переопределяет RBAC.

## Migration Plan

1. Утвердить exact executable Gate 1 против existing product specs и diagnosis.
2. Написать минимальный RED и получить independent test review.
3. Изменить только shell renderer/navigation helper и получить focused GREEN.
4. Прогнать architecture, lint, object-list/card/UI-shell/RBAC и full verify.
5. Получить independent code review. Rollback — вернуть bounded renderer diff;
   data migration отсутствует.
