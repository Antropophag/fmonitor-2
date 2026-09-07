# Code review: local role assignment manual fix

- Reviewer: independent agent `/root/nav_fix_review`
- Implementation author: `/root`
- Test author: `/root/launch_checks`
- Reviewed commit: uncommitted working-tree diff against base commit `00870b88e55e87d47a552e7ac1dff8abaae9e005`
- Specification: owner manual-test finding — an authenticated access administrator can attach and detach a role through the rendered Users page
- Approved test review: focused manual-pilot regression review only; no claim of a completed normal Gate 3 review
- Verification commands:
  - `php tests/InstallationProcess/pilot_local_trusted_scheme_001_test.php`
  - `php tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php`
  - `php tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php`
  - `php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php`
  - `php -l app/PilotHttp/PilotE2ECoordinator.php`
  - `php -l tests/InstallationProcess/pilot_local_trusted_scheme_001_test.php`
- Additional implementation evidence: root reported isolated headless Chromium login, Users navigation, attach and detach with persisted reload state and no page errors
- Verdict: `APPROVED`

## Findings

None blocking.

The dispatch change recognizes only the existing role mutation shapes, `/pilot/admin/users/{positive-user-id}/roles` and `/pilot/admin/users/{positive-user-id}/roles/{positive-role-id}`, and passes the parsed route to the existing `users()` application seam. Unsupported paths remain outside that allowlist. `users()` still resolves the authenticated active profile and requires the actor's persisted `access.administer` capability before reading the directory or attempting any mutation. The patch therefore makes the existing forms reachable through the owner-managed session; it does not create a new authorization route or grant a role by session state alone.

Every attach or detach still requires a rendered CSRF token bound to the authenticated actor and target user. Consumption removes the token before business validation, and the owner-session handoff now commits the changed session on redirect and error responses as well as successful page rendering. The focused test proves a replay returns 403 without new role or audit facts. Commit failure continues to fail closed with 503, as covered by the UserAccess fault test.

For owner-managed sessions, request validation now compares `auth_user_id` with the resolved active actor and obtains the expected origin scheme from the explicit `FMONITOR_TRUSTED_REQUEST_SCHEME`. Missing or invalid scheme remains fail closed. The added `Origin: null` allowance is limited to an authenticated owner-session dispatch that also presents `Sec-Fetch-Site: same-origin`; the unforgeable, actor/target-bound CSRF token and `access.administer` check remain mandatory. The final regression separately rejects hostile-origin/cross-site and null-origin/cross-site requests without database changes, then accepts null-origin/same-origin with the valid bound token. Root's real Chromium evidence establishes why this narrow browser case is needed under the page's `no-referrer` policy.

Role mutation remains in `MariaDbPilotUserDirectory::changeRole()`, preserving its validation and append-only `fm2_pilot_user_role_events` audit. The focused regression checks one persisted assignment, one actor-attributed attach event, reload visibility, removal, and one actor-attributed detach event. It also checks that the initial Users GET changes neither database facts nor private files. No privilege escalation was found in the reviewed routing or session changes.

All listed focused tests passed. `pilot_local_trusted_scheme_001_test.php` uses `SelectionHttpFixture`, which owns a uniquely named temporary database and cleans it up. The adjacent token, lifecycle, and fault tests use isolated task-owned session roots and clean up their scoped fixtures; no database reset was run. Both changed PHP files pass syntax checking.

## Required changes

None.

## Gate scope

This approval covers the focused 2026-09-07 manual-pilot role attach/detach repair and reviewed adjacent authorization/session behavior. It does not establish full-suite, `make verify`, complete browser matrix, normal full-gate completion, deployment correctness, or production/integration readiness.
