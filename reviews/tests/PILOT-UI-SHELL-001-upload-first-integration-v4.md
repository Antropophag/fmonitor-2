# PILOT-UI-SHELL-001 — independent source-boundary Gate 3 rereview v4

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_integration_gate3`
- Reviewed exact HEAD: `67a61b639f7e66653ff8f75c498590a3531555db`
- Correction base / prior review commit:
  `321d6c8e4dd737428433a121d8c2ca4a1ce9d1d0`
- Verdict: **APPROVED**

The reviewer authored none of the specifications, tests, production, RED
evidence or v4 correction. This append-only record is the only review edit.
No production or test file was changed during review.

## Independent assessment

No findings.

- The correction removes the false-positive plain `select ` substring scan and
  replaces only that predicate with `token_get_all()` inspection of PHP string
  tokens. It detects case-insensitive `SELECT ... FROM` statement shapes within
  a bounded literal while ignoring valid `<select>` markup and
  `shlz-field--select` / `shlz-select-root` class values.
- Executable probes are independent literals and run before fixture setup. One
  direct query literal and one assigned lowercase SQL literal produce exact
  count `2`; a separate PHP source containing both valid select markup and
  select-named classes produces exact count `0`. Reaching the later HTTP RED
  proves both probes executed successfully.
- Independent source analysis confirms the regex is insensitive to SQL keyword
  case and accepts intervening spaces, tabs and newlines within its explicit
  4096-byte statement bound. A reproduced mixed-case/newline/tab probe returned
  `1`, while the select-markup control returned `0`.
- The detector is intentionally a source-boundary ratchet, not an SQL parser.
  The scan still independently rejects `INSERT`, `UPDATE`, `DELETE`,
  `->query(`, `->prepare(`, `header(`, `setcookie(`,
  `file_put_contents(` and `fwrite(`. The production view membership scan and
  the separate prohibition of page markup in orchestration classes are
  unchanged. The correction therefore removes the observed presentation false
  positive without weakening representative SQL/call or ownership coverage.
- The v4 diff changes no UI, route, authorization, picker, CSP, compatibility,
  failure, concurrency or zero-write assertion. All v3-approved matrices and
  exact inputs remain byte-identical apart from the corrected source predicate,
  its sensitivity probes and append-only evidence.
- Canonical RED is genuine. URL and SQL source probes pass; the isolated DB,
  reader, CSS and router setup succeeds; configured root GET/HEAD metadata,
  stylesheet order, exact scripted CSP and navigation script manifest pass;
  the test then fails at the unchanged intended shared-shell identity mismatch.
  Cleanup completes.

## Reproduced evidence

At `2026-09-04T13:48:46+03:00` through
`2026-09-04T13:49:45+03:00`, on exact head
`67a61b639f7e66653ff8f75c498590a3531555db`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(38): assertSameValue()
... pilot_ui_shell_001_test.php(63): pusCommon()
exit 255

$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
exit 0

$ php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card
exit 0

$ php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
exit 0

$ php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS
exit 0

$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
exit 0

$ php -r '<same token detector; mixed-case SQL with real LF/TAB and select-markup control>'
array (
  0 => 1,
  1 => 0,
)
exit 0
```

The focused DB commands used
`FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local` and
`FMONITOR_TEST_DB_PORT=23306`. The branch was clean and synchronized (`0 0`)
before review, and `git diff --check` passed.

## Reviewed SHA-256 inputs

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
9d8cdc4a8e75714b3d5a0b282804942375a0fde89b7fbddcd542884e4992bb12  tests/InstallationProcess/pilot_ui_shell_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
f3e336e7c87b261b981704b9077381abbc036827fd39efdc1ef9b1296628bded  tests/InstallationProcess/pilot_route_csp_001_test.php
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
3a3c7f3a10bac2a529522f68a339af91a909accb35a5d7f371ee972e258fb13d  docs/operations/pilot-ui-shell-upload-first-integration-red-v4-2026-09-04.md
b5aa8558d94842c70beb701aa0f5d185ad83a7fea0e6ff14cde9714e49d9d820  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v3.md
abfbf3203ad0260cb2148bd96f0cae3b63ce636cb53446d625cf5c0073b0a998  app/PilotHttp/*.php sorted membership manifest
c93275056b67331f22d580048e873a0438715950389c42475c7fcfd987201101  app/InstallationProcess/*.php sorted membership manifest

METADATA  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v4.md
```

Gate 3 is **APPROVED** for the exact test bytes at
`67a61b639f7e66653ff8f75c498590a3531555db`. Gate 4 may change production only
and must preserve these approved expectations. This record makes no GREEN,
Gate 5, navigation-removal, repository integration or release claim.
