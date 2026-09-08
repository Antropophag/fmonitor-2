# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v40 production composition — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_composition_rereview`
- Reviewed commit: `846c6dd52a83679dbaafbfcca7227d0201c7fe68`
- Prior review: `docs/operations/pilot-assignment-order-original-production-composition-gate1-review-v39-2026-09-05.md`
- Scope: corrected production composition derivation amendment and reopened
  initial RED/Gate 3 lineage
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
production implementation, prior reviews or historical evidence. This
append-only review is the only authored artifact.

## Independently verified properties

V40 preserves the intended source boundary: the production reader uses the
canonical order and member rows in one read-only transaction snapshot and has
no caller, legacy-slot or fixed-hash fallback. The identity remains exact
`composition-<assignmentOrderId>-v<versionNo>`. The canonical compact JSON key
order remains
`caseId,compositionIdentity,engineerUserId,installers,orderId`, with JSON
integer IDs and included installer IDs numeric-sorted and unique. The same
derived identity and digest are explicitly propagated into accepted-operation
fingerprint, immutable root evidence and the evidence reader.

The Example A preimage is exactly:

```text
{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":31,"installers":[7001,7002],"orderId":81}
```

Independent `sha256sum` and PHP `hash('sha256', ...)` calculations produce:

```text
388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5
```

The order selection is now explicit by `id=assignmentOrderId` with exact case
equality. The amendment explicitly includes `assign|retain`, excludes
`release`, numeric-sorts included IDs, rejects unknown actions, and maps a
missing/mismatched order to `ORDER_NOT_FOUND`, an invalid composition to
`INVALID_COMPOSITION`, and dependency failure to the existing typed unavailable
outcome. No stream or mutation is reached at this lookup step.

The lifecycle correction is also explicit. Tasks 2.1 and 2.4 are open: the
active initial RED must replace its stale `111...` oracle with the derived
digest and receive a fresh independent Gate 3. The old active test remains
intentionally unchanged and pending at reviewed commit; its historical RED and
Gate 3 record are not edited. Task 2.3 remains checked only for the separately
scoped database-setup RED/review and does not claim approval of the changed
initial oracle.

`openspec validate replace-pilot-registration-with-original-upload --strict`
passes, and `git diff --check 466bd50..846c6dd` passes.

## Blocking findings

### G1-40-1 — the date source is absent from the exact source-column contract

The new exact order-column list contains only `id`, `installation_case_id`,
`version_no`, and `control_engineer_user_id`, but the member validity rule then
requires `valid_from<=order_date` and `valid_to>=order_date`. The reader cannot
apply that rule from the declared exact inputs. Moreover, active product data
model truth names the immutable proposed template date `template_date`, while
the older migration schema names `order_date`; the original's confirmed
`documentDate` is a different fact. Gate 1 therefore has not selected which
date is normative for membership derivation or bound it to an exact source
column. A Gate 2 author could choose template date, legacy order date, command
document date, or omit the interval rule, producing materially different
composition hashes.

Amend the executable spec and matching delta/design to name the one exact date
source and column, explain its relation to the active `template_date` truth and
the currently deployed schema, and state the exact invalid/unavailable mapping.
If no date is intended to participate, remove the interval comparison rather
than leaving an unavailable input in the algorithm.

### G1-40-2 — validity of excluded release rows remains ambiguous

V40 says every included (`assign|retain`) installer ID must be positive and
unique and gives interval checks in the same sentence. It does not say whether
a `release` row with an invalid/coercible/duplicate installer ID or invalid
date interval is rejected before exclusion or ignored because it is excluded.
The delta compresses this into “invalid date/ID/duplicate” without defining the
row set to which each predicate applies. Thus two conforming-looking readers
can disagree on `INVALID_COMPOSITION` for the same exact database snapshot.

State explicitly, for every `assign`, `retain`, and `release` row, which ID and
date predicates are validated, what duplicate means (among included rows,
across all rows, or duplicate action/interval tuples), and whether exclusion
occurs before or after validation. Keep unknown action and zero included-set
mapping exact. Synchronize the delta and design so they do not leave a looser
algorithm than the executable spec.

## Verdict and next boundary

Gate 1 v40 is **CHANGES_REQUESTED**. Task 1.27 must remain open, and the initial
RED correction/task 2.4 must not start from this batch. A corrected amendment
needs a fresh separately tasked Gate 1 rereview. Historical evidence and the
currently pending old active test remain unchanged.

## Exact reviewed hashes

```text
9799984c603cccc8b0f49e4c411caf2bd770771fc8b2ed83aa8dbc88c45e7c43  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
9680374f366e5bb2e509324b2d22a90aa1471e36e8efd5c03f2c5e5877fe9f32  openspec/changes/replace-pilot-registration-with-original-upload/design.md
80e5ce0290f701c3177242a88820ef9888c67680059ebbf71019087f3546cfd6  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
f54a9b2d10be9f44830c6c90c35f6ff2d9bef2ef53f9d5edc4324b02e9059f2e  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

Relevant deliberately pending active-test and historical-review hashes:

```text
0579c317f073fb303ec6bbe89c11b9f42643989546501ac4f35b2702f3559469  tests/InstallationProcess/assignment_order_original_upload_001_test.php
d8fa4511ab63b95d9032d39fe1895e878fead250e68c97befa81038f2261e609  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-initial-v2.md
```

This record intentionally omits its own circular hash.
