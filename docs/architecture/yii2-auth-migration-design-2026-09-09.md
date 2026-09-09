# Yii2 auth/session migration design — #76 / #71

Date: 2026-09-09. Baseline: `414ac0a7`. Scope: design only; no production or
test implementation. This decision follows issue #76 and the newer owner decision
that existing users may sign in again. Legacy cookie, payload and test-fixture
continuity are therefore not migration requirements.

## Decision

Yii owns the web authentication lifecycle. Configure `yii\web\User`, a small
`IdentityInterface` adapter over the canonical IdentityAccess tables, standard
`yii\web\Session`, framework request CSRF validation, and `AccessControl`/`User::can()`.
Do not port `RapidPilotLocalAuth`, build a compatibility session handler, copy the
old payload codec, or create a second authentication framework beside Yii.

Use a new cookie name, `fm2yii` (and `fm2yii_<port>` only in the isolated local
test contour). This intentionally makes every legacy `fm2auth*` cookie irrelevant.
The first Yii response may expire the matching old cookie as cleanup, but it never
reads its ID or payload. Users authenticate once after cutover.

Use standard file-backed `yii\web\Session` initially, with an operator-owned,
persistent save path mounted into the PHP-FPM contour. Configure lifetime 604800,
cookie path `/pilot`, `HttpOnly`, `SameSite=Strict`, and `Secure` from the already
validated trusted request scheme. Enable PHP strict session mode. The deployment
must create and permission the save directory; HTTP startup must not do so. Yii's
normal `Session::regenerateID(true)` is used through `User::login()`, and
`User::logout()` destroys the authenticated session. A database session table adds
an unnecessary schema and transaction dependency to the first slice; it can be a
later operational change if horizontal PHP-FPM nodes require shared sessions.

Configure `yii\web\User` with sessions enabled, auto-login disabled, a fixed
`loginUrl`, and no new idle or absolute authentication timeout in this refactor.
The seven-day session lifetime remains the effective limit. `findIdentity(id)`
returns an identity only when the canonical user is unique, `status=1`, and
`activation_state='active'`. Thus invited, blocked, disabled or missing users become
guests on their next request. The identity auth key is derived from the current
`user_id` and `session_version` with a deployment secret; changing
`session_version` invalidates an already stored Yii identity without storing a new
credential in the user row. Auto-login stays disabled, so there is no separate
long-lived identity cookie.

The login form normalizes and validates the same corporate email, loads the same
credential row, applies the existing rate-limit window before password admission,
records every attempted outcome in `fm2_pilot_auth_attempts`, and then calls
`Yii::$app->user->login($identity, 0)`. Existing password hashes remain byte-for-byte
unchanged. Verify them with PHP `password_verify()`: Yii 2's current
`Security::validatePassword()` validates bcrypt-shaped hashes before calling PHP and
therefore cannot admit the existing Argon2id hashes. New invitation activation
continues to generate `PASSWORD_ARGON2ID` hashes. Yii Request owns request CSRF;
Yii Security supplies general random values. Neither justifies rehashing credentials.

Authorization remains a fresh read of canonical FMonitor facts, not a second copy
in Yii RBAC tables. A narrow `CheckAccessInterface` adapter delegates
`checkAccess(userId, exactPermission, [])` to `AuthorizeLocalActor`. It rejects
parameters and unknown permission literals, performs no legacy fallback and no
cross-request grant cache, and maps unavailable reads to the established generic
503 path. Controllers declare their exact permission through `AccessControl` and
`User::can(permission, [], false)`. Guest denial redirects HTML GET to
`/pilot/login`; an authenticated user without the exact grant receives 403. Command
authorization is still repeated inside the public application operation where its
existing contract requires it; an HTTP filter is not the owner of domain facts.

## Canonical facts and framework state

The following remain external behavior and data contracts:

- the existing `fm2_pilot_users`, credentials, roles, assignments, permissions,
  invitations, attempts and append-only access-event tables;
