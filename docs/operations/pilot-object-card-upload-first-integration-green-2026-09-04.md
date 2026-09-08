# PILOT-OBJECT-CARD-001 upload-first integration GREEN — 2026-09-04

## Gate 4 candidate

- Approved Gate 2 test head: `0c879bb622c05bb300331f5c08c7eda1ffd62ae2`.
- Independent Gate 3 record head: `66d1288d974729c6ab164b24598cb92a07d4ed2a`.
- Gate 3 verdict: `APPROVED` in
  `reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v3.md`.
- Production implementation: `fba3db8417a651fc3a940238182a2c0bc170c122`.
- Production files changed: `app/PilotHttp/ObjectCardView.php`,
  `app/PilotHttp/PilotHttp.php` only.
- Tests, specifications and reviews changed during Gate 4: none.

The production candidate restores the approved `PILOT-OBJECT-CARD-001 v0.2`
five-group semantic card instead of the later unapproved dossier presentation.
It preserves the current object read projection and fail-closed integrity checks,
but no longer performs the out-of-contract artifact-table read. The renderer
shows the newest three durable events, keeps the predecessor compatibility
document, and emits exactly the ordered source-only `navigation.js` then
`object-details.js` scripts on the configured successful path.

The sole capable no-order action is the canonical prepare link with exact text
`Загрузить распоряжение`. Broad readers, wrong process state and a valid PTO act
receive no action. The card authorization composition accepts the approved
`objects.read` route permission or the exact process
`assignment_order.prepare` capability, while unresolved local identities remain
fail-closed and the least-privilege object-read failure remains `503`.

## GREEN evidence

At `2026-09-04T12:07:43+03:00`, on exact production SHA
`fba3db8417a651fc3a940238182a2c0bc170c122`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ make lint
exit 0

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check
PASS (no output)
```

Exact frozen and production bytes:

```text
8e348c95eab28ddb6a14fcdf18f512ca797f7dfd63f84df0d42f5678cfa5becc  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7bd594aea6aa60240bc474862c64cc4e3be17020437d326caa40e1c17430429b  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v3.md
c727613bb08cebc2753bb57292a8aadd05fc1ff4867698981e438748fd8db91e  app/PilotHttp/ObjectCardView.php
a76cbf70ace1cfa6445ad84eac133267b300accda562ba421f0bba581a7957cd  app/PilotHttp/PilotHttp.php
```

## Classified successor REDs

These results do not invalidate the bounded object-card GREEN and are not
claimed as integration completion:

- `pilot_object_list_001_test.php` at `2026-09-04T12:08:29+03:00` reaches the
  approved navigation-removal predecessor and fails `Expected: 0 / Actual: 2`;
- `pilot_http_auth_001_test.php` at `2026-09-04T12:08:37+03:00` reaches the same
  removal predecessor and fails `Expected: 0 / Actual: 1`;
- `pilot_ui_shell_001_test.php` at `2026-09-04T12:08:37+03:00` remains blocked
  by the separately owned UI-shell identity predecessor, `Expected: 1 / Actual: 0`.

No `VERIFY_OK`, navigation GREEN, integration completion or Gate 5 approval is
claimed. Fresh independent Gate 5 review remains required after successor
predecessors are resolved.
