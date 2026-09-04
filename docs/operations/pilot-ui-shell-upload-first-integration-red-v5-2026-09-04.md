# PILOT-UI-SHELL-001 actual CSS ownership RED v5

- Date: `2026-09-04`
- Gate: `2` correction; fresh independent Gate 3 required
- Author: separately tasked agent `/root/ui_shell_integration_red`
- Current production baseline: `78432e314d67ed922dd11e7a4166d9b22b325ecb`
- Test commit: `b54fdfc7c447c8bb53aa1d9a488b4b0a1ce6db28`
- Production changes: none

## New executable boundary

The prior synthetic `pilot.css` fixture remains the focused oracle for exact
responsive and focus declarations. A separate `--css-ownership-only` mode now
configures the real repository file `app/PilotHttp/pilot.css` through the
public asset route and proves before ownership analysis that it is:

- an absolute readable non-symlink regular file with fixed basename
  `pilot.css`;
- served byte-exactly by `GET|HEAD /pilot/assets/pilot.css`;
- returned as `text/css; charset=UTF-8`, exact `Content-Length`, empty HEAD,
  `Cache-Control: no-store` and inherited exact base CSP.

The syntax-aware scanner masks CSS comments and quoted strings before parsing
rule preludes/declarations. It rejects:

- any selector branch directly targeting a `.shlz-*` class, including a
  combined `.fm2-x,.shlz-button` selector or descendant override;
- any application rule selector without an `.fm2-*` owner, excluding at-rule
  and keyframe preludes;
- `!important` declarations;
- definitions of copied `--shlz-*` custom properties.

Consumption through `var(--shlz-*, safe-fallback)` remains permitted. This
distinguishes public token use from copying/owning a design-system token.

## Sensitivity probes

All probes execute before database setup:

```text
.fm2-x,.fm2-x:hover{color:var(--shlz-semantic-color-action-primary,#185abc)}
→ []

.fm2-x,.shlz-button{display:block}
→ shlz selector + non-fm2 selector

.fm2-x{display:block!important}
→ important declaration

:root{--shlz-copied:#fff}
→ copied shlz token + non-fm2 selector
```

Reaching either later RED proves the scanner accepted the valid control and
rejected every mutation with the exact expected finding set.

## Actual-CSS RED on current production

```text
2026-09-04T14:26:35+03:00
$ git rev-parse HEAD
78432e314d67ed922dd11e7a4166d9b22b325ecb

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php \
    --css-ownership-only

TestFailure: actual served pilot CSS ownership
Expected: []
Actual:
  important declaration
  non-fm2 selector :root
  shlz selector .shlz-scope
  shlz selector .shlz-status
  shlz selector .shlz-button
  non-fm2 native focus selectors
  descendant .shlz-button overrides
  non-fm2 selector ::selection
  descendant .shlz-pagination__item/.shlz-pagination__list overrides
  ... 20 exact unique findings total
# exit 255
```

The output is a genuine production ownership failure over bytes already proven
to come from the configured public asset response, not a synthetic mutation or
broken file/HTTP setup.

## Preserved pre-implementation RED

Current production already contains the previously reviewed Gate 4 shell
implementation, so the normal corrected cumulative verifier now exits `0`:

```text
2026-09-04T14:26:35+03:00
$ php tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell
# exit 0 at 2026-09-04T14:26:43+03:00
```

To prove that v5 did not erase or replace the original behavior RED, a detached
task worktree was created under the required home code directory at the exact
pre-implementation Gate 3 head
`796307ed6bd52bf1f98cc07b6dadd98bc3224fe8`. Only test commit `b54fdfc` was
applied and committed as temporary detached commit
`4d92decd73eaf85cc2e9465e33cf4dd3525b322a`:

```text
2026-09-04T14:28:04+03:00
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(40): assertSameValue()
... pilot_ui_shell_001_test.php(69): pusCommon()
# exit 255
2026-09-04T14:28:08+03:00
```

The test passes all URL, SQL and new CSS scanner probes before creating its
healthy isolated HTTP/DB fixture and reaching the original exact shell failure.
The worktree was verified clean, removed, and `git worktree prune` completed;
`/home/antropophag/code/fmonitor-2-ui-shell-v5-red` no longer exists.

## Verification and exact hashes

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php

$ git diff --check
# exit 0; no output

c5046fece897ab80e98949505cb6d48a2b688ce30ca3fc7179b8d5df87a09e9c  tests/InstallationProcess/pilot_ui_shell_001_test.php
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
```

The normal verifier's current pass is execution evidence only, not a Gate 3 or
Gate 5 claim for changed tests. The actual CSS ownership RED requires a minimal
production correction only after fresh independent Gate 3 approval.
