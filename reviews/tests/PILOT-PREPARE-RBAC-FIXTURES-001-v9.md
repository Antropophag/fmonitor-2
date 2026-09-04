# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v9

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3_v9`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `dd65f1cd12753c8b30b0e54cb6140e650d7a9937`  
Gate: corrected replacement Gate 2 RED v9 against owner-approved prepare upload-first v15  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the approved specifications/OpenSpec package,
the reviewed tests/support, nor production code, and did not perform the v8
review. No reviewed test or production file was edited during this review.
This append-only record is the reviewer's only change. OpenSpec task 2.2
remains unchecked.

## Reproduction and integrity

The worktree was clean at the requested commit before review. All normative
v15 hashes, the Gate 1 rereview hash and the owner-approval record match. The
task file differs from its owner-approved hash only because task 1.6 was
checked after approval; task 2.2 remains open. OpenSpec is strict-valid, PHP
and JavaScript syntax checks pass, and `git diff --check` is clean.

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`:

```text
$ date --iso-8601=seconds
2026-09-04T09:37:19+03:00

$ git rev-parse HEAD
dd65f1cd12753c8b30b0e54cb6140e650d7a9937

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

$ php -l tests/Support/PrepareRendererInvocationSpy.php
No syntax errors detected in tests/Support/PrepareRendererInvocationSpy.php

$ php -l tests/Support/pilot_prepare_renderer_spy_router.php
No syntax errors detected in tests/Support/pilot_prepare_renderer_spy_router.php

$ node --check tests/InstallationProcess/support/pilot_prepare_picker_client.js
# exit 0, no output

$ git diff --check
# exit 0, no output
```

The canonical MariaDB/raw-HTTP run reproduces the recorded intended RED:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1

# exit 255; completed 2026-09-04T09:37:23+03:00
```

The direct harness run against `app/PilotHttp/picker.js` fails at the same
assertion at `2026-09-04T09:37:37+03:00`. This remains a genuine missing-client
behavior RED, not a setup failure. The public asset request, retained authority
matrix and canonical renderer path execute before this assertion, and cleanup
completed.

## v8 closure that is adequate

- The verifier now requests exact repository-owned picker bytes through public
  GET/HEAD with identity absent and deliberately broken DB/CSS dependencies.
  It checks status, JavaScript type, exact length, `no-store`, literal asset
  CSP, HEAD parity, repeat bytes, POST 405/Allow and guarded state.
- Successful HTML now checks every template child node instead of selecting
  only matching spans: direct element name, empty span text, zero element
  descendants, exact six attributes and permitted interstitial whitespace are
  asserted for the delivered positive representation.
- Server-side public cases cover representable lower/upper ID failure, exact
  maximum ID, exact 300/160-code-point positive bounds, 161-code-point position
  rejection and the exact approved ASCII-whitespace normalization. Existing
  blank-name, deterministic order and 501-record checks remain.
- The v7 independent local/process admission gates, revoke checks, unsupported
  methods, identity replacement, non-enumeration, renderer decoration,
  GET/HEAD parity, read-only snapshots and cleanup remain intact. The unchanged
  spy/router still use the canonical factory-owned renderer decorator seam.

## Blocking findings

### 1. The zero-result client oracle contradicts the approved DOM grammar

For the two-code-point query `١٠`, the harness asserts
`ui.results.children.length === 0`. The v15 contract instead requires every
2+ zero-match query to render exactly one `p` direct child with text
`Ничего не найдено. Проверьте ФИО или табельный номер.` The harness checks
neither that child nor the exact empty-result copy. A conforming implementation
will fail this expectation, while an implementation that silently empties the
live result group can pass it. Correct the expected result grammar and retain a
separate assertion that non-ASCII decimal digits do not enter the tab branch.

### 2. Atomic parser fail-closed sensitivity is still incomplete

The malformed dataset matrix does not include an extra seventh attribute, a
nested element descendant, over-300-code-point name, over-160-code-point
position, or more than 500 records. Those are explicit client-side validation
requirements, independent of what MariaDB can persist. The positive PHP DOM
inspection proves only what the current server emitted; it does not prove the
downloaded client rejects those post-delivery mutations atomically. A parser
that accepts any of these malformed datasets can satisfy the current harness.

The matrix also does not independently prove acceptance of exact client bounds
or all permitted interstitial characters. Add exact-bound positive client
records and focused malformed client records without adding a production/test
input seam.

### 3. Material picker interaction and exclusion clauses remain unobserved

The harness covers selection from a result, hidden-ID creation, pressed/name
updates, focus after result rerender, opener state and Escape focus return. It
does not cover:

- chip removal, removal accessible name, hidden-ID removal and the required
  focus return to the picker opener after the chip disappears;
- exact zero-result `p` grammar/copy noted above;
- exclusion of position and nonempty display metadata from search;
- preservation of native Tab order/no focus trap;
- initial and updated modal/outside selection summaries sufficiently to catch
  a client that updates only one copy.

These are explicit owner-approved client/ARIA/keyboard outcomes. Plausible
regressions in each remain green under the current test, so the executable
oracle is not yet sensitive enough for Gate 3 approval.

### 4. Asset failure boundary is not exercised

The successful public asset and one unsupported method are good admission
evidence, but the test never proves the specified redacted `503` for missing,
unreadable or non-regular bundled `app/PilotHttp/picker.js`. Because the path is
repository-owned and has no environment override, this should be tested using
a safe task-owned repository/worktree strategy or another observable public
seam that does not alter production. At minimum one representative source
failure is required to distinguish a fail-open/fallback asset handler from the
normative fail-closed contract.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. The v9 RED is qualifying and closes meaningful
parts of v8, but the reviewed executable oracle both encodes a wrong normative
zero-result expectation and leaves material client parser, interaction and
asset-failure behavior insensitive. Task 2.2 must remain open. Correct the
test/harness under a new Gate 2 cycle, capture fresh RED evidence, and obtain a
fresh independent Gate 3 review before Gate 4 implementation.

## Reviewed hashes

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
fd69197956244097fa6acbe64dc2f5a14ab01a14e8f4e80aaa9d02ab710f8c9b  openspec/changes/pilot-prepare-rbac-fixtures/design.md
5fcca8ca64d443748a26a31adcf962acf8e7ace29c1f03484b2f16ba87420a5c  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
0c87ed39e3454e87339e606b3c1d4202538cd0d46534a590e69739cf8d19087a  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
c70299b78cc2a8698e7ca4d1eca381967ab0b11e949f2e2b8cb99ea7dcdb8576  docs/operations/pilot-prepare-rbac-fixtures-gate1-rereview-v15.md
5a9288ce38c1e4ad55b1c779b691404e4df06d85f1eb2ebab7d94795513bbba4  docs/operations/pilot-prepare-rbac-v15-exact-hash-owner-approval-2026-09-04.md
40ccb7d7accb87d4e3e65e2a62b95cfef3b56bcdc60ed9278de72a4dbf358bcf  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v9.md
c0ccf20c23a085d4dda1c1404d8640ffdd30bdb3b74e4c1d5b13fb27f7be2c0f  tests/InstallationProcess/pilot_prepare_form_001_test.php
cd8907209d40691ab8ad25a52305bd42f8d2d089981e93b2aa845b0f21745c5c  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
```
