# Independent Gate 3 rereview — PILOT-SESSION-STORAGE-001 v10 UserAccess CSS v8

- Date: 2026-09-04T19:25:00+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate3_v8`
- Test/implementation author: not this reviewer
- Reviewed commit: `b36d64627562ed3bf07b14299bee6452ec748e66`
- Parent commit: `099c3963305676b4245bd22ca15246f3697eca1d`
- Scope: test-only UserAccess router CSS fixture correction; the previously
  approved flash v7 and tokens v1 behavioral expectations
- Verdict: **APPROVED**

## Review

The reviewed commit changes exactly the two verifier-owned router paths and one
append-only Gate 2 evidence record. In each router, the configured
`FMONITOR_SHLZ_CSS_PATH` changes from the obsolete generated
`../shlz-ui/packages/styles/dist/shlz.css` path to the current task-designated
public source entrypoint `../shlz-ui/packages/styles/shlz.css`. The new file is
a regular `shlz.css` asset, is accepted by the production basename boundary,
and exposes the complete local import graph. No production file, executable
specification, focused test, literal expected status/body/header, fixture row,
owner payload, fault tuple, or cleanup path changed.

The exact focused test hashes remain those approved by UserAccess flash v7 and
tokens v1. Consequently their sensitivity is preserved: the flash test still
requires an accepted owner-backed GET, exact flash rendering, canonical owner
publication of flash removal, absence on repeat, and the exact publish-fault
envelope with byte-preserved prior material. The tokens test still requires the
rendered action token to be committed into the same canonical owner payload and
retains its exact publication-fault branch. Merely returning static HTML,
falling back to native session storage, omitting the owner commit, or leaking a
buffered success cannot satisfy these oracles.

I independently reset the canonical test database and ran the complete v1-v11
production migration before both focused tests. Both routers and both tests
passed syntax validation. Each focused test reached its first authenticated
UserAccess success expectation and returned `503` instead of `200`. Thus the
current RED is beyond CSS setup and is the intended production/local-profile
composition gap: `ownerUserAccess()` supplies the canonical local actor ID,
email, CSRF and owner state, while the downstream `users()` path still resolves
the principal through `MariaDbHttpUserDirectory::resolveActiveUser()` and its
legacy identity lookup before checking the already-local capability. The
fixtures deliberately contain no legacy positive-authority or display row.

Both failing runs executed their `finally` cleanup. The task-owned session root
was absent afterwards, and independent database queries found zero matching
fictional users and roles. The focused RED remains deterministic and isolated.

## Independent reproduction

```text
$ PATH="/Applications/Docker.app/Contents/Resources/bin:$PATH" make test-db-reset
TEST_DB_RESET_OK
$ PATH="/Applications/Docker.app/Contents/Resources/bin:$PATH" make migrate
{"ok":true,"schemaVersion":11,"appliedVersions":[1,2,3,4,5,6,7,8,9,10,11]}
$ php -l tests/Support/pilot_session_storage_user_access_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_router.php
$ php -l tests/Support/pilot_session_storage_user_access_fault_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_fault_router.php
$ php -l tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
$ php -l tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
$ php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
accepted UserAccess session reaches admin users
Expected: 200
Actual: 503
exit=255
$ php tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
no-flash UserAccess GET succeeds
Expected: 200
Actual: 503
exit=255
$ test ! -e /tmp/fmonitor2-session-storage-tests
exit=0
$ independent canonical DB residue query
RESIDUAL_USERS=0
RESIDUAL_ROLES=0
$ git diff --check 099c3963305676b4245bd22ca15246f3697eca1d b36d64627562ed3bf07b14299bee6452ec748e66
exit=0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
b4bfe456852e4799916878ca048de7c960eced1b1ab798c007935e8eeaa6ff94  tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
c83b1c071b5ccd32b2f2db74493b6083545c6fdfda340374edc51a2d82efbefc  tests/Support/pilot_session_storage_user_access_router.php
a59b3812c70727233e1f27b4e5ad07eaa55641785ca07309b6865ab0de29de2b  tests/Support/pilot_session_storage_user_access_fault_router.php
843b82376c9f8692824ba048f71f7a10c526cbc7da22c3f9ba5b845362b47a33  docs/operations/pilot-session-storage-user-access-css-red-v8-2026-09-04.md
98504c9e0a41e821aff7f737a75a80d929eabb78f627b22a364a6f8e95625643  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v7.md
e5d963b4a84c1ddd3fbe6fb2e983827d289dd42a36f6c4c48e0f9cc52e20668c  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-tokens-v1.md
545326795c383626aadfdd904fd6b9db686f34789d02630c319b4aff2ce7e683  app/PilotHttp/PilotE2ECoordinator.php
b3ea9bf60858e505bd2d12993e8c4120f9fa7f27a2ae9276e2eded00a7b7f74b  app/PilotHttp/PilotHttp.php
6e014bf5a7678894b656a808171966c76577d5d967471db1f5e63ec1363a15b9  ../shlz-ui/packages/styles/shlz.css
```

Gate 3 is **APPROVED** for these exact corrected fixtures and unchanged
expectations. Gate 4 may implement the minimal local-profile UserAccess
composition and owner publication behavior without changing the reviewed
oracles.