- byte-exact role and permission codes, union of permissions across active assigned
  roles, and no hierarchy, wildcard, prefix, case folding or implicit administrator
  business grant;
- admission only for `status=1` plus `activation_state='active'`; invited and blocked
  users cannot authenticate or retain an effective identity;
- current-snapshot permission checks on every HTTP invocation, fail closed on
  missing/incompatible/unavailable canonical facts;
- Argon2id hashes, single-use hashed invitation tokens, activation/reissue
  transactions, rate limiting, safe return URLs, POST+CSRF logout, neutral login
  errors, and access audit atomicity;
- server-side actor attribution and every domain append-only/history invariant.

The following are old implementation assertions and are retired for Yii routes:

| Old assertion | Classification and replacement |
|---|---|
| `fm2auth[_port]`, its ID grammar and seven-day `Set-Cookie` string | Internal protocol; replaced by configured `fm2yii[_port]` and Yii/PHP session IDs. Forced re-login is authorized. |
| Whole-array `serialize()` payload and `PilotSessionPayloadCodec` shape/canonical-byte rules | Internal encoding; replaced by PHP's configured standard session serializer. No legacy decode. |
| `PilotSessionStorage` interface, factory, lazy owner and one-owner request handoff | Internal composition; replaced by the Yii `session` application component. |
| Exact `s-*`, lock, stage and revoked filenames; modes; hard-link/rename/fsync algorithm; collision and GC order | Internal filesystem implementation; not reproduced. Deployment permissions, persistent save path, restart behavior and deny-on-session failure remain operational assertions. |
| Typed storage failure enum, 12-hex correlation and exact primitive traces | Internal diagnostic interface; replaced by Yii error handling and safe structured logging. External response remains a generic no-store 503 without secrets when session startup/write fails. |
| Buffered response may be emitted only after custom `writeCommit`, `regenerate` or `destroyCommit` | Custom lifecycle assertion; replaced by Yii/PHP completing session persistence before response completion. Tests observe no success/redirect on an injected session-write failure, without inspecting filesystem primitives. |
| `auth_user_id`, `auth_email`, `auth_signed_in_at`, `auth_csrf`, command `actor/secret/tokens/flash` keys | Internal payload names; replaced by Yii User, Request CSRF and Session flash/state interfaces. Public actor, anti-CSRF and one-time feedback behavior remain. |
| `FMONITOR_AUTH_USER_ID`, `FMONITOR_AUTH_CSRF` injected by LocalAuth | Temporary adapter protocol. Yii controllers use `Yii::$app->user->id` and request CSRF. A bounded legacy-handler adapter may inject the actor only for unmigrated routes and must be deleted with the last such route. |
| Direct class/file assertions for `RapidPilotLocalAuth`, codec, inspector and filesystem primitives | Architecture ratchets for the old implementation; replace with assertions that Yii owns session/auth and production reachability does not load those files. Historical tests/reviews stay in git. |

The authorized relaxation covers session continuity and disposable test users/data.
It does not relax password, role, invitation, audit, command authorization or domain
history semantics. Production identity/access rows are not deleted by this refactor.

## Module seams

Keep the external seam small:

1. `LocalIdentity::findIdentity(id)` and login lookup expose an admitted identity,
   hiding table joins and active-state rules.
2. `Yii::$app->user` owns login, logout, identity renewal and session regeneration.
3. `Yii::$app->user->can(exactPermission, [], false)` is the controller/test
   authorization interface; its adapter hides canonical RBAC reads and safe failures.
4. Existing IdentityAccess commands continue to own invite, activate, reissue,
   role/status changes and append-only audit in explicit transactions.

Do not create ActiveRecord models for every auth table merely to satisfy Yii. Yii
DB/DAO queries are enough, and one connection/transaction must cover each access
mutation. The identity object is a read result, not the write owner.

## First vertical auth slice

After `YII2-RUNTIME-001` and the first Yii-rendered `/pilot/admin/roles` route exist,
deliver one `YII2-AUTH-LOGIN-001` slice:

1. anonymous `GET /pilot/admin/roles` records a safe return URL and redirects 303
   to the Yii `GET /pilot/login` form;
