# Manual feedback: navigation and role assignment, 2026-09-07

Owner reported disappearing sidebar links while navigating, `Invalid request`
when assigning a role to himself, and missing installers, OTIZ and construction
control sections. These findings take priority over the remaining launch backlog.

## Sidebar reproduction and correction

Source checkpoint: `00870b88e55e87d47a552e7ac1dff8abaae9e005`.
The existing manual stand was running and healthy with image
`sha256:85e10300a4520482698e7a75e3c33e15f49642fe20829831413a1fd6e8e4aa63`.

Headless Chromium, using the configured owner login privately, reproduced:
objects/users/roles/calendar each showed four permitted links; entering an object
card reduced the links to objects/calendar; returning restored all four.
The private `runtime/owner-bugs-repro.cjs` exited 1 with `navStable=false`.
No role assignments or object facts were changed by this reproduction.

`LocalAuthenticatedHttpUser::resolve` loaded the full active local profile and
checked the required route permission, then replaced that profile with a new
`HttpUser` containing only the route permission. Returning the already-authorized
profile preserves the full navigation permission set without changing admission.

Regression command:

```sh
PATH=/opt/homebrew/bin:$PATH php tests/AssignmentOrderComposition/local_navigation_permissions_manual_test.php
```

RED on the current coordinator path: expected objects, calendar, construction
control, installers, users and roles; actual objects and calendar. GREEN after
the correction also confirms that another actor without object access receives
403. This isolated fixture tests the card path and native sidebar permissions;
the actual-stand reproduction covers navigation between pages. Fixture setup
attempts that returned 503 or used the compatibility path were not RED evidence.

Focused adjacent HTTP original/application/opening and checklist/photo/correction
smokes passed after the navigation correction. Syntax and `git diff --check`
passed. Independent review and deployed-image verification are recorded separately.

## Expected role visibility

Current role definitions intentionally do not give a superadministrator business
permissions. `manager` grants installers and construction-control access;
`otiz_specialist` grants OTIZ access. The owner assigns his own roles. Fixing
navigation must not silently grant these roles or remove server-side checks.

## Role-assignment browser reproduction

A private headless browser harness starts a `SelectionHttpFixture` with a uniquely
named synthetic database, sets a synthetic password on its existing administrator,
and uses the actual login and Users forms. Clicking `Назначить` returned 403
instead of a successful redirect, with no browser page errors. The runner is
`runtime/role-browser-fixture.php` plus `runtime/role-browser.cjs` in the private
manual-pilot runtime directory. No owner role was modified. The first harness's
cleanup control flow was corrected; its stopped synthetic fixture database and
files were explicitly cleaned before subsequent runs.

The role POST now uses the same authenticated session as the Users GET and passes
the captured user/role route to the existing directory command. Consumed action
tokens are committed on redirect and error responses as well as rendered pages.
Owner-session admission checks the authenticated user identity and configured
trusted scheme. The observed browser form sends `Origin: null` with
`Sec-Fetch-Site: same-origin`; that combination is admitted only in the owner
session branch and still requires a valid actor/target-bound CSRF token.

The original headless loop is GREEN: assignment 303, assigned role visible after
reload, removal 303, role absent after reload, zero page errors. Screenshot of the
synthetic Users page was inspected. The focused
`pilot_local_trusted_scheme_001_test.php` also verifies exact role/audit facts,
consumed-token rejection, hostile-origin rejection, null-origin cross-site
rejection, same-origin null-origin acceptance and missing-scheme fail-closed
behavior. Both fixture modes report cleanup success.

## Delivery status

Deployed code: `330bb419f1ca0eb5ef275660f4749abbf88e6eba`.
Image: `sha256:ba802ec993862adcbc32b4984e373a3bbd794dcb8b7793e2aaf808473e810920`.
The container is running and healthy. SHA-256 comparison of all 717 committed
runtime files in app/bin/public/rapid-pilot matches the running container.
The previous image remains tagged `fmonitor2-manual:checkpoint-00870b8` for rollback.
The pilot service was recreated without replacing its state volume or restarting
MariaDB. Safe aggregate checks found 381 installation cases, 1264 workforce
entries and one local user. The owner's assignments were not changed by the agent.

The original deployed headless navigation reproduction now exits 0 with
`navStable=true`: objects, Users, Roles, calendar, object card and return paths
all retain the same four currently authorized links. Business-role assignment and
removal were verified by real browser clicks on synthetic data, not on the owner's
account. Independent focused reviews are recorded in
`reviews/code/local-navigation-permissions-manual-2026-09-07.md` and
`reviews/code/local-role-assignment-manual-2026-09-07.md`.

This record is focused manual-pilot evidence, not a full production approval.
The full business journey and remaining integration gates remain outstanding.
