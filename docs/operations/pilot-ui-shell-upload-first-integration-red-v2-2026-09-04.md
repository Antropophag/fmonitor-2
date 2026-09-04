# PILOT-UI-SHELL-001 upload-first integration RED correction v2

- Date: `2026-09-04`
- Gate: `2` replacement after independent review
- Test author: separately tasked agent `/root/ui_shell_integration_red`
- Correction baseline: `a49deb9e6e0e442a70ccf278fc22bb39456b83db`
- Prior Gate 3 verdict: `CHANGES_REQUESTED`
- Prior review: `reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v1.md`
- Production changes: none

## Findings closed

### G3-v1-1 — exact route script and CSP manifests

The generic same-origin script allowance is removed. Successful configured and
compatibility responses now require exact ordered, source-only manifests from
the applicable `PILOT-ROUTE-CSP-001` route contract and the Prepare v0.2 picker
amendment:

```text
/pilot/                                      navigation.js
/pilot/objects                               navigation.js
/pilot/objects/4515                          navigation.js, object-details.js
/pilot/objects/4515/assignment-order/prepare navigation.js, picker.js[defer]
```

Every script is checked for its complete attribute map and empty inline text;
no extra same-origin script, alternate scheme, inline body, inline event
attribute or `javascript:` URL can pass. All successful page responses require
the byte-exact scripted HTML CSP. Every exercised plaintext error requires the
byte-exact base CSP without `script-src`.

This correction follows the current integration instruction to retain the
already delivered `navigation.js` route evidence. It does not implement or
remove that asset and does not infer a new product behavior from current HTML.

### G3-v1-2 — homogeneous provenance

The two candidates have the same source and update instant. The test now
requires exactly one group-level paragraph in the installer section, with one
`br` and exact pair:

```text
Источник кадровых данных: one_c_zup_via_bitrix
Актуально на: 2026-08-29T06:30:00+03:00
```

It also requires zero `.fm2-picker-provenance` lists. Mixed per-row provenance
remains owned and covered by the passing focused Prepare suite.

### G3-v1-3 — remaining Prepare v0.1 expectations

- The zero-installer branch now requires exact `Нет допустимых монтажников.`
  and absence of picker template/root/results/installer controls.
- Compatibility prepare now requires the upload-first heading and intro,
  breadcrumb current `Распоряжение`, fallback, engineer section, `Отмена`,
  `Нужен шаблон?`, exactly two picker records and no command controls.
- Compatibility card requires its exact upload-first link.
- Every compatibility page requires exactly one `shlz.css`, no configured
  `.fm2-shell`, its exact route script manifest and exact scripted CSP.

All prior queue/card structure, configured CSS, failures, capability probes,
empty/broken states, compatibility `pilot.css` 404 priority, repetition,
concurrency and zero-write fingerprints remain in place. The separate approved
navigation-removal RED expectations are not changed or implemented here.

## Verification and genuine RED

At exact baseline `a49deb9e6e0e442a70ccf278fc22bb39456b83db`:

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ git diff --check
# exit 0; no output

2026-09-04T13:27:12+03:00
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(36): assertSameValue()
... pilot_ui_shell_001_test.php(58): pusCommon()
# exit 255
2026-09-04T13:27:15+03:00
```

Before the intended identity failure, the same successful `200` root response
has passed GET/HEAD parity, parse, document metadata, exact two configured
stylesheets, exact `SCRIPT_HTML_CSP`, and the exact sole source-only
`navigation.js` manifest. The unique database, reader, CSS files and public
router setup are therefore healthy; attempt-safe cleanup completed.

Fresh predecessor controls on the same repository tree and MariaDB service:

```text
2026-09-04T13:26:01+03:00
$ php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card
# exit 0

2026-09-04T13:26:05+03:00
$ php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
# exit 0

2026-09-04T13:26:15+03:00
$ php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS
# exit 0
```

The environment prefix for all three commands was
`FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local
FMONITOR_TEST_DB_PORT=23306`.

## Exact hashes

```text
8a71eb0dba26b24fb4d0707dd7978d609bd998ac8bcf056342a3ec9a76ccfadc  tests/InstallationProcess/pilot_ui_shell_001_test.php
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
61d0f746849353d526df3b3c665b381fe1a16efd07c85501689dc304ba615e24  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v1.md
```

The current test remains intentionally RED at the approved shared-shell header
identity. Gate 4 is not authorized until a newly assigned independent Gate 3
reviewer approves these exact test bytes. This record makes no GREEN, Gate 5,
navigation-removal, integration or release claim.
