# Test rereview: PILOT-SESSION-STORAGE-001 v10 malformed payload — RED v2

- Gate: 3 — fresh independent test rereview
- Reviewer: separately tasked agent `/root/session_malformed_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, revised test, RED evidence, or production implementation
- Reviewed commit: `05ed978f77f33578de3e978417226a219db7c40a`
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Public seam: raw `GET /pilot/login` through the real `ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies(...)` graph, with a valid cookie addressing bytes committed by the real storage owner
- Prior review: `reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v1.md` remains unchanged append-only history
- Verdict: **CHANGES_REQUESTED**

## Findings

### Resolved from v1

- Readiness now becomes true only after a successful loopback connection, and
  the test separately requires the child still to be running.
- The test now parses the raw response and asserts the complete application-owned
  section-6 envelope: every exact required header/value, exact body, forbidden
  headers, and absence of unspecified application headers while allowing the
  identified transport headers.
- The object-bearing whole-array literal remains independently valid and is
  seeded through the real owner. Exact committed bytes are checked before and
  after HTTP execution.
- Payload/class text and the internal category are explicitly excluded from the
  public response. This correctly protects the public secrecy boundary.

### Blocking — RED evidence fingerprints the wrong test

The revised test hash is
`3510561c4e372755a0c0c6e5fb45cc59a74f85c7ddd48145fe5e7b7498fd217b`,
but the evidence's reviewed-hash block still records the superseded v1 hash
`17e05568...`. Gate 2 evidence therefore does not fingerprint the artifact
submitted for Gate 3. Update the evidence to the revised exact hash and rerun
the command before another review.

### Blocking — the evidence misstates the normative logging contract

The evidence says the normative log is optional (`MAY`). The approved
specification does not say that. Section 6 says unconditionally, “Log exact
once: `PILOT_SESSION_UNAVAILABLE category=<safe-enum>
correlation_id=<12hex>`,” then requires correlation/category to remain internal
to the response. The only nearby `MAY` permits the local CLI server to echo one
outer-validated SAPI Host header.

An internal diagnostic assertion does not contradict the prohibition on HTTP
exposure. The child stderr pipe is already an observable production output and
is currently drained and discarded. For this exact rejected-case slice,
requiring the exact single safe log record both checks the mandatory section-6
acceptance statement and distinguishes `PAYLOAD_INVALID` from another
unavailable category. It should also reject payload/session/path/class/exception
leakage in that diagnostic material. The public-response assertion that
`PAYLOAD_INVALID` is absent remains correct and should stay.

### Blocking — absence from the replaced response does not prove no dispatch

`!str_contains($body, '<form')` proves that the final response does not leak the
login form. It does not prove that login route/auth dispatch never occurred:
an implementation may dispatch, discard the produced form, and then emit the
same exact 503. Unchanged committed bytes only excludes mutation of this
addressed file; it likewise does not observe route/auth invocation.

Add a real-public-seam sentinel that changes if route/auth dispatch is entered
and remains unchanged for the malformed-object request. The contract forbids a
test-owned dispatcher or synthesized child claim, so the sentinel must observe
the real production graph. Keep this narrowly scoped to malformed object decode
before dispatch; it need not expand into the remaining codec matrix.

## Passing Gate 3 checks

- Traceability is exact to v10 sections 3, 6, and 8 and to the approved
  malformed-object rejected case.
- The raw public HTTP seam and real-owner seeding prevent result-DTO or owner
  self-attestation.
- The fixed literal
  `a:1:{s:12:"auth_user_id";O:8:"stdClass":0:{}}` has a correct 12-byte key
  length and a serialized object value. `allowed_classes => false` does not
  turn that value into an approved scalar/array shape, so `PAYLOAD_INVALID` is
  the independently determined expected category.
- The complete revised HTTP assertions are sensitive to partial response
  implementations, forbidden response leakage, route-body leakage, and
  committed-material mutation.
- Fixed clock/entropy, private randomized task root, bounded server startup,
  and `finally` cleanup make the exercised path isolated. The independent
  rerun left no task directory behind.
- Scope remains the minimal malformed-object contour. No claim is made for the
  accepted round trip or reference/cycle/trailing/noncanonical/limit cases.

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

The failure occurs after real-owner commit, exact-byte precondition, successful
child readiness, and raw request/response collection. It remains an intended
missing-behavior RED rather than setup failure.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
3510561c4e372755a0c0c6e5fb45cc59a74f85c7ddd48145fe5e7b7498fd217b  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
b3a4f16eb30b9cd1bcb02e25e080ad573d445a3e72fa58d2b85dfcff9a879248  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
ca7a627dfe792d7c656b2002b9f0d98b4f5bc89e541fc4387ef0ed8755ca7f6f  reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v1.md
```

## Required changes

1. Refresh Gate 2 evidence with the revised test hash and reproduced output.
2. Correct the false `MAY` statement and assert the mandatory exact-once safe
   `payload_invalid` diagnostic through the real child's internal log channel,
   without exposing it over HTTP.
3. Add a real-production-graph sentinel that detects any route/auth dispatch.
4. Obtain a fresh independent Gate 3 review for the new exact hashes.

Gate 3 remains **CHANGES_REQUESTED**; no minimal GREEN is authorized.
