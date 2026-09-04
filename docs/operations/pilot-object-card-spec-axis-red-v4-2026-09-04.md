# PILOT-OBJECT-CARD-001 spec-axis integration RED v4 — 2026-09-04

## Scope

- Gate: Gate 2 correction after Gate 5 spec-axis findings.
- Starting integration head: `4f0b653bace5db8abe9465fb0c386e783551666a`.
- Production/spec/support edits: none.
- Public seam: configured raw HTTP `GET|HEAD /pilot/objects/4512`.

`PILOT-OBJECT-CARD-001 v0.2` makes the card readable by every successfully
resolved active legacy user with an active legacy role. Neither local
`objects.read` nor a process capability is required for read admission; process
capabilities affect only command visibility.

The corrected fixture therefore adds actor `25 / Active Permissionless Reader`:

- exact active legacy user and active legacy role;
- exact active local identity and active assigned local role;
- zero local permission rows, including zero `objects.read`;
- zero process capability rows.

Pre-HTTP fixture assertions prove those zero counts independently. Configured
GET/HEAD must nevertheless return the complete Example A card with every fixed
identity, plan, empty order/team, closed work and empty event fact, while
showing no upload-first text or canonical prepare URL.

The existing actor `19 / No Capability Reader` remains separate: it has
`objects.read` but no process capability and still receives the same full card
without an action. This keeps list-route authority distinct from card read
admission.

## Shared-shell sensitivity

For configured actor 19, every configured state using `pocStructure`, and the
new permissionless actor, the DOM oracle now requires the shared `PilotView`
composition:

- exact ordered stylesheets `/pilot/assets/shlz.css`, then
  `/pilot/assets/pilot.css`;
- `.fm2-shell`, `.fm2-sidebar`, `.fm2-primary-nav` and `.fm2-main` landmarks;
- shared breadcrumb with canonical object-list link and exact current object;
- skip link, one `h1`, all five predecessor definition-list groups;
- existing ordered external `navigation.js`, then `object-details.js`
  assertions remain in `pocSuccess`.

Actor 19 additionally requires the shared navigation object-list link to be
current. The former private `legacyDocument` output lacks `pilot.css` and the
shared shell classes, so it cannot satisfy these assertions merely by copying
the card body and script tags.

All prior content/state/order/team/event, action cardinality, wrong-state/PTO,
route/method/query/body, authorization/error priority, CSP, escaping,
least-privilege, zero-write, repeat/concurrency, cleanup and corruption matrices
remain present.

## Qualifying RED

At `2026-09-04T12:14:56+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
$ git diff --check
PASS (no output)
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
TestFailure: Example A required shared-shell DOM pilot stylesheet
Expected: 1
Actual: 0
... pilot_object_card_001_test.php(569): pocStructure()
exit 255
```

This is a genuine configured presentation RED. Migration, exact fixture
identity/permission assertions, least-privilege setup, raw GET/HEAD success,
full Example A content, CSP and both ordered external-script checks pass first.
Current production then exposes its private `legacyDocument` path for actor 19,
which lacks the required configured `pilot.css` and shared shell. The
permissionless public case is executable later in the same test and current
production is also expected to reject it with `403`; the first qualifying
shared-shell mismatch is intentionally not bypassed to manufacture the later
failure.

## Exact candidate bytes

```text
ef25a2aa4a6c1678a3dbc955dc4899e268dc1c57b847cbf184dc7b0b0eff49ae  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
7bd594aea6aa60240bc474862c64cc4e3be17020437d326caa40e1c17430429b  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v3.md
c727613bb08cebc2753bb57292a8aadd05fc1ff4867698981e438748fd8db91e  app/PilotHttp/ObjectCardView.php
a76cbf70ace1cfa6445ad84eac133267b300accda562ba421f0bba581a7957cd  app/PilotHttp/PilotHttp.php
```

The changed test bytes invalidate earlier Gate 3 authorization for Gate 4. A
fresh independent Gate 3 review is required; this record makes no GREEN or
Gate 5 claim.
