## Why

Exact disposable restore operation `e82320ce-a1de-4728-971a-f472167717b8` durable-завершилась `OUTCOME_UNKNOWN`, сохранила matching lease и не опубликовала `restored.json`. Исправленный driver готов для будущего rollback, но никакая новая restore operation не может законно начаться до explicit append-only reconciliation прежнего UNKNOWN; ручное удаление lease разрушило бы fail-closed protocol и audit.

## What Changes

- Добавить один explicit public intent `stand-restore/reconcile-unknown`, принадлежащий существующему `StandRestoreApplication`/RuntimeRestore owner, а не generic unlock command.
- Требовать отдельный canonical owner authorization, связанный с exact UNKNOWN operation, target, bundle, retained lease, attested disposable identities и independently verified rollback bundle.
- До любого lease effect проверить неизменённый UNKNOWN ledger record, отсутствие restored pointer, exact lease ownership, target attestation и production/neighbor non-overlap.
- Append-only записать durable reconciliation fact: previous restore остаётся UNKNOWN, success не подтверждён, forward completion abandoned, следующий state-changing intent — только rollback.
- Освободить либо передать lease для rollback только после fsync reconciliation record; interruption сохраняет fail-closed состояние.
- Same exact reconciliation replay сделать idempotent; conflicting authorization/target/bundle отклонять без effects.
- После Gate 5 подготовить, но не выполнять, exact authorization для reconciliation operation `e82320ce…`; rollback остаётся отдельно авторизуемым следующим действием.
- Не переписывать restore protocol, UNKNOWN record или confirmed outcomes; не добавлять generic workflow engine/manual unlock и не трогать `RuntimeRecovery`/production cutover.

## Capabilities

### New Capabilities

- `operations/reconcile-unknown-stand-restore`: explicit append-only reconciliation одного exact UNKNOWN restore lease в состояние, допускающее только отдельно авторизованный rollback.

### Modified Capabilities

Нет. Существующий restore/backup contract и его UNKNOWN semantics сохраняются; новая capability добавляет отдельный recovery intent поверх доказанного retained state.

## Impact

- Actor: owner-authorized deployment operator.
- Source oracle: issue #76, failed operation `e82320ce-a1de-4728-971a-f472167717b8`, bundle `ed8d7613673b1bff987daa03547604f999aeab596baa517159b5fa27c0305108`, durable ledger/lease evidence и correction commit `9d0ca509a99a39cc765c8fad5afd0ef1b2087721`.
- Target seam: `php bin/yii stand-restore/reconcile-unknown --manifest=… --operation-id=… --authorization=… --interactive=0` через существующий RuntimeRestore application owner.
- Release value: безопасный переход от retained ambiguity к отдельно разрешаемому rollback без фальсификации прошлого результата.
- Affected areas: `app/RuntimeRestore`, Yii console adapter, focused recovery tests, runbook/evidence и verification registry.
- Non-goals: reconciliation execution в этом planning ходе, rollback/restore, generic unlock, deletion/rewrite UNKNOWN history, RuntimeRecovery retirement, production deployment/cutover.
