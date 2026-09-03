# Independent Gate 3 rereview — PILOT-SESSION-STORAGE-001 v10 UserAccess flash v4

- Date: 2026-09-03T22:23:25+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate3_v4`
- Test/implementation author: not this reviewer
- Reviewed commit: `97695e44bbab13ab835bf0fe97c731ba3bcb199c`
- Scope: UserAccess flash RED v4 against `PILOT-SESSION-STORAGE-001` v10
  sections 3, 6–8 and 11
- Prior append-only reviews:
  `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md` and
  `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v2.md` —
  `CHANGES_REQUESTED`; v3 — `APPROVED` before the CSS construction gap was
  discovered during the first implementation attempt
- Verdict: **APPROVED**

## Review

The v4 correction closes the later-discovered construction predecessor without
changing the already approved behavioral expectations. Both verifier-owned
routers now bind `FMONITOR_SHLZ_CSS_PATH` to the existing sibling-repository
`shlz-ui/packages/styles/dist/shlz.css` and `FMONITOR_PILOT_CSS_PATH` to the
existing repository `app/PilotHttp/pilot.css` before calling the real
`ProductionPilotHttpEntrypointFactory` injection seam. These are the same real
assets the production graph requires; neither path is request-derived. Both
files exist at the reviewed checkout, and both routers pass syntax validation.

The reviewed commit changes only the two support routers and append-only RED
evidence. No file under `app/` or `rapid-pilot/` differs from its parent, so the
RED contains no production implementation. The pre-existing stash remains
present and separate; its production change does not participate in the
reviewed HEAD or reproduction.

The test remains sensitive at the required public seam. It seeds a canonical
whole-array serialized session through the real owner for a fictional active
user with the exact `access.administer` permission, fixed valid CSRF and fixed
flash URL. The first raw authenticated UserAccess GET must return `200` and
render that URL. A new real owner must then observe that the flash key was
committed absent, and a repeated GET must omit the URL. A static response,
native-session side channel, identical-byte rewrite, missing commit, or repeated
flash therefore cannot satisfy the assertions.

The fault branch restores the exact original bytes through the real owner and
starts a separate injected production graph. Its task-owned external trace must
contain exactly one structural
`rename|committed|ordinal=1|native_false` event, tying the deterministic failure
to the flash-removal publication. The response must be the exact section-6
`503` status/body and exact-cardinality security/header envelope; forbidden and
unspecified application headers are rejected. The original committed payload
must remain byte-identical. This jointly detects success-byte leakage, an
earlier unrelated 503, a missing write attempt, and corruption on failed
publication.

The independently reproduced first failure is the intended missing production
UserAccess graph: fixture insertion, canonical owner publication, CSS
configuration and child startup have completed, but known authenticated
`GET /pilot/admin/users` returns `404` instead of `200`. It is not a broken
setup failure. Cleanup removed the exact task-owned state tree and left zero
matching fictional users or roles in the test database.

## Independent reproduction

At exact reviewed SHA:

```text
$ test -f ../shlz-ui/packages/styles/dist/shlz.css
exit=0
$ test -f app/PilotHttp/pilot.css
exit=0
$ php -l tests/Support/pilot_session_storage_user_access_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_router.php
$ php -l tests/Support/pilot_session_storage_user_access_fault_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_fault_router.php
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
$ git diff --name-only HEAD^ HEAD -- app rapid-pilot
(no output)
$ git diff --check HEAD^ HEAD
exit=0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
badfa9ae003c986e561270b1e71450de122694fd7f8c6e5316af4ff07ce3f525  docs/operations/pilot-session-storage-v10-payload-owner-decision-2026-09-03.md
5d28d6016734319970ced3294d3061b51ae5fa49d4614e5b50d13441b124b292  reviews/tests/PILOT-SESSION-STORAGE-001-consumers-v1.md
880abdc04e02aea1e2fe6312f5413cefd6a12566545ef1ea305643a11238bedd  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md
47ed0e1cba3077af288c3cd38df647ff89a9411b8f90e406fc0add8d9d474acf  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v2.md
414cd740aacf5328d3a28f5fd7767e4acf4b93f34c6674142bcbcafe01c6dd24  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v3.md
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
3c71da07f04041061fab43cc2fef3e35ac5cb9de9d017ee802275a33598c3b20  tests/Support/pilot_session_storage_user_access_router.php
1e670dae0575ff2e972049dde630501e2a82020bfce54b3d10b11933ec3c0bc1  tests/Support/pilot_session_storage_user_access_fault_router.php
e6ed53321c801ccb7430073efa0b4619da03a91d15345966b853340992c07a31  docs/operations/pilot-session-storage-user-access-flash-red-evidence-v4.md
e1421675a3fff8f317f64ff053d0972728bfddfed4491b97eb9ca46283a72c84  app/IdentityAccess/PilotSessionStorageTypes.php
```

Gate 3 for the focused UserAccess flash v4 slice is **APPROVED**. Gate 4 may
proceed against these exact reviewed expectations without changing them.
