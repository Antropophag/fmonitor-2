## Context

Existing `MariaDbAssignmentOrderApplication` и `MariaDbOriginalOpening` владеют отдельными транзакциями. Compound owner должен переиспользовать их guards/SQL helpers внутри одной внешней транзакции без HTTP SQL и без mysqli proxy.

## Goals / Non-Goals

**Goals:** одна application transaction, append-only facts, exact authority/replay, сохранение standalone APIs.

**Non-Goals:** upload opening, UI apply step, schema change, permission widening, deployment.

## Decisions

1. Новый command/result и production factory находятся в `AssignmentOrderComposition`.
2. Общие locked-read/write helpers извлекаются из существующих MariaDB owners и принимают caller-owned transaction; прежние public methods продолжают открывать собственную transaction.
3. Compound owner сначала блокирует case/current original/application/request, выполняет все validations, затем application writes и opening writes, commit один раз; любой Throwable/rejection rollback.
4. Replay receipt содержит normalized fingerprint и итоговый application id/opening projection; conflict не мутирует.
5. HTTP только валидирует shape/CSRF и переводит result; standalone action `apply` остаётся совместимым.

## Risks / Trade-offs

- [Частичное применение] → один transaction owner и rejection projection equality.
- [Deadlock] → один стабильный lock order case→original→application/request.
- [Authority widening] → compound проверяет только `installation.open`; standalone apply не меняется.
- [История correction] → новое применение добавляется, прежнее не UPDATE/DELETE.
