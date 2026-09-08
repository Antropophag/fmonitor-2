# PILOT-SESSION-STORAGE-001 v10 — UserAccess public CSS fixture correction v8

- Date: `2026-09-04`
- Gate: `2`, test-only correction
- Production changes: none
- Prior Gate 3 records: UserAccess flash v7 and tokens v1, historical after
  this fixture-byte change

Оба approved UserAccess routers указывали на удалённый generated path
`../shlz-ui/packages/styles/dist/shlz.css`. Фактический public export соседнего
read-only repository — `../shlz-ui/packages/styles/shlz.css`; он содержит полный
import graph и является тем же public dependency seam, который проверяет
production `ShlzCssAsset`.

Исправлены только:

- `tests/Support/pilot_session_storage_user_access_router.php`;
- `tests/Support/pilot_session_storage_user_access_fault_router.php`.

Ожидания flash/tokens, canonical owner, fault injection и production bytes не
менялись. Fresh canonical DB после reset/migrate подтверждает, что setup CSS
больше не является единственным объяснением, однако current production всё ещё
возвращает `503` на первом accepted UserAccess GET вместо `200`:

```text
$ php -l tests/Support/pilot_session_storage_user_access_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_router.php

$ php -l tests/Support/pilot_session_storage_user_access_fault_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_fault_router.php

$ php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
accepted UserAccess session reaches admin users
Expected: 200
Actual: 503
exit 255

$ php tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
no-flash UserAccess GET succeeds
Expected: 200
Actual: 503
exit 255
```

Static composition inspection identifies the remaining production boundary:
`ownerUserAccess()` passes canonical local actor facts into `users()`, but
`users()` resolves the actor through the legacy email directory before checking
the already local `access.administer` capability. The fixtures intentionally do
not create legacy authority/display rows. Minimal GREEN must use the active
local profile for this owner-backed UserAccess branch and preserve all existing
owner commit/fault assertions. Fresh independent Gate 3 is required before that
production change.
