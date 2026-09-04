# PILOT-OBJECT-CARD-001 spec-axis integration RED v5 — 2026-09-04

## Gate 3 correction

- Gate: Gate 2 tests/evidence only.
- Starting head: `ca8aef5e2cfc07ea3ae639d6886b39391372f9e8`.
- Review: `reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v4.md`.
- Review verdict: `CHANGES_REQUESTED`, findings G3-v4-1..3.
- Production/spec/support changes: none.

The common configured-card structure oracle is now parameterized by the
independently fixed requested object ID and whether the sole prepare action is
permitted. For every configured broad-reader, permissionless, capable,
wrong-state, PTO and opened-state invocation under review it requires:

- breadcrumb current text exactly `Объект монтажа № {requested ID}`;
- exactly one primary-navigation current item;
- that sole current item is the exact `Объекты монтажа` anchor to
  `/pilot/objects`;
- exact ordered stylesheet hrefs `shlz.css`, then `pilot.css`;
- exact anchor-href multiset: skip link, shared logo, current objects-nav link
  and breadcrumb link, plus only the canonical prepare href for the eligible
  capable card.

Consequently an arbitrary extra link, alternate process action, duplicate
canonical href, missing shared-shell link or actor-specific current-navigation
branch fails. The permissionless actor 25 now independently proves the exact
current objects navigation through the common oracle. Script `src` values
remain independently fixed and ordered by `pocSuccess` as `navigation.js`, then
`object-details.js`.

The earlier action-area cardinality/label/order assertions, broad and
permissionless no-action assertions, wrong-state/PTO negatives and every
content/state/security/read-only/cleanup matrix remain intact.

## Qualifying RED

At `2026-09-04T12:19:26+03:00`:

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

The same genuine configured shared-shell RED remains first. Fixture identities
and permission counts, least-privilege setup, raw GET/HEAD, complete Example A
content, CSP and both ordered scripts pass before the private legacy document
is rejected for missing `pilot.css`. No later assertion was bypassed and no
expected value was weakened.

## Exact candidate bytes

```text
10d0515cadd51958b1fcae5b8f910a339c254dce5200a94a55af126867997b2d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
df6cd50e2e8d74d7230b6138a88e564571bfc86541a01d42a0d04ba9ac8e55d9  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v4.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
c727613bb08cebc2753bb57292a8aadd05fc1ff4867698981e438748fd8db91e  app/PilotHttp/ObjectCardView.php
a76cbf70ace1cfa6445ad84eac133267b300accda562ba421f0bba581a7957cd  app/PilotHttp/PilotHttp.php
```

Fresh independent Gate 3 review is required before any Gate 4 correction. This
record makes no GREEN or Gate 5 claim.
