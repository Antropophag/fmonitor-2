# Proposed behavior-driven application module map

Это proposal из capability inventory; boundaries должны углубляться по мере миграции slices, а не по именам экранов.

| Module | Responsibility | Owned commands | Owned facts/state | Public seam | Dependencies | Prohibited dependencies |
|---|---|---|---|---|---|---|
| **Assignment Orders** | Версионировать юридическое основание назначения | `prepareAssignmentOrder`, `confirmRegistration`, позднее `prepareAssignmentChange` | order versions, installer/engineer snapshots, registration, artifacts | `AssignmentOrders` command service | installation-object facts, workforce/user directories, clock, renderer/store, transaction port | HTTP/UI, rapid-pilot, concrete MariaDB, checklist mutation |
| **Installation Execution** | Управлять открытием и текущей стадией монтажного дела | `openInstallation` | case state, actual start, opening audit/task transition | `InstallationExecution` | current registered order query, workforce validity, clock | HTTP/UI, MariaDB adapter, progress/premium ownership |
| **Inspection Planning** | Назначать/переносить/отменять контрольную работу после утверждения semantics | `scheduleInspection` (+ future reschedule/cancel) | inspection schedule and schedule events | `InspectionPlanning` | installation state, current engineer, calendar/clock | calendar UI ownership, checklist evidence, direct order SQL |
| **Inspection Evidence** | Принимать факты инспекции, progress evidence, attribution и photos offline-safe | `completeItem`, `changeItemAttribution`, `uploadPhoto`, `revokePhoto`, `completeSection` | operation log/revision, immutable template binding, installer snapshots, photo metadata | `InspectionRecording` | case eligibility, current assignments/workforce read ports, blob store, clock | HTTP/JS, current-screen state, premium formulas, runtime DDL |
| **Installation Completion** | Фиксировать документарные основания завершения | `recordPtoAct`, `recordDeclaration` (точный lifecycle после GRILL) | completion facts and audit | `InstallationCompletion` | verified progress query, clock, authorization | checklist item hacks, UI status ownership, premium calculations |
| **Premium Decisions** | Строить и принимать воспроизводимые расчётные snapshots | `calculatePremiumSnapshot`, `acceptPremiumSnapshot` | immutable operands/result/allocations/issues/evidence/hash, acceptance | `PremiumDecisions` | read-only evidence ports, versioned norms/rules, prior closures, clock | HTTP/UI, rapid-pilot, mutable historical facts, concrete MariaDB |
| **Payment Closure** | Добавлять факты выплаты/удержания и reversal | после GRILL: `recordPaymentClosure`, `reversePaymentClosure` | closure/reversal facts and audit | `PaymentClosures` | accepted premium decision, artifact/reference policy, clock | recalculation/back-solving progress, mutation of accepted snapshot, HTTP/UI |
| **Migration Evidence Control** | Карантинировать и разбирать legacy evidence во время migration | `recordEvidenceDecision`; import commands remain tooling | source snapshots, quarantine and decision ledger | CLI/admin migration seam | read-only legacy adapters, hashes/classifiers | operational case mutation by screen, implicit admission to payment |

## Projection ownership

- Object queue, object card, construction-control queue, calendar, installer directory, OTIZ queue/history and exports are read models.
- A projection may join facts from several modules, but cannot create or reinterpret them.
- HTTP routes translate authenticated requests into one public seam call. MariaDB repositories implement module ports.
- Existing `InstallationProcess` can remain a compatibility facade while PB-01..03 are extracted; it must not become the destination for inspection, completion and premium behavior.

## Cohesion notes

- Inspection Planning is separate from Inspection Evidence because a planned visit is assigned work, while an observed crew/progress/photo is evidence. A calendar is only a consumer of both.
- Installation Completion is separate from Premium Decisions: ПТО/декларация are operational facts; premium snapshots consume them without owning or editing them.
- Payment Closure is separate from entitlement calculation so actual payment cannot change earned progress or entitlement.
- Workforce Catalog remains an external/current-facts provider. Assignment Orders snapshot it; it does not own appointment commands.
