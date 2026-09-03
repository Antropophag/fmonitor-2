# PILOT-SESSION-STORAGE-001 v10 UserAccess tokens — Gate 2 RED

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
b4bfe456852e4799916878ca048de7c960eced1b1ab798c007935e8eeaa6ff94  tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
```

Public seam: authenticated raw `GET /pilot/admin/users` through the production
factory with canonical no-flash owner state.

```text
php -l tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
No syntax errors detected
php tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
INTENTIONAL_RED: rendered action token committed by owner
Expected: true
Actual: false
exit=255
```

The real directory returns 200 and the test extracts an actual rendered
32-hex action token before independently reopening the state through the real
owner. Current implementation leaves that token absent because it commits only
when an invitation flash was consumed.

The subsequent fault branch restores the original canonical state, injects the
exact existing-material publication failure, and requires 503, no success
headers/body, one redacted `rename|committed|1|native_false` trace and unchanged
prior bytes. Fictional DB facts, server and owned root are removed in `finally`;
no task root remains after the intended RED.
