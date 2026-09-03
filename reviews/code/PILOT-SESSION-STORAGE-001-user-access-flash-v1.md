# PILOT-SESSION-STORAGE-001 v10 UserAccess flash — independent Gate 5 review v1

- Date: 2026-09-03T22:44:48+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate5`
- Implementation author: not this reviewer
- Reviewed implementation commit: `858a877c5148be1b5e4440fc7f9c723fcde0dd67`
- Review base: `ae61ed32d4ebee1cd4db5181c7525cb00d9352a2`
- Gate 3 authority: `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-flash-v7.md` — `APPROVED`
- Gate 4 evidence: `docs/operations/pilot-session-storage-user-access-flash-green.md`
- Verdict: **CHANGES_REQUESTED**

## Blocking finding

### UA-G5-01 — rendered UserAccess command tokens are not owner-committed when no success flash exists

`PilotE2ECoordinator::users()` mutates the owner-backed state on every
`GET /pilot/admin/users`: line 88 generates a fresh token per directory user
through `token()`. In the new owner path, `ownerUserAccess()` writes the changed
state only inside the `kind=success` flash branch (line 269). With no flash —
the ordinary page load and every repeat after consumption — it returns the 200
response without `writeCommit`.

Consequently, the HTML exposes freshly generated CSRF/action tokens that exist
only in request memory. A following status/role POST reloads the previously
committed payload and cannot validate any token rendered by that GET. This is a
user-visible authorization workflow regression and violates the section 6
rule that UserAccess output must remain buffered until the corresponding
explicit session commit succeeds (`specs/PILOT-SESSION-STORAGE-001.md:156-161`).
It also means write failure for an ordinary token-generating GET cannot map to
the required exact 503 envelope.

Required correction: commit the complete owner state after all successful
UserAccess GET mutations, before releasing the buffered 200, whether or not a
success flash was present; map typed commit failure to the exact section-6 503.
Add an independently reviewed RED proving a token rendered by a no-flash GET is
present in the subsequently owner-read payload (and preferably usable at the
public POST seam), plus its write-failure buffering case. Because this changes
the approved oracle, restart at Gate 2 and obtain a fresh Gate 3 approval.

## Non-blocking observations

- `ownerSessionId` is assigned and cleared but never read. Remove it unless the
  corrected transaction boundary gives it a concrete use.
- The empty-prefix change in `AccessPolicy` matches the already accepted
  production dependency grammar. Prepared SQL still prevents prefix content
  from becoming user-controlled query data.
- `PilotSessionPayloadCodec` changes only fully qualify global calls; no codec
  expectation or assertion changed.
- The reviewed slice intentionally does not claim removal of the remaining
  native-session consumers or Compose restart proof. Those are not used to
  reject this narrow review.

## Verification evidence

Exact focused and session runs at worktree HEAD
`7a7168b3904ac4cd3a314b481fda18058b6123c7` (whose production implementation
is exact commit `858a877`):

```text
php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess flash owner handoff

for test_file in tests/InstallationProcess/pilot_session_storage*_test.php; do php "$test_file"; done
all 18 files PASS

php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

git diff --check ae61ed3..858a877
PASS
```

`process_user_directory_001_test.php` could not be independently rerun with
the currently exposed MariaDB credentials: the default root credential was
rejected, while `fmonitor2_test` connected but lacks CREATE DATABASE privilege.
This setup limitation does not cause the verdict; the focused test itself used
the existing repository test database and passed. Static review found the
blocking session-publication defect above despite the current GREEN oracle.

Reviewed test/spec hashes remain the Gate 3 v7 hashes; no assertion changed
between `be9f7f9` and `858a877`. Production diff is exactly three files:
`AccessPolicy.php`, `PilotE2ECoordinator.php`, and
`PilotSessionPayloadCodec.php`.

Gate 5 does not pass. Return this slice to Gate 2 for the missing no-flash token
commit/failure oracle and a fresh independent Gate 3 review.
