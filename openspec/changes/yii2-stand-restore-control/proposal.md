## Why

После поставленного PR #119 Yii2/PHP contour умеет создавать и независимо проверять stand-backup bundle, но destructive restore всё ещё принадлежит legacy `RuntimeRecovery` и отдельному bootstrap. Issue #76 требует следующий минимальный вертикальный срез: один проверяемый `backup → restore` contract через текущую Yii2/application architecture без live deployment или production cutover.

## What Changes

- Добавить application-owned stand restore и Yii2 console command, принимающий только ранее verified bundle протокола PR #119.
- Использовать один bundle/manifest/filesystem protocol для backup и restore; выделить лишь общую validation часть, необходимую обоим владельцам.
- До любых destructive effects отклонять corrupt/incompatible bundle, неверный target/source/image/inventory, unsafe/non-empty target и недопустимые filesystem objects.
- Сохранить применимые recovery-гарантии: DB state и auto-increment, persistent artifacts/session state, schema/inventory expectations и post-restore readiness.
- Зафиксировать fail-closed interruption/replay/conflict semantics: неизвестный или partial effect никогда не становится подтверждённым success.
- Доказать production-shaped focused roundtrip через реальные Yii2/application paths и зарегистрировать проверки в текущем verification inventory.
- После GREEN инвентаризировать production consumers legacy `RuntimeRecovery`/`bin/fmonitor2-runtime-recovery.php`; удалять их только если ответственность полностью замещена.
- Не выполнять live deployment, production cutover, rollback drill, изменение пользовательских сценариев, harness policy или общий cleanup #76.

## Capabilities

### New Capabilities

- `operations/yii2-stand-restore-control`: проверяемый Yii2/PHP restore ранее подтверждённого stand-backup bundle с roundtrip, fail-closed и ambiguous-outcome контрактами.

### Modified Capabilities

Нет. Поставленный backup contract не меняется; минимальное выделение общей validation является implementation detail, а новый restore потребляет его существующий bundle protocol.

## Impact

Actor — deployment operator. Source oracle — issue #76, merged PR #119 (`c5041e2134420f941d678c9977b147cb01f6e809`) и актуальный `main` `11b8587372040ab045d1a69faeeb1432ff85e400`. Target public seam — существующий `php bin/yii` console composition, с новым `stand-restore` command; application owner остаётся в `app/RuntimeRestore`. Затрагиваются только минимальные PHP application/filesystem protocol owners, Yii2 console wiring, normative/executable contracts и focused operational/runtime verification. Stand/deployment остаются неизменёнными и UNKNOWN.
