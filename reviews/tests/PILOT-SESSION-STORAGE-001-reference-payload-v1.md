# Test review: PILOT-SESSION-STORAGE-001 v10 reference payload — RED v1

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/session_malformed_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, shared harness, wrapper, RED evidence, or production implementation
- Reviewed commit: `05ed978f77f33578de3e978417226a219db7c40a`
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Public seam: reference wrapper selecting a parent-process fixture, then raw `POST /pilot/login` through the real production HTTP graph with bytes committed by the real owner
- Existing regression: changed shared harness's default object contour
- Verdict: **APPROVED**

## Findings

No blocking findings.

### Traceability and scope

The wrapper proves one section-3 rejected case: a recursively reachable PHP
reference is rejected as `PAYLOAD_INVALID` before recursive traversal and
before auth/DB dispatch, producing the exact section-6 503. Scope is limited to
the self-reference `R:1` contour. It does not claim other aliases/cycles,
accepted round trip, trailing/noncanonical encodings, limits, or write-side
validation.

### Shared harness and object regression

The common harness accepts only parent-test selector values `object` and
`reference`; default remains `object`. The separate production server receives
the normal explicit dependencies and no behavioral test selector. The other
shared changes add a bounded 32 MiB server memory limit and three-second client
read timeout.

The previously approved object case remains materially unchanged and passes:

```text
PASS: PILOT-SESSION-STORAGE-001 v10 object payload raw HTTP
OBJECT elapsed=0.12 exit=0
```

### Literal validity and sensitivity

The exact valid whole-array literal contains the matching 64-byte `auth_csrf`
and `x` encoded as `R:1`, a self-reference to the root array. An independent
probe confirmed the decoded CSRF matches and
`ReflectionReference::fromArrayElement($decoded, 'x')` is non-null.

The reference must be detected on the element before descending into its
value. Current production recursively follows it until the bounded child fails
instead of returning the exact 503. Missing the reference guard therefore
fails the first response assertion. A guard applied only after auth restoration
would encounter matching CSRF and is caught by the MariaDB sentinel.

### Public seam, response, and secrecy

The real factory/owner creates the ID and commits the literal; raw POST then
crosses the production HTTP factory/router with a real cookie. The wrapper only
chooses parent-process fixture bytes and neither synthesizes a result nor owns
dispatch.

On GREEN inherited assertions require the complete exact 503 envelope, exactly
one canonical internal `payload_invalid` line, no generic entrypoint failure,
no DB marker, unchanged bytes, and no payload/key/reference marker, CSRF,
email, password, session ID, or root in HTTP/stderr.

### Bounds, setup, and cleanup

The 32 MiB child memory limit and three-second read timeout bound the recursive
failure without affecting the object regression. Fixed owner dependencies,
private task paths, ephemeral ports, bounded readiness, and attempt-all cleanup
make the run isolated. Both children were reaped and no residue remained.

## Intended RED independently reproduced

```text
$ php -l tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
PHP Fatal error:  Uncaught TestFailure: INTENTIONAL_RED: malformed payload fails closed
Expected: true
Actual: false in tests/bootstrap.php:36
...
REFERENCE elapsed=3.09 exit=255
```

Owner commit, material precondition, sentinel/server setup, readiness, and HTTP
request precede the bounded response failure. RED is missing pre-recursion
reference rejection, not broken setup or unavailable production DB.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
342833c29488684837495d9c85289fef3f7ae18a78ad90486c4c17ae6a762d6f  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
a0c67f82512619da5912be10b9f15c60f9db1043c1ca98e7497622bdd0700653  tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
3dea7b6038baa556ae4edd2237e7f45f2ccf5d0a8bb2f0c4e5db52d8626bdd3e  docs/operations/pilot-session-storage-reference-payload-red-evidence-2026-09-03.md
721cf5ade1d37decc0962e39b22ac72c17688a6efe36c90d0b632bcf5089a95c  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
```

## Required changes

None.

## Authorized minimal GREEN

Gate 3 authorizes only the smallest codec correction that checks every array
element for the approved reference invariant before recursive descent, so this
exact `R:1` maps to `PAYLOAD_INVALID`, exact 503, and one safe log before auth/DB
dispatch without changing object behavior. No broader codec work or refactoring
is authorized.

Gate 3 is **APPROVED**.
