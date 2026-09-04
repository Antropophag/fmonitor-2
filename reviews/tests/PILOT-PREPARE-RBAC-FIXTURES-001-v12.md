# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v12

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `5b52624ae000cca15abd3c5acbb141858b6a031d`  
Gate: Gate 2 RED v12 expected-value correction against owner-approved prepare upload-first v15  
Verdict: **APPROVED**

The reviewer authored neither the approved specifications/OpenSpec artifacts,
the reviewed tests/support, nor production code. No reviewed test or production
file was edited. This append-only record and the post-verdict task 2.2 checkbox
are the reviewer's only changes.

## Integrity and verification

The worktree was clean at the exact requested commit. Owner-approved normative
hashes and the Gate 1 review hash match the approval record. The v12 test and
support hashes match the RED evidence. Task 2.2 was open throughout review.

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`:

```text
$ date --iso-8601=seconds
2026-09-04T09:54:34+03:00

$ git rev-parse HEAD
5b52624ae000cca15abd3c5acbb141858b6a031d

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ node --check tests/InstallationProcess/support/pilot_prepare_picker_client.js
# exit 0, no output

$ php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

$ php -l tests/Support/PrepareRendererInvocationSpy.php
No syntax errors detected in tests/Support/PrepareRendererInvocationSpy.php

$ php -l tests/Support/pilot_prepare_renderer_spy_router.php
No syntax errors detected in tests/Support/pilot_prepare_renderer_spy_router.php

$ git diff --check
# exit 0, no output
```

Direct downloaded-client reproduction:

```text
$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]

# exit 1
```

Canonical MariaDB/raw-HTTP reproduction:

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

# exit 255; completed 2026-09-04T09:54:53+03:00
```

Both commands fail at the same missing atomic client initialization behavior.
This is a genuine deterministic product RED, not setup or predecessor failure.
The canonical run reaches the public asset success/failure probes, retained v7
admission matrix and canonical renderer path before the client oracle; cleanup
completes through `finally`.

## Independent expected-value calculation for ASCII query `10`

The literal ordered dataset is `1042 / 001042`, `2088 / 002088`, then
`3001..3022 / 003001..003022`, followed by `999999 / 999999`. Applying the
approved ASCII-digit substring rule:

- `001042` contains `10`, so `1042` matches;
- `002088` does not contain `10`;
- among `003001..003022`, only `003010` contains `10`, so `3010` matches;
- `999999` does not contain `10`.

Therefore the independently derived result is exactly `[1042, 3010]` in
dataset/spec order. V12 runs this query in a fresh DOM execution, requires two
results, clicks both current result buttons in order, and asserts exact hidden
IDs `["1042", "3010"]`. This catches the prior one-result oracle error, wrong
substring behavior, wrong ordering, stale result-node interaction and hidden-ID
mapping errors without deriving expectations from production output.

## Regression and sensitivity assessment

The v12 delta changes only the erroneous ASCII-tab expectation, reopens task
2.2, and adds its evidence. All approved v11 coverage remains byte-identical:

- exact public picker asset GET/HEAD/repeat/method/failure behavior;
- complete server and client six-field/direct-child/parser validation;
- normalization, Unicode, ASCII-only tab, zero-result and 20-result query rules;
- selected and post-removal summaries, exact hidden IDs, ARIA state, live copy,
  focus, Escape and native Tab behavior;
- local/process one-sided grants, inactive/near-match chains, committed revokes,
  fully delivered unsupported methods, identity replacement, non-enumeration,
  canonical renderer decorator, GET/HEAD parity, read-only guards and cleanup.

No weakened expectation, private production seam, ambient environment
dependency or production-derived expected value was introduced.

## Gate decision

Gate 3 is **APPROVED** for the exact reviewed hashes below. Task 2.2 may be
checked and minimal Gate 4 implementation may begin. Any subsequent test or
test-support change restarts Gate 2 and requires a fresh independent Gate 3.

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
dc401a42e46fb6d061cf7e260a52655d4161138d168bede075a72d14cb69d112  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v12.md
aeb10393be84329a8fca8de4a75b9731a2786f6cd61effa3678e0aaaa1ec2c9d  tests/InstallationProcess/pilot_prepare_form_001_test.php
5f8cc0d803302d4469c0775e291a8278c692ec85897c5e8bafda4d830174952a  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```
