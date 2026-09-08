# Independent Gate 3 test review — PILOT-SESSION-STORAGE-001 v9 pure route recognition RED v4

- Date: 2026-09-04
- Reviewer: separately tasked agent `/root/object_list_cleanup_gate3`
- Independence: reviewer did not author the specification, test, RED evidence,
  prior implementation, or Gate 5 finding
- Reviewed RED commit: `c8aefad3cbf294dbb92095281b2a7e158d37298d`
- Gate 1 authority:
  `docs/operations/pilot-session-storage-gate1-rereview-v12.md` — `APPROVED`
- Trigger: append-only Gate 5 finding
  `reviews/code/PILOT-SESSION-STORAGE-001-v9-unknown-route.md`
- Public seam: raw anonymous HTTP `POST
  /pilot/objects/1/checklist/operations`
- Verdict: **APPROVED**

## Reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
d535ef0d8ea2856f0404e8adb56f01d743d261c65d1ea83cd33dd6aa1d2f8e82  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
da1c760f5e22b515bd4a42f9f112e276c98e9c29c9294f2bee6ba62bb42eccf8  docs/operations/pilot-session-storage-unknown-route-red-evidence-v4-2026-09-04.md
60d28e21686fc0141c140f7c888e38207bd31522ee567aa7e7caeb82c6e477db  reviews/code/PILOT-SESSION-STORAGE-001-v9-unknown-route.md
```

## Review result

No blocking findings.

The corrective tracer closes the exact sensitivity gap identified at Gate 5.
It sends the independently fixed JSON bytes `{"itemId":42}` to an existing
known checklist-operation route through the real raw-HTTP entrypoint while
session configuration is deliberately invalid. Under the approved ordering,
route recognition may establish only that the route is known; authentication
and session admission then fail closed with the section 6 response. The
expected public observation is therefore exact status/body pair
`[503, "Service unavailable.\n"]`.

The current response is status 503 followed by the checklist business
rejection JSON concatenated with `Service unavailable.\n`. This demonstrates
that the pre-authentication recognition step invoked the body-consuming,
response-emitting command handler. The failure is observable entirely at the
public HTTP seam. It does not depend on a private method name, call count,
planned predicate API, filesystem layout, or implementation exception.

## Traceability and oracle quality

- **Contract traceability:** section 7 places outer Host/URI validation and
  asset routing before session concerns, resolves unknown pilot routes before
  authentication, and permits only known login-required routes to proceed to
  login/session behavior. Section 6 independently fixes the exact unavailable
  status and body. The Gate 5 finding requires a pure recognition probe for a
  known command route; the new tracer exercises exactly that boundary.
- **Expected-value independence:** `503` and `Service unavailable.\n` are
  literals from the owner-approved failure contract. The oracle does not derive
  them from current production output. `itemId=42` is only the sensitivity
  stimulus that makes premature command execution externally visible.
- **Sensitivity:** the reviewed pre-GREEN implementation emits business bytes
  before authentication and the exact-body assertion fails. A correction that
  merely overwrites the final status, or that still invokes the handler during
  recognition, cannot pass. A route predicate may be implemented or named in
  any way so long as recognition has no public command effect.
- **Determinism and isolation:** the request uses a verifier-owned loopback
  server and fixed request bytes. Empty `FMONITOR_SESSION_STATE_ROOT` produces
  the deterministic configuration-unavailable branch. The outer `finally`
  stops the server; fresh review execution left no matching process or session
  temp directory.
- **Existing controls:** before reaching the new RED, the same run proves known
  asset success, unknown-asset 404, unknown non-asset exact 404, malformed Host
  400, and malformed URI 400. The unchanged remainder retains known login
  GET/HEAD/POST unavailable behavior, fault-path buffering, and HTTP/HTTPS
  authenticated-cookie unknown-route controls. Thus an unconditional early
  404, broad asset bypass, or reordering of outer validation cannot satisfy the
  complete approved test.
- **Rejected/setup classification:** the loopback server starts and all earlier
  controls pass. The failure compares the exact returned status/body at the new
  assertion; it is neither broken setup nor a private diagnostic assertion.

## Fresh independent RED

At exact reviewed HEAD:

```text
$ git rev-parse HEAD
c8aefad3cbf294dbb92095281b2a7e158d37298d

$ php -l tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_protocol_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: known command admission reaches authentication before body handling
Expected: array (
  0 => 503,
  1 => 'Service unavailable.
',
)
Actual: array (
  0 => 503,
  1 => '{"status":"rejected","message":"Последние 15% закрываются актом ПТО и декларацией в карточке объекта."}Service unavailable.
',
)
exit 255

$ git diff --check c8aefad^..c8aefad
exit 0
```

Post-run inspection found no matching PHP loopback server process and no fresh
`fmonitor2-session-http-*` temporary directory.

## Gate consequence

Gate 3 is **APPROVED** for the exact reviewed hashes. Minimal Gate 4 may replace
the side-effecting admission call with pure known-route recognition while
keeping command body handling after authentication and preserving every
existing priority/control. Any change to the reviewed expectation or seam
returns to Gate 2. This review does not approve production implementation or
supersede the required fresh Gate 5 review.
