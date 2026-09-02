# Independent integration code rereview — PILOT-CSP-RBAC-INTEGRATION-001

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED (scoped)**

The reviewer did not author or edit the reviewed production/tests. This record
is the only edit. The verdict covers only the bounded CSP/local-RBAC integration
listed below; it does not approve concurrent completion-v10 work or declare the
repository fully integrated.

The `code-review` skill calls for separate parallel Standards and Spec agents.
Both additional agent slots were unavailable at review time (`agent thread
limit reached`), so this already-independent reviewer performed and recorded
the two axes separately rather than fabricating agent independence.

## Scope and hashes

```text
567cd92982259c8197458f1f6ca575a916fe4355fb26fddea2ce25b430f6baf8  app/PilotHttp/PilotView.php
67b57cf3758dfbc0a1cd418d0fd98e5ebdea395ed76cdc56bfc3e1a53a779999  app/PilotHttp/PilotHttp.php
6f36c67bb2af7a21f3be517c3fa2623ae0235ec09d51d51966f4cc45a73874cd  app/PilotHttp/ProductionLocalObjectListAuthorization.php
bd9008cf2c15b1e418f5c8e6b4fbbc2f83b6030c0e4053cc1427fad90969dafb  tests/InstallationProcess/pilot_shlz_assets_001_test.php
5eb2e5d66e177dd9e630977d0f25bf42a35519a0f64ac6ddb3fb7a280bb53930  tests/InstallationProcess/pilot_http_auth_001_test.php
bc00672fff13dd46ab43a6f09a91c854597f9dc81214346f4e587b1ceda9e39b  docs/operations/pilot-css-canonical-href-owner-decision.md
```

Reviewed behavior:

- canonical configured HTML href `/pilot/assets/pilot.css`;
- `PilotHttpGateway` unexpected/close/correlation failure responses retaining
  byte-exact BASE CSP;
- lazy environment access by `ProductionLocalObjectListAuthorization`;
- exact local identity/RBAC fixture needed by the SHLZ configured-HTML probe;
- RBAC/process-prefix/environment-read/CSP-preservation portions of
  `pilot_http_auth_001_test.php`.

Excluded: its v9 migration expectation, already approved separately in
`docs/operations/inspection-planning-v9-integration-fixture-review.md`; all
completion-v10 implementation/fixture changes; and unrelated global-call
qualification edits in the shared `PilotHttp.php` hotspot.

## Standards axis

**APPROVED — no scoped finding.**

The canonical stylesheet change is one presentation literal in `PilotView` and
matches existing asset ownership; aliases remain available as explicitly
decided. Gateway fallback uses the existing pure `PilotRouteCsp::BASE` value and
does not duplicate or broaden the route classifier. The local authorization
adapter remains an HTTP adapter over the application-owned `IdentityAccess`
seam, with no domain write, legacy authority fallback or runtime DDL.

Its constructor performs no environment read, DB connection or schema access.
Authorization configuration is loaded only when exact `/pilot/objects`
admission is reached; profile configuration/connection is loaded only after an
`AUTHORIZED` result. Cached objects are request-instance resources, while every
authorization invocation still executes the application seam against current
facts. Unknown routes, invalid methods, redirects and public assets are handled
before local authorization.

The SHLZ test adds only canonical local user/role/assignment/permission fixture
facts; it does not weaken path, descriptor, CSS graph, response, security or
ordering assertions. The HTTP auth fixture retains exact environment read
ordering and adds only the process prefix required by local authorization.

No new architecture exception, rapid-pilot domain logic, persistence owner or
material Fowler smell was introduced by this bounded integration diff. The
compact pre-existing `PilotHttp.php` hotspot remains difficult to read, but the
reviewed changes delegate rather than deepen ownership there.

## Spec axis

**APPROVED — no scoped finding.**

The owner-approved canonical href is exact and preserves `shlz.css` before
`pilot.css`; no CSS bytes, cache policy, alias routes or CSP permission change.
The SHLZ public seam passes with the exact local grant fixture.

All coordinator responses continue through final representation-based CSP
selection. Authorization `401/403`, local-authorization `503`, gateway failure,
asset/error/redirect and non-HTML outcomes receive BASE CSP. Only successful
allowlisted HTML containing an external same-origin pilot script can receive a
script policy; local RBAC cannot widen CSP or turn CSP into a grant. Gateway
failure now explicitly carries BASE CSP alongside the established security,
no-store, length and redacted-body headers.

`GET /pilot/objects` remains the sole migrated local-RBAC route and passes only
literal `objects.read`. Missing trusted actor returns generic `401`; known actor
without the complete active local grant returns generic `403`; schema/read/
configuration ambiguity returns generic `503` with the approved opaque
correlation behavior. These outcomes occur before profile, list renderer or
business read. No `REMOTE_USER`, email, legacy role/right, client-selected
permission, case/prefix/wildcard or authenticated-only fallback was added.
Committed revoke, multi-role union and inactive user/activation/role behavior
remain green through the dedicated public route test.

## Verification

Green in the reviewed worktree:

```text
php tests/InstallationProcess/pilot_shlz_assets_001_test.php
PASS: PILOT-SHLZ-ASSETS-001 public CSS manifest

php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

php tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
pilot_route_csp_inventory_001_test: PASS

php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS

php tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
pilot_route_csp_completion_flow_001_test: PASS
```

PHP lint for all five reviewed files and `git diff --check` are green.

## Concurrent failures outside verdict

During this rereview the canonical runner advanced from v9 to v10. Therefore
`pilot_http_auth_001_test.php` now stops at its separately-owned migration
fixture expectation (`expected schemaVersion 9`, actual `10`) before reaching
the reviewed auth assertions. This does not contradict the separately approved
v9 edit, but the fixture must advance through its own review before full-suite
integration can be claimed.

The completion final-HTML CSP test now lacks the new exact completion-family
correction table and consequently observes CompletionFlow's fail-closed
unenhanced representation (`data-progress-cap` absent). `make
architecture-check` likewise reports only the new concurrent
`InstallationCompletionSchemaMigration.php` hotspot. Both belong to the active
completion-v10 integration, not this CSP/RBAC diff.

The direct login CSP test could not be reproduced on the host harness: its
container-default DB host was unavailable, and the host override reached the DB
but not a usable session-cookie fixture. Its previously approved CSP bytes were
not changed in this scope; the inventory/classifier and gateway matrices are
green.

Accordingly, this record approves the exact bounded CSP/RBAC integration hashes
above but does **not** close CSP task 3.4, RBAC integration Done, architecture or
full verification. Those remain open until the newly landed v10 fixtures and
architecture baseline are independently reconciled and the full focused HTTP
suite reaches its assertions.
