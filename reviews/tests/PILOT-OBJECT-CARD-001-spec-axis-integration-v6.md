# PILOT-OBJECT-CARD-001 — independent Spec-axis integration Gate 3 rereview v6

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_card_upload_gate3`
- Reviewed commit: `2d334efcdb81ff9facc311b6fb876aea4e2cd61b`
- Correction base: `925d018a09ff20f2d7eca06f550490dd6ffd9c4f`
- Public seam: configured raw HTTP `GET|HEAD /pilot/objects/{positive-id}`
- Verdict: **APPROVED**

The reviewer did not author specifications, tests, production, RED evidence or
the correction. This append-only record is the only review edit.

## Independent assessment

The v5 blocking finding is closed. Every response in the configured exhaustive
state loop now executes:

```php
pocStructure($state, $id, false, ...)
```

for exact independently fixed IDs `4514`, `4515` and `4516`. Therefore prepared,
registered-ready and needs-assignment-change states all require:

- exact ordered `shlz.css` then `pilot.css` stylesheets;
- exact shared shell/sidebar/primary-navigation/main landmarks;
- exact breadcrumb current text tied to their requested object ID;
- exactly one primary-navigation current item, the exact objects link;
- the exact configured anchor-href multiset with no prepare link and no other
  arbitrary/process-action anchor;
- all five semantic definition-list groups.

The duplicate earlier capable wrong-state call for `4514` remains useful and
does not replace the actor-19 state-matrix proof. Existing explicit calls still
cover actor-19 Example A, permissionless actor `25`, capable actor `18`, PTO
`4518`, and opened Example B `4513`; only the eligible capable card passes
`allowPrepare=true`, and its separate sole-anchor label/interactive/action-order
assertions remain intact.

The one-line test correction and new evidence change no production, support,
specification, fixture, privilege or expected product value. All previously
approved identity/content/current-order/team/event, route/method/query/body,
GET/HEAD, authorization/failure/redaction, CSP/scripts, escaping, capability and
action negatives, SELECT-only access, zero-write, repeat/concurrency,
corruption and cleanup matrices remain byte-preserved.

Expected values are fixed by the approved specifications. The reproduced test
passes fixture/migration/privilege setup, HTTP success, complete Example A
content, CSP and ordered scripts before failing on the same missing configured
shared-shell stylesheet. It is a behavior RED, not setup failure.

No blocking traceability, seam, sensitivity, expected-value independence,
authorization, scope, determinism, isolation, regression-preservation or
cleanup finding remains.

## Reproduced evidence

At `2026-09-04T12:23:22+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check \
    925d018a09ff20f2d7eca06f550490dd6ffd9c4f..2d334efcdb81ff9facc311b6fb876aea4e2cd61b
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

The failure matches the v6 RED record. No test or production file was edited
during review.

## Reviewed SHA-256 inputs

```text
e684d1daf0ad3ee69678632d32b06d60eb6aed57b4924454b75781c9a9620e5d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
268b7f8877d21de832759c84938ba9f5fcab5048f5566ff4ce99db6afe0f8760  docs/operations/pilot-object-card-spec-axis-red-v6-2026-09-04.md
9984ce2ee9cb97f11e702d764caf756ac4975f591092635efad1f884993ee487  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v5.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
c727613bb08cebc2753bb57292a8aadd05fc1ff4867698981e438748fd8db91e  app/PilotHttp/ObjectCardView.php
a76cbf70ace1cfa6445ad84eac133267b300accda562ba421f0bba581a7957cd  app/PilotHttp/PilotHttp.php
```

The review path is metadata because a self-hash is circular. Relevant spec,
test, support or scanned production membership changes require fresh review.

Gate 4 is authorized for the exact reviewed test bytes at
`2d334efcdb81ff9facc311b6fb876aea4e2cd61b`. This record makes no GREEN or Gate
5 claim.
