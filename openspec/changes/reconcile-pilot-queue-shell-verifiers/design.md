## Context

Current production behavior уже owner-approved и independently reviewed. Требуется заменить только stale verification representation, не product code.

## Goals / Non-Goals

**Goals:** sensitive current queue/shell HTTP oracle; transparent predecessor retirement; stable cleanup.

**Non-Goals:** protected E2E, UI redesign, authorization widening, runtime changes.

## Decisions

1. Object-list test сохраняет fixture/HTTP boundary, но ожидает native table, exact filters/count/page and canonical statuses вместо semantic list.
2. Shell test удаляет отдельный `/pilot/` «Моя работа» scenario и проверяет objects-first shell на representative permission profiles; exact nav строится из fixture grants.
3. Старые broad queue/card/prepare representation blocks заменяются ссылками на focused current tests, но security/CSP/escaping/GET-no-write assertions остаются непосредственно здесь.
4. Sentinel projection исключает atime, потому что test read сам изменяет его; bytes/hash/mode/uid/gid/mtime остаются exact.

## Risks / Trade-offs

- [Self-confirming DOM] → literal expected headers/labels/paths/counts, не production constants.
- [Ослабление RBAC] → positive/negative/revoke matrices сохраняются.
- [Скрытая mutation] → before/after DB projections вокруг GET/HEAD.
