# Test rereview: PILOT-SESSION-STORAGE-001 v10 malformed object payload — RED v5

- Gate: 3 — fresh independent test rereview
- Reviewer: separately tasked agent `/root/session_malformed_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, revised test, RED evidence, or production implementation
- Reviewed commit: `05ed978f77f33578de3e978417226a219db7c40a`
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Actual public action: raw `POST /pilot/login` through the real production HTTP graph
- Prior reviews: append-only v1–v4 remain unchanged
- Verdict: **CHANGES_REQUESTED**

## Findings

### Resolved since v4

- Gate 2 evidence now correctly identifies `POST /pilot/login` and fingerprints
  the submitted artifact.
- A matching 64-character CSRF value is present in both the serialized prefix
  and POST body. For the submitted trailing-data contour, a decoder that accepts
  the prefix reaches the child-owned fake MariaDB boundary, so the absent marker
  materially distinguishes rejection before auth/DB work from the previously
  identified CSRF-before-DB mutant.
- Exact response, single canonical internal log, diagnostic secrecy,
  committed-byte integrity, readiness, and cleanup controls remain sound.

### Blocking — the reviewed rejected case was replaced rather than repaired

The explicitly assigned scope for this Gate 3 review is the minimal
**malformed-object decode** contour. The submitted v5 literal is instead a
valid scalar whole-array encoding followed by `TRAILING` bytes. The evidence
accordingly says it covers trailing data and moves object payloads into the
remaining matrix.

That is a different normative rejected case. Earlier records consistently
reserved trailing/noncanonical/reference/cycle/limit cases for later RED
slices; changing to trailing bytes does not establish sensitivity of the
requested object-valued payload slice. Gate 3 cannot authorize a substituted
acceptance example merely because it shares the same public 503 outcome.

Retain the useful CSRF/DB sentinel but use a valid whole-array serialization
containing both the matching `auth_csrf` scalar and an additional object-valued
element that the application path ignores. A codec mutant that unserializes and
restores that array without applying the approved object-shape rejection then
passes CSRF and reaches the DB sentinel, while the correct codec rejects before
dispatch. Expected secrecy assertions should return to object/payload material.

## Passing checks

- The current trailing literal is independently grounded and would be a valid
  future tracer for byte-identical re-encoding/trailing-data rejection.
- Real-owner commit, raw HTTP, exact section-6 envelope, exact-one
  `payload_invalid` log, and the child-owned network sentinel avoid synthetic
  result or dispatcher assertions.
- The intended RED is independently reproducible after successful setup; no
  task root, marker, or sentinel child remains.
- Test and evidence hashes agree, and the evidence accurately describes the
  submitted POST/trailing contour.

## Independent RED reproduction

```text
$ php -l tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
PHP Fatal error:  Uncaught TestFailure: INTENTIONAL_RED: malformed payload fails closed
Expected: true
Actual: false in tests/bootstrap.php:36
...
EXIT=255
```

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a6d7c126512edaa86914be7845b7b7623f5eb0498caa41c79bdbac256ba61889  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
6a9d716bb4901a324c429dee07e891b55810a8b8746a8f0c4677eb96a293b9af  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
```

## Required changes

1. Restore the assigned object-bearing rejected case while preserving the
   matching CSRF and DB-dispatch sensitivity.
2. Refresh Gate 2 evidence/hashes for that exact object contour and obtain a
   fresh independent review.

Gate 3 remains **CHANGES_REQUESTED**; this review does not authorize GREEN for
either the substituted trailing-data case or the requested object case.
