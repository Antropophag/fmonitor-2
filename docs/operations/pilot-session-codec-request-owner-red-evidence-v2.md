# PILOT-SESSION-STORAGE-001 v10 codec/request owner — Gate 2 RED v2

Date: 2026-09-03

```text
42253d6c3ca1dd5013795dd152526de803b37d648bb40ef1f5d1ab9b019f41b7  tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
```

The corrected codec matrix accepts exact nesting depth 16 and exactly 4096
entries both flat and recursively totalled, rejects the next boundary, and
covers every allowed leaf kind plus float/object/reference/cycle on checked
encode. Exact canonical bytes remain independently produced by PHP `serialize`.

The request-owner branch now constructs the first graph through
`ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies`
with one distinguishable port set, then invokes the same public factory with a
second set and requires fail-closed `LogicException` instead of silently
returning a graph wired to the first dependencies.

```text
php tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
float rejected
Expected: NULL
Actual: ['float' => 1.5]
exit=255
```

The intended RED remains at the first forbidden scalar after canonical and
trailing-byte controls. No production file changed; v1 evidence/review remain
append-only.
