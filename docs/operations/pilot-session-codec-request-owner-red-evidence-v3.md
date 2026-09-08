# PILOT-SESSION-STORAGE-001 v10 codec/request owner — Gate 2 RED v3

Date: 2026-09-03

```text
f9088b72e6c3cc3c7f42ee20527cb3e464afced7019c85cacc0f66deae18429f  tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
```

The encode matrix now requires exact canonical success at depth 16, flat 4096
entries and recursively totalled nested 4096 entries, complementing the
existing one-over negative boundaries. All other v2 decode/encode and public
factory conflict assertions are unchanged.

```text
php tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
float rejected
Expected: NULL
Actual: ['float' => 1.5]
exit=255
```

The intended RED remains the forbidden float after valid decode/canonical
controls. Production is unchanged; v1/v2 records remain append-only.
