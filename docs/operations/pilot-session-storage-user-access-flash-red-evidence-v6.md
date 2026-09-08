# PILOT-SESSION-STORAGE-001 v10 UserAccess flash — Gate 2 RED v6

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
94698586125968ecb836fefffec6cc4db748ac99421bacf1f6bdeb816d96d82c  tests/Support/pilot_session_storage_user_access_router.php
2ccecadddec74ff950df663e79527958339eca8710b1ba6922f1d73c5a3cbc3f  tests/Support/pilot_session_storage_user_access_fault_router.php
```

Both verifier-owned routers now explicitly bind the final key required by
`ProductionPilotHttpDependencies::users()`: the valid empty
`FMONITOR_LEGACY_TABLE_PREFIX`. Together with DB, process prefix, CSS, artifact
root, fixed time and session settings, this is the exact current production
resource inventory reached by the UserAccess route.

```text
php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
exit=255
```

With production restored to HEAD, the intended missing complete-graph 404 is
unchanged. Cleanup is complete. The implementation WIP is preserved separately;
no production file contributes to this RED.
