# PILOT-UI-SHELL-001 CSS declaration ownership RED v7

- Date: `2026-09-04`
- Gate: `2` correction after independent v6 review
- Author: separately tasked agent `/root/ui_shell_integration_red`
- Correction baseline: `5ce89861726c21ca8ad991a571dbba0da4d838ee`
- Test commit: `c8cf60c6dd0c4509e15e20e362c1590a01e93d11`
- Production changes: none

## G3-v6-1 closure

The comment/string-masked declaration stream is now passed through the same
fail-closed CSS escape decoder used for selectors before ownership checks.
Copied custom-property definitions and priority keywords are therefore tested
in canonical form rather than raw bytes.

The scanner detects all committed mutations:

```css
.fm2-x { --\73 hlz-copied: #fff }
.fm2-x { --\s\h\l\z-copied: #fff }
.fm2-x { --SHLZ-copied: #fff }
.fm2-x { display:block !\69mportant }
.fm2-x { display:block ! \69 mportant }
.fm2-x { display:block !\important }
.fm2-x { display:block !ImPoRtAnT }
```

Both an escaped line break and an unfinished declaration escape produce exact
`ambiguous declaration escape` findings. The controls prove that normal and
escaped public-token reads through `var(--shlz-*, fallback)` remain allowed,
and that escaped forbidden text inside comments or quoted values remains inert.

All v6 selector/class-attribute/functional/nested-at-rule escape mutations,
actual asset delivery checks and the cumulative HTTP matrix are preserved.

## Current public asset RED

```text
2026-09-04T14:42:40+03:00
$ git rev-parse HEAD
5ce89861726c21ca8ad991a571dbba0da4d838ee

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php \
    --css-ownership-only
TestFailure: actual served pilot CSS ownership
Expected: []
Actual: 20 exact unique ownership findings
# exit 255
```

All declaration and selector mutation controls execute before DB setup. The
CSS-only path then proves exact real-file GET/HEAD delivery, content type,
length, no-store and base CSP before reporting current production ownership
violations.

## Current normal execution

```text
2026-09-04T14:42:40+03:00
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell
# exit 0
2026-09-04T14:42:48+03:00
```

This pass is execution evidence on already implemented current production, not
approval for the changed test bytes.

## Pre-implementation behavior RED

A detached task worktree at exact Gate 3/pre-implementation head
`796307ed6bd52bf1f98cc07b6dadd98bc3224fe8` received only cumulative test
commits `b54fdfc`, `331d1e1` and `c8cf60c`. At temporary detached head
`595355bb53bff0b1b9eedcd71e6a6103b0e8c2a6`:

```text
2026-09-04T14:43:09+03:00
$ php tests/InstallationProcess/pilot_ui_shell_001_test.php
TestFailure: shell identity
Expected: 1
Actual: 0
# exit 255
2026-09-04T14:43:12+03:00
```

All URL, SQL, selector and declaration probes ran before the original public
HTTP shell RED. The worktree was verified clean, removed from
`/home/antropophag/code/fmonitor-2-ui-shell-v7-red`, and followed by
`git worktree prune`.

## Integrity and hashes

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ git diff --check
# exit 0; no output

b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
0c2ef0e91610df3faaaf98b59d2b3789ab2cb822b7d0b641b5b4e3021a1e7c78  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v6.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
```

Gate 2 remains honestly RED on actual CSS ownership. Fresh independent Gate 3
review is required before production correction. This record makes no GREEN,
Gate 5, navigation-removal, integration or release claim.
