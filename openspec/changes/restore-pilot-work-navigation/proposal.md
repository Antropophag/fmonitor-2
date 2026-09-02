## Why

Штатный pilot shell больше не даёт пользователю вернуться в «Моя работа»:
`PilotView` безусловно рисует disabled label вместо ссылки `/pilot/`. Это
нарушает уже утверждённые `PILOT-OBJECT-LIST-001` и `PILOT-UI-SHELL-001`,
блокирует object-list RBAC verification и golden user journey перед
TEST-USER-READY.

## What Changes

- Восстановить ordinary same-origin ссылку «Моя работа» на `/pilot/` в
  штатном pilot shell.
- На самом `/pilot/` сохранить текущую семантику активного пункта без создания
  второго перехода или нового route.
- Доказать одинаковую навигацию для разрешённых специализированных экранов
  независимо от конкретного набора process permissions.
- Зафиксировать governed configured-composition screen set: work root,
  object list/card/prepare/checklist, construction-control queue/checklist,
  installer directory и admin user/role directories; compatibility composition,
  assets, commands, redirects и error bodies не становятся новыми shell
  behaviors.
- Провести отдельный RED/review/GREEN/code-review cycle, не смешивая regression
  с object-list RBAC fixture alignment.
- **Actor:** любой авторизованный штатный пользователь pilot shell.
- **Source oracle:** approved `PILOT-OBJECT-LIST-001` и `PILOT-UI-SHELL-001`,
  historical renderer behavior до commit `5da1aff` и текущий public HTTP shell.
- **Target public seam:** rendered navigation в успешном pilot HTML response.
- **Release value:** пользователь может вернуться в рабочую очередь, а RBAC/E2E
  verifiers достигают собственных behavior seams.
- **Explicit non-goals:** изменение route authorization, permission model,
  business state, queue filtering, breadcrumbs, mobile menu architecture или
  добавление новых navigation destinations.

## Capabilities

### New Capabilities

- `ui/pilot-work-navigation`: Точная доступность и active-state ссылки «Моя
  работа» в штатном pilot shell.

### Modified Capabilities

Нет.

## Impact

Затрагиваются только pilot shell renderer и его public HTML regression tests.
Persistence, HTTP authorization, application commands, rapid-pilot domain logic
и schema не изменяются. Existing object-list RBAC change остаётся consumer этого
predecessor behavior, но не владеет его implementation.
