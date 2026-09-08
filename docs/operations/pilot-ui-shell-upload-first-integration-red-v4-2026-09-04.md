# PILOT-UI-SHELL-001 source-boundary RED correction v4

- Date: `2026-09-04`
- Gate: `2` correction after approved v3 review exposed a false-positive oracle
- Author: separately tasked agent `/root/ui_shell_integration_red`
- Correction baseline: `321d6c8e4dd737428433a121d8c2ca4a1ce9d1d0`
- Production changes: none

## Correction

The source boundary no longer lowercases a complete view and rejects the plain
substring `select `. That predicate incorrectly classified valid presentation
bytes such as:

```html
<select class="shlz-field--select shlz-select-root">
```

The replacement tokenizes PHP source with `token_get_all()`, examines only PHP
string tokens (`T_CONSTANT_ENCAPSED_STRING` and
`T_ENCAPSED_AND_WHITESPACE`), and detects a case-insensitive SQL statement
shape `SELECT ... FROM` within one such token. It therefore remains sensitive
to SQL literals passed to a query call or assigned before a call without
treating an HTML tag or CSS class identifier as SQL.

The view scan still independently prohibits `INSERT`, `UPDATE`, `DELETE`,
`->query(`, `->prepare(`, `header(`, `setcookie(`, `file_put_contents(` and
`fwrite(`. The orchestration markup exclusion and view ownership/membership
checks are unchanged.

## Executable sensitivity

Before any database setup, the same detector is exercised against independently
constructed PHP source strings:

```text
$db->query('SELECT id, name FROM users WHERE status = 1')
$sql = "select * from roles"
expected SQL SELECT count: 2

<select class="shlz-field--select shlz-select-root">...</select>
class literal shlz-field--select shlz-select-root
expected SQL SELECT count: 0
```

Because the canonical verifier reaches the later HTTP RED, both positive and
negative probes executed successfully. This preserves the deep-module source
boundary while removing only the identified presentation false positive.

## Verification and genuine RED

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ git diff --check
# exit 0; no output

2026-09-04T13:46:59+03:00
$ git rev-parse HEAD
321d6c8e4dd737428433a121d8c2ca4a1ce9d1d0

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(38): assertSameValue()
... pilot_ui_shell_001_test.php(63): pusCommon()
# exit 255
2026-09-04T13:47:03+03:00
```

The verifier passes the URL-safety probes from v3 and both new SQL detector
probes, then creates the isolated fixture and validates the successful root
GET/HEAD metadata, stylesheets, exact CSP and script manifest before reaching
the unchanged intended shared-shell identity failure. Cleanup completed.

## Exact hashes

```text
9d8cdc4a8e75714b3d5a0b282804942375a0fde89b7fbddcd542884e4992bb12  tests/InstallationProcess/pilot_ui_shell_001_test.php
b5aa8558d94842c70beb701aa0f5d185ad83a7fea0e6ff14cde9714e49d9d820  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v3.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
```

Gate 2 remains honestly RED. Because the test bytes changed after v3 approval,
a fresh independent Gate 3 review is required before Gate 4. This record makes
no GREEN, Gate 5, navigation-removal, integration or release claim.
