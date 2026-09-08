# PILOT-SESSION-STORAGE-001 v9 unknown-route Gate 2 RED v2

- Specification SHA-256: `7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30`
- Session test SHA-256: `a7ffbccf465e3f95999f079e969cc561d85c8a2cc7625814bb8b967fdf7355d9`
- Companion HTTP-priority test SHA-256: `0c8074ed4548f34fc12e7c3f6a4a30458939f0726caaa78d7b21c3b1b4b1c118`
- Command: `FMONITOR_DB_HOST=127.0.0.1 FMONITOR_DB_PORT=23306 FMONITOR_DB_NAME=fmonitor2_test FMONITOR_DB_USER=fmonitor2_test FMONITOR_DB_PASSWORD=<REDACTED> php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php`
- Exit: `255`

The test now checks exact 404 body/content length/security headers and forbids cookie/location/auth headers. Positive HTTP/HTTPS cases resend the obtained cookie, proving authenticated coverage. The companion approved HTTP test independently proves the unknown route calls only correlation/close and reads zero environment/dependencies. Production remains RED at `503` before the first exact assertion.
