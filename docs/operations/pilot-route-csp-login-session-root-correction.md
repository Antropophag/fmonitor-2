# PILOT-ROUTE-CSP-001 — session-owner fixture correction

Date: 2026-09-03

Before correction, the unchanged CSP/login verifier stopped with
`SETUP_FAILURE: login cookie missing` because its isolated server environment
did not include the now-required session owner root/instance/scheme.

The helper now creates one random 0700 root below the short-lived test parent,
passes explicit `FMONITOR_SESSION_STATE_ROOT`, instance and trusted `http`
scheme, and recursively removes only that returned root in `prclStop`.
Assertions and production code are unchanged.

```text
c5c3886805fe5050ea48e6dd2a1be0cf79312308eb75d3350d2dee9078118301  tests/InstallationProcess/pilot_route_csp_login_001_test.php

pilot_route_csp_login_001_test: PASS
exit=0
CLEANUP_OK
```

This is test setup evidence, not a new product behavior or a claim of full
session Gate 5.
