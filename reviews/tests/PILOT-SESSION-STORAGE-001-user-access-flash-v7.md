# Independent Gate 3 rereview — PILOT-SESSION-STORAGE-001 v10 UserAccess flash v7

- Date: 2026-09-03T22:39:38+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate3_v7`
- Test/implementation author: not this reviewer
- Reviewed commit: `be9f7f96eee59f8f495f67ea2364f0221d4d4c11`
- Scope: UserAccess flash RED v7 against `PILOT-SESSION-STORAGE-001` v10
  sections 3, 6–8 and 11
- Prior append-only reviews: v1 and v2 — `CHANGES_REQUESTED`; v3–v6 —
  `APPROVED`, with successive implementation attempts exposing missing test
  graph prerequisites
- Verdict: **APPROVED**

## Review

The v7 correction closes the child-process configuration gap without changing
the already reviewed behavioral oracle. Both verifier-owned routers now call
`putenv('FMONITOR_PROCESS_TABLE_PREFIX=')`, alongside their existing explicit
empty legacy prefix. This distinction is necessary: the parent passes empty
prefix values to `proc_open`, but PHP omits empty entries from the child process
environment. Without child-side restoration, `getenv()` is `false`.
`ProductionPilotHttpDependencies::users()` requires the legacy prefix to be a
string, and `commandResources()` independently requires the process prefix to
be a string; both accept the empty string as the valid unprefixed namespace.

Static traversal of the production dependency graph confirms that the combined
parent/router environment now provides all dependencies reached by the known
UserAccess route: DB host, port, name, user and password; explicit empty legacy
and process prefixes; real shlz and pilot CSS paths; artifact root; fixed
process time; session state root and instance; and exact trusted request scheme.
Both CSS assets exist and all three verifier PHP files pass syntax validation.

The reviewed commit changes only the two test routers and append-only v7 RED
evidence. `app/` and `rapid-pilot/` are byte-identical to the parent commit, and
the worktree was clean before reproduction. The independently executed test
completed fixture creation, owner publication and server startup, then failed
at the intended first known authenticated request: `GET /pilot/admin/users`
returned `404` rather than `200`. This is the absent complete UserAccess route
composition, not a missing dependency or setup failure. A separately observed
implementation-WIP pass was explicitly excluded from this RED proof.

The unchanged full oracle remains sensitive and constructible. It seeds an
opaque byte-canonical whole-array serialized payload through the public owner
for a fictional active actor with exact `access.administer`, fixed CSRF and
fixed flash URL. The normal raw-HTTP branch requires the URL to be rendered,
uses a fresh real owner to prove committed flash removal, and requires a repeat
GET to omit it. Static output, native-session fallback, omitted commit,
identical-byte rewrite, or repeated rendering cannot satisfy this combination.

The fault branch restores the exact original bytes, then requires one and only
one external redacted `rename / committed / ordinal=1 / native_false` trace
event. It checks the exact section-6 status, body and header cardinalities,
rejects forbidden and unspecified application headers, and proves the prior
committed material remains byte-identical. The v1 trace/envelope findings and
v2 enum-literal mismatch remain closed. Cleanup removed the task-owned state
tree; an independent database query found zero matching fictional users and
roles.

## Independent reproduction

```text
$ date --iso-8601=seconds
2026-09-03T22:39:38+03:00
$ git rev-parse HEAD
be9f7f96eee59f8f495f67ea2364f0221d4d4c11
$ git status --porcelain=v1
(no output)
$ git diff --name-only HEAD^ HEAD -- app rapid-pilot
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
cff3d21dd30aaa7cfd91b374a254e9c4a463f0ae37675e14a94996478f9535b0  tests/Support/pilot_session_storage_user_access_router.php
18306b9015898a179a89961894011565f027e07c207f7f915afdca837313bc3f  tests/Support/pilot_session_storage_user_access_fault_router.php
b0085333ad578e9577c85bf8145347dd2f2cc516c591c6a22491d8d1f24075a0  docs/operations/pilot-session-storage-user-access-flash-red-evidence-v7.md
880abdc04e02aea1e2fe6312f5413cefd6a12566545ef1ea305643a11238bedd  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md
47ed0e1cba3077af288c3cd38df647ff89a9411b8f90e406fc0add8d9d474acf  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v2.md
414cd740aacf5328d3a28f5fd7767e4acf4b93f34c6674142bcbcafe01c6dd24  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v3.md
ee3a343b361136e45bd7dc702710c3e3f27cfc5dd6e4065aee0d4cca503b894f  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v4.md
ad8e6b28e5831b1c6b71a38efdc30f50f3439618dafe65d3ae4fce4144a652fe  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v5.md
2fcda353a840306cc9063610ab48f1535eb28e6b6207fe33896924bcb5817b34  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v6.md
0181d58862a9b397d45c272fbd0cedba7e5f11aaf653479619b6ed851c69cde  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
7e25e9a4fdaeead9647a508a0742c02d4042a4416056891a8e250d7635fbb9b9  app/PilotHttp/PilotHttp.php
33b298c2f28a7c9ee493270ded4b7d54c9192cd0a1d529ea4a7daeb5a7697f1a  rapid-pilot/UserAccessView.php
```

Gate 3 for UserAccess flash v7 is **APPROVED**. Gate 4 may proceed against
these exact reviewed expectations without changing them.
