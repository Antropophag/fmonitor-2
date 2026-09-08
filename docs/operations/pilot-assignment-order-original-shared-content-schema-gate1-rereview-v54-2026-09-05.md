# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 shared content schema — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_shared_content_rereview`
- Reviewed commit: `c03db6cb42baf35d0f884a9c6242ad304b9109ac`
- Reviewed parent: `509e6999847fc1baf7bef2363b617fbc23ea06dc`
- Superseded review: `docs/operations/pilot-assignment-order-original-shared-content-schema-gate1-review-v53-2026-09-05.md`
- Scope: v54 executable-spec/OpenSpec schema-v2 amendment only; untracked
  partial command production was neither inspected as a reviewed artifact nor
  changed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests, setup implementation, command implementation or prior evidence. This
append-only rereview is the only authored artifact.

## Findings

No blocking findings remain in the reviewed amendment.

### 1. Exact v1/v2 index classification and safe DDL target are now executable

The v54 contract first requires every other revisions column, key, FK and CHECK
to be exact. It then accepts exactly one single-column
`private_content_identity` candidate: a v1 UNIQUE index may have any physical
name matching `[A-Za-z0-9_$]{1,64}`, while v2 must be the non-unique exact name
`idx_aoou_revision_content`. Absence, multiplicity, wrong columns, wrong
uniqueness or a wrong v2 name conflicts before DDL. Thus the discovered v1 name
is both unambiguous and identifier-safe, and the replacement target is fixed.

The transition is one explicit MariaDB statement at revisions manifest
position: DROP the validated v1 index and ADD the exact v2 index. No row
DELETE/UPDATE, table rebuild contract, second index or historical-fact rewrite
is authorized. This closes prior finding 3 and preserves all content rows,
composition/reference facts and existential same-content lookup.

### 2. ALTER ordering, observation and recovery are now closed

The manifest walk upgrades an accepted v1 revisions table at its manifest
position, before creating any missing trailing suffix. It invokes the named
`AFTER_SCHEMA_V2_REVISION_INDEX_ALTER` phase after the single atomic ALTER and
freshly re-reads the exact v2 revisions schema before proceeding. Any
query/ALTER/observer/re-read failure maps to the fixed migration-unavailable
exception. The durable index state is constrained to exact v1 or exact v2, so a
retry classifies that state before further DDL and neither duplicates an index
nor rewrites rows.

After the manifest walk, the existing whole-schema reread still requires all
seven original tables exact v2 before the pre-capability observer. Capability
V4-to-V5 publication remains the last DDL and is appended to
`affectedTables()` only after its own fresh durable V5 proof. Therefore V5
cannot be exposed while the owned original schema is partial or non-exact.
This closes prior finding 1, including unknown-outcome retry and capability-last
ordering.

### 3. Partial, populated, repeat and conflict matrices are determinate

Only a leading existing manifest subset is compatible. A missing revisions
table is created directly as v2; an existing exact v1 revisions table, empty or
populated, is reconciled once when reached. General manifest-position reporting
plus the explicit literals make the required outcomes unique:

- clean creates all seven original tables in manifest order;
- a leading subset ending before revisions creates revisions directly v2 and
  then every missing suffix member in manifest order;
- roots plus v1 revisions reports revisions once, followed by each created
  trailing table;
- full exact v1, including the specified populated case, reports revisions
  only;
- exact populated v2 repeat is `UNCHANGED` with an empty list;
- any v1/v2 state with any other owned drift reports all conflicts in binary
  order and performs zero DDL.

In every accepted case a separate V4 capability upgrade, when needed, is
reported last; exact V5 contributes nothing. These rules also preserve exact
rows and same-content revision identity across upgrade/retry. This closes prior
finding 2 without changing workflow, roles, authorization, command result or
the opening/composition ownership boundary.

## Gate disposition

Gate 1 is freshly **APPROVED** for the v54 shared-content schema-v2 amendment at
the exact reviewed commit. This verdict authorizes only the next delivery step:
schema-v2 Gate 2 RED and its fresh independent Gate 3. It does not approve any
existing or future test or production implementation and does not waive Gate 4,
Gate 5, regression, architecture or full verification requirements.

## Verification evidence

```text
$ git rev-parse HEAD
c03db6cb42baf35d0f884a9c6242ad304b9109ac

$ git show -s --format='%H %P %s' c03db6cb42baf35d0f884a9c6242ad304b9109ac
c03db6cb42baf35d0f884a9c6242ad304b9109ac 509e6999847fc1baf7bef2363b617fbc23ea06dc spec: close shared content schema review

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 509e699..c03db6cb42baf35d0f884a9c6242ad304b9109ac
PASS (no output)
```

## Exact reviewed hashes

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
33f646c50f96e01e4b7939c5a0af8a114fd9836f8a779dcd08cc416ea75a2ffc  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
6c90bd61c18fd5147566f29b35a9bb59dee4ccf7729c69f0d4ec4eecf03f05be  docs/operations/pilot-assignment-order-original-shared-content-schema-gate1-review-v53-2026-09-05.md
```

This record intentionally omits its own circular hash.
