## Context

Main после #227 уже выполняет SQL COUNT/LIMIT/OFFSET и вычисляет completed. Дефект: owner получает только page, DOM-фильтр работает после pagination. PR #226 вводит `MariaDbEffectiveObjectDetails`; после его объединения queue использует тот же mechanism для effective address/regnumber.

## Goals / Non-Goals

Goals: единый SQL predicate; URL-state; >50-row proof; сохранение UI/sync. Non-goals: offline rewrite, migrations, statuses, card/object-list/OTiZ redesign.

## Decisions

- Controller принимает `ownership=mine|all`, bounded trimmed `query`, `completed=0|1`.
- Read owner формирует shared WHERE/parameters для COUNT и data query; current native assignment remains fail-closed.
- Default: mine, empty query, completed false. Pagination carries filters.
- Controls are GET form; JS submits filters, while local sync/prefetch code remains intact.
- После #226 both SELECT and search reuse effective-details owner; no post-filter relabeling.

## Risks / Trade-offs

- Current-assignment SQL may diverge: use canonical projection and coherence checks.
- #226 is not in base: require merge compatibility verification before publication.
- Escape SQL LIKE wildcards and test address/regnumber.

## Migration Plan

No schema/data migration; application-only rollback.
