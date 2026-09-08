## Context

См. proposal.md. `MariaDbInstallationProcessEnvironment` — физический creator;
`PilotE2ECoordinator` также пишет status/artifact facts. N-1 не знает registry.
Source inventory2026-09-05 фиксирует эти границы и не является cutover approval.

## Goals / Non-Goals

**Goals:** один migration owner двух metadata tables, immutable completion
receipt, точный allocator frontier и проверяемая recovery без изменения history.

**Non-Goals:** выдавать старым writers registry authority, выполнять rollout,
менять original parser/logger, назначать состав или создавать selection rows.

## Decisions

1. Owner — `InstallationProcess` migration area, по existing canonical migration
   conventions. Public production facade связывает inert verification observer;
   verification composition использует тот же engine. Application runtime/HTTP
   не получают DDL entrypoint. Architecture baseline не расширяется.
2. Registry и receipt — отдельная family. Dateless selection tables остаются
   следующим schema slice. Это позволяет отдельно проверить metadata backfill;
   registry engine GREEN не разрешает включать selection.
3. Frontier устанавливается до backfill transaction. В transaction все registry
   rows и единственный completion receipt фиксируются атомарно; incomplete
   committed backfill rows без receipt не усыновляются. При crash допустимы
   только exact empty family DDL states, которые безопасно повторяются.
4. Receipt замораживает historical upper ID/count/hash и captured frontier;
   последующие новые identities не меняют receipt. Registry current frontier
   никогда не понижается, в том числе после rollback gaps.
5. Deployment обязан остановить application writers до engine invocation и
   включать только совместимый release после отдельных cutover gates. Named
   migration lock сериализует миграторы, но не выдаётся за защиту от N-1 writer.
   Concrete all-writer exclusion остаётся release dependency со своим contract.
6. Ни runtime registration в canonical runner, ни literal migration version не
   добавляются в engine-only GREEN. На current base catalogue1–12; candidate
   next slot13 перепроверяется при отдельно gated registration. Это не резерв
   версии и не право добавлять schema без canonical integration до запуска.

## Risks / Trade-offs

- [DDL не rollback-able] → весь existing family preflight до DDL; exact empty
  partial states повторяемы, conflict не ремонтируется.
- [Потеря AUTO_INCREMENT gaps] → независимая decimal frontier формула включает
  current registry AUTO_INCREMENT, не только MAX(id).
- [Неполный writer stop] → engine не объявляет release ready; отдельный cutover
  proof и exact compatible-image deployment обязательны.
- [Receipt устаревает при новых orders] → frozen subset ограничен legacy_max_id;
  dynamic ownership проверяется отдельным release reader.

## Migration Plan

Executable `ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001` → independent Gate1 →
missing-seam/behavior RED → independent Gate3 → minimal engine → focused MariaDB
regression/prefix/recovery/cleanup → independent Gate5 engine-only. Далее нужны
selection-family, allocator/writer compatibility и original-reader contracts,
canonical registration с fresh RED/Gate3/GREEN/Gate5, full verify и clean deploy.
History не удаляется при rollback; incompatible old writer нельзя включать после
cutover. No parent Done из одного engine review.
