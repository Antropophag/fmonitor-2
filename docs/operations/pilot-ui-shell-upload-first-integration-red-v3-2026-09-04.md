# PILOT-UI-SHELL-001 upload-first integration RED correction v3

- Date: `2026-09-04`
- Gate: `2` replacement after independent rereview
- Author: separately tasked agent `/root/ui_shell_integration_red`
- Correction baseline: `cf2bb3d1f22d9f6989e3e078374cf13a33dfbdcb`
- Prior verdict: `CHANGES_REQUESTED`
- Production changes: none

## G3-v2-1 closure

The URL-safety predicate no longer applies `starts-with()` to an unnormalized
DOM attribute. It now iterates decoded `href` and `src` values and:

1. removes only leading and trailing `U+0009..U+000D` and `U+0020`;
2. applies ASCII-safe case folding;
3. rejects the resulting `javascript:` prefix.

The exact script element source/order/attribute/body manifests, arbitrary
case-insensitive `on*` attribute rejection, route CSP assertions, provenance,
Prepare v0.2 structures, compatibility matrix, error cases and zero-write
checks from v2 remain unchanged.

An executable pre-fixture sensitivity control constructs DOM attributes rather
than testing a constant regex:

```text
href decoded as " \tJaVaScRiPt:alert(1)" → dangerous count 1
href " /pilot/objects " + src "\t/pilot/assets/navigation.js\r" → dangerous count 0
```

Both assertions execute before database setup. Reaching the later HTTP RED
therefore proves both the dangerous mutation and valid same-origin controls
passed.

## Verification and RED

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ git diff --check
# exit 0; no output

2026-09-04T13:31:58+03:00
$ git rev-parse HEAD
cf2bb3d1f22d9f6989e3e078374cf13a33dfbdcb

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(37): assertSameValue()
... pilot_ui_shell_001_test.php(61): pusCommon()
# exit 255
2026-09-04T13:32:02+03:00
```

The canonical run passes both new URL sensitivity controls, creates and cleans
its isolated database/user/CSS fixture, reaches a successful configured root
GET/HEAD response, validates its exact stylesheet, CSP and script manifest,
then fails for the same intended shared-shell identity mismatch.

## Exact hashes

```text
554a368bd2874d5c2bb327ffeb1ff2e51c6c74936e8e78d20b693801ea511d54  tests/InstallationProcess/pilot_ui_shell_001_test.php
ca3837bce356e92f41512e5cf621b0c646f633bc2992a212ae56e26c8d89b008  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v2.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
```

Gate 2 remains honestly RED. Fresh independent Gate 3 review is required for
these exact test bytes before any production change. This record makes no
GREEN, Gate 5, navigation-removal, integration or release claim.