2. `POST /pilot/login` uses Yii request CSRF, the canonical user/credential lookup,
   existing rate limiting and Argon2id verification;
3. successful login regenerates the Yii session, redirects 303 to the safe return
   URL, and the same browser reaches `/pilot/admin/roles` only when it has exact
   `access.administer` (or the route's approved literal at Gate 1);
4. blocked/invited/disabled identities, inactive roles and near-match permissions
   cannot reach the page; canonical DB/read or session failure is 503;
5. `POST /pilot/logout` with Yii CSRF logs out and a replayed/new request is redirected
   to login; GET logout is rejected.

The slice does not migrate activation, invitations or user administration writes.
Those follow as a separate IdentityAccess command slice after login/logout is green.
The route permission literal must be confirmed in its executable specification;
the design does not silently invent one.

Removal condition for this slice: Yii login/logout and this protected route have no
runtime reference to `RapidPilotLocalAuth`, `PilotSessionPayloadCodec` or
`PilotSessionStorage`. Global deletion waits until all temporary legacy handlers no
longer consume their session protocol.

## Independent public RED plan

Write the executable specification first, then one black-box HTTP test against the
isolated Yii front controller and real MariaDB schema. It must fail before auth wiring
because the protected route cannot complete the new login/session flow. The reviewer
must not inspect session files or private identity methods.

The minimal RED proves the successful vertical path: create an active user with an
existing Argon2id hash and an active assigned role with the exact approved permission;
GET the protected URL, parse the Yii login CSRF field, POST credentials with a cookie
jar, assert session-cookie rotation and 303 back to the protected URL, then assert
200 and the expected roles-page marker. Assert the legacy `fm2auth` cookie alone does
not authenticate. Expected values come from the specification and fixture facts.

Independent sibling cases, approved before implementation, cover:

- wrong password and rate-limit threshold with attempt rows but no disclosure;
- invited, blocked, `status=0`, inactive-role, missing/near-match permission and
  committed revoke on the next request;
- malformed/missing CSRF on login and logout, GET logout, safe-return rejection;
- restart with the new persistent Yii session path (where operationally promised),
  logout invalidation, and `session_version` invalidation;
- injected canonical-read and session-open/write failures returning generic no-store
  503 with no credential, SQL, path, cookie or session payload leak;
- production source/load ratchet: Yii owns the route and the three retired legacy
  auth/session files are unreachable from this slice.

Capture the intended RED command/output, obtain an independent Gate 3 test review,
then implement only this slice. Focused GREEN includes real HTTP, cookie and restart
behavior; later activation/admin slices get separate RED and review. No old test is
silently deleted: each is mapped to an external replacement above or recorded as a
retired internal assertion under the owner decision.

## Official Yii references

- [Authentication guide](https://www.yiiframework.com/doc/guide/2.0/en/security-authentication): configure `yii\web\User` and implement `IdentityInterface`.
- [`yii\web\User`](https://www.yiiframework.com/doc/api/2.0/yii-web-user): session identity renewal, `login()`, `logout()`, `switchIdentity()` and `can()`.
- [`yii\web\Session`](https://www.yiiframework.com/doc/api/2.0/yii-web-session): cookie parameters, save path and ID regeneration.
- [`yii\web\IdentityInterface`](https://www.yiiframework.com/doc/api/2.0/yii-web-identityinterface): session identity lookup and auth-key contract.
- [`yii\rbac\CheckAccessInterface`](https://www.yiiframework.com/doc/api/2.0/yii-rbac-checkaccessinterface): the narrow adapter accepted by `User::can()`.
- [`yii\filters\AccessControl`](https://www.yiiframework.com/doc/api/2.0/yii-filters-accesscontrol): controller admission and guest/authenticated denial behavior.
- [`yii\base\Security`](https://www.yiiframework.com/doc/api/2.0/yii-base-security): password helpers; its current bcrypt-shaped validation guard is why existing Argon2id verification stays on PHP's native password API.
