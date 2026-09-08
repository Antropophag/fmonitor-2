# PILOT-SESSION-STORAGE-001 v10 accepted payload — Gate 2 RED

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
3365340f81b2646934bc63c6e20d4c376794d7833410b3f4b8c9f4aca8e056ce  tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
```

Public seam: raw `GET /pilot/login` through the explicit production dependency
composition, with a valid cookie and a whole-array payload committed by the
real storage owner.

Command:

```text
php tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
```

Observed exit `255`, intended RED:

```text
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: owner payload restores existing CSRF
Expected: true
Actual: false
```

Setup and predecessor behavior passed: real owner commit, server readiness,
valid-cookie start, HTTP 200 and response parsing. The response does not contain
the independently fixed 64-character CSRF from the committed whole-array
payload because the coordinator decodes but discards the returned state and
generates a replacement.

Subsequent assertions require no replacement `Set-Cookie` and byte-identical
committed material. They detect a response-only fake, session reseed or native
`name|value` rewrite. The test owns and cleans its root, process, pipes and
cookie and uses neither a test-owned result factory nor production serialization
to calculate its expected payload/CSRF.

This tracer covers restoration and unchanged reuse only. Mutation encoding,
regeneration, login POST and both complete consumers remain separate RED/GREEN
slices.
