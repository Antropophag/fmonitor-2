# Independent Gate 3 review — PILOT-SESSION-STORAGE-001 v10 UserAccess flash v3

- Date: 2026-09-03T22:18:24+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate3_v3`
- Test/implementation author: not this reviewer
- Reviewed commit: `a7c715e72b120173ef2906301c50061df6519d99`
- Scope: corrected UserAccess flash RED against `PILOT-SESSION-STORAGE-001`
  v10 sections 3, 6–8 and 11
- Prior append-only reviews:
  `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md` and
  `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v2.md` —
  `CHANGES_REQUESTED`
- Verdict: **APPROVED**

## Review

The v3 correction closes the sole v2 blocker without weakening or replacing
the behavioral oracle. `NativePilotSessionFilesystem` maps the configured
`rename|committed|1|false` tuple to the public
`PilotSessionPrimitiveOutcome::NATIVE_FALSE` case. The verifier-owned fault
router now deliberately maps that closed enum case to the redacted external
literal `native_false`; the parent requires exact structural equality with
`rename|committed|1|native_false` and cardinality one. The trace expectation is
therefore constructible from the reviewed wrapper and public event API.

The test uses the production factory and real owner to seed a byte-canonical
whole-array PHP payload containing a fictional active administrator, fixed
CSRF, and fixed success flash URL. Its normal raw-HTTP branch requires the real
UserAccess response to render that URL, observes committed removal through a
fresh real owner, and requires the repeated GET not to render it. A static
response, native-session side channel, identical-byte rewrite, or omitted
flash-removal commit cannot satisfy those observations.

For the fault branch, the original canonical payload is restored through the
owner before a single raw request. The exact injected publish failure is tied
to that request by the child-owned primitive trace, while the parent also
requires byte-for-byte preservation of the committed pre-request payload. The
buffered success cannot leak: status and body are exact, each required
section-6 header has exact cardinality and value, forbidden headers are absent,
and unspecified application headers are rejected while only the permitted
local SAPI envelope is tolerated.

Setup is deterministic apart from collision-resistant task naming and free
loopback port selection. It uses fictional data and local test infrastructure.
The `finally` path attempts process termination/reaping, closes pipes, removes
the inserted actor/role data, closes the database, and recursively removes only
the exact task-owned state root. Independent reproduction left the shared test
root absent.

The first reachable failure remains an honest missing-behavior RED: after the
database fixture, canonical owner commit and HTTP server startup succeed,
`GET /pilot/admin/users` returns the current `404` instead of the required
authenticated UserAccess `200`. The failure is not caused by setup, the trace
literal correction, or cleanup. The downstream assertions are now sensitive
and constructible enough to authorize the minimal production implementation.

## Independent reproduction

At exact reviewed SHA:

```text
$ php -l tests/Support/pilot_session_storage_user_access_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_router.php
$ php -l tests/Support/pilot_session_storage_user_access_fault_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_fault_router.php
$ php -l tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
$ php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
PHP Fatal error: Uncaught TestFailure: accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
exit=255
$ test ! -e /tmp/fmonitor2-session-storage-tests
exit=0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
5d28d6016734319970ced3294d3061b51ae5fa49d4614e5b50d13441b124b292  reviews/tests/PILOT-SESSION-STORAGE-001-consumers-v1.md
880abdc04e02aea1e2fe6312f5413cefd6a12566545ef1ea305643a11238bedd  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md
47ed0e1cba3077af288c3cd38df647ff89a9411b8f90e406fc0add8d9d474acf  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v2.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
12e032068af4c335bbf62ed43cd16b256c64c546ae047b3a82712694353c0333  tests/Support/pilot_session_storage_user_access_router.php
a5a1befc78a42ca1d32dff4ed21b1ee7e22cd638d819dc7d7c197d1b958660a3  tests/Support/pilot_session_storage_user_access_fault_router.php
a429f50212ae27b860baaa7d9eb9f3bff227658833f61963a42eb6647300e81b  docs/operations/pilot-session-storage-user-access-flash-red-evidence-v3.md
e1421675a3fff8f317f64ff053d0972728bfddfed4491b97eb9ca46283a72c84  app/IdentityAccess/PilotSessionStorageTypes.php
```

Gate 3 for UserAccess flash v3 is **APPROVED**. Gate 4 may proceed against the
reviewed expectations without changing them.
