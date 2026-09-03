# Independent Gate 3 rereview — PILOT-SESSION-STORAGE-001 v10 UserAccess flash v5

- Date: 2026-09-03T22:27:47+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate3_v5`
- Test/implementation author: not this reviewer
- Reviewed commit: `6cc306c5d43e3f5c28e38bf206622312e0449040`
- Scope: UserAccess flash RED v5 against `PILOT-SESSION-STORAGE-001` v10
  sections 3, 6–8 and 11
- Prior append-only reviews: v1 and v2 — `CHANGES_REQUESTED`; v3 and v4 —
  `APPROVED`, with the v4 implementation attempt subsequently exposing one
  more production-resource setup predecessor
- Verdict: **APPROVED**

## Review

The v5 correction resolves the implementation-discovered setup issue without
changing any approved behavioral expectation. In addition to the real shlz and
pilot CSS files already bound in v4, both verifier-owned routers now bind
`FMONITOR_ARTIFACT_STORAGE_ROOT` below the task-owned session root and fixed
`FMONITOR_NOW=2026-09-03T12:00:00+03:00`. Together with the existing explicit
DB and session configuration, those values cover every resource required by
the production UserAccess directory construction path. The raw server reaches
the normalized route response rather than a configuration/setup exception,
demonstrating that the real `ProductionPilotHttpEntrypointFactory` graph and
its resources are constructible. Both real CSS inputs exist and both routers
pass PHP syntax validation.

The reviewed commit changes only those two support routers and the append-only
v5 RED evidence. Production under `app/` and `rapid-pilot/` is byte-identical to
the parent of the reviewed commit; the saved implementation attempt remains a
separate stash and did not participate in this reproduction.

The downstream oracle is unchanged and remains sensitive at the confirmed raw
HTTP/public storage seams. A canonical whole-array serialized session is
published by the real owner for a fictional active user with the exact
`access.administer` permission, fixed valid CSRF, and fixed success URL. The
first authenticated UserAccess GET must return `200` and render that URL; a new
real owner must then observe the flash key absent from committed material, and
a repeated GET must return `200` without the URL. Static output, native-session
state, no commit, identical-byte rewrite, or repeatedly rendered flash cannot
satisfy this sequence.

The separate fault graph restores the exact original payload and injects one
specific committed-publication rename failure. The verifier requires exactly
one external structural trace event
`rename|committed|ordinal=1|native_false`, exact section-6 `503` body and header
envelope, no success/redirect/cookie leakage, and byte-identical preservation
of the prior committed payload. Thus an unrelated early failure cannot satisfy
the fault assertions.

The independently reproduced first failure is the intended missing production
route graph: canonical owner publication, DB fixtures, all CSS/artifact/time
configuration, factory construction and server startup complete, but known
authenticated `GET /pilot/admin/users` returns `404` instead of the required
`200`. This is not broken setup. The verifier's `finally` removed its process,
task-owned state/artifact tree, fictional users, credentials, role assignments,
permissions and role. Independent post-run checks found no task root and zero
matching residual users or roles.

## Independent reproduction

At exact reviewed SHA:

```text
$ git rev-parse HEAD
6cc306c5d43e3f5c28e38bf206622312e0449040
$ test -f ../shlz-ui/packages/styles/dist/shlz.css
exit=0
$ test -f app/PilotHttp/pilot.css
exit=0
$ php -l tests/Support/pilot_session_storage_user_access_router.php
No syntax errors detected
$ php -l tests/Support/pilot_session_storage_user_access_fault_router.php
No syntax errors detected
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
cd8f234ffe5defa78d0e3d4abb5b4fa44b3233a495cf6a638ef75f0762d426bb  tests/Support/pilot_session_storage_user_access_router.php
6fb96fbce9b7a3545a66d4f4ffe47497d626b68a0d7830be4747b6b3a5b57724  tests/Support/pilot_session_storage_user_access_fault_router.php
b1630b4873a1667ad394aba25385154e959487150db0f6b49034f45466e2b542  docs/operations/pilot-session-storage-user-access-flash-red-evidence-v5.md
ee3a343b361136e45bd7dc702710c3e3f27cfc5dd6e4065aee0d4cca503b894f  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v4.md
0181d58862a9b397d45c272fbd0cedba7e5f11aaf653479619b6ed851c69cde7  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
7e25e9a4fdaeead9647a508a0742c02d4042a4416056891a8e250d7635fbb9b9  app/PilotHttp/PilotHttp.php
33b298c2f28a7c9ee493270ded4b7d54c9192cd0a1d529ea4a7daeb5a7697f1a  rapid-pilot/UserAccessView.php
```

Gate 3 for UserAccess flash v5 is **APPROVED**. Gate 4 may proceed against
these exact reviewed expectations without changing them.
