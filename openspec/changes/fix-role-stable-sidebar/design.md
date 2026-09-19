## Context

См. `proposal.md` и `specs/runtime/role-stable-sidebar/spec.md`. На актуальном `main` `MainNavigation` уже владеет canonical permission mapping, но `ViewSupport::begin` использует его только при переданном `currentSection`; иначе рендерится отдельная статическая копия. Большинство вложенных preopening/checklist/feedback screens вызывают `ViewSupport` без этого аргумента. Некоторые standalone root views используют `MainNavigation` напрямую, а OTIZ имеет собственную shell-интеграцию.

## Goals / Non-Goals

**Goals:**

- Один presentation owner видимости main navigation для всех Yii2 screens с общим sidebar.
- Отделить необязательную отметку текущего пункта от обязательного permission filtering.
- Поймать дефект реальным HTTP DOM regression на root и nested routes.

**Non-Goals:**

- Перенос route guards или изменение RBAC semantics.
- Унификация всех layout-шаблонов, CSS/mobile redesign либо изменение внутренней навигации ОТиЗ.
- Persistence, audit, schema migration или новая зависимость.

## Decisions

1. **`MainNavigation` остаётся единственным owner membership.** `ViewSupport` всегда вызывает renderer, даже когда current section неизвестен. Renderer принимает nullable current section и в таком случае не ставит `aria-current`, сохраняя весь разрешённый набор. Альтернатива — назначить каждому вложенному экрану родительский section — допустима только там, где ownership однозначен, но не нужна для устранения visibility defect.
2. **Удаляется статический fallback целиком.** Поддержание второй permission mapping в template уже привело к drift. Локальное добавление недостающих links в fallback отвергнуто как повторение причины дефекта.
3. **Regression проходит через реальный Yii HTTP seam.** Минимальная матрица сравнивает semantic navigation membership/order одного identity на root route и существующих nested routes, включая full-permission и restricted permission phases. Проверка current marker не требует ложного выбора active item на экране без canonical section.
4. **Вложенные OTIZ routes проверяются отдельно.** Если они действительно используют общий sidebar на актуальном `main`, они входят в ту же матрицу; если сохраняют отдельную legacy shell, это фиксируется как явный scope gap и не маскируется assertion’ом на отсутствующий DOM.
5. **Architecture impact bounded.** Owner остаётся в `app/YiiRuntime`; зависимости и persistence owner отсутствуют, `rapid-pilot` не меняется. Нужны только planner-selected focused checks, без локального полного suite.

## Risks / Trade-offs

- [Некоторые nested routes требуют fixture prerequisites] → выбрать минимальные стабильные GET routes, реально использующие `ViewSupport`, и зафиксировать setup отдельно от navigation assertions.
- [Nullable current section оставит экран без `aria-current`] → это честнее неверной отметки; membership остаётся стабильным, а parent mapping можно добавлять отдельно без влияния на visibility.
- [Standalone shells сохранят drift] → regression инвентаризирует все общие sidebar owners; найденная дополнительная копия либо включается в bounded scope, либо поднимается до пересмотра planner lane.

## Migration Plan

Data migration отсутствует. Выпуск — заменить fallback и прогнать focused HTTP regression. Rollback — вернуть presentation delta; persisted state не затрагивается.
