# PILOT-UI-SHELL-001 — independent CSS ownership Gate 3 review v5

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_integration_gate3`
- Reviewed exact HEAD: `a4b98b46b29999eeb47e712330c5d054b89561e7`
- Test commit: `b54fdfc7c447c8bb53aa1d9a488b4b0a1ce6db28`
- Public seam: `GET|HEAD /pilot/assets/pilot.css`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specifications, tests, production, RED
evidence or CSS correction. This append-only record is the only review edit.
No test or production file was changed during review.

## Passing parts

- The `--css-ownership-only` mode configures the real repository `pilot.css`
  through the production router. Before parsing it proves fixed basename,
  readable non-symlink regular-file identity, GET/HEAD parity, exact served
  bytes, `200`, media type, length, no-store cache policy and exact asset CSP.
- The current public bytes produce the documented exact 20 unique findings:
  `!important`, copied `:root` token ownership, direct and descendant
  `.shlz-*` selectors, native/non-fm2 focus and selection selectors, and
  pagination overrides. This is a genuine RED rather than a synthetic file or
  setup failure.
- Comment and quoted-string masking prevents inert text from becoming selector,
  token or `!important` findings; unterminated comments fail explicitly.
  Selector lists are split by comma, surrounding whitespace and case do not
  suppress literal class matches, descendants and functional selectors that
  contain literal `.shlz-*` are detected, and nested `@media` rule bodies are
  scanned. Keyframe/at-rule preludes are not mistaken for application rules.
- The four committed probes are reachable before database setup: valid
  `.fm2-*` plus `var(--shlz-*, fallback)` yields no finding; a comma-separated
  `.shlz-button` branch yields both shlz/non-fm2 findings; `!important` and a
  copied `--shlz-*` definition produce their exact findings.
- Existing synthetic responsive/focus CSS assertions and the entire v4 HTTP,
  script/CSP, prepare/card/queue, compatibility, failure, determinism and
  zero-write matrix remain unchanged. The normal cumulative UI-shell verifier
  passes on the current post-implementation tree.

## Blocking finding

### G3-v5-1 — equivalent shlz selector ownership bypasses the scanner

The approved UI-shell contract forbids overriding the `.shlz-*` family and
permits application ownership only through `.fm2-*` composition selectors.
The scanner searches only literal dot-class syntax in ordinary rule preludes
and unconditionally skips every at-rule prelude. Consequently all of these
active CSS forms return an empty violation list:

```css
.fm2-x[class~="shlz-button"] { display:block }
.fm2-x.\73 hlz-button { display:block }
@scope (.shlz-scope) { .fm2-x { display:block } }
```

The first and second directly target the same `shlz-button` class through an
attribute selector and an escaped CSS identifier. The third scopes an otherwise
owned rule to a `.shlz-scope` root while the parser skips that selector-bearing
at-rule prelude. These are not comment/string false positives; browsers apply
them to design-system classes. A mutation can therefore violate the exact
ownership boundary and still satisfy `[]`.

The committed probes cover only literal comma selectors and a simple valid
rule, so they cannot demonstrate sensitivity to these evasion classes. Add
executable mutations for selector-list descendants/nested media, functional
selectors, class attribute selectors, CSS escapes and selector-bearing nested
at-rules. The parser may reject unsupported selector syntax fail-closed rather
than implementing a complete CSS parser, but it must not silently approve an
equivalent `.shlz-*` target. Preserve comment/string masking and valid
`var(--shlz-*, fallback)` consumption. Then record a fresh actual-CSS RED and
obtain another independent Gate 3 review before production correction.

## Reproduced evidence

At `2026-09-04T14:31:22+03:00` through
`2026-09-04T14:31:46+03:00`, on exact head
`a4b98b46b29999eeb47e712330c5d054b89561e7`:

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
TestFailure: Example A required shared-shell DOM exact configured anchor href
multiset permits no extra process/action link
Expected: [... '/pilot/objects', '/pilot/objects', '/pilot/objects']
Actual:   [... '/pilot/', '/pilot/objects', '/pilot/objects']
exit 255
```

The card failure is the separately owned navigation-removal predecessor RED,
not a CSS ownership setup failure. Prepare and CSP remain healthy controls.
The v5 durable evidence separately reproduces the original pre-implementation
shell RED at exact detached base `796307e...`; its clean home-directory
worktree was removed and pruned. The current review does not reuse that RED as
a GREEN or Gate 5 claim.

Independent mutation analysis of the exact scanner additionally produced:

```text
nested @media + :is(.SHLZ-button) -> shlz finding
comma/whitespace .shlz-button branch -> shlz + non-fm2 findings
.fm2-x:is([class~="shlz-button"]) -> []
@scope (.shlz-scope) {.fm2-x{...}} -> []
```

## Reviewed SHA-256 inputs

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
c5046fece897ab80e98949505cb6d48a2b688ce30ca3fc7179b8d5df87a09e9c  tests/InstallationProcess/pilot_ui_shell_001_test.php
66a94a907abfc2fa05dd8345bd2e5ea491ca932a7d54f87a15a3f720b2d4e847  docs/operations/pilot-ui-shell-upload-first-integration-red-v5-2026-09-04.md
c9e95a2c6b0996f1246ea5e6bfe9327abd592354bdc521bae8010695a697ba55  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v4.md
80b33e1ced3b8f1771fee7e86b210de7e2a353942bb74efa48394d7a4e1dfef4  app/PilotHttp/pilot.css

METADATA  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v5.md
```

Gate 4 is **not authorized** for the CSS ownership test at `a4b98b46...`.
This review makes no GREEN, Gate 5, navigation-removal, repository integration
or release claim.
