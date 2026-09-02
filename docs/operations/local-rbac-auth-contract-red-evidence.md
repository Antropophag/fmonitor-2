# LOCAL-RBAC-AUTH-CONTRACT-001 Gate 2 corrected RED evidence

- Date: `2026-09-02`
- Approved executable spec SHA-256: `f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392`
- Scope: OpenSpec tasks `2.1`–`2.4`, corrected after the first Gate 3
  `CHANGES_REQUESTED`; production code was not changed.
- Application-test SHA-256: `838e6fe4eb4d978cd92385b1615f923c18c763ad858a1b37e2ee1a5e8c386000`
- Real-route-test SHA-256: `bfbf9f1a9ced25873dcb189e384829680033ebf571565f7b0c2c80661bbb7c7a`

## Confirmed seams

- Application seam: `authorizeLocalActor(authenticatedLocalUserId, requiredPermission)` with four typed outcomes.
- HTTP seam: real raw `GET /pilot/objects`; the route owns the literal `objects.read` and admission precedes protected CSS, handler, read-model, and business-persistence reads.

## Characterization

Command:

```text
php tests/Verification/characterize_local_rbac_route_mappings_001_test.php
```

Result: `PASS`. Current evidence records `objects.read`, activation and active-role checks, exact PHP comparison, and the two existing object-route references. It deliberately does not approve `REMOTE_USER`, boolean `AccessPolicy`, or fallback behavior.

## Intentional application-seam RED

Command:

```text
bash tools/verification/run.sh red tests/InstallationProcess/local_rbac_auth_contract_001_test.php
```

Observed intended failure:

```text
TestFailure: INTENTIONAL_RED: application-owned authorization seam exists
Expected: true
Actual: false
RED_ASSERTION: expected failing behavior observed
```

Classification: `RED_ASSERTION`, not setup failure. The approved
application-owned seam is absent. Assertions staged after this first intended
failure cover exact grant and cross-route exact denial; missing/inactive user;
both canonical non-active activation states (`invited`, `blocked`); assignment,
active-role and exact-permission links; near-match and legacy fallback denial;
multi-role union/deactivation; configuration/schema/read unavailable categories;
duplicate identity; correlation; repeat/read-only behavior; and committed
revoke. A deterministic public-adapter barrier additionally commits a second
RBAC state after identity resolution and before grant resolution: the first
state has an active actor without a permission, while the second has an
inactive actor with the permission. Neither state authorizes, so the only exact
accepted results are `ACCESS_DENIED` from one consistent snapshot or typed
`AUTHORIZATION_UNAVAILABLE(AUTHORIZATION_READ_FAILED, correlationId)`; a mixed
commit `AUTHORIZED` fails. Those staged assertions did not execute in this RED
run and are not reported as observed outcomes.

## Intentional real-route RED

Command:

```text
bash tools/verification/run.sh red tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
```

The corrected test successfully creates canonical local-user/role/assignment/
permission tables, an independently readable real object projection, a full
read account for the positive route, and a separate authorization-only DB
account granted SELECT on the four RBAC tables but not the object/business
tables. It starts the real PHP HTTP server. The first probe gives role `701`
only `assignment_order.prepare` and gives exact `objects.read` only to the
second active assigned role `702`; this makes a first-role-only or always-deny
implementation observable. Observed intended behavior mismatch:

```text
exact objects.read grant admits real objects handler despite client-selected alternatives
Expected: 200
Actual: 401
RED_ASSERTION: expected failing behavior observed
```

Classification: `RED_ASSERTION`, not setup failure. The database, reader,
canonical grant, CSS asset and real object handler fixture were all available;
`/pilot/objects` still begins with legacy `REMOTE_USER` instead of the approved
local actor boundary. Assertions staged after that deterministic first RED (and
therefore not observed in this run) cover: deactivating role `702` after the
two-role success; wrong and near-match grants; real MariaDB route denials for
`invited`, `blocked`, and `user.status=0`; committed revoke; and missing/wrong
trusted local actor while query fields, cookies, headers and `REMOTE_USER`
hostilely claim actor `7301` and `objects.read`. Denial probes use the
authorization-only DB account, so `403` is impossible if the protected object
read executes: that read lacks a grant and would fail instead. Later unavailable
assertions cover missing schema, duplicate identity, invalid configuration and
read failure as generic `503`, with matching opaque correlation ID and redacted
safe-category logs. For each of all three unavailable categories the corrected
test requires exactly one event matching the closed grammar
`FMONITOR_AUTHORIZATION_UNAVAILABLE category=<EXPECTED_ALLOWLIST_CATEGORY>
correlation_id=<SAME_12HEX>` on one line. It rejects any extra authorization
category; user IDs `7301`, role IDs `701/702`, fixture names/emails, permission
values, DB/schema/table names, SQL verbs/statements, DB credentials, prefixes
and configuration-secret values. A closed test-owned transcription from the
approved v6 identity/access manifest additionally rejects every column,
primary/secondary/unique index, and FK constraint identifier belonging to the
four authoritative RBAC tables. A separate closed list rejects
`information_schema` catalogue identifiers and MariaDB-style schema error
fragments including unknown/undefined/invalid/duplicate column or key,
unknown/failed FK constraint, and missing table/view wording. Neither list is
derived from future GREEN code or live catalogue inspection. These are retained
sensitivity expectations for Gate 3, not claimed current production
observations.

## Gate state

Gate 2 is demonstrated. No test-review verdict is claimed here; Gate 3 requires a separately tasked reviewer who did not author these tests.
