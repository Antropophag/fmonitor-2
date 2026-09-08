## Why

`pilot_object_list_001_test.php` и `pilot_ui_shell_001_test.php` смешивают representation двух seams. Native `public/router.php` сохраняет unfiltered complete-set/cap500 и configured root body «Моя работа», тогда как manual `rapid-pilot/router.php` добавляет q/status/page и root redirect. Verification должна обновить только одобренные shared table/navigation изменения без приписывания adapter behavior native seam.

## What Changes

- Заменить stale semantic-list assertions на native rich table/current count, сохранив ignored query и cap500/501.
- Сохранить configured native root body «Моя работа», но убрать «Моя работа» из navigation и проверить permission-driven links.
- Оставить manual q/status/page/root redirect в существующих rapid-pilot verifier/E2E.
- Сохранить GET/HEAD parity, local RBAC, escaping, CSP, отсутствие inline execution и любых GET mutations.
- Исправить test-owned atime oracle так, чтобы чтение sentinel не считалось изменением данных.

Behavior slice: reconciliation verification текущего queue/shell UI. Actor: active local pilot user. Source oracle: approved manual UI/nav evidence и public HTTP. Target seam: GET/HEAD `/pilot/objects` и shared shell. Release value: VERIFY различает regression текущего UI от удалённых predecessor решений. Non-goals: production/UI change, новые routes/features, protected E2E, data/deploy/remote actions. NEEDS_GRILL отсутствует.

## Capabilities

### New Capabilities

- `verification/pilot-queue-shell-current`: текущий public HTTP verification contract для queue, shared shell и card representation.

### Modified Capabilities

Нет.

## Impact

Только `tests/InstallationProcess/pilot_object_list_001_test.php`, `pilot_ui_shell_001_test.php`, `pilot_object_card_001_test.php`, их support/review records. Production и protected E2E не меняются.
