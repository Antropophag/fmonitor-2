# PILOT-SESSION-STORAGE-001 v10 malformed payload — Gate 2 RED

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
342833c29488684837495d9c85289fef3f7ae18a78ad90486c4c17ae6a762d6f  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
```

Public seam: raw `POST /pilot/login` through
`ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies`,
using a valid cookie whose bytes were committed by the real storage owner.

Command:

```text
php tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
```

Observed exit `255`, intended RED:

```text
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: malformed payload fails closed
Expected: true
Actual: false
```

The real owner successfully generated the session ID, committed the independent
literal whole array containing valid `auth_csrf` plus an object-valued element,
closed, and the parent
confirmed exact committed bytes before starting the real HTTP graph. Server
readiness and response parsing succeeded. The first failed assertion requires
HTTP 503; current code instead ignores the returned payload and proceeds.

Later assertions independently require the complete exact section 6 header
envelope and body, absence of every forbidden/unspecified application header,
no login form, no payload/parser/category leakage, byte-identical committed
material. A positive readiness flag is set only after an actual loopback socket
connection while the child remains running. The test does not decode the payload, invoke a test-owned dispatcher,
construct an owner result or infer success from events. Its task-owned root,
server and pipes are cleaned in `finally`.

The production-server child runs with a bounded 32 MiB memory limit and the
client read has a three-second timeout. These bounds do not affect the object
case result and make a recursive-decoder regression fail deterministically.

The child stderr MUST contain exactly one safe
`PILOT_SESSION_UNAVAILABLE category=payload_invalid correlation_id=<12hex>`
line and no generic entrypoint failure or parser diagnostic. A child-owned
loopback TCP sentinel stands in for the external MariaDB boundary; the POST
login request must leave it unconnected, proving auth dispatch did not run.
The test also rejects any occurrence of the complete payload, payload key,
object type, CSRF, email, password, session ID or task root in HTTP/stderr. The POST
body supplies the exact CSRF value from the valid scalar element, so a decoder
that omits the required object-shape rejection necessarily advances authentication to the
MariaDB sentinel rather than stopping at an early CSRF rejection. This is the
canonical §6 internal classification evidence; the HTTP response itself remains
redacted. The rejected literal, exact log, pre-dispatch envelope, absence of
route output and zero-mutation material jointly prove the boundary.

This tracer covers object-bearing malformed payload at the real HTTP seam.
The remaining accepted round trip, reference/cycle/trailing/noncanonical and
limit matrix remains a subsequent RED slice.
