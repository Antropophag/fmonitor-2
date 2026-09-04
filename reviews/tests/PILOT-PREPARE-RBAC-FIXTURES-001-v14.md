# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v14

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `791b92bf5c7f3db8b04ff959cb3b8d9c59271d21`  
Pre-Gate4 RED baseline: `6137d5e83be6a31b00e801efe6acf00b4ce473ce`  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither specifications, tests/support nor production.
No reviewed test or production file was changed. This append-only record is the
only review change; OpenSpec task 2.2 remains unchecked.

## Integrity and current-head GREEN

The worktree was clean at the requested commit. Owner-approved normative
hashes and the Gate 1 review hash match. V14 test/support/task hashes match its
evidence. OpenSpec, syntax and diff hygiene checks pass.

```text
$ date --iso-8601=seconds
2026-09-04T10:40:04+03:00

$ git rev-parse HEAD
791b92bf5c7f3db8b04ff959cb3b8d9c59271d21

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ node --check tests/InstallationProcess/support/pilot_prepare_picker_client.js
# exit 0

$ php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

$ git diff --check
# exit 0

$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js
prepare picker client contract: PASS

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

# behavioral commands exit 0; completed 2026-09-04T10:40:15+03:00
```

The full current verifier reaches the newly added provenance and ordering
assertions; the GREEN is not masked by an earlier assertion.

## Independent pre-Gate4 RED

A detached worktree was created only under the permitted home code directory:

```text
/home/antropophag/code/fmonitor-2-prepare-v14-review-red
```

It used exact baseline `6137d5e83be6a31b00e801efe6acf00b4ce473ce`
with only the cumulative current test/support/task diff applied. The applied
test hashes were exact v14 hashes `526f7b72...` and `5955b599...`.

```text
$ date --iso-8601=seconds
2026-09-04T10:40:30+03:00

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1

# exit 255
```

This is the intended missing atomic-client behavior, not setup failure. The
patch was reverse-applied, clean status and exact detached baseline were
verified, then the worktree was removed and `git worktree prune` completed.
The path and worktree metadata entry no longer exist.

## Correctly closed findings and preserved coverage

- Mixed provenance now requires exactly one direct scoped list, exactly two
  direct rows in literal candidate order and exact name/source/time association,
  while excluding a group-level provenance paragraph.
- Installer equal-name ordering is strong: public rows are transformed from
  noncanonical source identity into `99,100`, distinguishing numeric from
  lexical order, with exact tabs `000099,000100`. The client accepts that
  ascending pair and rejects the reversed `100,99` pair.
- U+E000 before U+10000 remains an independent code-point-versus-UTF-16
  primary comparator oracle, with exact names and IDs.
- Candidate exclusions remain scoped to installer template records and the
  engineer fieldset, avoiding unrelated shared SVG text/geometry.
- All v12 client grammar/query/interaction/fail-closed checks and v7 local/
  process admission, revoke, method, renderer, parity, non-enumeration,
  read-only, environment-isolation and cleanup checks remain unchanged.

Expected literals and ordering are independently derived from the approved
specification and fixtures, not from production output.

## Blocking findings

### 1. Homogeneous provenance is not exact-cardinality sensitive

The new homogeneous assertion requires exactly one paragraph that itself has
the approved source text, one `br`, and approved timestamp, and it excludes the
mixed-mode `ul`. It does **not** require that this is the only direct
source/timestamp provenance paragraph in the installer section. An
implementation can emit the correct paragraph plus a second stale or duplicate
group-level provenance paragraph and still satisfy both assertions.

This is the exact near miss identified in v13: the homogeneous form must be one
group-level pair, not merely contain one correct pair. Count all scoped direct
paragraphs carrying either provenance prefix and require exactly one, then
retain the existing exact structure/literal check and per-row-list exclusion.

### 2. The inherited engineer numeric tie remains untested

`PILOT-PREPARE-FORM-001` independently specifies engineer ordering by Unicode
FIO and, for equal names, numeric user ID. V13 explicitly allowed this to remain
inherited only if an existing executable case was identified. V14 adds public
and client equal-name numeric ordering only for installer records; the two
engineers still have different names (`73 / Анна`, `74 / Борисова`). No cited
existing executable case exercises equal engineer names, and the client picker
cannot cover server-rendered engineer radios.

Add a public successful fixture with equal normalized engineer names, IDs that
distinguish numeric from lexical/insertion ordering, and assert exact radio
order. This needs no new production seam.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. Current GREEN and pre-Gate4 RED evidence are
valid, and v14 closes the mixed-provenance and installer-ordering cases, but the
two plausible regressions above remain green. Task 2.2 stays open. Correct the
test/evidence and obtain another independent Gate 3 before Gate 5 reliance.

## Reviewed hashes

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
fd69197956244097fa6acbe64dc2f5a14ab01a14e8f4e80aaa9d02ab710f8c9b  openspec/changes/pilot-prepare-rbac-fixtures/design.md
00e7265ea0d1d16dd50b4590cccf1358d8c99c5ce4b9d0448f108ba0c8ad5546  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
0c87ed39e3454e87339e606b3c1d4202538cd0d46534a590e69739cf8d19087a  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
c70299b78cc2a8698e7ca4d1eca381967ab0b11e949f2e2b8cb99ea7dcdb8576  docs/operations/pilot-prepare-rbac-fixtures-gate1-rereview-v15.md
5a9288ce38c1e4ad55b1c779b691404e4df06d85f1eb2ebab7d94795513bbba4  docs/operations/pilot-prepare-rbac-v15-exact-hash-owner-approval-2026-09-04.md
f195efd50c4c1fd02fae9bc9c6d30894e7f4f5833aa9bda8e7284258e1889872  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v14.md
526f7b7259bcb239f884453a484c4c76438d0e6ce8342d33216d1297e135c4f2  tests/InstallationProcess/pilot_prepare_form_001_test.php
5955b599e04b4f389e8a88cf50b02c106f9c757a0b33567b7a77b3161e5cb040  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```
