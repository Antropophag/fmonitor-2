## Why

Владелец продукта решил полностью убрать пункт «Моя работа» из общей pilot
navigation. Текущий disabled item и прежний план восстановления ссылки теперь
противоречат этому решению и блокируют честное продолжение object-list RBAC
verification.

## What Changes

- **BREAKING:** удалить navigation item с exact visible label «Моя работа» из
  всех успешных pilot HTML representations, использующих configured shared
  shell, включая exact `/pilot/`.
- Сохранить рабочую очередь и route `/pilot/` как самостоятельную public
  поверхность: не удалять, не перенаправлять и не менять их authorization,
  filtering или content.
- Сохранить остальные navigation items, route admission, redirects и exact
  error/method responses; изменение ограничено presentation общей navigation.
- Провести новый Gate 1 → RED → independent test review → minimal GREEN →
  independent code review cycle. `restore-pilot-work-navigation` и его reviews
  остаются superseded historical evidence и не дают approval этому change.
- **Behavior slice:** `PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001`.
- **Actor:** любой посетитель успешного configured pilot HTML shell после
  применимого route admission.
- **Source oracle:** append-only owner decision
  `docs/operations/pilot-work-navigation-owner-decision-remove-item-2026-09-02.md`
  и фактическая configured shared-shell composition.
- **Target public seam:** navigation DOM в успешном pilot HTML response.
- **Release value:** navigation соответствует явному решению владельца, а RBAC
  fixtures больше не зависят от отменённого требования ссылки `/pilot/`.
- **Explicit non-goals:** удаление или redirect `/pilot/`, изменение queue
  semantics/content, authorization/permissions, business state, persistence,
  breadcrumbs, mobile architecture или других navigation destinations.

## Capabilities

### New Capabilities

- `ui/pilot-work-navigation-item-removal`: Отсутствие пункта «Моя работа» в
  configured shared pilot navigation при сохранении route `/pilot/`, очереди,
  остальных navigation items и transport/authorization contracts.

### Modified Capabilities

Нет.

## Impact

Затрагиваются только shared pilot navigation renderer/composition и его public
HTML regression tests. Production routes, application/domain seams,
persistence, rapid-pilot business logic и schema не меняются. Downstream
object-list RBAC verifier должен заменить superseded predecessor assertion на
approved absence contract только после завершения соответствующих gates.
