# PILOT-SESSION-STORAGE-001 v10 reference payload — Gate 2 RED

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
342833c29488684837495d9c85289fef3f7ae18a78ad90486c4c17ae6a762d6f  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
a0c67f82512619da5912be10b9f15c60f9db1043c1ca98e7497622bdd0700653  tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
```

Public seam: the same real-owner/raw-HTTP POST login harness independently
approved for the object case, selected only in the parent test process with
`FMONITOR_TEST_SESSION_PAYLOAD_CASE=reference`. The production server receives
the standard explicit dependency composition; no production code reads that
test-only selector.

The exact committed literal is a whole array containing the matching 64-byte
`auth_csrf` and `x` as `R:1`, a self-reference to the root array. An independent
probe confirms `unserialize(..., ['allowed_classes' => false])` returns the
matching CSRF and `ReflectionReference::fromArrayElement($decoded, 'x')` is
non-null.

Command:

```text
php tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
```

Observed exit `255` in about three seconds, intended RED:

```text
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: malformed payload fails closed
Expected: true
Actual: false
```

The current decoder recursively follows the self-reference until its bounded
server child terminates instead of returning the exact 503. Client timeout then
makes the first status assertion fail. Correct behavior must reject the
reference before auth/DB dispatch, emit the exact single safe
`payload_invalid` log, preserve committed bytes and return the complete section
6 envelope. The inherited DB sentinel, secret checks and attempt-all cleanup
remain active.
