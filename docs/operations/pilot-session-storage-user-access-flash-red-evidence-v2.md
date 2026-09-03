# PILOT-SESSION-STORAGE-001 v10 UserAccess flash — Gate 2 RED v2

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
12e032068af4c335bbf62ed43cd16b256c64c546ae047b3a82712694353c0333  tests/Support/pilot_session_storage_user_access_router.php
6c5c916f0e972a07b41b553197ebb92eac843936287371115898df17f7e03ce1  tests/Support/pilot_session_storage_user_access_fault_router.php
```

The v1 canonical whole-array seed, real owner, fictional DB facts, raw GET,
flash consumption/removal/repeat assertions and bounded cleanup remain. The
fault child now emits only redacted AFTER primitive tuples to an owned trace
file. The parent requires exactly one
`rename|committed|1|native_false`, the unchanged original payload, and the full
section-6 response envelope including exact cardinality/value for every header,
forbidden header absence, and no unspecified application headers.

```text
php -l tests/Support/pilot_session_storage_user_access_fault_router.php
No syntax errors detected
php -l tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
No syntax errors detected
php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
exit=255
```

The same honest RED remains before the fault branch: current injected factory
does not assemble the promised UserAccess route graph. Owner seed and server
readiness pass; cleanup leaves no task root. No production file changed. The v1
evidence and `CHANGES_REQUESTED` review remain immutable.
