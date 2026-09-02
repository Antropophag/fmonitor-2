# INSPECTION-ITEM-COMPLETE-001 — independent Gate 3 rereview v4

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, helper, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved spec: SHA-256
  `64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`.
- Scalar projection helper: SHA-256
  `859891d0477358c5b1df6b2be4c2e48c5d25a9226eb5257ac57fde659b3e6d01`.
- Replay test: SHA-256
  `56e2b2d21aeae8fa1dbddae413a95fbdfd5689d8d4be4824bc0f62a7cc5f12d5`.
- Normalization test: SHA-256
  `46347e51dac4ee569de9378d86eb2b201650c0063a07528317b4ec586c6f305f`.
- Authorization-before-replay test: SHA-256
  `73c2887b71e48fcc2c592218414a7e170962604c4048027c9106b4aedf0a8a6d`.
- Combined-precedence test: SHA-256
  `d359f98146a9e44ef68721605e59154c09259f1b74e6dad451667225299aa4d1`.
- v4 RED evidence: SHA-256
  `ac9ac1d23fc3d899c46f763c25cf29994a6975bcb1f3111bf9b9ec28d41bbbe1`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

## Independently reproduced checks

`php -l` passed for the helper and all four affected tests. I reran every v4
behavior command:

```sh
FMONITOR_ITEM_TEST_CASE=reordered_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
FMONITOR_ITEM_TEST_CASE=revoked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=blocked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=schema_over_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
FMONITOR_ITEM_TEST_CASE=conflict_over_mutable tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_test.php
```

All six RED commands exited `0` through the RED harness and retained their
documented intended failures: reordered replay returned `ACCEPTED`; revoked and
blocked receipts threw rather than returning typed rejection; schema/replay
returned `ACCEPTED`; conflict/mutable and mutable exact replay threw
`CASE_NOT_WORKING`. No command failed from setup or from the projection helper.

The prior example-A test remains green:

```text
PASS: INSPECTION-ITEM-COMPLETE-001 example A
```

## V3-01 assessment

The object-identity defect itself is corrected. The helper consumes only the
public `ItemCompletionEvidence` DTO and produces a fresh scalar/array value. It
does not invoke production serialization, fixture state, repository methods or
SQL. `assertSameValue` therefore performs strict value equality on arrays, not
identity equality on DTO objects.

The projection includes client operation id, case/section/item ids, actual and
assigned actors, both timestamps, base/accepted revisions, template
id/version/hash, and every installer's tab id/full name/position in returned
order. Capturing the first public-query value as the replay immutability
expectation is appropriate: the expected rule is equality with the immutable
accepted evidence, not a production-derived calculation. Independent literal
assertions elsewhere continue to establish the accepted actor, assignment,
revision, installer identity and canonical order.

## V4-01 — BLOCKER: one normative public evidence field is still omitted

The approved public-seam contract states that `ItemCompletionEvidence` contains
both the operation's base/accepted revisions **and the current case checklist
revision** (`specs/INSPECTION-ITEM-COMPLETE-001.md`, public evidence query
definition). The v4 helper claims to project every normative field but stops at
`baseRevision` and `acceptedRevision`; it contains no current-case revision.
Current `ItemCompletionEvidence` production DTO likewise exposes no such field.

Therefore a query that omits or corrupts current case revision passes every
immutability comparison. This is a public-contract coverage gap, not an
implementation preference, and it directly contradicts the requested v4 check
that the helper cover every normative evidence field.

Action required:

1. add the named current case checklist revision to the test-side scalar
   projection using only the public DTO;
2. add an independently fixed assertion that the first accepted example exposes
   current revision `1`, so an absent property cannot compare as `null` on both
   sides and silently pass;
3. retain it in every before/after projection comparison, rerun the affected
   selector REDs, and append corrected Gate 2 evidence.

The test must not read fixture case state to obtain this value. The precise DTO
property name should be fixed consistently with the approved seam before GREEN.

## Reconfirmed prior coverage

- Canonical numeric installer ordering, reordered semantic replay and true
  duplicate rejection remain covered.
- Revoked and blocked exact replay require current receipt authorization first.
- Combined authorization > syntax > schema > replay/conflict > mutable
  precedence remains covered with `null` for never-accepted ids and immutable
  value projections for accepted ids.
- The fixture remains setup-only; it provides no result oracle or assertion
  side channel.
- No test claims concurrency. Real overlap remains deferred to the distinct
  MariaDB two-connection RED described in prior evidence.

## Gate decision

Gate 3 v4 remains `CHANGES_REQUESTED` for V4-01. The strict scalar-comparison
approach is correct and closes the identity portion of V3-01, but the helper is
not yet a projection of every approved public evidence field. Return only the
affected test/helper and append-only evidence to Gate 2, then request a fresh
independent rereview. Production expansion remains unauthorized.
