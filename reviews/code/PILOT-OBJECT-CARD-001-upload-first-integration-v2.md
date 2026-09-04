# PILOT-OBJECT-CARD-001 — upload-first integration Gate 5 rereview v2

- Date: `2026-09-04`
- Aggregating reviewer: separately tasked agent `/root/object_card_gate5_final`
- Standards reviewer: separately tasked agent
  `/root/object_card_gate5_final/standards_axis`
- Reviewed exact head: `a67f0a7e6cfcc86e2095c8de4b54e51c1c1beb6f`
- Correction base / prior review commit:
  `329fd80b0cda7ab4271204090cfe107b4da918ba`
- Correction production commit:
  `0e3eaf182a413ee692c28a3d71324ad1bb5d4f88`
- Public seam: configured and compatibility raw HTTP
  `GET|HEAD /pilot/objects/{positive-id}`
- Verdict: **APPROVED for the bounded object-card slice**

The reviewers authored none of the specifications, tests, RED evidence,
production or Gate 5 correction. This append-only record is the only review
edit. It supersedes v1 only for its corrected findings and does not reuse the
older approval for the changed integration composition.

## Standards

Fresh independent verdict: **APPROVED** — 0 findings.

`G5-v1-1` is closed. `ProductionObjectCardRenderer::cardBody()` is now the
explicit card composition seam. Both configured and compatibility rendering
consume the returned body directly. The compatibility path delegates document
composition to the shell-owning `ProductionPilotShellRenderer`; it no longer
searches serialized HTML for exact `<main>`, `</main>` or `</nav>` strings and
has no string-coupled fallback. The compatibility document is no longer owned
or duplicated by the card renderer.

The dead `legacyDocument(..., bool $scripts)` parameter and unreachable true
branch are removed. The configured and compatibility shells are now colocated
with their owner and intentionally differ in route-specific title,
navigation/current-page and asset semantics. The private two-string tuple is a
local, single-purpose return value, not an actionable data clump or speculative
abstraction.

Architecture remains GREEN (`7/7`), both changed production files are
syntactically valid through `make lint`, and the correction range plus current
working tree are `git diff --check` clean. No new documented-standard breach or
actionable smell was found.

The historical range-whitespace note `G5-v1-2` remains accurately preserved in
v1. It is not introduced by this correction, changes no executable byte, and
does not block this bounded rereview. This record does not rewrite prior
append-only evidence.

## Spec

Fresh aggregator verdict: **APPROVED** — 0 findings.

The correction is behavior-preserving and changes no approved specification,
test, fixture, authorization rule, read model or entry-point decision. The
focused public-HTTP verifier exercises both configured and compatibility card
paths and retains exact accepted output. In particular:

- exact active legacy user/active legacy role remains the broad card-read
  admission authority, independently of local identity/status/permission or a
  process capability;
- inactive legacy identity/role remains opaque `403`, and the identity-only SQL
  path remains least-privilege;
- `assignment_order.prepare` controls only the eligible upload-first action;
- configured shared-shell selection remains configuration-only;
- exact five-group content, current order/team, complete state matrix,
  newest-three durable events, one eligible `Загрузить распоряжение` action,
  negative action cases and ordered source-only scripts are unchanged;
- no artifact read or mutation was introduced; GET/HEAD, redaction, escaping,
  deterministic reads and cleanup remain covered.

The approved Gate 3 v3/v6/v7 test bytes and support bytes are unchanged and
remain applicable. No missing/partial requirement, wrong implementation, scope
creep, invariant bypass, authorization regression or test-sensitivity gap was
found.

## Reproduced verification

At `2026-09-04T13:03:28+03:00` through
`2026-09-04T13:04:16+03:00`, on exact head
`a67f0a7e6cfcc86e2095c8de4b54e51c1c1beb6f`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ make lint
exit 0

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check \
    329fd80b0cda7ab4271204090cfe107b4da918ba..a67f0a7e6cfcc86e2095c8de4b54e51c1c1beb6f
PASS (no output)

$ git diff --check
PASS (no output)
```

## Repository-wide boundary

The latest full `make verify` record remains pinned to the prior exact code
head and reports:

```text
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

No literal `VERIFY_OK` exists. The correction did not receive a new
repository-wide run, and this bounded approval does not claim otherwise. The
known navigation/UI-shell/E2E and checklist/session predecessor failures remain
separately owned. Therefore this Gate 5 approval completes only the
object-card upload-first/cross-source integration slice; repository integration,
CI bootstrap, release readiness and merge remain **NO-GO**.

## Exact reviewed-input manifest

```text
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
7bd594aea6aa60240bc474862c64cc4e3be17020437d326caa40e1c17430429b  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v3.md
f18b804204c838965daf70c3aa81b3e2b609db67c5df58e658302aaf321c88d8  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v6.md
3d9a8388de31ba9d52f092d1f4d78914f0839e1a8a1bf491a6ccc3a3713c673a  reviews/tests/PILOT-OBJECT-CARD-001-cross-source-identity-v7.md
9b59379329ee11f5756578e046ac4e745df29097a696634ca4f25efa80d96139  reviews/code/PILOT-OBJECT-CARD-001-upload-first-integration-v1.md
d61a808afcee2e39f594afee2e73069d95d2e5e8fbe57315e883f313db4f61e5  app/PilotHttp/ObjectCardView.php
6a1258e2d268bc42acbca47cfd026b570876f682f51fb491eec62cd93ddd0b37  app/PilotHttp/PilotShellView.php
19e5703061b69d5379a62c0086ab17b0b264fc72902c5c22dc15fd4e84fc228c  app/PilotHttp/PilotHttp.php
5f225ece450786d653badcf2f900fed23d42438740901aece904ef4aca471d25  app/PilotHttp/PilotView.php
be0e8dede13a68086bbcbc42bf8944c8b5cca721b40039641c3b16572d774768  app/InstallationProcess/MariaDbProcessUserDirectory.php
e4544d546bf4c957e9c0d177ea53671d74bbe3a9da1c20666177de1877c6fe8b  docs/operations/pilot-object-card-gate5-v1-correction-green-2026-09-04.md
b8ca2a58dfd18746e6dc082d93e0806dbbfda99245e277b4ba8c60fb15ca9354  docs/operations/pilot-object-card-full-verification-2026-09-04-1248.md

METADATA  reviews/code/PILOT-OBJECT-CARD-001-upload-first-integration-v2.md
```

Summary: Standards — 0 findings, `APPROVED`; Spec — 0 findings,
`APPROVED`. Overall bounded Gate 5: **APPROVED**. Repository-wide state remains
NO-GO until exact-head full verification emits literal `VERIFY_OK`.
