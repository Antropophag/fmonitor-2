## Context

См. proposal.md. На evidence SHA `aecd7bb73d1c8b1f52b73764c79cdb2d7289234a` coordinator выполняет SQL регистрации напрямую; `submitAssignmentOrderOriginal` в inspected HTTP PHP surfaces не найден. Это историческая characterization predecessor. На restart HEAD e1d3a64 native selection, selected-original binding и PDF получили Gate5 APPROVED; этот HTTP change всё ещё требует собственного executable Gate1 перед RED.

## Goals / Non-Goals

**Goals:** тонкий HTTP adapter, одна command mutation boundary и отдельная read-only projection/stream boundary для accepted evidence.

**Non-Goals:** новая domain logic в rapid-pilot, изменение opening/composition, runtime DDL, изменение защищённого legacy E2E без owner-approved Gate 1.

## Decisions

1. Владелец записи — AssignmentOrderOriginal application module; HTTP переводит transport DTO в command. Direct SQL в coordinator исключается из нового маршрута, поскольку иначе образуется второй владелец фактов.
2. Read/download получает отдельный production read-only seam, а не verification evidence reader. Последний раскрывает диагностические inventories и не является пользовательским query API.
3. Session/RBAC/CSRF admission использует существующий production HTTP контур. Owner-approved read mapping: `fkr_operator` и `manager` — доступные им объекты; `construction_control_engineer` — закреплённые за ним объекты; `otiz_specialist` — все распоряжения. Каждая ветвь требует active user/role и explicit `assignment_order.original.read`; read включает history revisions. Глобальный scope ОТиЗ нельзя ошибочно ограничивать engineer-assignment predicate. Административные roles автоматически не получают read; несколько roles объединяют только явно выданные полномочия. Точный источник текущего закрепления и SQL/read-port contract фиксируются в executable Gate 1.
4. Route strings, transport DTO/status mapping и read DTO проектируются совместно в executable Gate 1 `ASSIGNMENT-ORDER-ORIGINAL-HTTP-001`. Это технические решения для независимого review, не перенос legacy registration semantics.
5. Допустимые зависимости: HTTP → public command/read ports; adapters → storage/repository ports. `make architecture-check` проверяет отсутствие domain SQL/DDL в новом HTTP adapter и отсутствующий импорт verifier factories в runtime.

## Risks / Trade-offs

- [HTTP ещё не подключён] → переиспользовать approved native/selected-original/template owners и independently review только новую HTTP/read/UI поверхность.
- [Partial download после storage failure] → Gate 1 фиксирует preflight/streaming guarantees и exact response behavior до первого RED.
- [Legacy fixtures проверяют другую модель] → separate executable HTTP tests; blocked PILOT-E2E-FLOW-001 сохраняется до owner-approved amendment.

## Migration Plan

Сначала characterization текущего wiring и утверждение executable contract, затем RED/Gate 3, minimal adapter/read seam, regression/architecture, Gate 5. Новый route становится доступным только после явного migration wiring и regression обеих поверхностей. Откат deployment не удаляет accepted original bytes/revisions и не преобразует их в registration facts. Архивация возможна лишь после Done и полного verify.

## Approved binding reconciliation — 2026-09-07

Fresh commands вызывают createForSelections(db,config,freshConnect). Shared case
lock связывает selection replacement/upload; HTTP не определяет applicability.
Отдельный composition HTTP slice доставляет форму и optional PDF на native identity.
Шаблон не хранится, dateReader возвращает последний successful generation date
для явного подтверждения documentDate из original. Новые grants вводятся через
identity application/bootstrap, не runtime DDL и не прямой SQL из HTTP. Нет
launch требования переносить old writer history или поддерживать mixed rollout.

## Operator-first delivery —2026-09-07

Child expose-assignment-order-original-upload-ui доставляет operator submit/form
на orderId identity. Binary PDF+canonical metadata header заменяет unapproved
multipart plan для POST. Full GET metadata/history/download требует actual applied
engineer scope из upcoming composition application owner, без legacy-field fallback.
