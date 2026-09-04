# PILOT-UI-SHELL-001 CSS ownership RED correction v6

- Date: `2026-09-04`
- Gate: `2` correction after independent v5 review
- Author: separately tasked agent `/root/ui_shell_integration_red`
- Correction baseline: `d567c05718cda9e75c32a5a959add484c7691fde`
- Test commit: `331d1e12231dcf3100af4bdc00a9a3326a6d9d40`
- Prior verdict: `CHANGES_REQUESTED`
- Production changes: none

## G3-v5-1 closure

The CSS ownership oracle now canonicalizes CSS identifier escapes before
class-family checks. It supports one-to-six hex digits with optional CSS
whitespace terminator and ordinary simple escapes; invalid code points,
unfinished escapes and escaped line breaks fail closed.

Selector lists are split only on top-level commas while tracking brackets,
parentheses, strings and escapes. Consequently nested commas in `:is()` do not
create false branches, while `.shlz-*` targeting inside `:is()`, `:where()` and
`:not()` remains visible after canonicalization.

The scanner additionally rejects class-family targeting through decoded class
attribute selectors, including whitespace/case/quotes and case flags:

```css
[class~="shlz-button"]
[ class = 'SHLZ-button' i ]
```

Nested ordinary rules and rules inside `@media` remain scanned. Selector roots
inside `@scope (...)` and `selector(...)` within `@supports` are scanned rather
than skipped. Known non-selector at-rules/keyframes are handled explicitly;
unknown selector-bearing at-rules and ambiguous selector grammar fail closed.
This is deliberately a bounded ownership parser, not a claim of implementing
the full CSS grammar.

Comment and quoted-string masking remains active for declaration detection;
comment text is stripped from selector inputs. Legitimate consumption of
public `var(--shlz-*, fallback)` remains accepted, while `--shlz-*` definitions
remain rejected.

## Executable mutation matrix

All controls execute before database setup. Safe cases cover:

- `.fm2-*` selector lists with `:hover` and public token consumption;
- `.shlz-*`, attribute-selector and `!important` text inside comments/quoted
  declaration strings;
- a non-class `data-kind="shlz-button"` selector.

Rejected cases cover:

- comma lists with whitespace and mixed-case `.SHLZ-*`;
- nested `@media` with `:is()`;
- `:where()`, `:not()` and functional-selector commas;
- `[class~=]` and `[class= ... i]` forms;
- hex escape `.\\73 hlz-button` and simple escapes
  `.\\s\\h\\l\\z-button`;
- `@scope (.shlz-scope)` and `@supports selector(.shlz-button)`;
- malformed escapes and an unsupported selector-bearing at-rule.

The matrix must finish before either later public-seam run can be reached.

## Current actual-CSS RED

```text
2026-09-04T14:36:10+03:00
$ git rev-parse HEAD
d567c05718cda9e75c32a5a959add484c7691fde

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php \
    --css-ownership-only
TestFailure: actual served pilot CSS ownership
Expected: []
Actual: 20 exact unique ownership findings, including `!important`, `:root`,
direct/combined/descendant `.shlz-*`, native non-fm2 selectors and pagination
overrides.
# exit 255
2026-09-04T14:36:11+03:00
```

The public asset route first proves byte-exact GET/HEAD, content type/length,
no-store and base CSP for the actual repository `pilot.css`. The failure is
therefore an actual production ownership RED after the complete mutation
matrix, not a synthetic fixture failure.

## Current integrated execution and historical behavior RED

The normal verifier remains green on the already implemented current tree:

```text
2026-09-04T14:35:58+03:00
$ php tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell
# exit 0
2026-09-04T14:36:05+03:00
```

For sensitivity against missing shell behavior, a detached worktree under
`/home/antropophag/code/fmonitor-2-ui-shell-v6-red` was created at exact
pre-implementation Gate 3 head
`796307ed6bd52bf1f98cc07b6dadd98bc3224fe8`. Test-only commits `b54fdfc` and
`331d1e1` were applied as temporary detached commits. The cumulative v6 test
then produced:

```text
2026-09-04T14:36:32+03:00
$ php tests/InstallationProcess/pilot_ui_shell_001_test.php
TestFailure: shell identity
Expected: 1
Actual: 0
# exit 255
2026-09-04T14:36:35+03:00
```

All pre-DB mutation probes ran first. The temporary worktree was verified
clean, removed, and followed by `git worktree prune`; its path no longer
exists.

## Integrity

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ git diff --check
# exit 0; no output

e1712c8b4530bb7bc3dd80fcf44888c82c320e88d92ecda77ba385f25a7f9bee  tests/InstallationProcess/pilot_ui_shell_001_test.php
a34ee8136a0d6b77649455acbe31323eeae21ea180d66716854a1e7d9fd3621d  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v5.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
```

The current normal PASS is execution evidence only. Changed test bytes require
a fresh independent Gate 3 review before the actual CSS can be corrected.
This record makes no GREEN, Gate 5, navigation-removal, integration or release
claim.
