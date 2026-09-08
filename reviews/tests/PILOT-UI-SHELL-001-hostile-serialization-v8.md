# PILOT-UI-SHELL-001 — independent hostile serialization Gate 3 rereview v8

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/ui_hostile_gate3`
- Reviewed correction commit: `388cf4360c3fbc103e03daab2c91d8e09b140442`
- Reviewed test: `tests/InstallationProcess/pilot_ui_shell_001_test.php`
- Public seam: real raw HTTP `GET /pilot/objects/4515/assignment-order/prepare`, parsed DOM and the selected hostile engineer label
- Verdict: **APPROVED**

The reviewer authored none of the specification, test, production or Gate 2
evidence. This append-only record is the reviewer's only repository edit. No
test or production file was changed during review.

## Independent assessment

No findings remain.

- **Exact contract traceability passes.** `PILOT-UI-SHELL-001 v0.4` section 8
  requires exactly one eligible engineer `32` label, exact literal
  `<script>не имя</script>` in that label's `textContent`, no descendant
  `script`, and escaped serialized HTML while expressly accepting an HTML
  parser's semantically equivalent entity serialization. The correction
  replaces only the whole-document byte search with serialization of that
  exact selected label and adds the explicit zero-descendant assertion.
- **The public seam and expected values remain independent.** The request still
  reaches the real router, database-backed engineer directory and production
  renderer. User `32`, the hostile literal and every expectation are fixed by
  the approved specification and fixture; none is read back from production as
  an oracle.
- **The security boundary is not weakened.** The three assertions are
  complementary: cardinality prevents silent removal, `textContent` prevents
  substitution, selected-node serialization proves the literal boundary is
  escaped, and descendant cardinality rejects executable markup. Selecting the
  exact label prevents an unrelated escaped string elsewhere in the document
  from satisfying the serialization assertion.
- **Sensitivity passes.** An independent DOM probe using the same selection and
  assertion semantics accepts the safe escaped label and rejects each of:
  unsafe raw `<script>` markup, removal of engineer `32`, and replacement of
  the hostile name. The raw-script case has one descendant script and loses the
  required literal-tag `textContent`; removal has label cardinality zero;
  substitution lacks the required literal and escaped boundary.
- **The correction fixes a verifier portability defect rather than product
  behavior.** On the installed PHP 8.5 DOM implementation the exact selected
  UTF-8 label serializes with
  `&lt;script&gt;не имя&lt;/script&gt;`, while complete-document serialization
  does not contain that byte sequence because Cyrillic is entity/encoding
  normalized. That is the semantically equivalent parser serialization which
  the approved contract explicitly permits.
- **RED/GREEN lineage remains honest.** Gate 2 evidence records the old
  whole-document assertion failure and the corrected full test passing.
  Independent reproduction of the full corrected test passes after its real DB,
  server, route and DOM setup. No production path was touched by correction
  commit `388cf43`; its tree changes are exactly one test and one append-only
  Gate 2 evidence record.
- **Scope, determinism and inherited coverage remain intact.** All other UI
  assertions, fixtures, failure paths, repeat/concurrency checks and
  DB/filesystem fingerprints are byte-identical to the preceding test. This
  review approves only the changed hostile-serialization test bytes; it makes no
  fresh Gate 5 or release claim.

## Reproduced evidence

On the current tree, whose later commits do not touch the reviewed test or its
production surface:

```text
$ php -l tests/InstallationProcess/pilot_ui_shell_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_ui_shell_001_test.php
exit 0

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell
exit 0
```

Independent same-DOM-oracle sensitivity matrix:

```text
safe        count=1 text=yes escaped=yes scripts=0  verdict=PASS
raw_script  count=1 text=no  escaped=no  scripts=1  verdict=FAIL
dropped     count=0 text=no  escaped=no  scripts=-1 verdict=FAIL
substituted count=1 text=no  escaped=no  scripts=0  verdict=FAIL

selected node:
<label><input type="radio" value="32">&lt;script&gt;не имя&lt;/script&gt;</label>
whole document contains the same exact UTF-8 byte spelling: no
```

Repository checks:

```text
$ git diff-tree --no-commit-id --name-status -r 388cf4360c3fbc103e03daab2c91d8e09b140442
A docs/operations/pilot-ui-shell-hostile-serialization-gate2-2026-09-04.md
M tests/InstallationProcess/pilot_ui_shell_001_test.php

$ git diff --check
exit 0
```

The first test invocation without the active disposable DB credential failed at
database authentication before fixture setup. It is an environment probe, not
behavioral evidence and not a product/test finding. Re-running with the
repository's active test credential reached and passed the full public seam.

## Reviewed SHA-256 inputs

```text
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
149cd117958b94f0933c381b69e75734e806443f84faff2065de53b6023626f9  tests/InstallationProcess/pilot_ui_shell_001_test.php
cc59a537d91bd346074a048506836bfb40fa24a9db3525814b72b2ff053247f2  docs/operations/pilot-ui-shell-hostile-serialization-gate2-2026-09-04.md
89b18048743e0ad872f6ddeae85ec6f0cbd77ce5948ddcba2652ec7f31ea8a48  reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v7.md
47fbb292797b24e1772d3a8deb7a26a27b78818a7137c1f7cecaff9fdfd7a109  reviews/code/PILOT-UI-SHELL-001-upload-first-integration-v4.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
a0caae5e029afc189f7f782d3b3f19b7539aa00de067e68b9c943120adf23f77  public/router.php
20b6975a9e2917341b09fa1fb43096b4e9ba9a64787301847acaa3d63c621eed  app/PilotHttp/PrepareFormView.php
77021c6243e5688d3524f405a1b4d59e60f7ce6c708bccd7a8fb771337bbfa98  app/PilotHttp/PilotView.php
METADATA  reviews/tests/PILOT-UI-SHELL-001-hostile-serialization-v8.md
```

Gate 3 is **APPROVED** for the exact test bytes introduced by
`388cf4360c3fbc103e03daab2c91d8e09b140442`. Because Gate 4 production was
already unchanged and the full test is green, the next required lifecycle step
for this correction is a fresh independent Gate 5 review of the integrated
production/test boundary.
