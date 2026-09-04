# PILOT-OBJECT-CARD-001 spec-axis integration RED v6 — 2026-09-04

## Narrow Gate 3 correction

- Starting head: `925d018a09ff20f2d7eca06f550490dd6ffd9c4f`.
- Review: `reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v5.md`.
- Finding corrected: `G3-v5-1`.
- Scope: Gate 2 test/evidence only; no production, support or spec edit.

The existing configured state loop now invokes
`pocStructure($state, $id, false, ...)` for every response `4514`, `4515` and
`4516`. This closes the only identified bypass: registered-ready and
needs-assignment-change states now carry the same exact shared-shell,
stylesheet/script, breadcrumb-ID, primary-navigation-current and no-extra-href
ceiling as every other configured card. The earlier capable `4514` call is
retained; the loop invocation is the state-matrix proof and does not replace or
weaken any existing state/team/work consequence.

All other v5 assertions and fixtures are byte-preserved.

## Qualifying RED

At `2026-09-04T12:22:17+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
$ git diff --check
PASS (no output)
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
TestFailure: Example A required shared-shell DOM pilot stylesheet
Expected: 1
Actual: 0
... pilot_object_card_001_test.php(576): pocStructure()
exit 255
```

The same genuine configured shared-shell RED remains first after successful
fixture/setup, GET/HEAD, complete Example A content, CSP and ordered scripts.
No setup failure or manufactured later failure is claimed.

## Exact candidate bytes

```text
e684d1daf0ad3ee69678632d32b06d60eb6aed57b4924454b75781c9a9620e5d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
9984ce2ee9cb97f11e702d764caf756ac4975f591092635efad1f884993ee487  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v5.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
c727613bb08cebc2753bb57292a8aadd05fc1ff4867698981e438748fd8db91e  app/PilotHttp/ObjectCardView.php
a76cbf70ace1cfa6445ad84eac133267b300accda562ba421f0bba581a7957cd  app/PilotHttp/PilotHttp.php
```

Fresh independent Gate 3 review is required. No GREEN or Gate 5 claim is made.
