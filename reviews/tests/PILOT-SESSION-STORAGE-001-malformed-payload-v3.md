# Test rereview: PILOT-SESSION-STORAGE-001 v10 malformed payload — RED v3

- Gate: 3 — fresh independent test rereview
- Reviewer: separately tasked agent `/root/session_malformed_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, revised test, RED evidence, or production implementation
- Reviewed commit: `05ed978f77f33578de3e978417226a219db7c40a`
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Public seam: raw `GET /pilot/login` through the real production HTTP graph, using a valid cookie for malformed bytes committed by the real storage owner
- Prior reviews: append-only v1 and v2 remain unchanged
- Verdict: **CHANGES_REQUESTED**

## Findings

### Resolved since v2

- The Gate 2 evidence now fingerprints the submitted test hash exactly and its
  independently reproduced RED remains the intended first HTTP 503 failure.
- The evidence now correctly treats section 6 logging as mandatory rather than
  optional.
- Child stderr is observed through the real process boundary and requires a
  canonical `payload_invalid` record while HTTP still excludes the category.
- The previously accepted full response-envelope, real-owner seeding,
  readiness, literal validity, material-integrity, isolation, and cleanup
  checks remain present.

### Blocking — no observation distinguishes pre-dispatch from dispatch-then-discard

The added `payload_invalid` log proves that the decoder classified the literal.
It does not prove when classification occurred relative to route/auth dispatch.
An implementation can invoke the real login route, discard its generated form,
then decode/log `payload_invalid` and emit the exact 503. That implementation
passes the no-form final-body assertion, the exact response, the log assertion,
and the unchanged-file assertion. GET login rendering need not mutate the
committed file or emit `pilot_http_unexpected_failure`.

Therefore the current assertions are not sensitive to the normative “decode
failure occurs before route/auth execution” ordering. Add a real production
graph observation that changes on entry to route/auth dispatch and require it
to remain untouched. It must not be a test-owned dispatcher or synthesized
child claim. This remains one malformed-object contour and does not require the
rest of the codec matrix.

### Blocking — “exactly one safe log” and diagnostic secrecy are under-asserted

`preg_match_all` currently counts exactly one line matching the desired
category, but it permits additional `PILOT_SESSION_UNAVAILABLE` lines with a
different category or malformed correlation. Require the complete set of
session-unavailable records for the request to equal the single canonical
payload-invalid record.

The secrecy assertion rejects only `stdClass`. A parser diagnostic containing
the literal/key without that class token, or a record leaking session ID/root,
would pass despite section 6 and the evidence's “no parser diagnostic” claim.
Assert absence of the committed payload (and distinctive fragments such as
`auth_user_id`), session ID, task root/path, and other request/session material
available to this fixture in both raw response and captured diagnostic output.

## Passing checks

- Exact traceability to the approved v10 hash and the malformed-object rejected
  case is established.
- The literal
  `a:1:{s:12:"auth_user_id";O:8:"stdClass":0:{}}` is valid whole-array PHP
  serialization but invalid under the approved scalar/array shape grammar.
- The real owner generates the ID and commits the literal; the test does not
  synthesize an operation result or dispatch implementation.
- Complete section-6 response headers/body and forbidden/unspecified headers
  are checked at raw HTTP, with only identified transport headers allowed.
- The revised RED occurs after successful setup and is independently
  reproducible. Task-root and child cleanup leave no residue.
- Scope remains only malformed object decode, not the accepted path or the
  remaining codec rejection matrix.

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
51ed24c99085f1e1666f6428b42d238ecafce10d549e0649caa03dd2632a0eca  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
012650359d1074f80a75773a5ee17ae27f4c1ae32186f7394514f85504c31f3f  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
ca7a627dfe792d7c656b2002b9f0d98b4f5bc89e541fc4387ef0ed8755ca7f6f  reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v1.md
73e0a1ee77e94ec0b91d004669792ba140fc73883aaf80bc16f21b318c1dd4c3  reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v2.md
```

## Required changes

1. Add a non-self-attested real-graph sentinel proving route/auth dispatch was
   never entered.
2. Require the entire request's session-unavailable log set to be exactly one
   canonical `payload_invalid` record.
3. Strengthen response/log secrecy checks for the known payload, key, session
   ID and task-root/path material, refresh Gate 2 hashes/evidence, and obtain a
   fresh independent review.

Gate 3 remains **CHANGES_REQUESTED**; no minimal GREEN is authorized.
