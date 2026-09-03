# Test review: PILOT-SESSION-STORAGE-001 v10 malformed payload — RED v1

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/session_malformed_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, test, RED evidence, or production implementation
- Reviewed commit: `05ed978f77f33578de3e978417226a219db7c40a`
- Specification: `PILOT-SESSION-STORAGE-001` v10, exact hash owner-approved for Gate 2 in `docs/operations/pilot-session-storage-v10-payload-owner-decision-2026-09-03.md`
- Public seam: raw `GET /pilot/login` through the real `ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies(...)` graph, with a valid cookie addressing bytes committed by the real storage owner
- Red command and intended failure: `php tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php`; intended missing malformed-payload fail-closed behavior at the first HTTP 503 assertion
- Verdict: **CHANGES_REQUESTED**

## Findings

### Blocking — the tracer does not prove `PAYLOAD_INVALID`

The approved slice is specifically malformed whole-array decode to the closed
`PAYLOAD_INVALID` category. The test observes only a generic HTTP 503 subset.
Any storage/configuration/read/close failure that happens to map to 503 would
satisfy all current response assertions, so the test cannot distinguish the
approved codec rejection from an unrelated unavailable path.

Capture the real server's diagnostic channel and require exactly one safe log
record with category `payload_invalid` and a twelve-lowercase-hex correlation,
while also checking that payload/session/path/class/exception details do not
leak. This must remain observation of the real HTTP graph, not a test-constructed
operation result or dispatcher.

### Blocking — “exact 503” is only partially asserted

Section 6 specifies the complete unavailable response contract. The test checks
the status-code prefix, body, `Retry-After`, and absence of `Set-Cookie` and
`Location`, but omits exact `Content-Type`, `Content-Length`, all required
security/cache headers, and the forbidden response headers. A partial or
malformed 503 therefore passes. Assert the complete application-owned envelope
(allowing only the explicitly permitted SAPI-added outer header behavior).

The byte-identical committed-file assertion is useful: it would catch a write
or deletion of the addressed material. It does not compensate for the partial
HTTP assertion.

### Blocking — pre-dispatch rejection is not observable

The contract requires decode failure before route/auth execution. A GET of the
login page has no asserted dispatch sentinel. Production could dispatch the
route and only afterwards replace its response with the generic 503 while
leaving the committed file unchanged, and this test would pass. Add one
production-public-seam observation that distinguishes “rejected before
route/auth dispatch” from “dispatched then overwritten”; do not introduce a
test-owned dispatcher or private-method assertion. Scope this sentinel only to
the one malformed-object contour.

### Blocking — readiness can report success without a successful probe

The readiness loop closes a successful `$probe`, then the post-loop condition
checks only that `$probe` is set and the server process is running. If every
connection attempt fails, `$probe` is still set to `false`, yet readiness is
accepted. The following request may then fail as `SETUP_FAILURE`, making the
test timing-sensitive. Record an explicit successful-probe boolean and require
it after the bounded loop.

### Passing checks

- The specification hash is the exact owner-approved v10 hash and the cited
  acceptance statements are within the requested one-contour scope.
- The raw request crosses the real production HTTP construction seam.
- Seeding uses `PilotSessionStorageFactory`, anonymous `start`, and
  `writeCommit`; the test does not fabricate a result. The direct file reads
  are limited to setup/material-integrity assertions rather than the HTTP
  success oracle.
- The fixed literal
  `a:1:{s:12:"auth_user_id";O:8:"stdClass":0:{}}` is syntactically valid PHP
  whole-array serialization: `auth_user_id` has the declared length 12 and the
  value is an object. With `allowed_classes => false` it still decodes to an
  object-shaped value and is therefore rejected by the approved scalar/array
  grammar. The expected rejection is independently fixed, not calculated by
  production code.
- The task root is private and randomized, clock and owner entropy are fixed,
  and `finally` terminates the server, drains/closes pipes, and removes the task
  root. The independent rerun left no task directory behind.
- The RED is intended once setup succeeds: the owner start/commit assertions,
  exact-byte precondition, server startup, raw response parsing, and client
  request all precede the first failure. Current production returns a non-503
  response because it ignores the handed-off payload.
- The test correctly does not claim the accepted round trip or the remaining
  reference/cycle/trailing/noncanonical/limit codec matrix.

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
17e05568a1fc09331562a78e3a330555d85971ff3a4d8560288b72b8a95b7251  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
466892962e9678c5a7d47fb1971f546bc9da43d41e1395962090f38b12134be3  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
```

## Required changes

1. Make the exact `PAYLOAD_INVALID` category and safe single diagnostic record observable.
2. Assert the complete exact section-6 503 response envelope and forbidden headers.
3. Add a real-public-seam sentinel proving rejection precedes route/auth dispatch.
4. Fix readiness to require an actually successful connection probe.
5. Rerun Gate 2, refresh exact hashes/evidence, and obtain a fresh independent Gate 3 review.

No production implementation is authorized by this review. Gate 3 returns the
tracer to Gate 2 with **CHANGES_REQUESTED**.
