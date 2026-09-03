# PILOT-SESSION-STORAGE-001 v10 UserAccess flash — Gate 2 RED v4

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
3c71da07f04041061fab43cc2fef3e35ac5cb9de9d017ee802275a33598c3b20  tests/Support/pilot_session_storage_user_access_router.php
1e670dae0575ff2e972049dde630501e2a82020bfce54b3d10b11933ec3c0bc1  tests/Support/pilot_session_storage_user_access_fault_router.php
```

Both verifier-owned routers now bind the real repository `shlz.css` and
`pilot.css` paths before constructing the production graph. This closes the
configuration predecessor discovered during the first minimal implementation
attempt; no production file participates in the RED run.

```text
php -l <both support routers>
No syntax errors detected
php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
exit=255
```

The response remains the intended missing-complete-graph 404, now with valid
CSS configuration as well as valid DB, canonical owner payload, actor and role.
The v3 trace/header/removal/repeat oracle is unchanged. Cleanup leaves no task
root. Prior evidence/reviews and the implementation-attempt stash remain
preserved; production is byte-identical to HEAD during this RED.
