# Test rereview: PILOT-SESSION-STORAGE-001 v10 malformed object payload — RED v6

- Gate: 3 — fresh independent test rereview
- Reviewer: separately tasked agent `/root/session_malformed_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, revised test, RED evidence, or production implementation
- Reviewed commit: `05ed978f77f33578de3e978417226a219db7c40a`
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Public seam: raw `POST /pilot/login` through the real production HTTP graph
- Prior reviews: append-only v1–v5 remain unchanged
- Verdict: **CHANGES_REQUESTED**

## Findings

### Resolved since v5

- The requested object-bearing scope is restored. The exact literal is a valid
  canonical whole-array encoding with a matching 64-byte `auth_csrf` scalar and
  an object-valued `x` element.
- An independent PHP probe confirms that `unserialize(...,
  ['allowed_classes' => false])` returns the matching CSRF plus an
  `__PHP_Incomplete_Class`, and byte-identical re-encoding succeeds. Therefore
  only the required recursive value-shape check rejects this fixture.
- A mutant that restores the array without object-shape validation passes CSRF
  and reaches the child-owned MariaDB sentinel. Absence of the marker now
  materially proves rejection before the relevant auth dispatch for this
  contour.
- Evidence hashes/method/scope are consistent and correctly reserve
  trailing/reference/cycle/noncanonical/limit cases for later slices.

### Blocking — newly introduced request secrets are not in the leakage oracle

Section 6 explicitly forbids session data, CSRF, email, and password in the
internal failure output. The revised fixture adds a distinctive secret value
`$csrf = str_repeat('c', 64)` and POST credentials, but the secrecy loop checks
only the complete serialized payload, the `auth_csrf` key, `stdClass`, session
ID, and root.

A separate parser/auth diagnostic that logs only the 64-byte CSRF value, email,
or password—without the serialized framing/key/class—passes the current test.
The exact canonical `PILOT_SESSION_UNAVAILABLE` line does not prevent another
non-session-prefixed diagnostic line from leaking them.

Add the exact CSRF value and distinctive POST email/password values (including
the representation that the real request parser exposes) to the stderr secrecy
oracle. The exact HTTP envelope already prevents application-owned response
leakage, but applying the same known-secret set to both HTTP and stderr is
reasonable and consistent with the existing loop.

## Passing checks

- Traceability, raw seam, real-owner seeding, and independent expected literal
  are correct.
- Full exact 503 envelope, exactly one canonical `payload_invalid` record,
  absence of generic failure, committed-byte equality, and DB-dispatch marker
  absence are sensitive to the approved behavior.
- Fixed owner dependencies, bounded loopback readiness, private task root, and
  `finally` cleanup make the run isolated and deterministic. The independent
  rerun left no task/sentinel residue.
- The intended RED remains the first 503 assertion after successful setup and
  is not caused by fixture, serialization, or external DB availability.

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
ca618a1242d93cccd8c2428f8eb47b5ec7576eb9f6f1eabff20a098bda4f6ee0  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
637bdb25acb39ff9cd59a427fe2f6c3950a646b5607a51852420f5e443b82173  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
```

## Required changes

1. Add the exact CSRF, email, and password values introduced by the POST fixture
   to the diagnostic secrecy assertions.
2. Refresh Gate 2 evidence/hashes and obtain a fresh independent review.

Gate 3 remains **CHANGES_REQUESTED**; no minimal GREEN is authorized.
