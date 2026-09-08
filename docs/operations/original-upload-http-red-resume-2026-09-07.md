# Original upload HTTP — возобновление RED

Исходный HEAD `5d407ff57614e34040fccbc6e38ac7bc4545e2e8`, дерево чистое.
Persistent goal восстановлена дословно из handoff без token budget. Существующие
owner approvals сохранены; remote/deployment не менялись.

Gate1 v0.3 CSP amendment APPROVED независимым `/root/original_gate1_v3`:
`reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001-gate1-v3.md`.
Первый Gate3 CHANGES_REQUESTED сохранён в отдельном review record; четыре группы
замечаний закрываются дополненными RED tests. Production original HTTP ещё отсутствует.

## Evidence

Внешний каталог:
`/Users/antropophag/.local/state/fmonitor2-verification/original-http-20260907`.

- `checkpoint-red-20260906T235955Z.json`: повтор пяти checkpoint suites, SHA/commands/logs.
- `amended-red-20260907T000419Z.json`: пять PHP suites и Node client, intended
  missing route404/asset; каждый запускается отдельно, отказ не превращён в skip.
- `permissions-final-red-v2.log`: повтор после исправления test-only snapshot
  (снимать состояние после setup mutations прав). Final SHA теста:
  `3ca3aae9e7ecc67deba32b26230ebbc17ad543895416a524d62c7166945fdfaa`.
- `checkpoint-selection-regression.log`: existing native selection/replay/
  replace_pending/PDF/accepted-root new_order HTTP PASS.
- `extra-framing.php`: loopback characterization дополнила прошлое наблюдение:
  `Content-Length: 0327` и `Transfer-Encoding: identity` достигают PHP и получают
  текущий404; malformed chunked закрывается EOF до PHP. RED admission ожидает400
  для первых двух, bounds проверяет closed/healthy/unchanged facts для последнего.

Добавлены exact read/upload/correct grants, native correction denial, revoked
session, wrong object/order, overflow identity, missing correction reason,
shape-before-CSRF, alternate JSON encoding, exact form GET/HEAD CSP/header parity,
BASE JSON CSP, Retry-After60, client correction/replay/4xx/new intent/503 retry.
Независимый Gate3 повтор записывается отдельным v2 record. Только APPROVED
разрешает переход к GREEN. Полный VERIFY и general original read/download остаются открыты.

Gate3 APPROVED `/root/original_gate3`: `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001-v2.md`. GREEN разрешён для этого утверждённого test batch.
