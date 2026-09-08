# PILOT-UI-SHELL-001 — independent upload-first integration Gate 3 rereview v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_shell_integration_gate3`
- Reviewed exact HEAD: `d67cdedd8d568f6f9d5947fe697a7092820842df`
- Correction base / prior review commit:
  `a49deb9e6e0e442a70ccf278fc22bb39456b83db`
- Public seam: configured and compatibility raw HTTP `GET|HEAD` pilot pages
  and configured assets
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specifications, tests, production, RED
evidence or v2 correction. This append-only record is the only review edit.
No production or test file was changed during review.

## Prior findings

The v2 correction closes the three findings from the prior review:

- **G3-v1-1 closed for route/script traceability.** The exact
  `PILOT-ROUTE-CSP-001` bytes are owner-approved in
  `docs/operations/pilot-route-csp-owner-approval.md`; its independently
  approved and implemented route matrix supersedes the earlier script-free
  shell clause. Configured and compatibility pages now require exact ordered
  per-route script manifests, exact attribute maps, empty inline bodies and
  byte-exact `SCRIPT_HTML_CSP`. Prepare pins deferred `picker.js`; root/queue
  pin navigation, card additionally pins `object-details.js`. Exercised errors
  pin `BASE_CSP`.
- **G3-v1-2 closed.** The homogeneous workforce fixture now requires exactly
  one group-level source/update pair and explicitly excludes the per-row
  provenance list. The focused Prepare predecessor retains the mixed-source
  association matrix.
- **G3-v1-3 closed.** The configured empty case uses exact v0.2 copy and
  excludes all picker structures. Compatibility requires one `shlz.css`, no
  configured `.fm2-shell`, exact route scripts/CSP, the upload-first card link,
  upload-first prepare identity/breadcrumb/picker/fallback/engineer/cancel/
  helper structure and no command controls.

The old script finding's statement that `navigation.js` lacked approval was
incorrect because it did not account for the separately landed owner approval
and Gate 5 evidence for the exact Route CSP hash. This record corrects that
assessment without rewriting the append-only v1 record.

## Blocking finding

### G3-v2-1 — dangerous URL rejection is bypassable by leading HTML whitespace

`pusScripts()` claims to reject `javascript:` URLs, but its XPath applies
`starts-with()` directly to the decoded `href`/`src`. HTML user agents ignore
leading ASCII whitespace/control whitespace when processing URL schemes. The
test therefore accepts a semantically dangerous URL such as:

```html
<a href=" &#9;JaVaScRiPt:alert(1)">x</a>
```

An executed mutation probe using the exact v2 XPath produced:

```text
decoded href: " \tJaVaScRiPt:alert(1)"
matching nodes: 0
```

The exact script manifest prevents this bypass on `<script src>`, but not on
anchors or other URL-bearing elements, while both the Route CSP acceptance
scenario and the assigned rereview explicitly require absence of
`javascript:` URLs. CSP defense-in-depth does not make a false body-safety
assertion sensitive.

Normalize the decoded URL for leading HTML/ASCII whitespace before
case-insensitive scheme comparison (cover at least U+0009..U+000D and U+0020),
or iterate DOM attributes in PHP with an equivalently explicit canonicalizer.
Add an executable sensitivity probe covering mixed case and leading whitespace
so the predicate itself cannot silently regress. Then record a new RED and
obtain another fresh independent Gate 3 review.

## Remaining assessment

- The canonical test uses the real router, raw HTTP, parsed DOM and exact
  response headers. Expected values are fixed contract literals rather than
  production-derived values.
- Script source order, complete attributes, empty script bodies and unexpected
  extra script cardinality are sensitive on every configured and compatibility
  route. Inline event attributes are rejected by name regardless of case.
- Picker inert records, order, exact six attributes, empty children, hidden
  initial IDs, fail-closed opener/dialog, engineer prefill/unconfirmed state,
  hostile engineer escaping and read-only control boundary remain covered.
- Queue/card/prepare order, breadcrumbs/headings, CSS ownership/responsive/
  focus declarations, descriptor bytes, configured failures, capability
  faults, empty/broken states, repeat/concurrency and full DB/artifact/
  `shlz-ui` fingerprints are unchanged.
- The passing focused Prepare, object-card and Route CSP suites remain healthy
  controls and cover the deeper inherited authorization, catalog, client,
  provenance, CSP and failure matrices.
- Canonical RED remains genuine: a successful configured root passes HTTP
  setup, GET/HEAD parity, parse, metadata, stylesheet manifest, scripted CSP
  and exact `navigation.js` manifest, then fails on the intended shared-shell
  identity mismatch. Cleanup leaves no task artifact root.

## Reproduced evidence

At `2026-09-04T13:29:03+03:00` through
`2026-09-04T13:29:37+03:00`, on exact head
`d67cdedd8d568f6f9d5947fe697a7092820842df`:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PHP Fatal error: Uncaught TestFailure: shell identity
Expected: 1
Actual: 0
... pilot_ui_shell_001_test.php(36): assertSameValue()
... pilot_ui_shell_001_test.php(58): pusCommon()
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

$ php -r '<DOM mutation using the exact pusScripts XPath>'
array (
  0 => ' \tJaVaScRiPt:alert(1)',
  1 => 0.0,
)
exit 0
```

The three focused DB commands used
`FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local` and
`FMONITOR_TEST_DB_PORT=23306`. The branch was clean and synchronized (`0 0`)
before review.

## Reviewed SHA-256 inputs

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
8a71eb0dba26b24fb4d0707dd7978d609bd998ac8bcf056342a3ec9a76ccfadc  tests/InstallationProcess/pilot_ui_shell_001_test.php
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
f3e336e7c87b261b981704b9077381abbc036827fd39efdc1ef9b1296628bded  tests/InstallationProcess/pilot_route_csp_001_test.php
ca82fca35d722c9c626eb6eb69e1aa11e7958730bc99e67541aebb610dbf5952  tests/Support/LocalRbacFixture.php
acfccbdaaac49cc91a9a0964e277c63e6f0344764d384ab2df0e192c497664a9  docs/operations/pilot-ui-shell-upload-first-integration-red-v2-2026-09-04.md
61d0f746849353d526df3b3c665b381fe1a16efd07c85501689dc304ba615e24  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v1.md
0240d4dbdb15fac3083344a6a13ed3f297f3e135ed7ec8bf2d6c318e792864f8  docs/operations/pilot-route-csp-owner-approval.md
312982a37d6093914ddbd976246710c7ae86ff010635460c63c04cf27b5ef736  reviews/tests/PILOT-ROUTE-CSP-001-v4.md
f483029a2148f081b01f2f06c99157f07acd2e7162f20c02190fc141254eacfe  reviews/code/PILOT-ROUTE-CSP-001.md
abfbf3203ad0260cb2148bd96f0cae3b63ce636cb53446d625cf5c0073b0a998  app/PilotHttp/*.php sorted membership manifest
c93275056b67331f22d580048e873a0438715950389c42475c7fcfd987201101  app/InstallationProcess/*.php sorted membership manifest

METADATA  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v2.md
```

Gate 4 is **not authorized** for the test at `d67cdedd...`. This review makes no
GREEN, Gate 5, navigation-removal, repository integration or release claim.
