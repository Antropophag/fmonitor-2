# PILOT-SESSION-STORAGE-001 v9 route-recognition Gate 2 RED v4

- Recorded at: `2026-09-04T00:31:25+03:00`
- Base HEAD: `d6619b9474eeecf7b3f8d94248c8510789d305ee`
- Trigger: append-only Gate 5 finding
  `reviews/code/PILOT-SESSION-STORAGE-001-v9-unknown-route.md`
- Specification SHA-256:
  `7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30`
- Test SHA-256:
  `d535ef0d8ea2856f0404e8adb56f01d743d261c65d1ea83cd33dd6aa1d2f8e82`

Public seam: raw anonymous HTTP `POST` to the known
`/pilot/objects/1/checklist/operations` route with the independently specified
JSON body `{"itemId":42}` while the session configuration is invalid. Route
recognition must classify the path without executing command behavior; local
authentication must then fail closed with the exact existing `503` body.

Command:

```text
php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

Observed intended RED (exit `255`):

```text
INTENTIONAL_RED: known command admission reaches authentication before body handling
Expected: [503, "Service unavailable.\n"]
Actual: [503, "{\"status\":\"rejected\",\"message\":\"Последние 15% закрываются актом ПТО и декларацией в карточке объекта.\"}Service unavailable.\n"]
```

The status is ultimately overwritten to `503`, but the pre-authentication
completion handler has already read the payload and emitted its business JSON.
The oracle therefore fails on exact public response bytes, not on setup or a
private call count. Existing unknown-route, asset, Host and URI controls remain
in the same executable test.
