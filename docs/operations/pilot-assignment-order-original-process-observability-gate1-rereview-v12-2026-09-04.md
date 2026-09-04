# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v12 process observability amendment — fresh independent Gate 1 rereview

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_projection_rereview`
- Reviewed commit: `afca0bc9c6ca38c66e926c3c30e44be2d6cb38a4`
- Superseded Gate 1 verdict: commit `55a6bb4`, `CHANGES_REQUESTED`
- Scope: corrected checklist/decoy process-observability contract and its
  coherence with the complete executable specification/OpenSpec change
- Verdict: **APPROVED**

The reviewer did not author or edit the reviewed executable specification,
OpenSpec artifacts, tests, support oracle or production code. This append-only
review record is the only artifact added.

## Prior findings resolved

### G1-v11-1 — canonical opening state

V12 now maps checklist availability to the established public stored value
`process_state='working'` plus all three non-null opening fields
`actual_start_date`, `opened_at` and `opened_by_user_id`. This agrees with
`OPEN-INSTALLATION-001` and `PERSISTENCE-OPEN-001`. Any other state or any
missing opening field maps to `blocked_until_opening`.

### G1-v11-2 — one coherent projection definition

The superseded multi-identity/empty-checklist wording is removed. For every
valid exact `(caseId, orderId)` target, the reader first proves that the case
and order both exist and that the order belongs to the case, then hashes
exactly one case-owned item with member order `availability`,
`checklistIdentity` and identity `installation-case-<caseId>`. Consequently a
valid checklist projection cannot be empty. Missing case, missing order or a
mismatched case/order pair throws the fixed
`AssignmentOrderOriginalEvidenceUnavailable` and cannot return partial JSON.

`orderId` participates only in target admission/ownership validation; it is not
an input to the admitted case-owned checklist item. Original, assignment-order
status and task facts are also explicitly excluded as digest inputs.
`tasksSha256` remains separate and cannot substitute for `checklistSha256`.

The empty projection is now assigned only to `decoySha256` when there are no
other prefixed installation-case rows. Otherwise every non-target case maps to
exact `{caseId,marker}` with `marker=process_state`, ordered numerically by case
ID. This includes the Example-A decoy row `9999/fixture-decoy-v1` and does not
read original tables or a fixture callback.

## Coherence and boundary review

- Example A's prepared target deterministically maps to
  `blocked_until_opening`; the adjacent literal hashes to the published
  `ccb8260ee585db0d0cec53f376d71a66869cf1eca3f287e5a5d7a4e91a04d546`.
- The unrelated row maps to the adjacent decoy literal and published
  `963ca80eddc50543eb940cf813923bd451d0974585a529e7880107df6982e2ca`;
  the independently checked empty decoy projection hashes to
  `eef46741adfc3a9f76294d3b78f37a45f113092ac9d44ee77c7a038a88ff09a1`.
- The positive opened-state projection is independently determinate:
  `working` with all opening fields yields `available`; partial/inconsistent
  opening facts remain fail-safe as `blocked_until_opening`.
- Initial or correction upload cannot mutate either projection: the reviewed
  slice is limited to private original-evidence persistence and explicitly
  forbids changes to case/opening, composition, tasks and checklist
  availability.
- The evidence reader remains fresh-connection and read-only, with no DDL/DML,
  schema inference, command repository, fixture callback or mutation path.
  Construction, read and first/repeated close failures preserve the single
  fixed evidence-unavailable outcome and no partial JSON or diagnostics.
- Fixture setup/cleanup remains DML-only, exact-row validated, one-transaction
  `SERIALIZABLE`, reverse dependency ordered and bounded to its owned rows.
  Reader close precedes child reaping/artifact cleanup where required; repeated
  close performs no I/O, and safe-log removal occurs only after reader close
  and child termination/reaping.

No product behavior, role, authority, public command contract or upload
mutation scope is expanded by this amendment. It supplies the missing
independent row-to-projection oracle needed to resume the existing Gate 2 test
correction.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ sha256(canonical blocked checklist JSON)
ccb8260ee585db0d0cec53f376d71a66869cf1eca3f287e5a5d7a4e91a04d546

$ sha256(canonical available checklist JSON)
a1cb1309c712a631479d3eda2ab1c04ddd8df2b7aa84e9b53455da5126c8c2a7

$ sha256(canonical decoy JSON)
963ca80eddc50543eb940cf813923bd451d0974585a529e7880107df6982e2ca

$ sha256('{"items":[]}')
eef46741adfc3a9f76294d3b78f37a45f113092ac9d44ee77c7a038a88ff09a1

$ git diff --check
PASS (before adding this append-only review; no output)
```

## Exact reviewed SHA-256 inputs

```text
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
b298fde9e7c126622bd17beafd0b990824fdb2974cd22f6045ff4e35efe60984  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9e2c354069c3b62f5d65eea37dd0417b6bbd8ef78d804f870882beda537dbbc1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
3279725925f577c54860dab89f1aa55abaab3a8e29ab6cf513da9693b932baf9  docs/operations/pilot-assignment-order-original-process-observability-gate1-review-v11-2026-09-04.md
24c37e0e0ff55eba8040f6c07f6b73eefbf9602e347586808b4c55c81c657e51  docs/operations/assignment-order-original-database-setup-projection-observability-gap-2026-09-04.md
72d4252762912d5c26dfb35ec0008f4d74a2e9e477ec89a601cbf8ee077ef400  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
35afcdd180441a2bf3631c6715dac225135df5bdc25011f31abfe319ef5c69ee  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
```

The review-record path is metadata because a self-hash is circular.
