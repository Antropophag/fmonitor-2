## Context

См. proposal.md. На evidence SHA `aecd7bb73d1c8b1f52b73764c79cdb2d7289234a` coordinator выполняет SQL регистрации напрямую; `submitAssignmentOrderOriginal` в inspected HTTP PHP surfaces не найден. Полный command Gate 5 ещё не завершён. Этот документ задаёт направление миграции, не разрешает Gate 2.

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

- [Command ещё не имеет полного Gate 5] → planning продолжается, implementation ждёт exact approved predecessor.
- [Partial download после storage failure] → Gate 1 фиксирует preflight/streaming guarantees и exact response behavior до первого RED.
- [Legacy fixtures проверяют другую модель] → separate executable HTTP tests; blocked PILOT-E2E-FLOW-001 сохраняется до owner-approved amendment.

## Migration Plan

Сначала characterization текущего wiring и утверждение executable contract, затем RED/Gate 3, minimal adapter/read seam, regression/architecture, Gate 5. Новый route становится доступным только после явного migration wiring и regression обеих поверхностей. Откат deployment не удаляет accepted original bytes/revisions и не преобразует их в registration facts. Архивация возможна лишь после Done и полного verify.
