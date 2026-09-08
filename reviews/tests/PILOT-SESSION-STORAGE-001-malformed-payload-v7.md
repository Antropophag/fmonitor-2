# Test rereview: PILOT-SESSION-STORAGE-001 v10 malformed object payload — RED v7

- Gate: 3 — fresh independent test rereview
- Reviewer: separately tasked agent `/root/session_malformed_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, revised test, RED evidence, or production implementation
- Reviewed commit: `05ed978f77f33578de3e978417226a219db7c40a`
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Public seam: raw `POST /pilot/login` through the real production HTTP graph, with a valid cookie addressing malformed bytes committed by the real storage owner
- Prior reviews: append-only v1–v6 remain unchanged finding history
- Verdict: **APPROVED**

## Findings

No blocking findings.

### Traceability and bounded scope

The test cites v10 sections 3, 6, and 8 and proves one rejected case: a valid
whole-array serialization containing an object value is classified
`PAYLOAD_INVALID`, rejected before auth/DB dispatch, and mapped to the exact
redacted 503 without committed-material mutation. This is the requested minimal
malformed-object contour. It does not claim the accepted round trip or the
reference/cycle/trailing/noncanonical/limit matrix.

### Public seam and real-owner seeding

The fixture creates the real storage owner through
`PilotSessionStorageFactory`, obtains an anonymous ID, commits the exact opaque
bytes with `writeCommit`, closes the owner, and addresses those bytes through a
raw cookie on the real production HTTP factory/router. It neither constructs an
operation result nor supplies a test-owned dispatcher. Direct committed-file
reads establish only setup and zero-mutation material facts; HTTP, diagnostic,
and external-boundary observations remain the rejection oracle.

### Independent rejected literal and sensitivity

The literal contains a canonical matching `auth_csrf` string and an
object-valued `x` element. Independent probing confirms
`unserialize(..., ['allowed_classes' => false])` yields the exact CSRF and an
`__PHP_Incomplete_Class`, and re-encoding is byte-identical. Thus a codec that
only unserializes/re-encodes but omits recursive object-shape validation accepts
the payload, passes CSRF, and connects to the child-owned fake MariaDB boundary.
The correct codec rejects first and leaves the marker absent. This closes the
plausible dispatch-before-validation mutant without expanding the codec matrix.

### Exact response, category, material, and secrecy

The raw response must contain the exact status/body and every required
section-6 header, no forbidden header, and no unspecified application header.
The committed bytes must remain identical. Captured stderr must contain exactly
one total `PILOT_SESSION_UNAVAILABLE` record, exactly category
`payload_invalid` with a 12-lowercase-hex correlation, and no generic
entrypoint failure.

Both HTTP and stderr reject the complete payload, key/type fragments, literal
64-byte CSRF, decoded email, password, session ID, and task root. This covers
the complete known secret set introduced by the fixture while preserving the
required internal-only category boundary.

### Determinism, setup, readiness, and cleanup

Owner clock and entropy are fixed. Random suffixes isolate the private task root
and marker without changing expectations. HTTP readiness requires an actual
successful loopback connection and a live child. The DB sentinel is a bounded
child on an ephemeral loopback port. `finally` terminates/reaps the HTTP and DB
children, drains/closes pipes, removes the marker and task root, and removes the
empty shared parent. The independent rerun left no residue.

## Intended RED independently reproduced

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

The owner start/commit, exact-byte precondition, both child setups, HTTP
readiness, request, and response collection all precede the first failed 503
assertion. The RED is therefore missing codec/consumer behavior, not malformed
fixture setup or an unavailable external system.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
7db8e4d6758afc0ea6561e536bce500fc09e57f5dbf48e88dd9a741c9013a5e5  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
a0b52d4880c36c18ffccb32f5e4670d5faa9da207dced4c0bbb2191ff405ed51  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
```

## Required changes

None.

## Authorized minimal GREEN

Gate 3 authorizes only the production HTTP adapter codec/consumer plumbing
needed for this exact canonical whole-array object-bearing payload to:

1. reject the recursively reachable object as `PAYLOAD_INVALID` before auth/DB
   dispatch or session write;
2. emit the exact section-6 503 and exactly one safe internal category record;
3. preserve the committed bytes and redact all tested secret material.

It does not authorize implementing or claiming coverage of the accepted round
trip, references/cycles, trailing/noncanonical encodings, depth/entry limits,
write-side shape validation, other routes/consumers, or unrelated refactoring.
Any change to reviewed expectations restarts Gate 2.

Gate 3 is **APPROVED**.
