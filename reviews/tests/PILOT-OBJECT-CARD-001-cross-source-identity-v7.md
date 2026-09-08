# PILOT-OBJECT-CARD-001 — independent cross-source identity Gate 3 review v7

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_card_upload_gate3`
- Reviewed commit: `91534b2f72c71c221a987962ff7d726dab9d8ca9`
- Gate 2 base: `3c08131578a1d7de002a86549f5949c0770fdd63`
- Public seam: configured raw HTTP `GET|HEAD /pilot/objects/4512`
- Verdict: **APPROVED**

The reviewer authored neither specifications, tests, production, RED evidence
nor correction. This append-only review record is the only review edit.

## Independent assessment

The four new cross-source fixtures isolate the approved legacy admission source
from the non-authoritative local access projection:

- actor `26` has an inactive exact legacy user but active local user/role with
  zero permissions and process capabilities; GET and HEAD require exact `403`;
- actor `27` has an active exact legacy user assigned to inactive legacy role
  `6`, while local user/role are active and empty; GET and HEAD require `403`;
- actor `28` has active legacy user/role but an inactive local user; GET and HEAD
  require full configured `200` card with no action;
- actor `29` has active legacy user/role and no local identity row; GET and HEAD
  require the same full `200` card with no action.

Actor `25` remains an independent active legacy/local control with zero local
permissions and zero process capabilities and still requires `200`. Together,
these contrasts reject local permission, local status, local existence, or
process capability as an accidental card-read admission gate while retaining
exact legacy user and active-role authority.

The fixtures use explicit independent names in both the legacy literals and
local setup where a local row exists. Actor `29` has no local row, so its exact
visible legacy name independently proves the fallback/source ownership rather
than a helper default. Actor `28`'s local inactivity with successful response
separately proves that the local row is not authoritative for admission. None
of the expected response values is derived from production output.

The denied cases use `pocParity()` and exact generic `403` bodies; no actor,
object ID, registration, address, shell, SQL or internal reason can leak. A
separate newly created SQL principal receives only column-level SELECT on:

```text
legacy_users: id, name, email, role_id, status
legacy_users_roles: id, status
poc_fm2_pilot_users: user_id, full_name, email, status, activation_state
poc_fm2_pilot_roles: role_id, status
poc_fm2_pilot_user_roles: user_id, role_id
```

It receives no permission-table, phone/source, object/process/artifact table or
write privilege. Actor `26` still must return exact `403`; therefore the denial
is executable before any card read and does not depend on broad directory or
object access. The unique SQL account has no ambient grants and is dropped in
the outer attempt-safe cleanup.

Successful actors `28/29` run full GET/HEAD parity, independently fixed Example
A content, exact shared shell/stylesheets/breadcrumb/current navigation, CSP,
ordered scripts and closed href/action multiset. Actor `25` and all prior
configured state/capable/PTO/broad cases remain intact.

The database fingerprint is captured after fixture/grant construction and
compared after all public requests; filesystem guards cover every request. All
added servers are stopped in local `finally`, retained outer cleanup covers
partial execution, and the new SQL principal is always removed.

No blocking traceability, seam, authorization contrast, read-order sensitivity,
expected-value independence, privilege, redaction, scope, determinism,
isolation, mutation or cleanup finding remains. The full prior content/action/
CSP/RBAC/failure/corruption matrices were not weakened.

## Reproduced evidence

At `2026-09-04T12:39:01+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check \
    3c08131578a1d7de002a86549f5949c0770fdd63..91534b2f72c71c221a987962ff7d726dab9d8ca9
PASS (no output)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PHP Fatal error: Uncaught TestFailure:
legacy inactive despite active local identity denied before card read status
Expected: 403
Actual: 200
... pilot_object_card_001_test.php(599): pocError()
exit 255
```

All prior fixture, Example A, configured shell, exact links, CSP/scripts and
read-only assertions pass first. The failure is the intended legacy-identity
authorization RED, not setup failure.

## Reviewed SHA-256 inputs

```text
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
fb34e326b3de97c814b1a97f205a56eec45a20d7c976baa284f940c85798b905  docs/operations/pilot-object-card-cross-source-identity-red-v7-2026-09-04.md
f18b804204c838965daf70c3aa81b3e2b609db67c5df58e658302aaf321c88d8  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v6.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
065d4bddd974cf8acec1becff5e85fda23adef728ef44b3b6f6f9a45a7ae647d  app/PilotHttp/ObjectCardView.php
39a27d0d686b0c548592d33acf9ce539d1b0a5909b38d059c9a93faba7ba816f  app/PilotHttp/PilotHttp.php
5f225ece450786d653badcf2f900fed23d42438740901aece904ef4aca471d25  app/PilotHttp/PilotView.php
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
```

The review path is metadata because a self-hash is circular. Relevant spec,
test, support or scanned production membership changes require fresh review.

Gate 4 is authorized for exact reviewed test bytes at
`91534b2f72c71c221a987962ff7d726dab9d8ca9`. This record makes no GREEN or Gate
5 claim.
