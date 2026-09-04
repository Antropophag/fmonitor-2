# PILOT-OBJECT-CARD-001 — independent Spec-axis integration Gate 3 rereview v5

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_card_upload_gate3`
- Reviewed commit: `65fe3e6d86ff1bf6ed0c1bf107227fe4cf4d568d`
- Correction base: `ca8aef5e2cfc07ea3ae639d6886b39391372f9e8`
- Public seam: configured raw HTTP `GET|HEAD /pilot/objects/{positive-id}`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither specifications, tests, production, RED evidence
nor correction. This append-only record is the only review edit.

## Closure achieved for the explicitly wired cases

The parameterized `pocStructure(response, expectedObjectId, allowPrepare, why)`
correctly closes all three v4 findings for every call site that invokes it:

- its breadcrumb current item is exact `Объект монтажа № {requested ID}`;
- it requires exactly one `aria-current=page` inside primary navigation and
  ties that item to exact `/pilot/objects` / `Объекты монтажа`;
- it compares the complete sorted anchor-href multiset. Non-capable cases allow
  only skip, logo, primary objects navigation and breadcrumb links; the capable
  eligible case adds exactly one canonical prepare href.

Consequently arbitrary links, alternate actions, duplicate canonical hrefs,
wrong breadcrumb IDs and actor-specific missing/extra current navigation are
rejected for Example A actor `19`, permissionless actor `25`, capable actor `18`,
wrong-state `4514`, PTO `4518`, and opened Example B `4513`. Exact stylesheet
order, shared shell/sidebar/navigation/main landmarks, five groups, and ordered
external scripts remain independently asserted. The old `legacyDocument`
cannot satisfy these conditions.

## Blocking finding

### G3-v5-1 — two configured state-matrix responses bypass the corrected oracle

The exhaustive state loop still handles configured actor `19` cards `4514`,
`4515` and `4516` with `pocSuccess()` plus group assertions only. Card `4514`
also happens to be covered earlier by the capable wrong-state call, but cards:

- `4515` — registered basis / `Готов к открытию`;
- `4516` — opened `needs_assignment_change` / `Требуется изменение`

never call `pocStructure()`.

For those two distinct configured durable states a production branch can emit
the wrong or absent breadcrumb current object, omit or duplicate the current
primary-navigation item, omit the configured shared shell/stylesheets, or add
an arbitrary anchor/process action, and the test still passes. `pocSuccess()`
forbids forms/inputs/buttons but does not constrain anchors. This leaves the v4
findings open over the full approved state matrix and weakens the inherited
no-action requirement for registered/change states.

Required correction: invoke the exact parameterized structure/link oracle with
`allowPrepare=false` and the independently fixed route ID for every state-loop
response, including at minimum `4515` and `4516`. Preserve existing state/team/
work consequences and all other expectations. Capture a fresh qualifying RED
and obtain a new independent Gate 3 review.

Because mandatory shared-shell and action-negative sensitivity is incomplete
for two approved states, Gate 4 is **not authorized** for the v5 test bytes.

## Preserved evidence

- Actor `25` remains an active legacy and local identity with an active assigned
  local role, exact zero local permissions and exact zero process capabilities
  before HTTP. Its GET/HEAD returns the independently fixed complete Example A
  expectation and no allowed process action once the current RED is corrected.
- Actor `19` remains a distinct `objects.read` broad reader without process
  capability; actor `18` remains the sole exact prepare-capable fixture.
- No production/support/spec or DB-grant change is present. Artifact tables and
  columns remain outside the SELECT-only principal's privilege set.
- Complete identity/plan/content/current-order/team/event, process-state
  consequences, route/method/query/body, GET/HEAD, CSP/scripts, authorization/
  failure/redaction, escaping, least privilege, zero-write, repeat/concurrency,
  corruption and cleanup matrices remain byte-present. The one gap is the
  missing invocation of the newly strengthened structure/action oracle for the
  two states above.
- Expected values remain independent specification literals. The reproduced
  failure is the same configured shared-shell presentation RED after successful
  setup, HTTP, content, CSP and script assertions.

## Reproduced evidence

At `2026-09-04T12:20:52+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check \
    ca8aef5e2cfc07ea3ae639d6886b39391372f9e8..65fe3e6d86ff1bf6ed0c1bf107227fe4cf4d568d
PASS (no output)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PHP Fatal error: Uncaught TestFailure:
Example A required shared-shell DOM pilot stylesheet
Expected: 1
Actual: 0
... pilot_object_card_001_test.php(576): pocStructure()
exit 255
```

The observed failure matches the v5 RED record and is not setup failure. No test
or production file was edited during review.

## Reviewed SHA-256 inputs

```text
10d0515cadd51958b1fcae5b8f910a339c254dce5200a94a55af126867997b2d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
eb2115a32fd594e5d4bc8cfc06aab9f407f09fe8eab83a4e7caf944a9fee368d  docs/operations/pilot-object-card-spec-axis-red-v5-2026-09-04.md
df6cd50e2e8d74d7230b6138a88e564571bfc86541a01d42a0d04ba9ac8e55d9  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v4.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
c727613bb08cebc2753bb57292a8aadd05fc1ff4867698981e438748fd8db91e  app/PilotHttp/ObjectCardView.php
a76cbf70ace1cfa6445ad84eac133267b300accda562ba421f0bba581a7957cd  app/PilotHttp/PilotHttp.php
```

The review path is metadata because a self-hash is circular. Relevant spec,
test, support or scanned production membership changes require fresh review.
No GREEN or Gate 5 claim is made.
