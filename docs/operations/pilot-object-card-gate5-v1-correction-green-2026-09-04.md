# PILOT-OBJECT-CARD-001 Gate 5 v1 correction GREEN — 2026-09-04

## Correction scope

- Source review/head: `reviews/code/PILOT-OBJECT-CARD-001-upload-first-integration-v1.md`
  at `329fd80b0cda7ab4271204090cfe107b4da918ba`.
- Blocking finding corrected: `G5-v1-1`.
- Production candidate: `0e3eaf182a413ee692c28a3d71324ad1bb5d4f88`.
- Production files changed: `app/PilotHttp/ObjectCardView.php` and
  `app/PilotHttp/PilotShellView.php` only.
- Tests, specifications and reviews changed: none.

`ProductionObjectCardRenderer` now owns an explicit private `cardBody()`
composition returning the escaped object ID and body HTML from the card view
model. The configured renderer passes that body directly to `PilotView` and
adds the approved object-details script. The compatibility renderer passes the
same body directly to the shell-owned
`ProductionPilotShellRenderer::renderObjectCardCompatibility()` method.

There is no serialized-document search, `<main>`/`</nav>` marker coupling,
substring fallback, duplicate compatibility document in the card renderer, or
dead `scripts` boolean branch. The focused verifier proves the observable HTML
bytes remain accepted for configured and compatibility paths.

## Verification

At `2026-09-04T12:59:56+03:00` through
`2026-09-04T13:00:09+03:00`, on the exact production tree committed as
`0e3eaf182a413ee692c28a3d71324ad1bb5d4f88`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ make lint
exit 0

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check
PASS (no output)
```

The applicable object-list regression reaches the separately owned navigation
removal RED:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_list_001_test.php
TestFailure: approved removal predecessor: no work item or root navigation destination
Expected: 0
Actual: 2
exit 255
```

Exact relevant bytes:

```text
d61a808afcee2e39f594afee2e73069d95d2e5e8fbe57315e883f313db4f61e5  app/PilotHttp/ObjectCardView.php
6a1258e2d268bc42acbca47cfd026b570876f682f51fb491eec62cd93ddd0b37  app/PilotHttp/PilotShellView.php
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
9b59379329ee11f5756578e046ac4e745df29097a696634ca4f25efa80d96139  reviews/code/PILOT-OBJECT-CARD-001-upload-first-integration-v1.md
```

The append-only whitespace note `G5-v1-2`, repository-wide verification,
navigation removal and fresh independent Gate 5 are outside this bounded
production correction. No Gate 5 approval or integration completion is claimed.
