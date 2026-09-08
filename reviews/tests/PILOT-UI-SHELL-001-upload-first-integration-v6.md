# PILOT-UI-SHELL-001 — independent CSS ownership Gate 3 rereview v6

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_integration_gate3`
- Reviewed exact HEAD: `f166b24e9dab578e145f9d07777409ddc3b4c383`
- Test commit: `331d1e12231dcf3100af4bdc00a9a3326a6d9d40`
- Public seam: `GET|HEAD /pilot/assets/pilot.css`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specifications, tests, production, RED
evidence or v6 correction. This append-only record is the only review edit.
No test or production file was changed during review.

## V5 finding closure

The selector portion of `G3-v5-1` is closed:

- one-to-six-digit hex escapes with optional CSS whitespace terminator and
  ordinary simple escapes are decoded before ownership checks;
- invalid scalar values, unfinished escapes, escaped line breaks and
  unbalanced selector grammar fail closed;
- top-level comma splitting respects parentheses, brackets, strings and
  escapes;
- literal and escaped `.shlz-*`, class equality/token attribute selectors,
  case flags, `:is`, `:where`, `:not`, nested functional commas and nested
  `@media` rules are detected;
- selector-bearing `@scope` roots and `@supports selector(...)` are scanned;
  unsupported at-rules fail closed while explicitly non-selector at-rules and
  keyframe steps avoid false positives;
- comments and quoted declaration strings remain inert, a `data-*` value named
  `shlz-button` is accepted, and public `var(--shlz-*, fallback)` consumption
  remains allowed.

The committed mutation matrix exercises all of these categories before DB
setup. Its rejection helper requires a shlz, ambiguous or unsupported reason,
so a constant unrelated finding cannot satisfy the selector mutations.

## Blocking finding

### G3-v6-1 — declaration identifiers are not escape-canonicalized

CSS identifier escape decoding is applied only to selector preludes. The
declaration scans for copied `--shlz-*` definitions and `!important` use still
run directly against masked raw bytes. CSS permits escapes in both identifier
tokens, so the exact scanner returns `[]` for both active forbidden forms:

```css
.fm2-x { --\73 hlz-copied: #fff }
.fm2-x { display: block !\69mportant }
```

After CSS tokenization these are `--shlz-copied` and `!important`, exactly the
two ownership violations the contract prohibits. This is a real evasion, not
comment/string text. An independent executable call of the exact helper at the
reviewed HEAD produced:

```text
escaped_token:     []
escaped_important: []
valid_var:         []
```

The third control, `.fm2-x{color:var(--shlz-color,#fff)}`, confirms the desired
public-token consumption remains accepted, but the current committed matrix
contains no escaped declaration mutation and therefore cannot distinguish the
two forbidden results from this valid control.

Canonicalize declaration identifier escapes before testing custom-property
definitions and important priority, while continuing to mask comments/strings
and permitting `var(--shlz-*, fallback)` reads. Add executable mutations for
hex/simple escapes, optional whitespace termination, invalid/ambiguous escapes
and the safe `var()` control. Then record a fresh CSS RED and obtain another
independent Gate 3 review before production changes.

## Reproduced evidence

At `2026-09-04T14:38:39+03:00` through
`2026-09-04T14:39:34+03:00`, on exact head
`f166b24e9dab578e145f9d07777409ddc3b4c383`:

```text
$ php tests/InstallationProcess/pilot_ui_shell_001_test.php \
    --css-ownership-only
TestFailure: actual served pilot CSS ownership
Expected: []
Actual: 20 exact unique findings
exit 255

$ php tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell
exit 0

$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
exit 0

$ php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
exit 0

$ php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS
exit 0

$ php tests/InstallationProcess/pilot_object_card_001_test.php
TestFailure: configured anchor href multiset
Expected includes no `/pilot/`; actual retains `/pilot/`
exit 255
```

The card failure is the separately owned navigation-removal RED and does not
invalidate the CSS asset setup. The CSS-only run first proves exact public
GET/HEAD bytes, headers and CSP, then reports the same 20 current production
ownership findings.

The original behavior RED was independently reproduced in a detached task
worktree under `/home/antropophag/code` at base
`796307ed6bd52bf1f98cc07b6dadd98bc3224fe8` with only test commits `b54fdfc`
and `331d1e1` applied. At `2026-09-04T14:39:15+03:00` it failed on exact shell
identity (`Expected: 1`, `Actual: 0`) after all pre-DB URL/SQL/CSS probes. The
worktree was verified clean, removed and followed by `git worktree prune`.

## Reviewed SHA-256 inputs

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
e1712c8b4530bb7bc3dd80fcf44888c82c320e88d92ecda77ba385f25a7f9bee  tests/InstallationProcess/pilot_ui_shell_001_test.php
8661ac1b5a69c18c04549f611aed6ef40b9a700f63ba30921811b722c25a6632  docs/operations/pilot-ui-shell-upload-first-integration-red-v6-2026-09-04.md
a34ee8136a0d6b77649455acbe31323eeae21ea180d66716854a1e7d9fd3621d  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v5.md
80b33e1ced3b8f1771fee7e86b210de7e2a353942bb74efa48394d7a4e1dfef4  app/PilotHttp/pilot.css

METADATA  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v6.md
```

Gate 4 is **not authorized** for the CSS ownership test at `f166b24e...`.
This review makes no GREEN, Gate 5, navigation-removal, repository integration
or release claim.
