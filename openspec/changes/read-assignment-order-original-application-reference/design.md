## Context

Original upload UI complete; application writer ещё отсутствует. Existing
submission source/StoredReader уже владеют metadata/backing validation.

## Goals / Non-Goals

**Goals:** reuse owner validation, immutable scoped reference, current locking
confirmation without opening nested/owning caller transaction.
**Non-Goals:** byte download/integrity recheck, application chronology, grants,
new schema, domain/audit writes, source compatibility migration.

## Decisions

Factory/DTO/query remain AssignmentOrderOriginal; future Composition consumer
uses public interface. Snapshot reuses current owning-module helpers. Guard reads
known source rows with explicit FOR UPDATE, so a stale caller RR snapshot cannot
hide a newly committed original. Opaque seal covers original/selection data;
metadata whitelist excludes private identity/configuration. No generic SQL
interception or query-string rewriting. Both methods borrow the connection.

Two phases separate preflight from current guard; the application writer must
perform guard and its eventual INSERT under the same case transaction. Reader
never decides which order should apply, or how date corrections affect application.

## Risks / Trade-offs

Metadata reference does not prove current PDF downloadability → state this
explicitly; byte delivery remains original storage/read scope. Guard locks remain
owned by caller even on failure → tests prove transaction preservation and require
caller cleanup. No architecture baseline change or runtime DDL is required.
