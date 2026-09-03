# PILOT-SESSION-STORAGE-001 v10 UserAccess flash — Gate 2 RED v7

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
cff3d21dd30aaa7cfd91b374a254e9c4a463f0ae37675e14a94996478f9535b0  tests/Support/pilot_session_storage_user_access_router.php
18306b9015898a179a89961894011565f027e07c207f7f915afdca837313bc3f  tests/Support/pilot_session_storage_user_access_fault_router.php
```

The verifier routers now explicitly restore the valid empty
`FMONITOR_PROCESS_TABLE_PREFIX` inside the child process. PHP `proc_open` drops
the parent's empty environment entry, so relying on the parent array produced
`getenv(...) === false` and an unrelated `commandResources()` 503. Both empty
legacy and process prefixes are now explicit child-side configuration.

```text
php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
exit=255
```

With clean production HEAD the intended missing complete-graph 404 remains.
With the separately preserved clean implementation WIP the complete test was
also observed PASS before restashing, including owner removal, repeat absence,
exact primitive fault trace and full failure envelope. That GREEN observation
does not authorize implementation; this record is solely the production-clean
RED. Cleanup is complete and temporary diagnostics were deleted.
