# PILOT-OBJECT-CARD-001 upload-first integration RED — 2026-09-04

## Scope and traceability

- Gate: 2 (tests/evidence only; no production edit and no GREEN claim).
- Integration base: `2eb022cb2bb514a6ac024394c3739c428ca6f7cb` on
  `codex/remove-pilot-work-navigation-v2`.
- Owner-approved replacement source:
  `PILOT-PREPARE-FORM-001 v0.2`, section 0 and replacement row for section 4,
  changes the capable object-card launch copy from
  `Сформировать распоряжение` to `Загрузить распоряжение`, retaining the
  canonical `/pilot/objects/{ID}/assignment-order/prepare` GET route.
- The approved replacement explicitly covers the card-link assertions in
  `PILOT-UI-SHELL-001` section 6. No other presentation or product decision is
  changed here.

The corrected object-card oracle preserves the original Examples A/B, exact
identity/plan/process facts and order, state matrix, current-team and newest
three event tuples, broad-read/narrow-write separation, route/method matrix,
GET/HEAD parity, exact headers/CSP/scripts, redaction, escaping, zero-write
database/filesystem fingerprints, repeated/concurrent reads, fault priority,
cleanup and corrupt-projection coverage. It additionally proves that:

- broad reader `19`, who has no process capability, sees neither upload-first
  action text nor the prepare URL;
- capable actor `18` sees exactly one ordinary link with visible text
  `Загрузить распоряжение` and the unchanged canonical href;
- that action follows the current-order reason and precedes the team-history
  explanation;
- the superseded `Сформировать распоряжение` copy is absent.

The UI-shell integration oracle receives only the same two exact card-action
expectation corrections. Its queue, breadcrumb, headings, DOM structure,
prepare form and CSS assertions are unchanged.

## RED execution

At `2026-09-04T11:30:12+03:00`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
PHP Fatal error: Uncaught TestFailure: Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
... pilot_object_card_001_test.php(541): pocSuccess()
exit 255
```

This is the intended card-presentation RED, not setup failure: canonical
migration, prefixed RBAC fixture, exact SELECT-only principal, HTTP startup,
GET/HEAD, CSP and both approved external-script assertions have already
completed. The first missing independently fixed card fact is registration
number `77-000123`. The later upload-first action assertions are executable but
are intentionally not reached until production restores the earlier required
card facts; no expected value was weakened to skip that predecessor failure.

The broader UI-shell verifier was also sampled and remains independently
blocked before its card assertions:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_ui_shell_001_test.php
TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(57): pusCommon()
exit 255
```

That result is classified as an existing UI-shell setup/presentation
predecessor, not as evidence for this object-card RED.

Syntax and diff hygiene:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
$ git diff --check
PASS (no output)
```

## Exact reviewed inputs and candidate test bytes

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
ff5afebbe67b6436b49af8ef4f327ed193f8baa9f2af9e48b401a13c9b15f6ec  tests/InstallationProcess/pilot_object_card_001_test.php
38bd40ed9503900f84d33bc01953f58ea5864481a1eb9e7c394367299765b1fa  tests/InstallationProcess/pilot_ui_shell_001_test.php
```

These candidate test bytes require a fresh independent Gate 3 review. Prior
object-card and CSP approvals remain historical evidence and are not reused for
the changed integration composition.
