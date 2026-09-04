# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v15

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3_v9`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `a7e49598787f72a05735cbe94478b50f6149b510`  
Pre-Gate4 RED baseline: `6137d5e83be6a31b00e801efe6acf00b4ce473ce`  
Gate: corrected Gate 2 v15 against owner-approved prepare upload-first v15  
Verdict: **APPROVED**

The reviewer authored neither specifications/OpenSpec artifacts, reviewed
tests/support nor production. No test or production file was edited during
review. This append-only record and the post-verdict task 2.2 checkbox are the
reviewer's only changes.

## Integrity and current-head GREEN

The main worktree was clean at the exact requested commit. All owner-approved
normative hashes and the Gate 1 review hash match the approval record. The v15
test/support/task hashes match its RED evidence. OpenSpec is strict-valid; PHP
and Node syntax checks and worktree diff hygiene pass.

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`:

```text
$ date --iso-8601=seconds
2026-09-04T10:45:28+03:00

$ git rev-parse HEAD
a7e49598787f72a05735cbe94478b50f6149b510

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

$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js
prepare picker client contract: PASS

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

# both behavioral commands exit 0; completed 2026-09-04T10:45:43+03:00
```

The complete verifier reaches both new v15 cases, so GREEN is not hidden by an
earlier assertion.

## Independent pre-Gate4 RED

A detached temporary worktree was created at
`/home/antropophag/code/fmonitor-2-prepare-v15-review-red` from exact baseline
`6137d5e83be6a31b00e801efe6acf00b4ce473ce`. Only the cumulative reviewed test
and test-support diff through v15 was applied; no production, specification,
review or operations file was changed. Applied hashes matched current v15:

```text
72f6c766...  tests/InstallationProcess/pilot_prepare_form_001_test.php
5955b599...  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0cca...  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5...  tests/Support/pilot_prepare_renderer_spy_router.php
```

Canonical reproduction:

```text
$ date --iso-8601=seconds
2026-09-04T10:45:57+03:00

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1

# exit 255; completed 2026-09-04T10:46:00+03:00
```

This is the intended earliest missing atomic-client behavior on pre-Gate4
production, not setup or a later-only v15 assertion. The patch was
reverse-applied, clean status and the exact detached HEAD were verified, the
worktree was removed, and `git worktree prune` completed. Its path and metadata
entry no longer exist.

## v14 blocker closure

### Homogeneous provenance cardinality and shape

The homogeneous response now requires exactly one scoped direct paragraph
whose direct text carries either provenance prefix. Independently, that sole
form must contain exactly one `br`, exact source line
`Источник кадровых данных: one_c_zup_via_bitrix`, exact timestamp line
`Актуально на: 2026-08-27T18:15:00+03:00`, and zero scoped per-row provenance
lists. A duplicate or stale second group paragraph is observable.

The retained mixed case is mutually exclusive: exactly one scoped `ul`,
exactly two direct `li` rows, exact candidate/source/timestamp association in
candidate order, and zero group-level provenance paragraphs. Group-versus-row
behavior is therefore regression-sensitive in both directions.

### Equal-name engineer numeric ordering

The public GET/HEAD fixture transforms the two active eligible engineers from
the deliberately noncanonical source mapping through temporary identity
`70074` into equal exact names at IDs `10` and `9`. The expected order `9,10`
distinguishes numeric from lexical ID order and from the original source
mapping. The same parsed fieldset independently requires checked states
`[true,false]`, proving prefill only for exact eligible ID `9`, and exactly one
separate unchecked `controlEngineerConfirmed=yes` checkbox. Restoration occurs
before the retained card and later matrices.

## Complete retained assessment

- Candidate exclusion remains scoped to exact installer record identities,
  six-digit tabs and names, and to engineer radio values/labels. Ordinary SVG
  geometry cannot cause a false failure.
- Raw forbidden command markers still exclude submit, CSRF, multipart, file
  input and mutation URL bytes; DOM checks retain no initial hidden IDs, inline
  script/style, select or textarea.
- Installer numeric tie order remains covered at `99,100`, including tabs and
  reversed client rejection. U+E000/U+10000 remains the independent Unicode
  code-point-versus-UTF-16 primary ordering oracle.
- The full v12/v11 client dataset grammar, bounds, normalization, search cap,
  zero state, selection/removal, hidden IDs, ARIA, focus, Escape, native Tab and
  fail-closed initialization matrix remains byte-identical.
- The v7 authority/admission matrix remains intact: independent local/process
  grants, inactive and near-match chains, committed revokes, fully delivered
  unsupported methods, actor replacement, object/state/DB boundaries,
  canonical renderer decorator, GET/HEAD parity, non-enumeration, read-only
  snapshots, explicit environment isolation and attempt-all cleanup.

Expected values are independently fixed from the approved contract and literal
fixtures, not derived from production output. No weakened assertion, private
production seam or new product decision was found.

## Gate decision

Gate 3 is **APPROVED** for the exact reviewed hashes below. Task 2.2 may be
checked. These tests may now be relied on for subsequent focused verification
and a separately tasked Gate 5 review. Any later test or support edit restarts
Gate 2 and requires a fresh independent Gate 3. This verdict does not perform
or approve Gate 5.

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
5f152f53d1506aeceaf74e98f8ebc8f635d3daf077f104fa047dcf7fee360ad6  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v15.md
72f6c7668ba5f45f1da0ee1f8814e6807a0bb302f43e0db8162ade47b88af69c  tests/InstallationProcess/pilot_prepare_form_001_test.php
5955b599e04b4f389e8a88cf50b02c106f9c757a0b33567b7a77b3161e5cb040  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```
