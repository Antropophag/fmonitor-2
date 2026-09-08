## Context

Registry engine Gate5 APPROVED наb6f619f, canonical runner сейчас1–13.
Selection v0.8 требует пять dateless tables, общий allocator и отдельный
release-compatibility gate. Source evidence — независимый
`selection-compatibility-next-package-review-2026-09-06.md`.

## Goals / Non-Goals

**Goals:** exact standalone five-table migration, проверяемые metadata/data
preconditions, interruption/repeat и bounded lock, production/verification
composition без runtime DDL.

**Non-Goals:** live admission/cutover, canonical version, registry repair,
application state changes, renderer/artifact family или новая product policy.

## Decisions

- Owner — `InstallationProcess`, публичный `AssignmentOrderSelectionSchemaMigration`.
  DDL находится только в `*SchemaMigration.php`, metadata/data inspection —
  отдельные MariaDB adapters. Shared utilities допустимы только после проверки
  неизменности прежних migrations; baseline не расширяется.
- Parent table registry — prerequisite. Existing public `isBackfillComplete`
  подтверждает immutable receipt; selection migration дополнительно проверяет
  exact referenced metadata. Она не вызывает registry apply, не продвигает frontier.
- Порядок: selections → members → requests → events → audits. Foreign keys и
  CHECKs закрепляются exact executable specification. Prefix0..25 сохраняется:
  самый длинный suffix requests39 + prefix25 =64. Constraints получают короткие
  deterministic names, не suffix table целиком.
- Empty leading partial prefix recoverable; gaps, incompatible metadata и nonempty
  partial state fail closed. Полная populated family требует exact read-only
  shape/data-coherence proof перед idempotent accept. Отдельный registry/source
  all-writer readiness остаётся later owner; schema completion не доказывает cutover.
- Verification-only observer используется для deterministic child-process
  interruption/lock tests. Production facade создаёт inert observer без config
  selector. Exact phases, exception mapping и snapshots закрепляются Gate1.
- Schema engine не имеет runtime callers. `rapid-pilot` не получает нового adapter;
  reader/writer wiring проходит последующий combined compatibility lifecycle.

## Risks / Trade-offs

- [MariaDB DDL commits частично] → recovery только exact empty leading prefix;
  no pretend transaction rollback или drop-on-failure.
- [CHECK на AUTO_INCREMENT не поддерживается] → event/audit unsigned physical
  type, отдельный read/precommit capacity contract; не возвращать запрещённый CHECK.
- [Metadata visibility скрывает FK rules] → partial visibility отличается от
  несовместимой shape и не становится silent readiness.
- [Полная family содержит malformed facts] → read-only coherence rejection,
  без migration repair, counter reset или synthetic facts.
- [N−1 writer не знает registry] → engine остаётся disabled; live exclusion
  требует отдельного deployment evidence и не заменяется marker.

## Migration Plan

1. Exact executable schema/public API/data proof/failure matrix → independent Gate1.
2. Real synthetic public-seam RED → independent Gate3.
3. Minimal disabled GREEN → relevant regression, architecture-check, independent Gate5.
4. Canonical registration только после всех compatibility dependencies и отдельного
   gate на actual frontier. Rollback после facts только forward-compatible;
   историю и registry frontier не удалять/не понижать.

## Exact standalone contract v0.2

ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 и два normative JSON fixtures фиксируют
five-table metadata, AST-sensitive fingerprints, populated example, read-only
coherence, phases/lock/recovery/public snapshots. Canonical registration не
входит в engine. Gate1 required до RED. No arbitrary row ceiling; proof O(rows).

Registry public completion `false` означает недоказанный prerequisite и fixed
SCHEMA_MIGRATION_CONFLICT, включая скрытые этим bool API native failures.
Ошибки собственных selection SQL queries остаются unavailable; private registry
proof не дублируется и новый registry API не вводится.
