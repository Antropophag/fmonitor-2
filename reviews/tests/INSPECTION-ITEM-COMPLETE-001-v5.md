# INSPECTION-ITEM-COMPLETE-001 — independent Gate 3 rereview v5

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, helper, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `APPROVED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved spec: SHA-256
  `64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`.
- Scalar projection helper: SHA-256
  `d3f876a6d362c25fc778c24d2a58ff3e3b99c64e992601952116b631e2d164e9`.
- Replay test: SHA-256
  `eee12e50afbc568b43538c880665d0c4ed40c3fe4ed012f1f5281bb24ab1da9b`.
- Normalization/current-revision test: SHA-256
  `3274a37db5404be397c40e9bf4f44a3164c5d8b76b086839fe896c5ec698914f`.
- Authorization-before-replay test: SHA-256
  `9506fca497e3aa34227244696591d3089ce6530a0bfe550b54c07a33672f948e`.
- Combined-precedence test: SHA-256
  `d73302fe980d00e31f2bb81f2ba21caa965d4e7cae2595eab20ecb3211a21bcd`.
- v5 RED evidence: SHA-256
  `221c195e1d16fb0e7dfad82f3d4e086a06df897449081047db116922e3f55041`.
- Unchanged previously approved example-A test: SHA-256
  `b82775a4b93092f25d61cd8a8f5ac27dedb1155f90f1cc7e9feb392e6f0080ff`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

## Independently reproduced checks

`php -l` passed for the helper and all four affected test files. I reran the
seven v5 RED commands exactly as documented:

```sh
FMONITOR_ITEM_TEST_CASE=current_revision tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
FMONITOR_ITEM_TEST_CASE=reordered_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
FMONITOR_ITEM_TEST_CASE=revoked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=blocked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=schema_over_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
FMONITOR_ITEM_TEST_CASE=conflict_over_mutable tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_test.php
```

All seven exited `0` through the RED harness after deterministic successful
acceptance and the intended public-contract failure:
`currentChecklistRevision` was expected as integer `1` and was actually `null`.
No failure came from setup, autoload, fixture, database or network.

The unchanged example-A test remains green:

```sh
php tests/InstallationProcess/inspection_item_complete_001_test.php
```

Output: `PASS: INSPECTION-ITEM-COMPLETE-001 example A`.

## V4-01 closure

V4-01 is closed. The helper now projects a distinct
`currentChecklistRevision` key alongside `baseRevision` and
`acceptedRevision`. It consumes only the public `ItemCompletionEvidence` DTO;
it has no fixture, repository, SQL or production-serialization dependency.

The independent literal `1` is asserted immediately after the first accepted
query in the standalone current-revision selector and in every affected replay,
authorization and precedence path. Consequently a missing property cannot pass
as matching `null` values. After that literal check, each replay or rejection
compares the complete before/after scalar projection strictly, so current
revision participates in immutable/no-mutation evidence with all other fields.

The projection covers every public evidence field fixed by the approved spec:
operation/case/section/item identities; actual and nullable assigned actors;
device and server times; base, accepted and current checklist revisions;
immutable template id/version/hash; and every installer tab id/full name/
position in returned order. Comparisons are scalar/array value comparisons,
not PHP object identity. Expected current revision `1` is independently fixed by
the approved worked example rather than computed from production behavior.

## Reconfirmed prior findings

- Canonical ascending installer persistence and reordered-set replay remain
  required, while a genuinely duplicated installer list remains invalid and a
  genuinely changed normalized payload remains conflicting.
- Revoked and blocked exact replays still require current receipt-time
  authorization to precede replay resolution.
- Combined authorization > syntax > schema > replay/conflict > mutable
  precedence remains in the tests. Never-accepted ids use the public query to
  prove `null`; accepted ids use the complete scalar projection to prove no
  mutation.
- The fixture supplies deterministic prerequisites only and contains neither a
  result oracle nor an assertion-side persistence view.
- The prior A test was not edited and remains GREEN.
- No sequential test claims concurrency. Real same-base overlap remains
  explicitly deferred to its distinct MariaDB two-connection RED.

## Gate decision

There are no unresolved findings in the v1-v5 reviewed RED matrix. Incremental
Gate 3 is `APPROVED` for the exact hashes above. Gate 4 may implement only enough
production behavior to make this reviewed matrix green without changing its
expectations. The separately deferred real MariaDB concurrency example still
requires its own demonstrated RED and independent review before it can be
claimed complete.
