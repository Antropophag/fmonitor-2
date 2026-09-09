## Context

ADR0002 назначает `app/Otiz` владельцем closures/reversals и требует сериализации по финансовому основанию объекта через срезы. Сейчас rapid adapter блокирует только snapshot и считает closure внутри snapshot, что воспроизводит A02 double payment. Native input mapping подтверждает: operational `case_id` даёт process evidence, но snapshot/closure/finance адресуются устойчивым legacy `object_id`.

## Goals / Non-Goals

**Goals:** три Yii-callable product operations, whole-transaction Yii DB connection, cross-snapshot serialization, append-only replay/reversal и удаление заменённого SQL.

**Non-Goals:** изменение PremiumCalculation/#66, allocation, publication/accept, UI или historical backfill.

## Decisions

- `OtizSettlement` владеет ровно `recordDiscipline`, `completeSnapshotPayments` и `reverse`; общий append helper остаётся private.
- Additive migration создаёт per-object ledger lock row и operation receipt с unique actor+operation UUID/fingerprint. Проверка accepted-среза блокирует snapshot; затем команды блокируют финансовые объекты в сортированном порядке и перечитывают остаток. Сторно блокирует финансовый объект и исходную closure, не захватывая snapshot. Это сохраняет единый порядок между пересекающимися финансовыми операциями.
- Budget читается как accepted snapshot object's immutable accrued minus global signed closures for object. Альтернатива snapshot pool rejected: она повторно вычитает closed-before и не сериализует snapshots.
- Yii DB/Transaction используется целиком; mysqli и Yii DB не смешиваются в одной atomic operation.
- Bulk блокирует sorted financial objects, повторно считает каждый budget и фиксируется all-or-nothing; concurrent snapshots могут успешно добавить100000+50000.
- Reversal копирует три historical components с обратным знаком и unique original reference. Successful zero bulk хранит только replay receipt `no_change`.

## Risks / Trade-offs

- [Два native cases одного legacy object] → сознательно один financial ledger согласно ADR; process facts остаются case-scoped.
- [Deadlock bulk complete/reversal] → sorted object locks и один порядок lock acquisition.
- [Legacy closures без receipt] → учитываются в signed budget, не backfill; replay действует на новые commands.

## Migration Plan

Additive schema → RED/Gate3 → application owner → Yii/compat adapters → удалить rapid SQL → focused DB/HTTP/browser and architecture → Gate5/CI. Rollback возвращает adapters только до новых writes; новые append-only rows не удаляются.
