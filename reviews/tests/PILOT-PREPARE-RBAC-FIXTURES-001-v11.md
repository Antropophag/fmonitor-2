# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v11

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3_v9`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `992ec6aaa0c4a2f05e6be487df2b742143f7601c`  
Gate: corrected replacement Gate 2 RED v11 against owner-approved prepare upload-first v15  
Verdict: **APPROVED**

The reviewer authored neither the approved specification/OpenSpec artifacts,
the reviewed tests/support, nor production code. No test or production file
was edited during review. This append-only record and the post-verdict task 2.2
checkbox are the reviewer's only changes.

## Integrity and reproduction

The worktree was clean at the exact requested commit. The owner-approved v15
normative hashes and Gate 1 review hash match the approval record. The tasks
file differs from its approved hash only by the already recorded completion of
task 1.6; task 2.2 was open throughout review. The v11 test/support hashes
match its RED evidence.

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`:

```text
$ date --iso-8601=seconds
2026-09-04T09:47:38+03:00

$ git rev-parse HEAD
992ec6aaa0c4a2f05e6be487df2b742143f7601c

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

The direct downloaded-client oracle reproduces the intended RED:

```text
$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]

# exit 1; run started 2026-09-04T09:47:38+03:00
```

The canonical MariaDB/raw-HTTP verifier independently reaches the same product
RED:

```text
$ date --iso-8601=seconds
2026-09-04T09:47:49+03:00

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1

# exit 255; completed 2026-09-04T09:47:53+03:00
```

The failure is deterministic missing client initialization behavior, not setup.
Before it, the canonical verifier exercises public asset success/failure,
retained admission and canonical allowed GET/HEAD; its `finally` cleanup
completed.

## v10 blocker closure

The sole v10 finding is closed without changing the PHP verifier, renderer
support, router or production. After dispatching the selected installer's
outside removal button, the harness now independently requires both summaries
to contain exactly one `SPAN.fm2-picker-selection-empty`, exact outside/modal
texts `Монтажники ещё не выбраны` and `Пока никого`, null remove `aria-label`,
and no button or selected remove accessible name. The existing assertions still
require deletion of the exact hidden installer ID, `Выбрано: 0`, and focus on
the picker opener. A stale chip in either summary can no longer pass.

## Complete traceability and sensitivity assessment

- Exact repository asset GET/HEAD bytes, content type/length, `no-store`, CSP,
  deterministic repeat, pre-identity/DB/CSS admission, wrong method and a safe
  task-owned missing-asset copy are observed through the public seam.
- Server representation checks enforce the complete direct-child span/text/
  six-attribute grammar. Public malformed/boundary fixtures cover representable
  ID and code-point limits, normalization, row integrity, deterministic order
  and ceilings without a private production input seam.
- The isolated VM/DOM oracle executes the real repository asset and is
  sensitive to atomic fail-closed initialization, direct-child grammar, exact
  attributes, nested descendants, missing/invalid fields, duplicate/order,
  500 cap and Unicode bounds.
- Query checks cover the enumerated ASCII whitespace normalization, Russian
  locale folding on both operands, Unicode code-point minimum, ASCII-only tab
  extraction, six-digit substring matching, exclusion of position/metadata,
  exact zero-result grammar/copy and 20-result cap/order.
- Interaction checks cover result native-button pressed/accessibility state,
  exact hidden IDs, both summaries, live counts, rerender focus, removal and
  focus return, opener state, Escape and untrapped native Tab behavior. DOM
  construction remains guarded against `innerHTML`.
- The inherited v7 authority/admission matrix remains intact: independent
  local/process grants, inactive and near-match chains, committed revokes,
  fully delivered unsupported methods, actor replacement, object/state/DB
  boundaries, non-enumeration, canonical factory renderer decoration,
  GET/HEAD parity, read-only snapshots, environment isolation and attempt-all
  cleanup.

Expected values are fixed by the approved specifications, not copied from the
production renderer or query output. No weakening or newly introduced test
seam was found.

## Gate decision

Gate 3 is **APPROVED** for the exact reviewed hashes below. Task 2.2 may be
checked and minimal Gate 4 implementation may begin. Any subsequent test or
test-support change restarts Gate 2 and requires a fresh independent Gate 3
review.

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
ec3974870e4458103fdbd6bc3a1be12756b113e34ef5de25cdc06ad02e52b218  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v11.md
aeb10393be84329a8fca8de4a75b9731a2786f6cd61effa3678e0aaaa1ec2c9d  tests/InstallationProcess/pilot_prepare_form_001_test.php
f14603a93467d5a47d0d315a7cfdb43dce001c385e5aa4b7d5a57963eee34bdf  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```
