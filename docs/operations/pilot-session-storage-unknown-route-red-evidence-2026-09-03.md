# PILOT-SESSION-STORAGE-001 v9 unknown-route Gate 2 RED

- Specification SHA-256: `7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30`
- Test SHA-256: `c58d4e21cafe91f1e589deb3d4c413ce5528ed2367df1403b43cea01967ec30c`
- Command: `FMONITOR_DB_HOST=127.0.0.1 FMONITOR_DB_PORT=23306 FMONITOR_DB_NAME=fmonitor2_test FMONITOR_DB_USER=fmonitor2_test FMONITOR_DB_PASSWORD=<REDACTED> php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php`
- Exit: `255`

```text
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: unknown non-asset rejected before invalid storage
Expected: 404
Actual: 503
```

The same server first proves known/unknown assets bypass invalid session configuration. The new unknown non-asset assertion then receives 503 because production still enters session configuration. Expected 404 is independently inherited from `PILOT-HTTP-AUTH-001`; no setup component failed.
