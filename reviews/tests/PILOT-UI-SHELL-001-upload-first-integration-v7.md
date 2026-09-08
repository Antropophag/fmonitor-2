# PILOT-UI-SHELL-001 — independent CSS ownership Gate 3 rereview v7

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_integration_gate3`
- Reviewed exact HEAD: `5774aedff5ffe4376fd55800a8ef05e61c7ef2fd`
- Test commit: `c8cf60c6dd0c4509e15e20e362c1590a01e93d11`
- Public seam: `GET|HEAD /pilot/assets/pilot.css`
- Verdict: **APPROVED**

The reviewer authored none of the specifications, tests, production, RED
evidence or v7 correction. This append-only record is the only review edit.
No test or production file was changed during review.

## Independent assessment

No findings remain.

- **G3-v6-1 is closed.** The comment/string-masked declaration stream now uses
  the same fail-closed CSS identifier escape decoder as selectors before
  testing `--shlz-*` definitions and `!important`. Hex escapes with optional
  whitespace termination, ordinary simple escapes and case variants resolve
  to their canonical forbidden identifiers. Invalid scalar values, unfinished
  escapes and escaped line breaks add `ambiguous declaration escape`.
- **Declaration sensitivity passes.** The committed pre-DB matrix covers
  hex/simple/case copied-token definitions; contiguous, separated,
  whitespace-terminated, simple-escaped and mixed-case important priorities;
  two ambiguous escape forms; safe literal and escaped public-token reads via
  `var(--shlz-*, fallback)`; and escaped forbidden text inside comments and
  quoted values. Independent execution of the exact helper reproduced
  `copied shlz token`, `important declaration`, and empty results for the two
  safe controls respectively.
- **Selector sensitivity remains complete for the bounded grammar.** Direct,
  descendant and comma-list `.shlz-*` targets, case/whitespace variants,
  hex/simple escaped classes, class attributes and flags, nested
  `:is`/`:where`/`:not`, nested media, `@scope`, `@supports selector`, empty or
  malformed grammar and unsupported selector-bearing at-rules remain covered.
  Comments, strings and non-class `data-*` values remain valid controls.
- **Public RED is genuine.** The CSS-only path runs all mutation controls,
  validates the repository file as the configured fixed-basename readable
  regular asset, then proves byte-exact GET/HEAD delivery, status, media type,
  length, empty HEAD, no-store and base CSP. The actual bytes still yield the
  exact same 20 unique ownership findings.
- **No weakening or scope drift.** The v7 diff changes only declaration
  canonicalization, its executable probes and append-only evidence. The full
  v6 selector matrix, synthetic responsive/focus checks, route scripts/CSP,
  upload-first prepare/card, compatibility, failure, repeat/concurrency and
  DB/filesystem zero-write matrix is unchanged. Normal cumulative UI-shell,
  focused Prepare and Route CSP pass on the current tree.
- **Historical behavior sensitivity remains valid.** The test-only cumulative
  commits applied to the exact pre-implementation Gate 3 head still fail at
  shell identity after all pre-DB mutation probes, proving the CSS addition did
  not replace the original public behavior RED.

## Reproduced evidence

At `2026-09-04T14:44:31+03:00` through
`2026-09-04T14:45:25+03:00`, on exact head
`5774aedff5ffe4376fd55800a8ef05e61c7ef2fd`:

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
```

Independent direct helper execution:

```text
hex_token:       [copied shlz token]
simple_token:    [copied shlz token]
hex_important:   [important declaration]
space_important: [important declaration]
safe_var:        []
safe_string:     []
```

At `2026-09-04T14:44:52+03:00`, a detached task worktree under
`/home/antropophag/code` at exact base
`796307ed6bd52bf1f98cc07b6dadd98bc3224fe8`, with only test commits `b54fdfc`,
`331d1e1` and `c8cf60c` applied, reproduced `shell identity` with `Expected: 1`,
`Actual: 0`. It was verified clean, removed, and `git worktree prune` ran.

The branch was clean and synchronized (`0 0`) before review, and
`git diff --check` passed.

## Reviewed SHA-256 inputs

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
44abf0290139a42cd118c18288b0b7012cb9ed99d3fc965864aaeb23fe5f2e6a  docs/operations/pilot-ui-shell-upload-first-integration-red-v7-2026-09-04.md
0c2ef0e91610df3faaaf98b59d2b3789ab2cb822b7d0b641b5b4e3021a1e7c78  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v6.md
80b33e1ced3b8f1771fee7e86b210de7e2a353942bb74efa48394d7a4e1dfef4  app/PilotHttp/pilot.css

METADATA  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v7.md
```

Gate 3 is **APPROVED** for the exact test bytes at
`5774aedff5ffe4376fd55800a8ef05e61c7ef2fd`. Gate 4 may change production CSS
only and must preserve the approved test expectations. This record makes no
GREEN, Gate 5, navigation-removal, repository integration or release claim.
