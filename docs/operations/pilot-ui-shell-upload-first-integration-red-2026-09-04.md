# PILOT-UI-SHELL-001 upload-first integration RED

- Date: `2026-09-04`
- Gate: `2` — replacement integration RED; independent Gate 3 required
- Author: separately tasked agent `/root/ui_shell_integration_red`
- Baseline HEAD: `d2759ea7c74a634ce549d5cad7dff995615f01b5`
- Branch: `codex/remove-pilot-work-navigation-v2`
- Public seam: raw HTTP `GET|HEAD` successful pilot pages and configured assets

## Scope and traceability

The cumulative UI-shell verifier was audited after integration of the approved
Prepare v0.2/RBAC predecessor and the approved object-card integration. This
correction changes tests and evidence only. It does not change production,
navigation behavior, specifications, fixtures shared with another verifier, or
any domain fact.

The correction removes stale pre-v0.2 prepare expectations and requires the
approved upload-first representation:

- card launch remains the sole exact `Загрузить распоряжение` link;
- prepare has the `Загрузить распоряжение` page identity, upload-first intro,
  compact immutable object summary, `Монтажники`, engineer selection, `Отмена`,
  and `Нужен шаблон?` in fixed semantic order;
- workforce fixtures use independently fixed candidate identities `1042` and
  `2088`; the DOM exposes two ordered empty inert records with exact six-field
  grammar, padded display tabs, empty unapproved busy data, and initial
  `data-selected=0`;
- row-associated workforce provenance remains visible and exact;
- the picker opener/dialog/results expose the approved fail-closed hidden and
  accessibility state before JavaScript; no hidden selected installer IDs
  exist initially;
- engineer `31` remains prefilled but unconfirmed, and hostile engineer `32`
  remains literal escaped text rather than executable markup;
- GET remains free of file/upload/submit/CSRF/revision controls;
- only external same-origin scripts are permitted by the shared assertion, and
  prepare fixes exact order `navigation.js`, then `picker.js`; inline and remote
  scripts remain forbidden.

The approved pre-navigation-removal expectations are deliberately preserved:
the configured shell still requires the product/actor landmark header, the
ordinary `Моя работа` and `Объекты монтажа` navigation links, one exact current
link, and the original paired unavailable navigation items. This Gate 2 does
not implement or approve the separate navigation-removal behavior.

The existing queue/card structure, compatibility matrix, configured CSS
descriptor/bytes/declarations, missing/faulted capability cases, empty states,
redacted failures, repeat/concurrency checks, and full DB/artifact/`shlz-ui`
zero-write fingerprints are retained.

## Exact inputs

Before the edit the worktree and upstream were synchronized and clean:

```text
$ git rev-parse HEAD
d2759ea7c74a634ce549d5cad7dff995615f01b5

$ git rev-list --left-right --count origin/codex/remove-pilot-work-navigation-v2...HEAD
0  0
```

Normative predecessor hashes:

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
```

Corrected test hash before this evidence record:

```text
03590dc7e3058e57ffe251a78e05e1cb12f0f872fb63605468a2a56bcba905f2  tests/InstallationProcess/pilot_ui_shell_001_test.php
```

## Syntax, hygiene and OpenSpec

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ git diff --check
# exit 0; no output

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
```

`openspec list` reports `pilot-prepare-rbac-fixtures` complete and
`remove-pilot-work-navigation-item` at `3/11`; this record does not advance the
latter change or claim navigation GREEN.

## Healthy predecessor controls

The two directly integrated page predecessors pass against the same MariaDB
service and repository tree:

```text
2026-09-04T13:13:38+03:00
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card
# exit 0

2026-09-04T13:13:42+03:00
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
# exit 0
```

The HTTP-auth and object-list suites currently fail only on their already
approved navigation-removal RED assertions (`work navigation removed`, actual
`1`; and `no work item or root navigation destination`, actual `2`). Those
failures are not represented as setup health or as UI-shell GREEN.

## Genuine replacement RED

```text
2026-09-04T13:13:57+03:00
$ git rev-parse HEAD
d2759ea7c74a634ce549d5cad7dff995615f01b5

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php

PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... tests/InstallationProcess/pilot_ui_shell_001_test.php(35): assertSameValue()
... tests/InstallationProcess/pilot_ui_shell_001_test.php(57): pusCommon()
# exit 255
2026-09-04T13:14:00+03:00
```

The verifier has already created its unique database, applied all migrations,
installed local and legacy identity fixtures, created the SELECT-only reader,
validated both configured CSS descriptors, started the real public router,
performed GET/HEAD parity and parsed a successful `200` root document before
this assertion. Cleanup left no `t_pus_*` database, `pus_*` database user, or
`.test-artifacts/pus-*` directory. Therefore the failure is the intended
configured shared-shell behavior mismatch, not broken setup.

Current production places actor identity in a sidebar and emits no landmark
header containing the approved `АО «ЩЛЗ»`, `FMonitor 2.0`, then actor identity.
The correction intentionally does not adapt the expected value to that output.

## Gate decision

Gate 2 is honestly RED for the corrected test bytes above. An independent
reviewer must assess the complete corrected oracle, including unreachable
post-RED upload-first assertions and retained predecessor matrices, before any
production correction. This record makes no Gate 3, GREEN, Gate 5, integration,
or navigation-removal claim.
