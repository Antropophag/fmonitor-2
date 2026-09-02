## Context

См. proposal.md. Production уже хранит content-addressed combined PDF; stale E2E
создаёт ожидание двух artifacts и падает до golden journey.

## Goals / Non-Goals

**Goals:** один public artifact; independent semantic PDF oracle; preserved
authorization/integrity/fault/reload snapshots; isolated task-owned storage.

**Non-Goals:** renderer redesign, extra appendix artifact, signing, RBAC changes,
storage schema redesign или production data.

## Decisions

1. Public projection содержит только combined `order`. Tests не добавляют
   compatibility appendix: owner decision и ARTIFACT-STORE already reject it.
2. Expected PDF semantics проверяются independent decoder/literal marker/page
   order. Byte hash не фиксируется между independent renders (renderer metadata
   допустима), но GET bytes обязаны совпасть с persisted metadata hash/size.
3. Fault fixtures создают один combined digest/shard and snapshot full public
   process projection before/after. Authorization denial precedes fault/store.
4. Golden journey доказывает RBAC/process admission до PDF assertion. Isolated
   fault/concurrency cases используют separate DB/user/server/session/artifact
   ownership; finally order: stop/reap server → restore fault → close handles →
   revoke/drop DB user/database → delete verified task roots.

Owning application remains InstallationProcess artifact service/store. HTTP/E2E
adapters не владеют persistence; rapid-pilot получает только wiring. Architecture
baseline не расширяется.

## Risks / Trade-offs

- [Test декодирует не тот artifact] → exact object/version/type and metadata match.
- [Production/test hashes self-confirm] → test-owned renderer inputs + semantic decoder.
- [Fault changes process facts] → before/after public projection, artifacts,
  events, counters and storage identity snapshots.

## Migration Plan

Gate 1 review/approval → E2E RED → independent test review → minimal fixture или
production correction → focused/full/fresh verification → independent code review.
Rollback не возвращает separate appendix contract.
