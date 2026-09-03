# Independent Gate 3 rereview — PILOT-SESSION-STORAGE-001 v10 UserAccess flash v6

- Date: 2026-09-03T22:30:58+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate3_v6`
- Test/implementation author: not this reviewer
- Reviewed commit: `7401eb762059ebb2181fe3106e9e1770aba27c95`
- Scope: UserAccess flash RED v6 against `PILOT-SESSION-STORAGE-001` v10
  sections 3, 6–8 and 11
- Prior append-only reviews: v1 and v2 — `CHANGES_REQUESTED`; v3–v5 —
  `APPROVED`, with each later implementation attempt exposing one additional
  production dependency needed to make the verifier setup complete
- Verdict: **APPROVED**

## Review

The v6 correction supplies the one dependency omitted from v5 without changing
the approved behavioral oracle. Both verifier-owned routers now explicitly set
`FMONITOR_LEGACY_TABLE_PREFIX` to the valid empty prefix. Static comparison with
the exact current `ProductionPilotHttpDependencies::users()` and
`commandResources()` implementations confirms that the combined parent/router
environment now contains every required key:

```text
FMONITOR_DB_HOST
FMONITOR_DB_PORT
FMONITOR_DB_NAME
FMONITOR_DB_USER
FMONITOR_DB_PASSWORD
FMONITOR_LEGACY_TABLE_PREFIX
FMONITOR_PROCESS_TABLE_PREFIX
FMONITOR_ARTIFACT_STORAGE_ROOT
FMONITOR_NOW
```

The same graph also has both CSS paths and the complete session configuration
(`FMONITOR_SESSION_STATE_ROOT`, `FMONITOR_SESSION_INSTANCE`, and trusted
request scheme). The legacy and process prefixes are deliberately present
empty strings; both are valid under the production validators. The two real
CSS files exist and all three verifier PHP files pass syntax validation.

The reviewed commit changes only the two verifier routers and append-only v6
RED evidence. Production under `app/` and `rapid-pilot/` is byte-identical to
the parent. With that production tree clean, independent execution again
completes database fixtures, canonical owner publication and server startup,
then fails at the first known authenticated `GET /pilot/admin/users`: expected
`200`, actual `404`. This is the intended absent UserAccess route composition,
not a missing configuration key or broken test setup.

The full oracle remains independently sensitive. The normal path seeds an
opaque, canonical whole-array serialized payload through the public owner for a
fictional active actor with exact `access.administer`, fixed CSRF and fixed flash
URL. It requires a raw `200` rendering that URL, verifies through a fresh real
owner that committed material no longer contains the flash, and requires a
second raw GET to omit the URL. This rejects static output, native-session
fallback, no commit, identical-byte rewrite, and repeat rendering.

The fault path restores the exact original bytes and binds its `503` assertions
to exactly one external redacted trace event for
`rename / committed / ordinal=1 / native_false`. It checks the complete exact
section-6 header/body envelope, absence of forbidden and unspecified
application headers, and byte-identical preservation of prior committed
material. The v1 trace/envelope findings and v2 enum-literal mismatch therefore
remain closed. The test's `finally` removed its process, task-owned tree and DB
fixtures; independent post-run queries found zero matching users and roles.

## Independent reproduction

```text
$ date --iso-8601=seconds
2026-09-03T22:30:58+03:00
$ git rev-parse HEAD
7401eb762059ebb2181fe3106e9e1770aba27c95
$ git status --porcelain=v1
(no output)
$ test -f ../shlz-ui/packages/styles/dist/shlz.css
exit=0
$ test -f app/PilotHttp/pilot.css
exit=0
$ php -l tests/Support/pilot_session_storage_user_access_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_router.php
$ php -l tests/Support/pilot_session_storage_user_access_fault_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_fault_router.php
$ php -l tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
$ git diff --name-only HEAD^ HEAD -- app rapid-pilot
(no output)
$ php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
PHP Fatal error: Uncaught TestFailure: accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
exit=255
$ test ! -e /tmp/fmonitor2-session-storage-tests
exit=0
$ query matching session.actor/session.target users and session-role roles
RESIDUAL_USERS=0
RESIDUAL_ROLES=0
$ git diff --check HEAD^ HEAD
exit=0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
94698586125968ecb836fefffec6cc4db748ac99421bacf1f6bdeb816d96d82c  tests/Support/pilot_session_storage_user_access_router.php
2ccecadddec74ff950df663e79527958339eca8710b1ba6922f1d73c5a3cbc3f  tests/Support/pilot_session_storage_user_access_fault_router.php
36d9d58636d279799c2bfd5e1526452e1f5d033b4809f765b86226cb8da74d19  docs/operations/pilot-session-storage-user-access-flash-red-evidence-v6.md
880abdc04e02aea1e2fe6312f5413cefd6a12566545ef1ea305643a11238bedd  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md
47ed0e1cba3077af288c3cd38df647ff89a9411b8f90e406fc0add8d9d474acf  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v2.md
414cd740aacf5328d3a28f5fd7767e4acf4b93f34c6674142bcbcafe01c6dd24  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v3.md
ee3a343b361136e45bd7dc702710c3e3f27cfc5dd6e4065aee0d4cca503b894f  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v4.md
ad8e6b28e5831b1c6b71a38efdc30f50f3439618dafe65d3ae4fce4144a652fe  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v5.md
0181d58862a9b397d45c272fbd0cedba7e5f11aaf653479619b6ed851c69cde7  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
7e25e9a4fdaeead9647a508a0742c02d4042a4416056891a8e250d7635fbb9b9  app/PilotHttp/PilotHttp.php
33b298c2f28a7c9ee493270ded4b7d54c9192cd0a1d529ea4a7daeb5a7697f1a  rapid-pilot/UserAccessView.php
```

Gate 3 for UserAccess flash v6 is **APPROVED**. Gate 4 may proceed against
these exact reviewed expectations without changing them.
