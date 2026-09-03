# PILOT-SESSION-STORAGE-001 v10 UserAccess flash — Gate 2 RED v5

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
cd8f234ffe5defa78d0e3d4abb5b4fa44b3233a495cf6a638ef75f0762d426bb  tests/Support/pilot_session_storage_user_access_router.php
6fb96fbce9b7a3545a66d4f4ffe47497d626b68a0d7830be4747b6b3a5b57724  tests/Support/pilot_session_storage_user_access_fault_router.php
```

The verifier routers now bind every production resource used by the existing
UserAccess directory path: real shlz/pilot CSS, an artifact root beneath the
owned session test root, and fixed `FMONITOR_NOW`. DB and session settings were
already explicit. This closes the `commandResources()` setup predecessor found
during the approved v4 implementation attempt.

```text
php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
exit=255
```

Production is byte-identical to HEAD during the run. The intended missing
factory-graph 404 remains, all later flash/removal/repeat/fault assertions are
unchanged, and cleanup leaves no task root. The implementation WIP is preserved
in a separate stash and is not RED evidence.
