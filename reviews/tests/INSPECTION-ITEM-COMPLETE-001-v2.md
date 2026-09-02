# INSPECTION-ITEM-COMPLETE-001 — independent incremental Gate 3 review v2

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, fixture, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Reviewed baseline and exact artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved spec, `specs/INSPECTION-ITEM-COMPLETE-001.md`: SHA-256
  `64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`.
- Replay RED: SHA-256
  `c91eaa260e3d81266c76b9f889ce857564814a696bfd9b8ec95a0750154eac03`.
- Authorization RED: SHA-256
  `fc7d89edb6767996206eeadc35a3d11e337d3ffd69cdd1edd326a52aa805f815`.
- Conflict/stale RED: SHA-256
  `fb22111119c6e052680bc5ce3725a02223d36f51de27302eb3a3e35a9dda5dce`.
- Typed-rejections RED: SHA-256
  `4bed58e616a76c4f5818e2151e3a44710f37d964674cae8fdc2e2298b5019b72`.
- Expanded fixture,
  `tests/Support/InMemoryInspectionEvidenceEnvironment.php`: SHA-256
  `aa656f5bff91303991b67dc44eb5906c2832e925eeafd7e74bea346f33974549`.
- Incremental RED evidence,
  `docs/operations/inspection-item-completion-red-evidence-v2.md`: SHA-256
  `4e6a33240ce3cf4a6eeee081315b690a485407636da37cc63abf43c2c6b184dd`.
- Previously approved example-A test: SHA-256
  `b82775a4b93092f25d61cd8a8f5ac27dedb1155f90f1cc7e9feb392e6f0080ff`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Current minimal production `InspectionEvidence.php`: SHA-256
  `d58b7896dec034650a39b62a54e29021b2dd1cf787353eec4534e25843720a01`.

## Reproduced commands

I ran `php -l` on all four new test files; all passed. I then ran:

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_authorization_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_conflict_revision_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_rejections_test.php
php tests/InstallationProcess/inspection_item_complete_001_test.php
```

The four RED harness invocations each exited `0` after observing the intended
underlying failure. Respectively, current production threw
`DomainException(CASE_NOT_WORKING)`, threw
`DomainException(ACTOR_NOT_AUTHORIZED)`, returned `ACCEPTED` instead of
`OPERATION_PAYLOAD_CONFLICT`, and returned `ACCEPTED` instead of
`INVALID_COMMAND`. The prior example-A test exited `0` with its exact PASS line.
These are behavioral REDs after healthy in-memory setup, not setup failures.

## Findings

### V2-01 — BLOCKER: normalized replay equivalence is not tested

The approved command contract requires `installerTabIds` to be normalized as
ascending numeric identifiers before both payload comparison and persistence.
The conflict test changes the only installer from `1042` to `2048`; it proves a
genuinely different payload conflicts, but it cannot distinguish correct
normalization from a raw order-sensitive comparison.

An implementation may accept `[2048, 1042]`, store that order, and return
`OPERATION_PAYLOAD_CONFLICT` for an exact semantic replay containing
`[1042, 2048]`; every reviewed v2 test would still pass. This violates both the
normalization and replay contracts.

Action required: add a first acceptance with at least two assigned installers
in non-canonical order, assert the query persists them in ascending order, then
replay the same semantic payload in the other order and require `DUPLICATE` with
the original revision and unchanged evidence. Retain a genuinely changed
normalized payload case for `OPERATION_PAYLOAD_CONFLICT`.

### V2-02 — BLOCKER: authorization-before-replay precedence is not tested

The authorization test rejects only fresh operation ids after capability
revocation/blocking. The replay test repeats an existing id only while the actor
remains authorized. Consequently an implementation that resolves an exact
replay before current receipt-time authorization would pass both files and
wrongly return `DUPLICATE` to a now-blocked actor.

Approved example D2 explicitly requires `ACTOR_NOT_AUTHORIZED` to win over
replay resolution. Action required: after accepting an operation, revoke its
actor's capability (and/or block the actor), repeat that exact command, require
a typed `ACTOR_NOT_AUTHORIZED` result, and prove through the public query that
the original accepted evidence remains unchanged.

### V2-03 — BLOCKER: observable rejection precedence remains unprotected

Individual rejection rows establish reason vocabulary but not the normative
order `authorization -> syntax -> v8 deployment -> replay/conflict -> mutable
first-acceptance checks -> locked revision`. A production implementation can
check syntax before authorization or return `DUPLICATE` while v8 is unavailable
and still pass the current matrix.

Action required: add focused combined-condition cases that require at least:

1. unauthorized plus malformed command -> `ACTOR_NOT_AUTHORIZED`;
2. authorized malformed command plus unavailable schema -> `INVALID_COMMAND`;
3. authorized syntactically valid exact replay plus unavailable schema ->
   `INSPECTION_SCHEMA_UNAVAILABLE` with unchanged original evidence;
4. changed-payload existing id plus now-invalid mutable case/template/crew ->
   `OPERATION_PAYLOAD_CONFLICT` with unchanged original evidence.

Each must assert a stable `ItemCompletionResult` status, not an exception, and
must use only the public evidence query to prove no partial mutation.

## Checks that pass

- Every behavioral assertion uses only `completeItem` and
  `getItemCompletion`; fixture methods are deterministic setup controls, not an
  assertion or persistence side channel.
- The current REDs are sensitive to mutable-fact replay ordering, current
  capability/activity on fresh receipts, a genuinely changed payload, stale
  revision and its absent loser evidence, and the listed individual typed
  rejection reasons. Assigning each result before reading `status` also ensures
  exceptions cannot satisfy the expected typed-result shape once reached.
- Fixed literal inputs and isolated process memory make all cases deterministic.
  The fixture's new schema toggle and case mutation method do not change the
  semantics of the previously reviewed API; the exact example-A test remains
  GREEN. The fixture change therefore does not invalidate its v1 approval.
- The stale test honestly proves sequential stale-loser behavior only. The v2
  evidence explicitly defers example F to a separately reviewed MariaDB RED
  using two independent connections/processes and a deterministic overlap
  barrier. It does not misrepresent sequential calls as concurrency.
- The test set does not pull inspection planning, photos, section completion,
  percentages or premium behavior into this slice.

## Gate decision

Gate 3 v2 is not approved. Return only the affected tests/fixture and append-only
RED evidence to Gate 2, demonstrate fresh intended REDs for V2-01 through
V2-03, and request a new independent rereview. Production must not be expanded
to satisfy this v2 matrix before that approval. The prior example-A Gate 3
approval and its current GREEN remain intact.
