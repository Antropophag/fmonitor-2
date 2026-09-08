# PILOT-SESSION-STORAGE-001 v10 codec/request owner — Gate 2 RED

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
157f6750d4e42ce1dd5cec0eaa55d1a66ebfc1d83709c7b32a3fdb17134e73c8  tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
```

Public interfaces: `PilotSessionPayloadCodec` and the request-scoped
`PilotSessionRequestOwner` binding used by both production factory methods.

```text
php tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
float rejected
Expected: NULL
Actual: ['float' => 1.5]
exit=255
```

Valid canonical state and trailing-byte rejection pass first. The intended RED
is the first forbidden scalar. Subsequent independent literals require depth
17 and 4097 entries to fail, checked canonical encode symmetry, and a
`LogicException` rather than silent dependency reuse when a second distinct
request owner is bound. No production file changed for this RED.
