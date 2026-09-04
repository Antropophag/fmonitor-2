# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v10

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `b4785bb3787129c72c5f2f309d09c73851a3a662`  
Gate: corrected replacement Gate 2 RED v10 against owner-approved prepare upload-first v15  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the approved specifications/OpenSpec package,
the reviewed tests/support, nor production code. The review was performed as a
new turn after the historical v8 review and against the v9 findings. No reviewed
test or production file was changed. This append-only record is the only review
change; OpenSpec task 2.2 remains unchecked.

## Integrity and reproduction

The worktree was clean at the exact requested commit before review. All
owner-approved normative hashes and the Gate 1 review hash match the approval
record. The tasks hash differs from the approved pre-approval hash only by
task 1.6 being checked after owner approval; task 2.2 is still open. The v10
test/support hashes match its RED evidence.

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`:

```text
$ date --iso-8601=seconds
2026-09-04T09:43:25+03:00

$ git rev-parse HEAD
b4785bb3787129c72c5f2f309d09c73851a3a662

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

The canonical MariaDB/raw-HTTP command reproduces the intended client RED:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1
...
tests/InstallationProcess/pilot_prepare_form_001_test.php(124)

# exit 255; completed 2026-09-04T09:43:39+03:00
```

This is a deterministic product RED at the downloaded picker behavior seam,
not a setup failure. Before the harness assertion the verifier reaches the
public asset, missing-copy failure, retained admission matrix and canonical
factory/render path. MariaDB, PHP and Node are available, and task-owned state
is removed by `finally`.

## v8/v9 finding closure

- Public `GET|HEAD /pilot/assets/picker.js`, repeat bytes, exact headers/CSP,
  unauthenticated pre-DB/CSS admission and wrong-method `405` are covered. A
  task-owned copy of the canonical app graph with only copied `picker.js`
  removed proves redacted `503` without modifying repository production.
- The positive server DOM check walks every direct child node, requires only
  empty spans with the exact six attributes, and constrains interstitial text.
  The client matrix independently rejects extra elements/attributes, nested
  elements, span/interstitial text, missing/invalid fields, bounds, duplicate/
  out-of-order records and 501 records, while accepting exact field bounds and
  all four permitted interstitial whitespace characters.
- The server public seam now covers representable ID bounds, exact maximum ID,
  name/position positive bounds, overlong position, exact ASCII whitespace
  normalization, existing blank-name corruption, deterministic ordering and
  ceiling behavior.
- Query behavior distinguishes whitespace collapse and Russian locale lower
  casing on query/candidate, Unicode code-point length, non-ASCII digits from
  the ASCII tab branch, six-digit substring matching, position exclusion,
  exact zero-result `p` copy and the 20-result cap.
- Selection coverage proves exact hidden-ID creation, both selected summaries,
  pressed/accessibility state, metadata exclusion, live count, rerender focus,
  opener/Escape focus and non-prevented native Tab behavior.
- The v7 local/process one-sided grants, inactive/near-match chains, committed
  revokes, unsupported fully delivered methods, identity replacement,
  non-enumeration, renderer decorator/delegation, GET/HEAD parity, read-only
  guards, deterministic fixtures and cleanup remain present.

Expected values are literal specification examples rather than values derived
from production mapping or rendered output. The client harness executes the
exact bytes retrieved from the repository-owned asset path.

## Blocking finding — removal summaries are not regression-sensitive

The v9 finding required chip removal and both outside/modal selection summaries
to be observed strongly enough to catch updating only one copy. The v10 harness
dispatches removal and proves hidden-ID deletion, live count and focus, but then
asserts only:

```text
[selection.children.length, modalSelection.children.length] === [1, 1]
```

Both a selected remove-button and the intended empty-state placeholder have one
child. The harness does not inspect either post-removal child tag, text,
accessible name or button semantics. A plausible regression that deletes the
hidden ID and updates the count/focus while leaving the selected chip in one or
both summaries therefore passes every current post-removal assertion. It also
does not actually prove the stated “chip disappears” outcome.

Add literal post-removal assertions that independently distinguish the empty
state in both summaries from a selected/remove button (including absence of the
selected installer's remove accessible name). Retain the current hidden-ID,
count and focus assertions. This is a test-only Gate 2 correction and requires
fresh RED evidence and a new independent Gate 3 review.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. The v10 RED is qualifying and closes all other
identified v8/v9 gaps, but the remaining summary-removal sensitivity hole is a
material owner-approved interaction outcome. Task 2.2 must remain open and
Gate 4 must not begin from these hashes.

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
1ca89fc42c9467e46dfb53e5b9c331a6eccefe5f2bb19826f2433d37af1a8a20  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v10.md
aeb10393be84329a8fca8de4a75b9731a2786f6cd61effa3678e0aaaa1ec2c9d  tests/InstallationProcess/pilot_prepare_form_001_test.php
56305f4d12d7ffc2f3707d283a22f4143c9bca15a7f5a8ffb0eace18968d9bb4  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```
