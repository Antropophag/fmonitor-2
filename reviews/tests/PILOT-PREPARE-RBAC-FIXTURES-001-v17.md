# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v17

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3_v9`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `932b2ae870ba8827e8508c180c8de48c67f7b620`  
Tests-only pre-correction baseline: `02e16bbcbe0c667e62634801a73f5fed88171dce`  
Verdict: **APPROVED**

The reviewer authored neither specifications/OpenSpec artifacts, reviewed
tests/support nor production. No test or production file was edited during
review. This append-only record and the post-verdict task 2.2 checkbox are the
reviewer's only changes.

## Integrity and current RED reproduction

The main worktree was clean at the exact requested commit. Owner-approved v15
normative hashes and the Gate 1 review hash match the approval record. V17
test/support/task hashes match its evidence. OpenSpec is strict-valid; PHP and
Node syntax checks and worktree diff hygiene pass.

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`:

```text
$ date --iso-8601=seconds
2026-09-04T11:04:13+03:00

$ git rev-parse HEAD
932b2ae870ba8827e8508c180c8de48c67f7b620

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

The direct real-asset harness reaches the intended new product RED:

```text
$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js

Error: malformed provenance association 4 stays atomically fail closed:
expected [true,false,false,0], actual [false,true,true,0]

# exit 1; completed 2026-09-04T11:04:19+03:00
```

The canonical MariaDB/raw-HTTP verifier independently reaches the same result:

```text
$ date --iso-8601=seconds
2026-09-04T11:04:24+03:00

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: malformed provenance association 4 stays atomically fail closed:
expected [true,false,false,0], actual [false,true,true,0]
Expected: 0
Actual: 1

# exit 255; completed 2026-09-04T11:04:27+03:00
```

Mutation 4 inserts forbidden non-whitespace interstitial text between the two
provenance rows. Current production ignores it, completes initialization and
hides the fallback list; therefore the failure is a genuine missing parser
validation, not setup or an unrelated predecessor.

## Independent tests-only pre-correction RED

A detached temporary worktree was created at
`/home/antropophag/code/fmonitor-2-prepare-v17-review-red` from exact baseline
`02e16bbcbe0c667e62634801a73f5fed88171dce`. Only the cumulative reviewed test
and test-support diff through v17 was applied. Applied hashes matched v17:

```text
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
fae262571db508b02175a6c2f52cd67e8867b15b9ad7a572da05e2888f3c7ec8  tests/InstallationProcess/support/pilot_prepare_picker_client.js
```

Both direct and canonical runs remain honestly RED at the earlier missing
mixed-provenance initialization:

```text
$ date --iso-8601=seconds
2026-09-04T11:04:38+03:00

$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js
Error: validated mixed provenance fallback list hidden after initialization:
expected true, actual false
# exit 1

$ date --iso-8601=seconds
2026-09-04T11:04:45+03:00

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
Error: validated mixed provenance fallback list hidden after initialization:
expected true, actual false
Expected: 0
Actual: 1
# exit 255; completed 2026-09-04T11:04:48+03:00
```

The test diff was reverse-applied; clean status and exact detached HEAD were
verified. The worktree was removed and `git worktree prune` completed. Its path
and metadata entry no longer exist.

## V16 finding closure

The positive mixed fixture now fixes exactly two ordered direct `LI` rows and
asserts each row's tag, exact three attribute names, ID and literal visible
text. After successful initialization, independent searches for Иванов and
Петров require provenance as the third result-text child after name/details,
with exact associated source and timestamp. Wrong second-row association can no
longer hide behind a correct first row.

Twelve separately constructed mutation lists exercise:

1. missing row;
2. extra row/cardinality;
3. reversed order;
4. forbidden interstitial text;
5. extra attribute;
6. missing attribute;
7. wrong row tag;
8. nested element descendant;
9. wrong row text;
10. wrong second ID association;
11. wrong second source association;
12. wrong second update-time association.

Every mutation receives a fresh provenance list and fresh application
execution. Each requires the same atomic fail-closed tuple: opener remains
hidden, generic fallback remains visible, redundant provenance source list
remains visible, and no hidden installer IDs exist. Thus no malformed case can
partially initialize or inherit successful state from a prior mutation.

## Retained coverage

- The v16 public catalog-tail case still proves that 503 ineligible rows do not
  hide eligible `999998`, followed by validation of invalid tail ID `1000000`.
- Server mixed/homogeneous provenance cardinality, association and mutually
  exclusive placement remain intact, as do DOM-scoped candidate exclusions.
- Installer U+E000/U+10000 primary order, installer `99,100` numeric tie and
  engineer `9,10` numeric tie/prefill/unchecked confirmation remain unchanged.
- Full direct-child six-field picker grammar, query normalization/search cap,
  ARIA, selection/removal, keyboard/focus and no-JS fail-closed checks remain.
- The v7 admission/authority matrix remains intact: independent local/process
  grants, inactive/near-match cases, committed revokes, methods, actor
  replacement, non-enumeration, canonical renderer/decorator, GET/HEAD parity,
  DB/filesystem read-only state, environment isolation and attempt-all cleanup.

Expected values are independently literal and specification-derived. No test
weakening, production-derived oracle or private production seam was introduced.

## Gate decision

Gate 3 is **APPROVED** for the exact hashes below. Task 2.2 may be checked and
minimal Gate 4 correction may implement the missing provenance grammar. Any
subsequent test/support edit restarts Gate 2 and requires a fresh independent
Gate 3. This review does not perform GREEN or Gate 5.

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
20c7548bf5ba4ce8e0cbbc6f216c2cda9cf3fd7c574288b361d6eadcddbd6439  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v17.md
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
fae262571db508b02175a6c2f52cd67e8867b15b9ad7a572da05e2888f3c7ec8  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```
