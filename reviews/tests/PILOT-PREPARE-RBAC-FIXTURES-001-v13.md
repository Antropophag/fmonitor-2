# Independent Gate 3 test rereview — PILOT-PREPARE-RBAC-FIXTURES-001 v13

Date: 2026-09-04  
Reviewer: independently tasked agent `/root/prepare_v15_gate3_v9`  
Test author: independently tasked agent `/root/prepare_v15_red`  
Reviewed commit: `91cd544aa3d2b6b9d08bdb13153662fe60cffad9`  
Pre-Gate4 RED baseline: `6137d5e83be6a31b00e801efe6acf00b4ce473ce`  
Gate: post-Gate5-preflight Gate 2 correction against owner-approved prepare upload-first v15  
Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the approved specifications/OpenSpec package,
the reviewed tests/support, nor production. No reviewed test or production
file was edited. This append-only record is the reviewer's only change;
OpenSpec task 2.2 remains unchecked.

## Integrity and current-head verification

The primary worktree was clean at the requested commit before review. The
owner-approved normative hashes and Gate 1 rereview hash still match the
approval record. The v13 test/support/task hashes match the evidence record.
OpenSpec is strict-valid, PHP/Node syntax and worktree diff hygiene pass.

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`:

```text
$ date --iso-8601=seconds
2026-09-04T10:31:48+03:00

$ git rev-parse HEAD
91cd544aa3d2b6b9d08bdb13153662fe60cffad9

$ openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

$ php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_prepare_form_001_test.php

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

# both behavioral commands exit 0; completed 2026-09-04T10:32:05+03:00
```

Thus the corrected v13 test executes completely and is GREEN against current
production; it is not masked by the former whole-document SVG substring.

## Independent pre-Gate4 RED reproduction

A detached temporary worktree was created at
`/home/antropophag/code/fmonitor-2-prepare-v13-red` from exact baseline
`6137d5e83be6a31b00e801efe6acf00b4ce473ce`. Only the
`cc707fe9..91cd544a` diff for
`tests/InstallationProcess/pilot_prepare_form_001_test.php` was applied. Its
SHA-256 was the exact v13 hash `2be50722...`; test support remained the v12/v13
hash `5f8cc0d8...` already present at the baseline.

```text
$ date --iso-8601=seconds
2026-09-04T10:32:22+03:00

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1

# exit 255; completed 2026-09-04T10:32:25+03:00
```

This is the same intended missing atomic-client behavior that originally
authorized Gate 4, not setup or a late v13-only assertion. The test diff was
reverse-applied, clean status and exact detached HEAD were verified, then the
temporary worktree was removed and `git worktree prune` completed. The path no
longer exists and no worktree metadata entry remains.

## Findings closed by v13

- Candidate exclusions are now scoped to the owning inert installer records
  and engineer fieldset. Exact IDs/tabs/names are checked without treating
  unrelated shared SVG geometry as business data. This correctly removes the
  false `75` positive and permits the SVG workaround reversion.
- File, CSRF, multipart, submit and mutation-URL literals remain asserted over
  raw response bytes; unlike short numeric identities these are meaningful
  forbidden command markers. Existing DOM assertions additionally retain no
  initial hidden installer IDs, no inline script/style, and no select/textarea.
- The mixed-provenance fixture independently fixes both eligible rows' names,
  sources and timestamps and requires the per-row provenance list.
- The U+E000/U+10000 pair is well chosen: Unicode scalar/code-point order is
  U+E000 before U+10000, while UTF-16 code-unit comparison reverses them. The
  asserted IDs and exact names therefore detect the production bug reported by
  Gate 5 preflight.
- The complete v12/v11 client grammar, query, ARIA, focus, keyboard and
  fail-closed matrix and the v7 local/process authority, method, revoke,
  non-enumeration, renderer, read-only and cleanup matrix remain unchanged.

## Blocking findings

### 1. Mixed provenance does not prove group-versus-row exactness

The new case requires one `ul.fm2-picker-provenance` and the two expected `li`
rows, but it does not require exactly two direct `li` children and does not
exclude the homogeneous group-level source/timestamp presentation. Conversely,
the initial homogeneous case checks only that the source/timestamp literals
occur somewhere in flattened document text; it does not prove one group-level
pair or the absence of a per-row list.

The approved contract distinguishes these representations: identical pairs are
shown once at group level, while mixed pairs are shown separately per eligible
row. An implementation that renders both forms, adds a third stale row, or
always renders a per-row list passes the current assertions. Add structural,
mutually exclusive homogeneous and mixed assertions with exact list cardinality
and exact row association.

### 2. Numeric-ID tie ordering remains untested

The supplementary-plane fixture proves the primary Unicode code-point name
ordering, but its two names differ. It therefore cannot exercise the normative
tie rule `numeric installerTabId ASC`. Existing positive fixtures also have
different names, and the client malformed-order matrix only reverses different
names. A sorter with the correct code-point comparison but insertion-order or
lexical-ID behavior for equal names remains green.

Add a public successful equal-normalized-name fixture inserted in deliberately
noncanonical order whose decimal IDs distinguish numeric from lexical order,
and assert exact resulting IDs. Preserve the supplementary-plane case as the
independent primary-comparator oracle. The engineer tie rule may remain an
inherited obligation only if an existing executable case is identified;
otherwise its same-name/numeric-user-ID ordering needs equivalent coverage.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. V13 restores sound candidate exclusion and
adds valuable production-sensitive provenance and Unicode cases, and its
pre-Gate4 RED/current GREEN evidence is valid. However, it does not yet make
the approved group-versus-row provenance choice or numeric tie-break ordering
regression-sensitive. Task 2.2 must stay open. Amend only tests/evidence under a
fresh Gate 2 cycle and obtain a fresh independent Gate 3 review before relying
on these tests for a new Gate 5 review.

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
1a88f36e463655c1f27cc5c3fdac5d851713f2b7836541418173842e2319351c  docs/operations/pilot-prepare-rbac-fixtures-red-evidence-v13.md
2be50722a62def245ccd78dadd3851dc78e6cd7790531555131a4185511fe04f  tests/InstallationProcess/pilot_prepare_form_001_test.php
5f8cc0d803302d4469c0775e291a8278c692ec85897c5e8bafda4d830174952a  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```
