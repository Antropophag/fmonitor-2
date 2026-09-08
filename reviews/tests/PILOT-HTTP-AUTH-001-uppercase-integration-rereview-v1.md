# PILOT-HTTP-AUTH-001 uppercase integration — independent test rereview v1

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/http_auth_uppercase_rereview`
- Reviewed HEAD: `2f925ccdd9335d88246f6b5e3ea7a2a7956b86e6`
- Public seam: production `GET /pilot/` through `public/router.php`
- Disputed assertion: `tests/InstallationProcess/pilot_http_auth_001_test.php:65`
- Verdict: **CHANGES_REQUESTED — stale test expectation; no production fix is authorized**

## Independent conclusion

The controlling approved contract for the exact `/pilot/` shell in the
production `create()` composition remains `PILOT-HTTP-AUTH-001 v0.12`. It says
that `REMOTE_USER` is resolved byte-exactly against an active legacy user with
an active legacy role and that the read-only shell is available to every such
exact match. The fixture deliberately contains the distinct exact row
`SIDOROV@shlz.ru` (legacy user `23`, active role `5`). Its specified and
historically reviewed result is therefore `200`, displaying `Upper Exact`.

`LOCAL-RBAC-AUTH-CONTRACT-001` does not supersede that shell contract. Its
approved first vertical consumer is only `GET /pilot/objects → objects.read`;
it explicitly leaves other routes for separately gated successor slices.
Likewise, `PILOT-SESSION-STORAGE-001 v10` owns session lifecycle and bounded
payload handoff, but does not amend `/pilot/` admission or turn the absence of a
local row for legacy user `23` into `403`.

The current `403` expectation was introduced in checkpoint commit
`2bff0a0e6baaab61679321001c57cbc916609295` together with local-RBAC fixture
rows. No corresponding approved amendment to `PILOT-HTTP-AUTH-001`, no
route-specific successor Gate 1, and no fresh Gate 3 authority were found.
Before that commit, this exact fixture asserted `200` as
`byte exact uppercase distinct user`. Thus the expectation, not the fixture
and not current production, is stale relative to the controlling contract.

## Reproduction

At the reviewed HEAD, with the active local MariaDB test endpoint:

```text
$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
  FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_http_auth_001_test.php

Fatal error: Uncaught TestFailure: byte-exact uppercase legacy identity has no distinct canonical local grant status
Expected: 403
Actual: 200
```

This reaches the intended real HTTP/MariaDB seam. Setup and the preceding
shell cases succeed; failure is isolated to line 65. Production `create()`
constructs the legacy `RemoteUserIdentity`/`MariaDbHttpUserDirectory` path and
does not install the optional LocalAuth closure. `PilotHttpCoordinator` applies
local authorization only to the already migrated `/pilot/objects` (and its
separately wired prepare route), then resolves `/pilot/` through the exact
legacy directory. The observed `200` is consequently contract-conforming.

The injected `createWithSessionStorageDependencies()` path does install
LocalAuth, but its closure intentionally leaves an already present non-empty
`REMOTE_USER` untouched. It therefore cannot serve as authority for the new
line-65 expectation either. `LocalAuthenticatedHttpUser` also retains explicit
legacy fallback when a trusted local actor ID is absent on successor routes;
that broader migration debt is outside this predecessor test correction.

## Required next action

1. Return this cumulative predecessor test to Gate 2 and restore the line-65
   expected outcome to `200` with the fixture's exact legacy identity/display
   assertion. Preserve the uppercase row: it is useful sensitivity for the
   byte-exact legacy contract.
2. Obtain a fresh independent Gate 3 review for the corrected cumulative test
   because its current hash no longer matches the original approved Gate 3
   manifest and the unreviewed expectation change was behavior-significant.
3. Do not change production to force `403` under `PILOT-HTTP-AUTH-001 v0.12`.

If the intended target is instead to migrate `/pilot/` itself to local
session identity and local RBAC, first create a separate executable Gate 1
amendment/successor and obtain owner approval. It must decide at least:

- whether `/pilot/` requires a named permission, and which exact literal;
- whether a non-empty `REMOTE_USER` remains admissible, is only a decoy, or is
  prohibited when local session identity is absent;
- the exact `401/403/503` precedence for absent/malformed session identity,
  legacy-only exact rows, inactive local actors and unavailable local RBAC;
- whether `ProductionPilotHttpEntrypointFactory::create()` and the injected
  construction seam must have identical authentication ownership;
- removal of any legacy fallback on the migrated route and preservation of
  CSS/read ordering, zero writes and representation identity.

Only that owner-approved Gate 1 may authorize a new RED expecting `403` for
the uppercase legacy-only fixture and subsequent production migration.

## Reviewed evidence

```text
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
a16229cb573cf48abe743c993afdc968fc7925a92b3a0469d8ec908fcec0cf3a  tests/InstallationProcess/pilot_http_auth_001_test.php
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
b77e9672237088a767d7e7f123b802aa6abc09d111d26df7aeae799eed96be4a  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
f6491662738821743976e06086bcb988269c78a4b3d87b9899df4f65575b30b0  app/PilotHttp/PilotE2ECoordinator.php
910fce64bfc23ec73a4b813effabe6ab1b1dfb503963eafd6c402dd06ebbb9ba  app/PilotHttp/LocalAuthenticatedHttpUser.php
```

Also reviewed: `PRODUCT.md`, `CONTEXT.md`, pilot product/data-model contracts,
`docs/development-process.md`, local-RBAC/session owner approvals, and the
existing Gate 3/Gate 5 records for all three named specifications.
