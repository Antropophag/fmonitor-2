## Context

См. proposal.md. Stable authorization seam и exact `objects.read` уже approved;
этот change владеет только fixtures/verifiers.

## Goals / Non-Goals

**Goals:** один reusable test-owned canonical RBAC builder; явный actor ID;
exact `GET /pilot/objects` positive/negative matrix; task-owned DB cleanup.

**Non-Goals:** production policy, новые permissions, login redesign, card-route
authorization migration, card/UI-shell fixtures или DOM redesign.

## Decisions

1. Fixture builder создаёт exact local schema/facts через approved canonical
   migration/manifest, а не сокращённые ad-hoc tables. Это предотвращает
   self-confirming drift; альтернатива legacy compatibility rows отвергнута.
2. Каждый request явно задаёт/удаляет trusted actor ID. Наследование ambient env
   запрещено, чтобы negative branches не получили positive actor случайно.
3. Representation expectations берутся из approved object-list spec/review и
   исполняются только после exact GET admission; card/shell excluded.

Owning area — tests/support. Persistence owner production не меняется;
rapid-pilot только вызывается как public oracle. Architecture baseline не должен
расти.

## Risks / Trade-offs

- [Shared env заражает negative case] → новый process/env на case и explicit unset.
- [Fixture копирует production DDL] → canonical migration/independent literals + manifest assertions.
- [Representation drift маскирует auth] → сначала exact GET admission, затем list assertions.

## Migration Plan

Gate 1 spec/review/approval → RED fixture sensitivity → independent test review →
minimal fixture alignment → focused/full verify → independent code review.
