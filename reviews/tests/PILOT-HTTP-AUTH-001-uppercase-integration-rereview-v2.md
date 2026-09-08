# PILOT-HTTP-AUTH-001 uppercase integration — independent Gate 3 rereview v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/http_auth_uppercase_rereview`
- Reviewed commit: `61b717353515b3d8c1295c07faa548fc28f618da`
- Predecessor rereview: `reviews/tests/PILOT-HTTP-AUTH-001-uppercase-integration-rereview-v1.md` (`CHANGES_REQUESTED`)
- Scope: corrected uppercase exact-legacy-identity assertion at `tests/InstallationProcess/pilot_http_auth_001_test.php:65`
- Verdict for this correction: **APPROVED**

## Gate 3 result

The corrected scenario is traceable to approved `PILOT-HTTP-AUTH-001 v0.12`:
`REMOTE_USER` is not case-folded and an exact active legacy user plus exact
active legacy role owns both admission and display identity for `GET /pilot/`.
The fixture contains two distinct principals, lowercase user `18` and uppercase
user `23`. The assertion now requires:

- `200` for exact `SIDOROV@shlz.ru`;
- visible display name `Upper Exact` from row `23`;
- absence of lowercase row `18` display name;
- the full inherited scripted HTML security/CSP envelope.

This is sufficiently sensitive to the prior wrong `403`, principal
case-folding, display-identity substitution and loss of the route's security
headers. The adjacent unchanged mixed-case principal `sIdorov@shlz.ru` remains
`403`, so a permissive case-insensitive implementation cannot satisfy the
matrix. Expected values come from the approved contract and fixture, not from
current production structure. The test uses the real public HTTP/MariaDB seam,
and the correction introduces no production change.

The focused run reaches and passes all new uppercase assertions. It then fails
at the next unchanged line-67 assertion. That later failure does not invalidate
the independently reviewed uppercase correction, but the cumulative file is
not globally approved/green until the separately classified stale fault fixture
is corrected and freshly reviewed.

## Independent reproduction

```text
$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
  FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_http_auth_001_test.php

Fatal error: Uncaught TestFailure: query failure status
Expected: 503
Actual: 200
... pilot_http_auth_001_test.php(67)
```

No line-65 failure occurs. The run exits `255` only at the downstream fault
case. `git diff --check 61b7173^..61b7173` passes.

## Separate downstream classification

The line-67 expectation is contract-correct (`503` when the shell's required
identity-directory query is unavailable), but its fault injection is stale.
It renames `process_fm2_pilot_role_permissions` and then requests `/pilot/`.
Under controlling `PILOT-HTTP-AUTH-001`, that route resolves the exact legacy
identity through `legacy_users` joined to `legacy_users_roles`; it does not
consult local permissions. Therefore compatibility `200` is the required
result while only the unrelated local permission table is hidden.

Neither the UI-shell contracts nor `LOCAL-RBAC-AUTH-CONTRACT-001` change this:
the former own representation, while the latter's owner-approved first route
is only `/pilot/objects → objects.read`. `PILOT-SESSION-STORAGE-001 v10` owns
session lifecycle and likewise does not move `/pilot/` authorization to local
RBAC. Production is conforming; forcing it to read the local permission table
for the root shell would be an unauthorized behavior change.

The stale injection originated in checkpoint commit
`2bff0a0e6baaab61679321001c57cbc916609295`. Before that commit the same
`query failure` scenario renamed `legacy_users`, which actually owns the
required lookup.

## Exact required next action

Return the downstream line-67 fault scenario to Gate 2 and change only its
fault target/restoration from:

```text
process_fm2_pilot_role_permissions
```

to:

```text
legacy_users
```

Retain the request `GET /pilot/`, expected exact redacted `503`,
`Retry-After: 60`, security headers and cleanup rename-back. Demonstrate that
this corrected injection reaches the intended legacy-directory query failure,
then obtain a fresh independent Gate 3 for the new cumulative test hash. No
production change and no Gate 1 amendment are required for that correction.

If the product instead wants `/pilot/` to depend on local session/RBAC, that is
a distinct route-authority migration requiring an executable successor Gate 1
and owner approval before any corresponding test or production change.

## Reviewed-input hashes

```text
4a7f86cbe59bf7352506021d32608fe164115457ff1aa4f80ae797f4b9c4a3cb  tests/InstallationProcess/pilot_http_auth_001_test.php
8881711364e7b10a335ee823f31cfc7e2e43e538948be772822f96d59acb04e5  docs/operations/pilot-http-auth-uppercase-gate2-correction-2026-09-04.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
0a5a78836e78ccb2219f7c99a37178f15d8229b225a84636a4017908d727a853  app/PilotHttp/PilotShellView.php
b77e9672237088a767d7e7f123b802aa6abc09d111d26df7aeae799eed96be4a  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
```
