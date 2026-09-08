# Code review: local navigation permissions manual fix

- Reviewer: independent agent `/root/nav_fix_review`
- Implementation author: `/root`
- Reviewed commit: uncommitted working-tree diff against base commit `00870b88e55e87d47a552e7ac1dff8abaae9e005`
- Specification: owner manual-test feedback recorded in `tests/AssignmentOrderComposition/local_navigation_permissions_manual_test.php` — entering an object card preserves the actor's authorized sidebar links
- Approved test review: focused owner-feedback regression supplied for the manual-pilot exception; no claim of a completed normal Gate 3 review
- Verification commands:
  - `php tests/AssignmentOrderComposition/local_navigation_permissions_manual_test.php`
  - `php -l app/PilotHttp/LocalAuthenticatedHttpUser.php`
  - `php -l tests/AssignmentOrderComposition/local_navigation_permissions_manual_test.php`
- Verdict: `APPROVED`

## Findings

None blocking.

The production diff replaces a newly constructed `HttpUser` containing only the route's required permission with the full active profile already returned by `MariaDbLocalUserProfile::read()`. That profile obtains its permission list from `AccessPolicy::forUser()`, which selects permissions through active users, active assigned roles, and their persisted role-permission rows. The preceding `hasCapability($profile->id, $requiredPermission)` admission check is unchanged, so an actor still needs `objects.read` to enter a card (or the route-specific permission at another caller).

The change does not write role assignments or role permissions and therefore grants no new authority. Restored permissions make the shared sidebar accurately reflect authority the actor already has. Object-card command visibility and execution remain independently derived from `ProductionPilotHttpDependencies::hasCapability()` and the downstream application authorization seams; they do not trust the restored `HttpUser::permissions` array for mutation authorization.

The focused regression uses `SelectionHttpFixture`, whose backing fixture creates a uniquely named temporary database and removes it on close. It verifies that an entitled actor sees objects, calendar, installers, construction control, users, and roles after entering `/pilot/objects/4512`, and that another active actor without object access still receives HTTP 403. Verification result: `PASS local card navigation preserves all role permissions and denied access`. Both changed PHP files also pass syntax checking.

## Required changes

None.

## Gate scope

This is a focused independent review for the owner-authorized 2026-09-07 manual-pilot repair. It approves this one-line production change and its focused regression only. Full suites, `make verify`, browser re-verification on the deployed stand, remaining normal delivery gates, and final production/integration readiness were not established by this review.
