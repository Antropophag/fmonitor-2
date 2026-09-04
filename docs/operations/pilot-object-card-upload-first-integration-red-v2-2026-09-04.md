# PILOT-OBJECT-CARD-001 upload-first integration RED v2 — 2026-09-04

## Gate 3 correction

- Gate: corrected Gate 2 tests/evidence only.
- Corrected integration head before this record:
  `84ea58968e944c97a8bd83bb9faa877a2507d72f`.
- Independent review input:
  `reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v1.md`.
- Review verdict: `CHANGES_REQUESTED`, blocking finding `G3-1`.
- Production files changed: none.

The v1 combined XPath could miss a duplicate canonical href carrying different
text or a second process action. The v2 public-HTTP oracles now independently
assert, for every capable-card example under review:

1. exactly one anchor has the canonical
   `/pilot/objects/{ID}/assignment-order/prepare` href regardless of text;
2. that sole anchor has exact normalized label `Загрузить распоряжение`;
3. the `Распоряжение и команда` next-step area contains exactly that one
   interactive element and no extra anchor, button, form, input, select or
   textarea;
4. the reason/action/history visible order and absence of superseded
   `Сформировать распоряжение` copy remain unchanged.

The isolated object-card fixture additionally proves the canonical prepare
href and upload-first control are absent for the capable actor when the object
is in a wrong state and when an otherwise eligible no-order object has a valid
PTO act date. Its existing broad reader continues to prove absence of both
action text and href. The PTO fixture adds only the legacy columns already read
optionally by the production public card reader and grants the SELECT-only
principal those exact two columns; all existing least-privilege checks remain.

## Qualifying RED

At `2026-09-04T11:39:09+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
$ git diff --check
PASS (no output)
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
TestFailure: Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
... pilot_object_card_001_test.php(545): pocSuccess()
exit 255
```

The failure remains the same qualifying presentation RED after canonical
migration, RBAC setup, exact SELECT-only access, HTTP success, CSP and the two
external-script assertions. It is not a setup failure. The earlier missing
registration-content predecessor deliberately remains first; the new
cardinality, negative-state and PTO assertions become reachable after minimal
production correction. No assertion was reordered or weakened to manufacture
a later failure.

## Exact candidate bytes

```text
cb4e00ce9f139e3efa56ebe2e8f8070d9ac9e692d15f71677c23d1409bc3b257  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
b43d139f9cba57bde3f73c477eb0843622ec903d56b7fb37c4ad3a5b08171341  reviews/tests/PILOT-OBJECT-CARD-001-upload-first-integration-v1.md
```

A fresh independent Gate 3 review is required for these changed bytes. The v1
review and earlier card/CSP approvals remain append-only historical evidence
and do not authorize Gate 4.
