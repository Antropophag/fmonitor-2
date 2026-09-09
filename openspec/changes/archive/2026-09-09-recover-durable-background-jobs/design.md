## Context

См. `proposal.md` и `PRODUCTION-JOBS-RECOVERY-001`. Существующий #36 recovery
намеренно зафиксирован на v22/63 tables/35 AUTO families и externalizes jobs/outbox
через deferred literal. #34 добавляет migration23, шесть Jobs tables и отдельные
scheduler/worker процессы. Recovery обязан сохранить их состояние, не становясь
вторым queue/outbox owner.

## Goals / Non-Goals

**Goals:** отдельный v23 schema contract; exact backup/restore Jobs state; ordered
writer quiesce; fake-only deterministic resume; v22→v23 forward evidence.

**Non-Goals:** изменение v22 constants, online backup, in-place restore, real
transport, product triggers/templates, provider policy, broker, retention/RPO/RTO и
schema downgrade.

## Decisions

### 1. V22 и v23 имеют отдельные literal recovery contracts

`RuntimeRestore` остаётся deployment owner и получает новый v23 inventory рядом с
неизменным V22 contract. V23 содержит 69 base tables и 39 AUTO families. Выбор
контракта связан с exact source image/frontier; один новый массив, переписанный с22
на23, отвергнут, потому что уничтожает проверяемую совместимость старого bundle.

### 2. Restore владеет bytes/schema, Jobs владеет transitions

Recovery использует существующий standard data-only dump, canonical migration и
private state pipeline. Он не вызывает `JobQueue`, scheduler, outbox sweep, handler
или operator retry. После restore отдельный DML-only fake acceptance вызывает уже
публичные Jobs seams. Architecture check сохраняет DDL только в deployment recovery/
migration owners; `app/Jobs` не получает restore SQL.

### 3. Health отделён от integrity admission

Runtime schema/storage readiness обязана пройти. JobsHealth может быть unhealthy из-
за честно восстановленных stale heartbeat, expired lease или dead rows; это
операционное состояние, а не corruption. Services стартуют только после
`RESTORE_COMPLETED`, затем health отражает recovery progress.

### 4. Unknown delivery сохраняется, а не разрешается restore-ом

Stopped worker мог потерять ответ после provider acceptance. Bundle сохраняет
intent/job/attempt как есть. Fake-only resume проверяет stable provider identity и
at-least-once retry; real send запрещён. Альтернативы reset lease, mark success и
automatic linked retry отвергнуты как потеря истории или guessed external outcome.

### 5. Совместимость достигается forward migration

V22 bundle восстанавливает exact v22 image, затем migration23 добавляет empty Jobs
family. V23 bundle не читается v22 tooling. Web/php-only rollback требует отдельной
HTTP compatibility проверки и остановленных jobs services; полный downgrade при
pending/leased/ambiguous work не является capability.

`rapid-pilot` не участвует в recovery и не получает adapter/domain logic.

## Risks / Trade-offs

- [Scheduler создаёт work во время snapshot] → остановить scheduler первым и
  сохранить private orchestrator evidence.
- [Worker killed после внешнего effect] → оставить lease/intent unknown и повторять
  только через existing stable idempotency semantics.
- [Restore кажется unhealthy] → отделить exact readiness от JobsHealth backlog/
  freshness и не мутировать state ради зелёного health.
- [V22 constants случайно переписаны] → отдельные literal V22/V23 contracts и
  cross-version zero-mutation tests.
- [Bundle/public report раскрывает payload/token] → primary dump/evidence 0600 вне
  repository; public report содержит только counts/hashes/outcomes.

## Migration Plan

1. Получить independent Gate1/3 для normative v23 contract и public RED matrix.
2. Добавить отдельный v23 recovery inventory и fail-closed preflight, не меняя v22.
3. Выполнить exact v23 backup/restore без запуска services; сравнить rows/state/AI.
4. Запустить fake-only resume и проверить leases/outbox/history/DML boundary.
5. Выполнить v22 restore → migration23 → v23 roundtrip и cross-version rejection.
6. Обновить runbook/evidence, получить Gate5 и полный CI до Done.
