# Test rereview: PILOT-SESSION-STORAGE-001 v10 malformed payload — RED v4

- Gate: 3 — fresh independent test rereview
- Reviewer: separately tasked agent `/root/session_malformed_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, revised test, RED evidence, or production implementation
- Reviewed commit: `05ed978f77f33578de3e978417226a219db7c40a`
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Actual public action: raw `POST /pilot/login` through the real production HTTP graph, with malformed bytes committed by the real storage owner
- Prior reviews: append-only v1–v3 remain unchanged
- Verdict: **CHANGES_REQUESTED**

## Findings

### Resolved since v3

- The submitted test and evidence hashes agree.
- The complete set of stderr lines containing `PILOT_SESSION_UNAVAILABLE` must
  now contain exactly one record, and that record must be the canonical
  `payload_invalid` category with a twelve-lowercase-hex correlation.
- HTTP and stderr now exclude the complete payload, its distinctive key/object
  type, session ID, and task root; the generic entrypoint failure remains
  forbidden.
- The child-owned loopback MariaDB sentinel is a real external-boundary
  observation rather than a synthesized dispatcher result. Its process and
  marker are reaped/removed in `finally`.

### Blocking — the DB sentinel does not observe route/auth entry

The normative ordering is failure before **route/auth execution**, not merely
before the first database connection. The submitted POST uses
`csrfToken=fixed`, while the malformed committed array supplies no valid
`auth_csrf`. An implementation can enter the real login/auth path with empty or
unrestored state, reject CSRF before opening MariaDB, then classify/log the
malformed payload and replace the response with the exact 503. The DB marker
remains absent, the committed file remains unchanged, no generic failure is
logged, and every current assertion passes despite route/auth execution having
occurred.

Thus this is a useful **DB-dispatch** sentinel but not the requested
route/auth-entry sentinel. Add an observation at the real production dispatch
boundary that changes immediately on route/auth entry, before CSRF and other
early exits, and require it untouched. It must continue to use the production
graph rather than a test-owned dispatcher or synthesized child claim.

### Blocking — Gate 2 evidence names the wrong HTTP method

The executable tracer sends `POST /pilot/login`, and the DB-sentinel rationale
depends on POST login processing. The evidence's `Public seam` paragraph still
records raw `GET /pilot/login`. Later prose says POST, so the evidence is
internally contradictory. Correct it to POST and refresh its exact hash with
the next Gate 2 run.

## Passing checks

- The serialized literal is independently valid but contains a prohibited
  object value, grounding exact `PAYLOAD_INVALID` without production-derived
  expectations.
- Real-owner seeding and raw HTTP prevent operation-result self-attestation.
- Full section-6 envelope, forbidden/unspecified headers, exact-one safe log,
  response/log secrecy, and addressed-file non-mutation are sensitive and
  correctly asserted.
- Readiness is bounded and material; server, DB sentinel, pipes, marker, and
  private randomized root have deterministic cleanup. The independent RED run
  left no task residue.
- The scope remains the single malformed-object contour rather than the full
  codec matrix.

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

The first failure remains the intended missing 503 after successful owner
commit, child setup/readiness, and raw response collection.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
6edc7f6963cccf3bb680d28923008f24e2ed6783a7fd51105b5438c15d6f5270  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
091a64d5f30dd82388b21676c4d7450388acf6da75ac2e324c3d9d71a55204cc  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
```

## Required changes

1. Replace or supplement the DB-connect marker with a real production
   route/auth-entry sentinel that fires before CSRF/other early rejection.
2. Correct Gate 2 evidence from GET to POST, refresh exact hashes/evidence, and
   obtain a fresh independent review.

Gate 3 remains **CHANGES_REQUESTED**; no minimal GREEN is authorized.
