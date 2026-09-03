# PILOT-SESSION-STORAGE-001 v10 LocalAuth lifecycle — Gate 2 RED v3

Date: 2026-09-03

The v2 lifecycle, return-to and exact fault tests remain unchanged. A dedicated
canonical tracer adds the two missing material oracles identified by Gate 3 v2:

```text
60304fd0d10e49b0ff9d56c3c8d6d46907c3bb8ef148bc310b28a7849ccd0a00  tests/InstallationProcess/pilot_session_storage_local_auth_canonical_001_test.php
```

It independently reads the initial anonymous and regenerated authenticated
files from the task-owned root, decodes them with classes disabled, and requires
for each exact `raw === serialize(decoded)` bytes. It additionally binds the
rendered CSRF to anonymous state and actor/email to regenerated state.

```text
php tests/InstallationProcess/pilot_session_storage_local_auth_canonical_001_test.php
INTENTIONAL_RED: canonical anonymous LocalAuth GET
Expected: 200
Actual: 503
exit=255
```

Current LocalAuth still fails at its legacy hardcoded session setup. DB/server
setup succeeds, production is unchanged, and cleanup removes prefix tables,
process and owned root. This record supplements rather than rewrites v1/v2.
