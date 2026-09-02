# INSPECTION-ITEM-COMPLETE-001 — independent incremental Gate 3 rereview v3

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, fixture, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved spec: SHA-256
  `64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`.
- Prior v2 review: SHA-256
  `500848ac01ab08f29b1eb2970a4de29f73d39ae2c02b6fcee2c18ce9ab31a15e`.
- Normalization test: SHA-256
  `5157daa3bcb6a9ed2404d2eee3852a6bfc491c32ec4650dc8d3c280ddbdea98f`.
- Authorization-before-replay test: SHA-256
  `87ea0f64f9bf7505317417b36ef45c48ebb559921e3e7ea82a169bb01dc32795`.
- Combined-precedence test: SHA-256
  `5a644bc5104172e2f8c8d63c40db7fe50960fbdbd5f311251b3f40a61db7ba39`.
- Updated typed-rejections test: SHA-256
  `ea36648180b843dac16e405d69cc73afeef39b8ceec403463fced89c1a16b4e3`.
- Existing changed-payload/stale test: SHA-256
  `fb22111119c6e052680bc5ce3725a02223d36f51de27302eb3a3e35a9dda5dce`.
- Fixture: SHA-256
  `aa656f5bff91303991b67dc44eb5906c2832e925eeafd7e74bea346f33974549`.
- v3 RED evidence: SHA-256
  `8fef97dbe238f379988f2854c02293e201f4c8ccdd83e9e6cf2f5a593842c1af`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Current minimal production `InspectionEvidence.php`: SHA-256
  `d58b7896dec034650a39b62a54e29021b2dd1cf787353eec4534e25843720a01`.

## Independently reproduced evidence

`php -l` passed for the normalization, replay-authorization, precedence and
updated rejection files. I independently ran every documented selector:

```sh
FMONITOR_ITEM_TEST_CASE=ordered_persistence tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
FMONITOR_ITEM_TEST_CASE=reordered_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
FMONITOR_ITEM_TEST_CASE=duplicate_installer tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_rejections_test.php
FMONITOR_ITEM_TEST_CASE=revoked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=blocked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=auth_over_syntax tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
FMONITOR_ITEM_TEST_CASE=syntax_over_schema tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
FMONITOR_ITEM_TEST_CASE=schema_over_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
FMONITOR_ITEM_TEST_CASE=conflict_over_mutable tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
```

All nine commands exited `0` through the RED harness and reproduced the exact
behavioral failures recorded in v3 evidence: two normalization failures, one
duplicate-list acceptance, typed-result exceptions for revoked/blocked/auth
precedence, wrong syntax/schema outcome, wrong schema/replay outcome and wrong
conflict/mutable outcome. None depended on database, network or missing setup.

The prior example-A integration command also remains green:

```sh
php tests/InstallationProcess/inspection_item_complete_001_test.php
```

Output: `PASS: INSPECTION-ITEM-COMPLETE-001 example A`.

## Closure of prior findings

- **V2-01 semantically covered:** the new test accepts `[2048, 1042]`, requires
  public evidence ordered `[1042, 2048]`, and requires `[1042, 2048]` under the
  same operation id to be `DUPLICATE(1)`. The isolated duplicate row preserves
  the distinction between reordered set equivalence and an actually duplicated
  identifier. Existing changed-payload coverage remains in force.
- **V2-02 semantically covered:** separate revoked and blocked selectors accept
  first, change only current authority, repeat the exact command and require a
  typed `ACTOR_NOT_AUTHORIZED` before replay resolution.
- **V2-03 semantically covered:** the four combined scenarios require
  authorization over syntax, syntax over unavailable schema, unavailable schema
  over exact replay, and payload conflict over mutable case/template/crew
  rejection. New-operation scenarios query `null`; accepted-operation scenarios
  query the original facts.
- All assertions remain at `completeItem` and `getItemCompletion`. Fixture calls
  seed or change prerequisites only; they do not provide expected results or
  expose an assertion-only fact channel. Scenario selectors isolate genuine
  behavioral REDs and do not alter expectations.
- No test claims real concurrency. The MariaDB two-connection overlap RED remains
  explicitly deferred as stated in v2/v3 evidence.

## V3-01 — BLOCKER: immutability assertions require PHP object identity

The new replay/precedence tests capture an `ItemCompletionEvidence` object and
later call `assertSameValue($original, $recording->getItemCompletion(...))`.
`assertSameValue` in `tests/bootstrap.php` uses strict `!==`. For objects this
does not compare immutable values; it requires the second query to return the
same in-memory object instance.

A correct public query may, and the current implementation does, hydrate a new
`ItemCompletionEvidence` value object on every read. A MariaDB adapter necessarily
may do the same. Two objects with identical actor, assignment, timestamps,
revisions, template identity and installer snapshots therefore fail the current
assertion solely because their PHP identities differ. Conversely, forcing a
cache to return the same object is not part of the approved seam and would make
the test sensitive to an internal implementation detail rather than immutable
facts.

The overconstraint appears in:

- normalized reordered replay;
- revoked and blocked replay authorization;
- schema-over-replay precedence;
- conflict-over-mutable precedence;
- and the earlier replay test inherited by the v2 matrix.

Action required: compare an independently constructed scalar projection of all
normative evidence fields (including ordered installer snapshot values), or add
a test helper that converts each returned evidence value to that projection.
Query twice and compare those scalar/array values strictly. Do not use object
identity, production serialization, fixture storage arrays or a repository/SQL
read. Retain `null` assertions for never-accepted operation ids. Re-run the
affected isolated REDs and append corrected evidence.

## Gate decision

The three v2 behavioral gaps are now represented, but Gate 3 v3 remains
`CHANGES_REQUESTED` because V3-01 would reject a conforming replaceable public
query implementation. Return the affected tests and append-only RED evidence to
Gate 2, then request a fresh independent rereview. Prior example-A approval and
GREEN remain valid; production must not be expanded against the v3 matrix yet.
