# Session empty-environment fixture RED и proposed patch

Дата: 2026-09-05. Автор: `/root`. Base source SHA:
`af75b2002766391ad0dce78bf609e4bbdeeadfa7`.
Technical Gate1 APPROVED:
`session-empty-env-fixture-gate1-review-2026-09-05.md`, supporting spec hash
`d7c9a4acd71aaadd25c21bed1bc836b34ee8e61c64c6318877e44301711760cf`.

## Наблюдаемый input

Native child probe запускает PHP через proc_open с единственным synthetic env
`FMONITOR_SESSION_STATE_ROOT=>''` и читает только этот ключ двумя native getenv
формами. Host и exact image дали `[false,false]`, exit0: empty key не доставлен.
Добавление argv prefix `/usr/bin/env FMONITOR_SESSION_STATE_ROOT=` тому же child
дало `["",""]`, exit0. Это независимо заданный input, не новая runtime policy.

На image default `/home/fmonitor/.local/state/fmonitor2` допустим, поэтому
прежний protocol test фактически проверял absent-key default и получал200.
Host GREEN не был sensitivity proof explicit-empty branch: недоступный default
path там случайно давал503. Runtime strict-false fallback менять нельзя.

После technical Gate1 тот же неизменённый test повторён в network-none
unprivileged exact image с readonly tests mount:

```text
php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
Fatal error: Uncaught TestFailure: INTENTIONAL_RED: GET storage failure status
Expected: 503
Actual: 200
exit255
```

Raw log в private external archive
`/Users/antropophag/.local/state/fmonitor2-verification/session-compose-uz0c2i_b/protocol-red-after-gate1.log`,
SHA-256 `6af61c820b5ef44cba3f78cf8a48c077e7663815f1358f7454ac444af692aa96`.
Image ID:
`sha256:b98963779a006f167986f082f7c7ff78f28e9fc4e30d66a6e11f9d8ecb7613d8`.
Это fixture-delivery RED; не новый production-behavior RED. Пять initial
DB-connection setup failures image suite сюда не включаются.

## Proposed patch, ещё не применён

`docs/operations/patches/pilot-session-empty-env-fixture-v1.patch`, SHA-256
`02f590c9d8127486d6ac801f83d32d1d9c424b3e2cb03f2f8caaeb080336eb53`.
Input test hash остаётся
`315825c95c7ba4059b63e298bf3f710621ff9ffd3e33c57c4982fb43146204d3`.
`git apply --check` PASS; actual test не изменён.

Patch добавляет bounded native child setup probe и prefix только при explicitly
present empty known key; probe и server используют один prefix/environment/cwd.
Никакие password/другие env values не переносятся в argv. All HTTP assertions,
runtime, routers, protected E2E и safe-log оставлены неизменными.
Нужен fresh independent Gate3 exact patch до применения; затем host/image
focused GREEN и independent Gate5. Clean Compose startup/restart остаются
отдельным неуспешным prerequisite, а не допустимым отклонением.
