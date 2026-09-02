## Why

Очередь стройконтроля является release-critical read-only путём тестового пользователя, но её наблюдаемая выборка и представление не имеют отдельного executable oracle: существующий runtime-DDL verifier доказывает только HTTP `200/503`. До изменения projection или выделения целевого read-model seam нужен bounded `PILOT_ONLY` срез, который зафиксирует текущие факты и одновременно не превратит известные дефекты фильтрации, времени и смыслов «инспекции»/«завершения» в требования продукта.

## What Changes

- Добавляется planning-only behavior slice `CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001` для синтетического активного пользователя rapid-pilot с текущим `construction_control.read`.
- Source oracle — реальный public HTTP `GET/HEAD /pilot/construction-control` через production composition, текущую MariaDB projection и `ProductionConstructionControlRenderer`; target public seam в этом срезе не создаётся, будущий кандидат — read-only `InspectionExecution::getConstructionControlQueue(actorId, page)`.
- Будущий verifier SHALL наблюдать только bounded private fixtures: authorization result, working-only selection, server ordering/pagination, engineer snapshot precedence/fallback, current checklist-activity projection, PTO-derived display flag, escaped checklist links, GET/HEAD parity, infrastructure failures и отсутствие DB/file/session mutation. Независимый MariaDB guard SHALL одновременно запрещать runtime writes least-privilege grants и наблюдать каждую DML/DDL attempt через уже включённую `performance_schema` statement history; serial requests, concurrent worker A/B и sensitivity probe получают четыре разные exact runtime principals, поэтому каждый principal SHALL однозначно соответствовать не более чем одному active connection/thread. Недоступный/неполный audit является `SETUP_FAILURE`.
- Текущие hazards SHALL сохраняться только как `PILOT_ONLY` contrast: browser-side `mine/all` после server pagination, broad visibility, `MAX(device_time)` любой checklist operation как «последняя инспекция», legacy `ptoactdate` как `completed`, live-clock labels и browser session/offline effects.
- До RED обязательны fresh independent Gate 1 review и explicit owner approval exact executable-spec hash. Reviewer тестов и reviewer кода SHALL быть разными агентами и не SHALL утверждать собственную работу.
- Non-goals: scheduling/cadence/overdue, назначение или переназначение инженера, checklist/photo mutation, offline/service-worker/sessionStorage semantics, target authorization, pagination redesign, UI polish, runtime-DDL/schema migration, production/primary evidence и secrets.
- `NEEDS_GRILL`: target queue visibility/assignment semantics, meaning of inspection activity/completion и any target read-model API остаются отдельным будущим slice; эта characterization их не разблокирует и не меняет.

## Capabilities

### New Capabilities

- `verification/construction-control-queue-characterization`: воспроизводимый `PILOT_ONLY` контракт наблюдения текущей read-only очереди стройконтроля через публичный HTTP seam.

### Modified Capabilities

Нет.

## Impact

Planning artifacts затрагивают будущий verifier под `tests/Verification/` и отдельный test-support harness без изменения production code, shared hotspot tests или rapid-pilot domain logic. Evidence anchors: `docs/operations/pilot-behavior-inventory.md`, `docs/operations/inspection-schedule-behavior-evidence.md`, `app/PilotHttp/PilotE2ECoordinator.php`, `app/PilotHttp/ConstructionControlView.php`, `app/PilotHttp/control-queue.js` и существующий schema-readiness test `tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php`.
