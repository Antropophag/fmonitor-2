## Why

Production-синхронизация кадров всё ещё имеет самостоятельную PHP composition в `bin/fmonitor2-sync-workforce.php`, хотя плановые jobs уже достигают той же операции через общий Yii2 console runtime. Issue #76 требует одну framework-owned console composition без изменения workforce facts, Bitrix delivery contract или append-only history.

## What Changes

- Добавить явный Yii2 console command для операторского запуска workforce synchronization.
- Сохранить `MariaDbWorkforceSynchronization` и существующую Bitrix delivery boundary единственными владельцами бизнес-правил и сохранённых workforce facts.
- Превратить retained standalone script в тонкий compatibility alias к той же Yii2 command с сохранением документированных input/output и exit behavior.
- Доказать parity для success, repeat/no-change, transport/configuration failure, database failure, redaction и single-composition/package closure.
- Переключить только repository-owned callers с доказанной эквивалентностью; рабочий stand не изменять.

## Capabilities

### New Capabilities

- `operations/yii2-workforce-sync-console`: Yii2 console transport для операторского workforce synchronization через существующего канонического application owner.

### Modified Capabilities

Нет.

## Impact

Actor: авторизованная deployment/operator automation. Source oracles: issue #76, `BITRIX-WORKFORCE-DELIVERY-001`, `BITRIX-WORKFORCE-HISTORY-001`, `WORKFORCE-CANONICAL-RUNNER-001` и уже поставленный Yii2 jobs console. Target public seam: закрытая command `php bin/yii workforce-sync/run --interactive=0` и behaviorally identical retained alias. Release value: устранить оставшийся независимый manual workforce-sync bootstrap, сохранив существующую synchronization transaction и evidence.

Явные non-goals: никакие OTIZ files или behavior; никакие schema/migration changes; никакие изменения workforce eligibility, staleness, conflict или scheduling policy; никакого нового network protocol; никакого web/runtime cutover, deployment или удаления исторических verification oracles.
