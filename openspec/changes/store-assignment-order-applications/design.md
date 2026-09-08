## Context

Application contract APPLY-001 v0.2 Gate1 APPROVED. Actual canonical frontier15;
новые application snapshots и попытки ещё не хранятся. См. parent apply change.

## Goals / Non-Goals

**Goals:** exact additive two-table family, preserved predecessor facts, bounded
native schema ownership and restartable empty-prefix DDL.
**Non-Goals:** application behavior, grants, old writer adoption, canonical frontier
change before coherent application/opening integration, mutation from rapid-pilot.

## Decisions

Fact header carries stable relational identity/request/event links and two immutable
JSON snapshots. This avoids one pass-through table per nested value while preserving
native FK/unique/CHECK constraints. Runtime verifies exact JSON application semantics.
Application sequence and unique request key are separate concurrency identities.
Audit has no FK to potentially absent actor/object so real denials need no fake rows.

Only InstallationProcess schema classes produce DDL. Existing native schema catalogue
and literal-definition rendering may be reused within that owner; no baseline ratchet.
Migration inspects all shapes before creating anything and never repairs existing DDL.
A failed second CREATE can leave only an empty applications prefix, which is recoverable.

## Risks / Trade-offs

JSON shape CHECK only proves JSON/size; application owner must prove semantic snapshots.
Case/request/event links are relational, and immutable facts are never updated.
New files remain below150lines; architecture-check required. Canonical registration
and exact frontier consumers will be amended once next native writers are complete;
this is not launch completion or a replacement for full make verify.
