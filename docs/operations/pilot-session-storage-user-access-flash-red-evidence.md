# PILOT-SESSION-STORAGE-001 v10 UserAccess flash — Gate 2 RED

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
2705e913fe041947dd734243914b7198f68eae5f94666285e866ef6374be06d6  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
12e032068af4c335bbf62ed43cd16b256c64c546ae047b3a82712694353c0333  tests/Support/pilot_session_storage_user_access_router.php
e1ca5aff7cf3ba5f4a72568204e19497ed4ff99765527877ed302eab512fc67c  tests/Support/pilot_session_storage_user_access_fault_router.php
```

Public seam: authenticated raw `GET /pilot/admin/users` through
`ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies`,
followed by the real `RapidPilotUserAccessView` production enhancer.

The fixture creates fictional canonical DB identity/role facts and commits a
literal canonical whole-array `serialize()` payload through the real owner. It
contains fixed `auth_user_id`, `auth_email`, 64-hex `auth_csrf` and a fixed
successful `fm2_invitation_flash` URL.

```text
php -l tests/Support/pilot_session_storage_user_access_router.php
No syntax errors detected
php -l tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
No syntax errors detected
php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
exit=255
```

The RED occurs after owner seed, server readiness and raw authenticated request.
The injected factory currently does not compose the v10 UserAccess consumer for
this route, so it returns 404 before the subsequent fixed-flash assertion. This
is the missing complete-graph behavior described by v10 §8, not a DB or native
session setup failure. `finally` removes both fictional users/role, the owned
state root and server process; `/tmp/fmonitor2-session-storage-tests` is absent
after the run.

No production file changed. Later assertions require owner-committed flash
removal, repeat absence, and an exact existing-material `rename/committed/1`
publish fault that must discard the buffered 200 response, expose no redirect
or cookie, and preserve the original canonical payload with its flash.
