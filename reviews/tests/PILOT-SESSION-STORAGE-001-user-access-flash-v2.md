# Independent Gate 3 review — PILOT-SESSION-STORAGE-001 v10 UserAccess flash v2

- Date: 2026-09-03T22:14:52+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate3_v2`
- Test/implementation author: not this reviewer
- Reviewed commit: `49beec8471874b934259fbbae8c101c7d87fea59`
- Scope: corrected UserAccess flash RED against `PILOT-SESSION-STORAGE-001`
  v10 sections 3, 6–8 and 11
- Prior append-only review:
  `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md` —
  `CHANGES_REQUESTED`
- Verdict: **CHANGES_REQUESTED**

## Sound corrections

The v2 test keeps the canonical whole-array owner seed, fictional authenticated
actor with `access.administer`, fixed CSRF and fixed invitation URL. Its normal
branch still requires the real raw-HTTP UserAccess response to render the URL,
requires a fresh real owner to observe committed flash removal, and requires a
second GET not to render the consumed URL. The fault branch now compares every
section-6 application header with exact cardinality and value, rejects the
listed forbidden headers and any unspecified application header (while allowing
the local SAPI envelope), checks the exact status/body, and preserves the
original canonical bytes.

The fault router also writes only redacted AFTER event fields to task-owned
external trace evidence. It exposes no path, session ID/hash, payload, user or
secret. The parent intends to require exactly one configured
`rename / committed / ordinal 1` native-false event. This is the right boundary
and would close both v1 findings once its literal is constructible.

## Blocking finding

The new trace assertion cannot succeed with the reviewed public API. The router
serializes `PilotSessionPrimitiveOutcome::NATIVE_FALSE` using
`$event->outcome()?->value`. In the normative/public implementation enum this
case is backed by the exact string `false`, not `native_false`:

```php
enum PilotSessionPrimitiveOutcome:string {
    case NATIVE_FALSE='false';
}
```

However the parent accepts a trace line only when its decoded `outcome` equals
`native_false`. Consequently the injected `NativePilotSessionFilesystem`
reaching the exact `rename|committed|1` fault will write
`{"operation":"rename","artifact":"committed","ordinal":1,"outcome":"false"}`;
the filter necessarily finds zero matches. The assertion is currently
unreachable for an oracle-literal mismatch, independently of whether the
missing UserAccess graph is implemented correctly.

Change the expected trace outcome to the public backed value `false`, or have
the verifier router deliberately map the enum case name to a documented
redacted literal and assert that same mapping. Then capture and hash a fresh
honest RED and request another independent Gate 3 review. Production
implementation remains unauthorized by this review.

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

The current failure remains the intended missing complete UserAccess graph, not
fixture setup. The task-owned state tree is absent afterward. The evidence-v2
hashes match the reviewed test and both routers, but the unreachable downstream
trace expectation prevents approval.

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
5d28d6016734319970ced3294d3061b51ae5fa49d4614e5b50d13441b124b292  reviews/tests/PILOT-SESSION-STORAGE-001-consumers-v1.md
880abdc04e02aea1e2fe6312f5413cefd6a12566545ef1ea305643a11238bedd  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
12e032068af4c335bbf62ed43cd16b256c64c546ae047b3a82712694353c0333  tests/Support/pilot_session_storage_user_access_router.php
6c5c916f0e972a07b41b553197ebb92eac843936287371115898df17f7e03ce1  tests/Support/pilot_session_storage_user_access_fault_router.php
4cbe02b8c36dbb1ee20b03ccfbc212c72376895aa59c756ff7f69be7ad254a51  docs/operations/pilot-session-storage-user-access-flash-red-evidence-v2.md
e1421675a3fff8f317f64ff053d0972728bfddfed4491b97eb9ca46283a72c84  app/IdentityAccess/PilotSessionStorageTypes.php
```

Gate 3 for UserAccess flash v2 is **CHANGES_REQUESTED**.
