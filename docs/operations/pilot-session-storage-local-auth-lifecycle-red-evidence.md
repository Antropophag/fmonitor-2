# PILOT-SESSION-STORAGE-001 v10 LocalAuth lifecycle — Gate 2 RED

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
f8cfa30eace1eb62c8e32cc3a5d308019003088992746769e671e4635c8dcb9a  tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
```

Public seam: raw HTTP through `rapid-pilot/router.php` and the real
`RapidPilotLocalAuth`, with prefix-scoped fictional auth tables and a random
0700 session root owned by the test.

```text
php -l tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
No syntax errors detected
php tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
INTENTIONAL_RED: LocalAuth GET uses configured owner
Expected: 200
Actual: 503
exit=255
```

DB setup and server readiness pass. Current LocalAuth ignores the configured
owner root and attempts its legacy hardcoded native session path, which is
absent and non-creatable in this host contour; the outer fail-closed mapping
returns 503 without creating foreign material.

Subsequent assertions require canonical whole-array anonymous CSRF material,
safe return-to persistence, successful login regeneration with a new cookie and
old-ID invalidation, authenticated actor facts, and logout destruction leaving
no valid ID. The process, prefix tables and owned root are removed in `finally`;
no test root remains after RED.
