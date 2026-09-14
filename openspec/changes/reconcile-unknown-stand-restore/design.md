## Context

> **Owner decision / supersession — 2026-09-14.** Design остаётся историческим описанием implemented recovery protocol, но больше не является маршрутом closure issue #76. UNKNOWN ledger/lease/evidence не изменять, reconciliation/rollback не выполнять, offline recovery code не удалять. Новый delivery route — отдельный `yii2-clean-stand-cutover`.

См. `proposal.md`. Operation `e82320ce-a1de-4728-971a-f472167717b8` имеет durable canonical `OUTCOME_UNKNOWN`, matching retained lease, no confirmed pointer и partially restored target. Existing `StandRestoreApplication` replay правильно возвращает UNKNOWN, а любой новый restore/rollback UUID получает `LEASE_HELD`. Root cause volume stdin исправлен commit `9d0ca509a99a39cc765c8fad5afd0ef1b2087721`, но это не разрешает обход retained ambiguity.

## Goals / Non-Goals

**Goals:**

- Добавить один narrow recovery intent под существующим RuntimeRestore application owner.
- Сохранить исходные ledger/lease/replay semantics и расширить их append-only reconciliation fact.
- Сделать lease transition crash-repairable и разрешающим только rollback.
- Подготовить exact authorization до execution; actual reconciliation и rollback остаются отдельно owner-authorized.

**Non-Goals:**

- Generic unlock/manual delete, generic workflow engine или новый restore owner.
- Retroactive success/failure rewrite, `RESTORE_VERIFIED`, retry старой operation.
- Payload restore, restart, RuntimeRecovery cleanup, production cutover.

## Decisions

### 1. Existing application owner получает отдельный reconcile method

Yii controller адаптирует `stand-restore/reconcile-unknown`; `StandRestoreApplication` либо узкий collaborator внутри того же RuntimeRestore owner читает existing ledger/lease и владеет state transition. Альтернатива shell/runbook unlink отвергнута: она обходит authority и durable ordering.

### 2. Separate immutable reconciliation authorization

Canonical package связывает reconciliation UUID, prior operation UUID, target/bundle/lease digests, verified rollback bundle, source/image/runtime tuple, compose/service/DB/network/volume identities, disposable marker, expiry и expected preflight. Secret values отсутствуют. Existing restore/rollback authorization не переиспользуется.

### 3. Recovery ledger отделён от immutable restore ledger

Новый `restore-reconciliations.jsonl` хранит versioned canonical facts. Restore `restore-operations.jsonl` остаётся byte-identical. Один prior operation может иметь максимум один compatible terminal reconciliation; conflicting second fact invalidates admission.

### 4. Lease переходит в rollback-only capability

После durable fact application atomically публикует `rollback-ready.json` с exact prior/reconciliation/target/bundle digests и удаляет matching restore lease, либо заменяет его rollback-only lease. Выбор реализации должен сохранить atomic rename/fsync и позволить exact replay repair. Plain absence lease без durable reconciliation никогда не означает rollback readiness.

### 5. Crash boundaries являются частью public evidence

Recording filesystem/attestation adapter инъецирует interruption перед append, после append, после fact fsync, перед/после lease transition и перед rollback-ready publication. Replay repair разрешён только при canonical matching fact; unexpected file/symlink/content даёт `OUTCOME_UNKNOWN`/conflict без mutation.

### 6. Current target reconciliation authorization готовится после Gate 5

До explicit owner authorization выполняются только read-only checks и package generation. Current retained lease не трогается. После authorized reconciliation формируется новый rollback package; existing package from source `4f4f7edb…` не переиспользуется автоматически.

## Risks / Trade-offs

- [Fact durable, lease transition ambiguous] → exact replay repair и rollback-ready только после подтверждённого fsync.
- [Ручное удаление выглядит проще] → command grammar не предоставляет path/unlock intent; architecture test запрещает unlink outside owner.
- [Partially restored target drift] → repeat exact attestation и bundle verify до fact; mismatch blocks.
- [Reconciliation ошибочно трактуют как success] → literal UNKNOWN retention assertions, no restored pointer, result `UNKNOWN_RECONCILED_FOR_ROLLBACK`.
- [Rollback package устаревает] → генерировать только после successful reconciliation на fresh source/identities; отдельная authorization обязательна.

## Migration Plan

1. Root пишет normative executable spec, verification input и intended RED matrix.
2. Independent Gate 3 approves tests/evidence.
3. Separate executor реализует narrow owner method/controller, recovery ledger и durable lease transition.
4. Generated focused plan GREEN; independent Gate 5 approves exact source.
5. Non-destructive preflight генерирует exact reconciliation authorization for `e82320ce…`; остановка до owner approval.
6. После отдельной authorization выполняется reconciliation; exact rollback package генерируется, но rollback не запускается.
