## Context

См. proposal.md. Golden journey смешивает несколько actors/routes и устаревший
artifact expectation; security fixture и artifact migration должны оставаться
разными slices.

## Goals / Non-Goals

**Goals:** deterministic fictional actors, exact grants, explicit trusted IDs,
revoke sensitivity, cleanup and clear downstream PDF boundary.

**Non-Goals:** combined-PDF implementation, production/imported identities,
broad admin role, password/session redesign или новые permissions.

## Decisions

1. Main fixture seed задаёт local role `objects_reader` с единственным
   `objects.read` только actor 18. Actor 19 в main negative branch остаётся
   legacy-only без local role/grant. Command process capabilities остаются в
   existing namespace; один broad local superuser запрещён.
2. Journey requests получают actor through real local auth/session seam либо
   approved trusted test boundary; direct `REMOTE_USER` authority исключена.
3. Revoke выполняется в отдельной isolated DB branch: exact committed DELETE
   actor-18 role-permission между двумя GET; authorization audit не ожидается.
   Main fixture не мутируется; его grant остаётся для downstream journey/PDF.
4. RBAC assertions завершаются до artifact assertions. Combined-PDF failure
   остаётся visible downstream dependency, а не повод вернуть legacy artifacts.
5. Snapshot сравнивает full DB/process/storage equality непосредственно
   вокруг каждого authorization read. Между этой точкой и artifact
   boundary canonical journey выполняет legitimate prepare mutation,
   поэтому verifier допускает только approved assignment-order, event,
   artifact-metadata и owned artifact-bytes delta. Он отдельно сравнивает
   exact local-user/role/assignment/permission rows, authority-related counters
   и schemas byte-for-byte. Полное equality через prepare отклонено
   как противоречащее public journey; снятие snapshot sensitivity тоже
   отклонено.
6. Все DB/users/sessions/artifact roots task-owned и очищаются in finally.

Owning area — E2E fixtures/harness. Production application seams, persistence и
rapid-pilot domain logic не меняются. Architecture baseline не расширяется.

## Risks / Trade-offs

- [Object grant трактуется как command grant] → assertions сохраняют separate process-capability denials.
- [E2E cleanup скрывает failure] → capture result before cleanup, cleanup failures classified separately.
- [PDF debt маскируется] → exact downstream marker and separate OpenSpec dependency.

## Migration Plan

Gates 1–5 fixture slice; после GREEN продолжить independent combined-PDF change.
Rollback fixture-only, без production fallback.
