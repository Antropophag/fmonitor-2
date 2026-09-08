# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v16

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `eeeffa30e2050e5cbf13e2116ff1bec1a4431a0e`  
Pre-correction RED baseline: `02e16bbcbe0c667e62634801a73f5fed88171dce`  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the specifications, reviewed tests/support nor
production. No test or production file was edited. This append-only record is
the reviewer's only change; OpenSpec task 2.2 remains unchecked.

## Integrity and current GREEN

The worktree was clean at the requested commit. Owner-approved normative hashes
and Gate 1 review hash match. V16 test/support/task hashes match its evidence.
OpenSpec, PHP/Node syntax and diff hygiene pass.

```text
$ date --iso-8601=seconds
2026-09-04T10:59:04+03:00

$ git rev-parse HEAD
eeeffa30e2050e5cbf13e2116ff1bec1a4431a0e

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ node --check tests/InstallationProcess/support/pilot_prepare_picker_client.js
# exit 0

$ php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

$ php -l tests/Support/PrepareRendererInvocationSpy.php
No syntax errors detected in tests/Support/PrepareRendererInvocationSpy.php

$ php -l tests/Support/pilot_prepare_renderer_spy_router.php
No syntax errors detected in tests/Support/pilot_prepare_renderer_spy_router.php

$ git diff --check
# exit 0

$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js
prepare picker client contract: PASS

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

# behavioral commands exit 0; completed 2026-09-04T10:59:20+03:00
```

The current full verifier reaches the new catalog and mixed-provenance cases.

## Independent tests-only RED

A detached worktree under the permitted home directory was created at
`/home/antropophag/code/fmonitor-2-prepare-v16-review-red` from exact baseline
`02e16bbcbe0c667e62634801a73f5fed88171dce`. Only the cumulative current
test/support/task diff was applied. Exact applied hashes were
`5955242329...` for the PHP verifier and `aa0afd4453...` for the client harness.

```text
$ date --iso-8601=seconds
2026-09-04T10:59:35+03:00

$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js
Error: validated mixed provenance fallback list hidden after initialization:
expected true, actual false
# exit 1

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
Error: validated mixed provenance fallback list hidden after initialization:
expected true, actual false
Expected: 0
Actual: 1
# exit 255
```

This is a genuine missing mixed-provenance client behavior, not setup failure.
The diff was reverse-applied, clean status and exact baseline HEAD were verified,
then the worktree was removed and `git worktree prune` completed. Its path and
metadata entry no longer exist.

## Correctly covered behavior

- The public MariaDB case inserts 503 valid dismissed rows, then eligible ID
  `999998`; exact returned eligible IDs `1042,2088,999998` catch an early
  catalog limit that hides the tail. A subsequent ineligible but invalid
  `1000000` row must return redacted GET/HEAD `503`, catching truncation before
  structural validation.
- Positive mixed client execution requires the redundant no-JS list to remain
  visible before execution and become hidden only after valid initialization.
  The ID-mismatch case keeps opener hidden, fallback/list visible and hidden IDs
  empty, proving atomic failure for that association mismatch.
- The dynamic result assertion scopes into the result text container and
  requires provenance as the third child after name/details, with exact class
  and exact associated source/timestamp text for installer `1042`.
- Homogeneous provenance remains without a per-row list. Public mixed mode still
  has exact list/row cardinality, order and literal association. Six-field inert
  template grammar, U+E000/U+10000 ordering, installer and engineer numeric ties,
  DOM-scoped exclusions, and all v7–v15 admission/query/interaction/read-only/
  cleanup matrices remain unchanged.

## Blocking finding — client provenance parser is under-sensitive

The v16 evidence claims the client harness proves exact mixed-provenance row
IDs, attributes, cardinality, order and text. It does not. The positive harness
constructs two valid rows and checks successful initialization plus only the
first candidate's generated provenance. It never asserts the source list's own
two-row cardinality/order/three-attribute/text grammar as outcomes, nor renders
and checks the second candidate's association. The only malformed provenance
case is one row paired with one person whose `data-id` mismatches.

Consequently plausible broken parsers remain GREEN, including implementations
that validate ID but accept an extra/missing attribute, non-`li` or nested row,
incorrect source-row text, missing/extra row in a multi-person dataset, or fail
to associate the second row correctly. Positive literals alone do not provide
mutation sensitivity to ignored validation branches.

Add focused client malformed cases for cardinality, order, exact attribute set,
row element/descendants and exact text, each requiring atomic fallback/list/
opener/hidden-ID state. Add a positive assertion for both candidate associations
in order. Keep the existing mismatch and no-JS visibility assertions.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. Current GREEN and pre-correction RED are valid,
and server catalog sensitivity is strong, but the new client provenance grammar
is not yet independently executable at the claimed breadth. Task 2.2 remains
open; correct tests/evidence and obtain fresh Gate 3 before Gate 5 reliance.

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
1ae3a9b4cdc70c6dd19c7880a9c401a2611fe3ae264e1b0b6564523ac007354e  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v16.md
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
aa0afd4453d208699919019c6133086b2bf1fae561b47280d36df1471db236a2  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```
