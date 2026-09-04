# INSPECTION-ITEM-COMPLETE-001 — independent local-auth integration Gate 3 review v1

Date: 2026-09-04  
Reviewer: `/root/inspection_local_auth_gate3` (independently tasked; did not
author the reviewed test, evidence, specification or production)  
Reviewed commit: `fc99bd247679a68d1a624af0cb30f38e0bce7b29`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Approved executable spec `specs/INSPECTION-ITEM-COMPLETE-001.md`: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Cross-cutting local RBAC contract
  `specs/LOCAL-RBAC-AUTH-CONTRACT-001.md`: SHA-256
  `f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392`.
- Corrected raw endpoint/UI verifier: SHA-256
  `324484016245f39458a99ef51e1f89f5489bd37d4f60f01a48f38bae3dec474e`.
- Claimed RED evidence: SHA-256
  `64cd23f0e2e5a5c7a6a20b3bfb4eb7f44bf77543cc8402e7626b1757a2e1add0`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Production route/factory evidence: `public/router.php`
  `a0caae5e029afc189f7f782d3b3f19b7539aa00de067e68b9c943120adf23f77`,
  `app/PilotHttp/production-entrypoint.php`
  `1c7be0b2eefa07ad537353b2ed84dcb03ab94f84bdd97d1864496955fe725d83`,
  `app/PilotHttp/ProductionPilotHttpEntrypointFactory.php`
  `61bd0662c87f029ea3ea8ab0ee24b70a650fa1b28afc1692e034eaf06b898e27`,
  `app/PilotHttp/PilotHttpEntrypoint.php`
  `7464c3610b5466b0c8cfc41d077c189d6384a4ea9499f2997717c7628205de86`,
  and `rapid-pilot/LocalAuth.php`
  `746f5167f3d7e1ae51cc140bc75a7cdf470c316aafbcf4cf09a41b52d4302ca1`.

The reviewed commit changes one test input from legacy `REMOTE_USER` to
`FMONITOR_AUTH_USER_ID=7301` and adds the RED narrative. It does not alter the
approved HTTP/JSON/mutation expectations or production.

## Independent reproduction

Docker was available at `/Applications/Docker.app/Contents/Resources/bin`; the
existing Compose MariaDB was healthy. Syntax passed:

```text
php -l tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
No syntax errors detected
```

The ordinary RED wrapper reproduced an endpoint failure:

```text
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
PRIMARY: JsonException: Syntax error
RED_ASSERTION: expected failing behavior observed
```

The public UI mode reproduced a non-200 checklist page and unavailable item-only
controls:

```text
FMONITOR_TEST_ITEM_UI_CONTRACT=1 tools/verification/run.sh red \
  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
PRIMARY: TestFailure: Item-only UI/client contract:
- public checklist page is HTTP 200
- root exposes item-only completion capability
- all 42 item completion controls are usable
...
RED_ASSERTION: expected failing behavior observed
```

Both runs cleaned their exact temporary database, router and artifact root. A
post-run database probe found zero `t_iea_%` schemas and no `iea-*` artifact
root remained.

## Blocking finding LAI-01 — RED does not reach the claimed LocalAuth handoff

The failure is real but is not RED for the stated missing behavior. The public
router loads `app/PilotHttp/production-entrypoint.php`, which calls
`ProductionPilotHttpEntrypointFactory::create()`. At the reviewed fixed point,
that method constructs `PilotHttpEntrypoint` **without** its optional LocalAuth
callback. Only the separate `createWithSessionStorageDependencies()` path passes
`self::localAuth($owner)`, and the reviewed verifier does not use that path.

Consequently no callback updates PHP server globals during these requests and
there is no callback-produced identity to lose from the request-array copy. The
test simply removes the only legacy `REMOTE_USER` principal while supplying an
environment/server value which is not a canonical authenticated session. The
ordinary route then fails legacy identity resolution at generic 401, causing
the later JSON decode failure; the UI mode fails for the same upstream reason.
This contradicts the RED evidence statement that the production entrypoint
invokes `RapidPilotLocalAuth` and then passes a stale pre-auth copy.

The current test therefore cannot distinguish the intended copy-propagation
implementation from materially different changes such as restoring/bypassing
legacy principal admission or treating `FMONITOR_AUTH_USER_ID` environment data
as authentication authority. That is a security-sensitive false-positive: the
local RBAC contract requires actor identity to come only from the approved local
authentication/session boundary and forbids client/request identity authority
or a second owner.

## Required changes

1. Return the verifier/evidence to Gate 2 and make the public test enter the
   actual production LocalAuth/session path with canonical owner-produced
   authenticated session state. The test must not obtain positive authority
   merely from `FMONITOR_AUTH_USER_ID`, `REMOTE_USER`, a client header or a
   parallel test-owned identity seam.
2. Demonstrate that LocalAuth really runs and produces the exact trusted actor
   ID and CSRF, while the subsequent request construction still sees the
   pre-auth copy and yields the intended 401/non-200 RED. The proof must fail if
   the LocalAuth callback is absent, skipped, or replaced by direct environment
   identity.
3. Retain the existing exact-capability-only fixture and all item-only
   HTTP/JSON/UI/mutation expectations, including non-item denial. Minimal GREEN
   may propagate only values produced by that callback into the same request;
   it must not add another session owner or broaden identity authority.
4. Correct the operations RED narrative to match the reproduced public path,
   then request a fresh independent Gate 3 review on exact hashes.

Gate 4 is not admitted for `fc99bd2`.
