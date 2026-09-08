# PILOT-UI-SHELL-001 — independent upload-first integration Gate 3 rereview v3

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_integration_gate3`
- Reviewed exact HEAD: `2ff01818530c3b5278a0c4c32baa1fc4d16b909b`
- Correction base / prior review commit:
  `cf2bb3d1f22d9f6989e3e078374cf13a33dfbdcb`
- Public seam: configured and compatibility raw HTTP `GET|HEAD` pilot pages
  and configured assets
- Verdict: **APPROVED**

The reviewer authored none of the specifications, tests, production, RED
evidence or v3 correction. This append-only record is the only review edit.
No production or test file was changed during review.

## Review result

No findings remain.

- **G3-v2-1 is closed.** `pusDangerousUrlCount()` iterates every decoded
  `href` and `src`, removes exact boundary U+0009..U+000D/U+0020, performs
  case-insensitive ASCII scheme comparison and rejects the resulting
  `javascript:` prefix. It does not alter or reinterpret valid same-origin
  paths.
- **Executable sensitivity is genuine.** Before database setup, the test
  constructs DOM nodes with `href=" \tJaVaScRiPt:alert(1)"` and proves count
  `1`. A separate DOM containing boundary-whitespace-wrapped same-origin
  `href` and `src` values proves count `0`. Reaching the later HTTP RED proves
  both controls ran and passed; these are not constant-only regex assertions.
- **Script/event boundary remains exact.** Every configured and compatibility
  route pins complete ordered script source/attribute/body tuples. Prepare
  alone adds deferred `picker.js`; card adds `object-details.js`; all include
  the owner-approved navigation asset. Extra scripts, changed order or
  attributes fail. Inline `on*` attributes are detected case-insensitively,
  normalized dangerous URLs fail, successful HTML pins exact
  `SCRIPT_HTML_CSP`, and exercised errors pin exact `BASE_CSP`.
- **Prior findings stay closed.** Homogeneous provenance is one exact group
  pair with no row list; configured empty installers use v0.2 copy and no
  picker; compatibility preserves one stylesheet/no configured shell while
  requiring exact scripts/CSP, upload-first card link, prepare identity,
  breadcrumb, picker/fallback/engineer/cancel/helper and no command controls.
- **Traceability and public seam pass.** Navigation/script policy is grounded
  in owner-approved `PILOT-ROUTE-CSP-001` and its independent Gate 3/Gate 5
  records. Prepare/card/list/UI-shell expected values remain specification
  literals. Behavior is observed through the real router, raw HTTP, headers
  and parsed DOM rather than private renderer output.
- **Inherited coverage is preserved.** The v3 diff changes only the URL helper,
  its two probes and append-only RED evidence. Queue/card/prepare ordering,
  inert picker grammar, hostile engineer escaping, CSS responsive/focus
  declarations, configured and compatibility paths, failures, repeat/
  concurrency, and DB/filesystem/`shlz-ui` zero-write fingerprints are
  unchanged. Passing card, Prepare and CSP predecessors retain the deeper
  authorization/catalog/client/provenance/error matrices.
- **RED validity passes.** The canonical run creates the isolated fixture and
  reaches a successful configured root after both URL probes, GET/HEAD parity,
  parsing, document metadata, stylesheet order, exact CSP and exact
  `navigation.js` manifest. It then fails on the intended missing shared-shell
  header identity. Cleanup leaves no task artifact root.

## Reproduced evidence

At `2026-09-04T13:33:12+03:00` through
`2026-09-04T13:33:32+03:00`, on exact head
`2ff01818530c3b5278a0c4c32baa1fc4d16b909b`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(37): assertSameValue()
... pilot_ui_shell_001_test.php(61): pusCommon()
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
```

The three focused DB commands used
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
554a368bd2874d5c2bb327ffeb1ff2e51c6c74936e8e78d20b693801ea511d54  tests/InstallationProcess/pilot_ui_shell_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
f3e336e7c87b261b981704b9077381abbc036827fd39efdc1ef9b1296628bded  tests/InstallationProcess/pilot_route_csp_001_test.php
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
a323e50736302017ecb73bf9bb38b40c5395933d283300c7c4cba2079c1a0443  docs/operations/pilot-ui-shell-upload-first-integration-red-v3-2026-09-04.md
ca3837bce356e92f41512e5cf621b0c646f633bc2992a212ae56e26c8d89b008  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v2.md
0240d4dbdb15fac3083344a6a13ed3f297f3e135ed7ec8bf2d6c318e792864f8  docs/operations/pilot-route-csp-owner-approval.md
312982a37d6093914ddbd976246710c7ae86ff010635460c63c04cf27b5ef736  reviews/tests/PILOT-ROUTE-CSP-001-v4.md
f483029a2148f081b01f2f06c99157f07acd2e7162f20c02190fc141254eacfe  reviews/code/PILOT-ROUTE-CSP-001.md
abfbf3203ad0260cb2148bd96f0cae3b63ce636cb53446d625cf5c0073b0a998  app/PilotHttp/*.php sorted membership manifest
c93275056b67331f22d580048e873a0438715950389c42475c7fcfd987201101  app/InstallationProcess/*.php sorted membership manifest

METADATA  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v3.md
```

Gate 3 is **APPROVED** for the exact test bytes at
`2ff01818530c3b5278a0c4c32baa1fc4d16b909b`. Gate 4 may implement only the
reviewed UI-shell integration behavior without changing tests/specifications.
This record makes no GREEN, Gate 5, navigation-removal, repository integration
or release claim.
