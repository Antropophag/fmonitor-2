# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v8

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `e3d491e9b42aaba5b7053c89bdf7b51c166161ad`  
Gate: replacement Gate 2 RED v8 against owner-approved prepare upload-first v15  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the executable specifications, reviewed tests and
support, nor production code. No reviewed test or production file was changed
during this review. This append-only review record is the reviewer's only
change; OpenSpec task 2.2 remains unchecked.

## Reproduction

The worktree was clean at the exact requested commit before review. The
owner-approved specification/OpenSpec/review hashes match the approval record.
The tasks file differs from its approved hash only by the recorded completion
of task 1.6. OpenSpec is strict-valid, all three reviewed PHP files are
syntactically valid, and the scoped whitespace check passes.

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`:

```text
$ date --iso-8601=seconds
2026-09-04T09:26:56+03:00

$ git rev-parse HEAD
e3d491e9b42aaba5b7053c89bdf7b51c166161ad

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

$ php -l tests/Support/PrepareRendererInvocationSpy.php
No syntax errors detected in tests/Support/PrepareRendererInvocationSpy.php

$ php -l tests/Support/pilot_prepare_renderer_spy_router.php
No syntax errors detected in tests/Support/pilot_prepare_renderer_spy_router.php

$ git diff --check -- tests/InstallationProcess/pilot_prepare_form_001_test.php \
    tests/Support/PrepareRendererInvocationSpy.php \
    tests/Support/pilot_prepare_renderer_spy_router.php
# exit 0, no output
```

The canonical MariaDB/raw-HTTP command reproduced the recorded intended RED:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

PHP Fatal error: Uncaught TestFailure: two inert installer records
Expected: 2
Actual: 3
...
tests/InstallationProcess/pilot_prepare_form_001_test.php(118)

# exit 255; completed 2026-09-04T09:27:19+03:00
```

This is a genuine product RED at the public GET representation: the fixed
business date is `2026-08-28`, so installer `3099` with `employed_from =
2026-09-01` must be excluded. Setup, the canonical factory, MariaDB and cleanup
completed sufficiently to reach the owner-approved representation assertion.

## Findings

### Blocking — the inherited picker asset/client contract is not tested

The v15 OpenSpec delta explicitly inherits the exact picker
record/parser/asset/keyboard contract in full. The verifier only asserts that
successful HTML contains one deferred script element whose `src` is
`/pilot/assets/picker.js`. It never requests that public asset and never
executes its behavior. Consequently the test remains green if the route returns
wrong bytes/headers/CSP/HEAD behavior, requires identity or DB, accepts a wrong
method, or if the script does not implement the approved normalization/search,
20-result bound, selection/hidden-ID synchronization, accessible names/live
copy, open/close/focus/Escape behavior, or fail-closed initialization.

Add public-seam sensitivity for the exact `GET|HEAD
/pilot/assets/picker.js` asset contract and an isolated DOM/client execution
oracle for the exact v0.2 query and keyboard behavior. It must cover the v15
distinguishing query cases: U+0009..U+000D/U+0020 collapse and boundary trim,
`toLocaleLowerCase('ru-RU')` on query and candidate name, Unicode-code-point
minimum via `Array.from`, ASCII-only digit extraction and substring matching
against six-digit `data-tab`. It must also prove parser rejection leaves the
fallback visible and hidden installer IDs absent.

### Blocking — the direct-child grammar assertion is incomplete

The positive DOM assertion checks the two selected direct `span` nodes, their
six attribute names and absence of element descendants. It does not prove that
`template.content.children` contains *only* those spans: an additional direct
element of another name is ignored by `//template[@data-picker-data]/span`.
It also does not require each span to be empty of text or constrain
inter-record text nodes to only U+0009/U+000A/U+000D/U+0020. Plausible malformed
templates therefore pass the approved test even though the normative client
grammar must reject them. Assert the complete direct-child and text-node
grammar independently.

### Blocking — server fail-closed record validation is materially under-covered

The replacement preserves one blank-name corruption and the 501-row ceiling,
but it does not make the public server prove the newly approved record
validation boundary: canonical ID range `1..999999`, name/position code-point
bounds and exact whitespace normalization, duplicate identity/order ambiguity,
or equivalent source corruption that would produce an invalid/mismatched
six-field record. As written, a renderer that emits partial picker data for
these malformed source cases can pass. Add focused raw-HTTP rejected cases with
literal `503`/redaction/no-rendered-partial/no-mutation expectations wherever
the source schema permits the condition, plus positive boundary/normalization
examples for constraints enforced before storage.

## Preserved strengths

The v7 local/process one-sided grant matrix, inactive and near-match chains,
committed local and process revocation, unsupported fully delivered methods,
identity replacement, object/state/DB faults, canonical renderer decorator,
GET/HEAD parity, read-only DB/filesystem guards, provenance faults, empty sets,
ceilings and cleanup remain present. Expected positive record values are
literal and independent of production output. The recorded RED is deterministic
for the fixed fixture/business date and fails for missing v0.2 eligibility
behavior rather than setup.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. The current RED is qualifying but the reviewed
test does not cover material owner-approved v15 behavior. Task 2.2 must remain
open. Amend the test/support under a new Gate 2 cycle, capture a fresh RED, and
obtain a new independent Gate 3 review before any Gate 4 implementation.

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
1fa9dc9bd7a46ccaf9380745bfae1b420e0eff5195cbc603d4a4252ca687e792  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
8f9129efd2c8f550dd0af3b0d64c4f3c4b9efd3272c16eeadd8b4a1afb06bfd0  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v8.md
```
